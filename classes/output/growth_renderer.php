<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Growth course report renderer.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_growth\output;

use plugin_renderer_base;
use renderable;
use core\{chart_bar, chart_line, chart_series};

/**
 * growth report renderer.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class growth_renderer extends plugin_renderer_base {

    /** @var stdClass context. */
    protected $context;

    /**
     * Trigger Event.
     *
     * @param int $page
     */
    protected function trigger_page(int $page = 1) {
        // Trigger a report viewed event.
        $event = \report_growth\event\report_viewed::create(['context' => $this->context,  'other' => ['tab' => $page]]);
        $event->trigger();
    }

    /**
     * Render page.
     *
     * @param array $rows
     * @param int $page
     */
    protected function render_page(array $rows, int $page = 1): string {
        $page = ($page > count($rows) || $page == 0) ? 1 : $page;
        $i = 1;
        $tabs = [];
        $func = 'table_';
        $fparam = '';
        foreach ($rows as $key => $value) {
            $params = ['p' => $i, 'contextid' => $this->context->id];
            $tabs[] = new \tabobject($i, new \moodle_url('/report/growth/index.php', $params), $value);
            if ($i == $page) {
                $func .= $key;
                $fparam = $value;
            }
            $i++;
        }
        return $this->output->tabtree($tabs, $page) . \html_writer::tag('div', $this->$func($fparam), ['class' => 'p-3']);
    }

    /**
     * Collect course table.
     *
     * @param string $title Title
     * @param string $table1 First table
     * @param string $table2 Second table
     * @param string $fieldwhere Where lookup
     * @param string $fieldresult The field that has to be calculated
     * @return string
     */
    protected function collect_course_table($title, $table1, $table2, $fieldwhere, $fieldresult): string {
        global $DB;
        $ids = $DB->get_fieldset_select($table1, 'id', 'courseid = :courseid', ['courseid' => $this->context->instanceid]);
        $insql = $fieldresult . '< 0';
        $inparams = [];
        if (count($ids) > 0) {
            sort($ids);
            list($insql, $inparams) = $DB->get_in_or_equal($ids);
            $insql = $fieldwhere . ' ' . $insql;
        }
        return $this->create_charts([], $table2, $title, $fieldresult, $insql, $inparams);
    }


    /**
     * Collect category table.
     *
     * @param string $title Title
     * @param string $table First table
     * @param string $fieldwhere Where lookup
     * @param string $fieldresult The field that has to be calculated
     * @return string
     */
    protected function collect_category_table($title, $table, $fieldwhere, $fieldresult): string {
        global $DB;
        $insql = $fieldresult . '< 0';
        $inparams = [$fieldresult => 0];
        $courseids = $DB->get_fieldset_select('course', 'id', 'category = :category', ['category' => $this->context->instanceid]);
        if (count($courseids) > 0) {
            sort($courseids);
            list($insql, $inparams) = $DB->get_in_or_equal($courseids);
            $insql = $fieldwhere . ' ' . $insql;
        }
        return $this->create_charts([], $table, $title, $fieldresult, $insql, $inparams);
    }

    /**
     * Collect category table.
     *
     * @param string $title Title
     * @param string $table1 First table
     * @param string $table2 Second table
     * @param string $fieldwhere Where lookup
     * @param string $fieldresult The field that has to be calculated
     * @return string
     */
    protected function collect_category_table2($title, $table1, $table2, $fieldwhere, $fieldresult): string {
        global $DB;
        $inparams = [];
        $courseids = $DB->get_fieldset_select('course', 'id', 'category = :category', ['category' => $this->context->instanceid]);
        if (count($courseids) > 0) {
            sort($courseids);
            list($insql, $inparams) = $DB->get_in_or_equal($courseids);
            $insql = 'courseid ' . $insql;
            $ids = $DB->get_fieldset_select($table1, 'id', $insql, $inparams);
            if (count($ids) > 0) {
                sort($ids);
                list($insql, $inparams) = $DB->get_in_or_equal($ids);
                $insql = $fieldwhere . ' ' . $insql;
            }
        }
        $insql = count($inparams) > 0 ? $insql : $fieldresult . '< 0';
        return $this->create_charts([], $table2, $title, $fieldresult, $insql, $inparams);
    }
    /**
     * Create charts.
     *
     * @param array $data
     * @param string $table
     * @param string $title
     * @param string $field optional
     * @param string $where optional
     * @param string $params optional
     * @return string
     */
    protected function create_charts($data, $table, $title, $field = 'timecreated', $where = '', $params = []): string {
        global $DB;
        $toyear = intval(date("Y"));

        $tbl = new \html_table();
        $tbl->attributes = ['class' => 'table table-sm table-hover w-50'];
        $tbl->colclasses = ['text-left', 'text-right'];
        $tbl->size = [null, '5rem'];
        $tbl->caption = $title;
        $tbl->data = $data;
        $wh = ($where == '') ? "$field > 0" : "($field > 0) AND ($where)";
        $cnt = $DB->count_records_select($table, $wh, $params);
        if ($cnt > 0) {
            $tbl->data[] = [\html_writer::tag('b', get_string('total')), $cnt];
            if ($rows = $this->get_sql($field, $table, $wh, $params)) {
                $week = get_string('week');
                $chart1 = new chart_line();
                $chart1->set_smooth(true);
                $series = $labels = $quarter1 = $quarter2 = $quarter3 = $quarter4 = $qlabels = $totals = [];
                $x = current($rows);
                $total = 0;
                $fromyear = is_object($x) ? intval(explode(' ', $x->week)[0]) : $toyear - 7;
                $fromweek = is_object($x) ? intval(explode(' ', $x->week)[1]) : 1;
                $nowweek = date('W');
                for ($i = $fromyear; $i <= $toyear; $i++) {
                    for ($j = $fromweek; $j <= 52; $j++) {
                        $str = "$i $j";
                        $total += array_key_exists($str, $rows) ? $rows[$str]->newitems : 0;
                        $series[] = $total;
                        $labels[] = "$i $week $j";
                        if ($i == $toyear && $j > $nowweek) {
                            break;
                        }
                    }
                    $fromweek = 1;
                }
                $chart1->add_series(new chart_series($title, $series));
                $chart1->set_labels($labels);
            }
            if ($rows = $this->get_sql($field, $table, $wh, $params, false)) {
                $q = get_string('quarter', 'report_growth');
                for ($i = $fromyear; $i <= $toyear; $i++) {
                    $x1 = array_key_exists("$i 1", $rows) ? $rows["$i 1"]->newitems : 0;
                    $x2 = array_key_exists("$i 2", $rows) ? $rows["$i 2"]->newitems : 0;
                    $x3 = array_key_exists("$i 3", $rows) ? $rows["$i 3"]->newitems : 0;
                    $x4 = array_key_exists("$i 4", $rows) ? $rows["$i 4"]->newitems : 0;
                    $quarter1[] = $x1;
                    $quarter2[] = $x2;
                    $quarter3[] = $x3;
                    $quarter4[] = $x4;
                    $totals[] = $x1 + $x2 + $x3 + $x4;
                    $qlabels[] = $i;
                }
                $chart2 = new chart_bar();
                $chart2->set_stacked(true);
                $series = new chart_series(get_string('total'), $totals);
                $series->set_type(chart_series::TYPE_LINE);
                $chart2->add_series($series);
                $chart2->add_series(new chart_series($q . '1', $quarter1));
                $chart2->add_series(new chart_series($q . '2', $quarter2));
                $chart2->add_series(new chart_series($q . '3', $quarter3));
                $chart2->add_series(new chart_series($q . '4', $quarter4));
                $chart2->set_labels($qlabels);
                $out = count($data) == 0 ? '' : \html_writer::table($tbl) . '<br/>';
                return  $out . $this->output->render($chart1, false) . '<br/>' . $this->output->render($chart2);
            }
        }
        return get_string('nostudentsfound', 'moodle', $title);
    }

    /**
     * Collect data for charts.
     *
     * @param string $field
     * @param string $table
     * @param string $wh
     * @param array $params
     * @param bool $weeks optional
     * @return bool/array
     */
    protected function get_sql(string $field, string $table, string $wh, array $params = [], bool $weeks = true) {
        global $DB;
        $family = $DB->get_dbfamily();
        switch ($family) {
            case 'mysql':
                $func = $weeks ? 'WEEKOFYEAR' : 'QUARTER';
                $concat = "CONCAT(YEAR(FROM_UNIXTIME($field)), ' ', $func(FROM_UNIXTIME($field)))";
                $sql = "SELECT $concat AS week, COUNT(*) AS newitems FROM {" . $table . "}
                        WHERE $wh GROUP BY $concat ORDER BY $field";
                break;
            case 'mssql':
                $func = $weeks ? 'WEEK' : 'qq';
                $field = "dateadd(S, $field, '1970-01-01')";
                $concat = $DB->sql_concat_join("' '", ["DATEPART(YEAR, $field)", "DATEPART($func, $field)"]);
                $sql = "SELECT $concat AS week, COUNT(*) AS newitems FROM {". $table . "}
                        WHERE $wh GROUP BY $concat ORDER BY $concat";
                break;
            case 'oracle':
                $func = $weeks ? 'YYYY WW' : 'YYYY Q';
                $sql = "SELECT TO_CHAR(TO_DATE('1970-01-01','YYYY-MM-DD') + $field / 86400, '$func') week,
                        COUNT(*) newitems FROM {" . $table . "}
                        WHERE $wh GROUP BY TO_CHAR(TO_DATE('1970-01-01','YYYY-MM-DD') + $field / 86400, '$func')
                        ORDER BY week";
                break;
            default:
                $func = $weeks ? 'YYYY WW' : 'YYYY Q';
                $sql = "SELECT TO_CHAR(TO_TIMESTAMP($field), '$func') AS week, COUNT(*) AS newitems FROM {" . $table . "}
                        WHERE $wh GROUP BY 1 ORDER BY 1";
                break;
        }
        return $DB->get_records_sql($sql, $params);
    }
}
