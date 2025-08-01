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
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_growth\output;

use html_writer;
use plugin_renderer_base;
use renderable;
use core\{chart_bar, chart_line, chart_series};

/**
 * growth report renderer.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class growth_renderer extends plugin_renderer_base {
    /** @var stdClass context. */
    protected $context;

    /**
     * Collect certificates.
     *
     * @return array
     */
    protected function certificate_tabs() {
        global $CFG;
        $rows = [];
        $plural = 'modulenameplural';
        if (!empty($CFG->enablebadges)) {
            $rows['badges'] = get_string('badges');
        }
        if (file_exists($CFG->dirroot . '/mod/certificate')) {
            $rows['certificates'] = get_string($plural, 'mod_certificate');
        }
        if (file_exists($CFG->dirroot . '/mod/customcert')) {
            $rows['customcerts'] = get_string($plural, 'mod_customcert');
        }
        if (file_exists($CFG->dirroot . '/mod/coursecertificate')) {
            $rows['coursecertificates'] = get_string($plural, 'mod_coursecertificate');
        }
        return $rows;
    }

    /**
     * Trigger Event.
     *
     * @param int $page
     */
    protected function trigger_page(int $page = 1) {
        // Trigger a report viewed event.
        $event = \report_growth\event\report_viewed::create(['context' => $this->context, 'other' => ['tab' => $page]]);
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
        // Hack for behat testing.
        $extra = defined('BEHAT_SITE_RUNNING') ? '.' : '';
        foreach ($rows as $key => $value) {
            $params = ['p' => $i, 'contextid' => $this->context->id];
            $tabs[] = new \tabobject($i, new \moodle_url('/report/growth/index.php', $params), $value . $extra);
            if ($i == $page) {
                $func .= $key;
                $fparam = $value;
            }
            $i++;
        }
        return $this->output->tabtree($tabs, $page) . html_writer::tag('div', $this->$func($fparam), ['class' => 'p-3']);
    }

    /**
     * Collect course table.
     *
     * @param string $title Title
     * @param string $table1 First table
     * @param string $table2 Second table
     * @param string $field to collect from first table
     * @param string $fieldwhere Where lookup
     * @param string $fieldresult The field that has to be calculated
     * @return string
     */
    protected function collect_course_table($title, $table1, $table2, $field, $fieldwhere, $fieldresult = 'timemodified'): string {
        global $DB;
        $ids = $DB->get_fieldset_select($table1, 'id', $field . ' = :courseid', ['courseid' => $this->context->instanceid]);
        [$insql, $inparams] = $this->insql($ids, $fieldwhere, $fieldresult);
        return $this->create_charts($table2, $title, $fieldresult, $insql, $inparams);
    }

    /**
     * Collect course table.
     *
     * @param array $fieldset
     * @param string $fieldwhere
     * @param string $fieldresult
     * @return array
     */
    protected function insql($fieldset, $fieldwhere, $fieldresult) {
        global $DB;
        $inparams = [];
        $insql = $fieldresult . '< 0';
        if (count($fieldset) > 0) {
            [$insql, $inparams] = $DB->get_in_or_equal($fieldset);
            $insql = $fieldwhere . ' ' . $insql;
        }
        return [$insql, $inparams];
    }

    /**
     * Table country.
     *
     * @param array $rows
     * @param string $title
     * @return string
     */
    protected function create_countries(array $rows, string $title = ''): string {
        $out = get_string('nostudentsfound', 'moodle', get_string('users'));
        if (count($rows) > 0) {
            $chart = new chart_bar();
            $chart->set_horizontal(true);
            $series = [];
            $labels = [];
            foreach ($rows as $row) {
                if (!empty($row->country) && $row->country != '') {
                    $series[] = $row->newusers;
                    $labels[] = get_string($row->country, 'countries');
                }
            }
            $series = new chart_series($title, $series);
            $chart->add_series($series);
            $chart->set_labels($labels);
            $out = $this->output->render($chart);
        }
        return $out;
    }

    /**
     * Create intro table.
     *
     * @param array $data
     * @param string $title
     * @return string
     */
    protected function create_intro(array $data, string $title): string {
        $tbl = new \html_table();
        $tbl->attributes = ['class' => 'table table-sm table-hover w-50'];
        $tbl->colclasses = ['text-start', 'text-end'];
        $tbl->size = [null, '5rem'];
        $tbl->caption = $title;
        $tbl->data = $data;
        return count($data) > 0 ? html_writer::table($tbl) . html_writer::empty_tag('br') : '';
    }

    /**
     * Create chart 1.
     *
     * @param string $title
     * @param array $series
     * @param array $labels
     * @return string
     */
    private function create_chart_one(string $title, array $series, array $labels): string {
        $chart = new chart_line();
        $chart->set_smooth(true);
        $chart->add_series(new chart_series($title, $series));
        $chart->set_labels($labels);
        return $this->output->render($chart, false);
    }

    /**
     * Create chart 2.
     *
     * @param array $quarters
     * @param array $labels
     * @param array $totals
     * @return string
     */
    private function create_chart_two(array $quarters, array $labels, array $totals): string {
        $q = get_string('quarter', 'report_growth');
        $chart = new chart_bar();
        $chart->set_stacked(true);
        $series = new chart_series(get_string('total'), $totals);
        $series->set_type(chart_series::TYPE_LINE);
        $chart->add_series($series);
        $chart->add_series(new chart_series($q . '1', $quarters[1]));
        $chart->add_series(new chart_series($q . '2', $quarters[2]));
        $chart->add_series(new chart_series($q . '3', $quarters[3]));
        $chart->add_series(new chart_series($q . '4', $quarters[4]));
        $chart->set_labels($labels);
        return $this->output->render($chart);
    }

    /**
     * Create charts.
     *
     * @param string $table
     * @param string $title
     * @param string $field optional
     * @param string $where optional
     * @param array $params optional
     * @return string
     */
    protected function create_charts($table, $title, $field = 'timecreated', $where = '', $params = []): string {
        $toyear = intval(date("Y"));
        $nowweek = date('W');
        $wh = ($where == '') ? "$field > 0" : "($field > 0) AND ($where)";
        if ($rows = $this->get_sql($field, $table, $wh, $params)) {
            $week = get_string('week');
            $series = $labels = [];
            $x = current($rows);
            $total = 0;
            $fromyear = is_object($x) ? intval(explode(' ', $x->week)[0]) : $toyear;
            $fromweek = is_object($x) ? intval(explode(' ', $x->week)[1]) : 1;
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
            $search = 'help_' . $table;
            $charts = [];
            $manager = get_string_manager();
            if ($manager->string_exists($search, 'report_growth')) {
                $charts[] = html_writer::tag('figcaption', get_string($search, 'report_growth'), ['class' => 'figure-caption']);
            }
            $charts[] = $this->create_chart_one($title, $series, $labels);
            $labels = $totals = $quarter1 = $quarter2 = $quarter3 = $quarter4 = [];
            // If it worked the first time...
            $rows = $this->get_sql($field, $table, $wh, $params, false);
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
                $labels[] = $i;
            }
            $quarters = [null, $quarter1, $quarter2, $quarter3, $quarter4];
            $charts[] = $this->create_chart_two($quarters, $labels, $totals);
            return implode(html_writer::empty_tag('br'), $charts);
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
                $sql = "SELECT $concat AS week, COUNT(*) AS newitems FROM {" . $table . "}
                        WHERE $wh GROUP BY $concat ORDER BY $concat";
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
