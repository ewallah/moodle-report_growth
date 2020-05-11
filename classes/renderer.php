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
 * growth report renderer.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * growth report renderer.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_growth_renderer extends plugin_renderer_base {


    /**
     * Create Tabs.
     *
     * @param int $p Selected tab
     * @return string
     */
    public function create_tabtree($p = 1) {
        global $CFG, $OUTPUT;
        $ur = '/report/growth/index.php';
        $rows = ['summary', 'users'];
        if (isset($CFG->logguests) and $CFG->logguests) {
            $rows[] = 'policydocaudience2-tool_policy';
        }
        if (!empty($CFG->enablemobilewebservice)) {
            $rows[] = 'mobile-';
        }
        if (!empty($CFG->enablebadges)) {
            $rows[] = 'badges';
        }
        if (!empty($CFG->enablecompletion)) {
            $rows[] = 'coursecompletions';
        }
        if (file_exists($CFG->dirroot . '/mod/certificate')) {
            $rows[] = 'modulenameplural-mod_certificate';
        }
        if (file_exists($CFG->dirroot . '/mod/customcert')) {
            $rows[] = 'modulenameplural-mod_customcert';
        }
        $rows = array_merge($rows, ['courses', 'enrolments-enrol', 'questions-question', 'resources', 'countries-']);
        $p = $p > count($rows) ? 1 : $p;
        $i = 1;
        $tabs = [];
        $func = 'table_';
        foreach ($rows as $row) {
            if (strpos($row, '-') == true) {
                $expl = explode('-', $row);
                $local = ($expl[1] == '');
                $str = get_string($expl[0], $local ? 'report_growth' : $expl[1]);
                if ($i == $p) {
                    $func .= $local ? $expl[0] : $expl[1];
                }
            } else {
                $str = get_string($row);
                if ($i == $p) {
                    $func .= $row;
                }
            }
            $tabs[] = new tabobject($i, new moodle_url($ur, ['p' => $i]), $str);
            $i++;
        }
        return $OUTPUT->tabtree($tabs, $p) . html_writer::tag('div', $this->$func(), ['class' => 'p-3']);
    }


    /**
     * Table summary.
     *
     * @return string
     */
    public function table_summary() {
        $siteinfo = \core\hub\registration::get_site_info([]);
        $lis = strip_tags(\core\hub\registration::get_stats_summary($siteinfo), '<ul><li>');
        return str_replace(get_string('sendfollowinginfo_help', 'hub') , '', $lis);
    }

    /**
     * Table users.
     *
     * @return string
     */
    public function table_users() {
        global $DB;
        $arr = [
           [get_string('deleted'), $DB->count_records('user', ['deleted' => 1])],
           [get_string('suspended'), $DB->count_records('user', ['suspended' => 1])],
           [get_string('confirmed', 'admin'), $DB->count_records('user', ['confirmed' => 1])],
           [get_string('activeusers'), $DB->count_records_select('user', 'lastip <> ?', [''])]];
        return $this->create_charts($arr, 'user', get_string('users'));
    }


    /**
     * Table courses.
     *
     * @return string
     */
    public function table_courses() {
        global $DB;
        $arr = [[get_string('categories'), $DB->count_records('course_categories', [])]];
        return $this->create_charts($arr, 'course', get_string('courses'), 'timecreated', 'id > 1');
    }

    /**
     * Table enrolments.
     *
     * @return string
     */
    public function table_enrol() {
        global $DB;
        $enabled = array_keys(enrol_get_plugins(true));
        $arr = [];
        foreach ($enabled as $key) {
            $ids = $DB->get_fieldset_select('enrol', 'id', "enrol = '$key'");
            if (count($ids) > 0) {
                list($insql, $inparams) = $DB->get_in_or_equal($ids);
                $cnt = $DB->count_records_sql("SELECT COUNT('x') FROM {user_enrolments} WHERE enrolid {$insql}", $inparams);
                $arr[] = [get_string('pluginname', 'enrol_'. $key), $cnt];
            }
        }
        return $this->create_charts($arr, 'user_enrolments', get_string('enrolments', 'enrol'));
    }

    /**
     * Table mobile.
     *
     * @return string
     */
    public function table_mobile() {
        return $this->create_charts([], 'user_devices', get_string('mobile', 'report_growth'));
    }

    /**
     * Table badges.
     *
     * @return string
     */
    public function table_badges() {
        return $this->create_charts([], 'badge_issued', get_string('badges'), 'dateissued');
    }

    /**
     * Table completions.
     *
     * @return string
     */
    public function table_coursecompletions() {
        return $this->create_charts([], 'course_completions', get_string('coursecompletions'), 'timecompleted');
    }

    /**
     * Table questions.
     *
     * @return string
     */
    public function table_question() {
        return $this->create_charts([], 'question', get_string('questions', 'question'));
    }

    /**
     * Table resources.
     *
     * @return string
     */
    public function table_resources() {
        return $this->create_charts([], 'course_modules', get_string('resources'), 'added');
    }

    /**
     * Table guests.
     *
     * @return string
     */
    public function table_tool_policy() {
        return $this->create_charts([], 'logstore_standard_log', get_string('policydocaudience2', 'tool_policy'),
           'timecreated', 'userid = 1');
    }

    /**
     * Table certificates.
     *
     * @return string
     */
    public function table_mod_certificate() {
        return $this->create_charts([], 'certificate_issues', get_string('modulenameplural', 'mod_certificate'));
    }

    /**
     * Table certificates.
     *
     * @return string
     */
    public function table_mod_customcert() {
        return $this->create_charts([], 'customcert_issues', get_string('modulenameplural', 'mod_customcert'));
    }

    /**
     * Table country.
     *
     * @return string
     */
    public function table_countries() {
        global $DB, $OUTPUT;
        $sql = "SELECT country, COUNT(country) as newusers FROM {user} GROUP BY country ORDER BY country";
        $rows = $DB->get_records_sql($sql);
        $chart = new core\chart_bar();
        $chart->set_horizontal(true);
        $series = [];
        $labels = [];
        foreach ($rows as $row) {
            if (empty($row->country) or $row->country == '') {
                continue;
            }
            $series[] = $row->newusers;
            $labels[] = get_string($row->country, 'countries');
        }
        $series = new core\chart_series(get_string('country'), $series);
        $chart->add_series($series);
        $chart->set_labels($labels);
        return $OUTPUT->render($chart);
    }

    /**
     * Create charts.
     *
     * @param array $data
     * @param string $table
     * @param string $title
     * @param string $field optional
     * @param string $where optional
     * @return array
     */
    private function create_charts($data, $table, $title, $field = 'timecreated', $where = '') {
        global $DB, $OUTPUT;
        $family = $DB->get_dbfamily();
        $week = get_string('week');
        $total = get_string('total');
        $toyear = intval(date("Y"));

        $tbl = new html_table();
        $tbl->attributes = ['class' => 'table table-sm table-hover w-50'];
        $tbl->colclasses = ['text-left', 'text-right'];
        $tbl->size = [null, '5rem'];
        $tbl->caption = $title;
        $tbl->data = $data;
        $wh = ($where == '') ? "$field  > 0" : "($field > 0) AND ($where)";
        $cnt = $DB->count_records_select($table, $wh);
        if ($cnt > 0) {
            $tbl->data[] = [html_writer::tag('b', $total), $cnt];
            $concat = "CONCAT(YEAR(FROM_UNIXTIME($field)), ' ', WEEKOFYEAR(FROM_UNIXTIME($field)))";
            $sql1 = "
                SELECT $concat AS week, COUNT(*) as newitems
                FROM {" . $table . "}
                WHERE $wh
                GROUP BY $concat
                ORDER BY $field";
            $sql2 = "
                SELECT
                    TO_CHAR(TO_TIMESTAMP($field), 'YYYY WW') AS week,
                    COUNT(*) AS newitems
                FROM {" . $table . "}
                WHERE $wh
                GROUP BY 1
                ORDER BY 1";
            $sql = ($family === 'mysql' or $family === 'mssql') ? $sql1 : $sql2;
            if ($rows = $DB->get_records_sql($sql)) {
                $chart1 = new \core\chart_line();
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
                        if ($i == $toyear and $j > $nowweek) {
                            break;
                        }
                    }
                    $fromweek = 1;
                }
                $series = new core\chart_series($title, $series);
                $chart1->add_series($series);
                $chart1->set_labels($labels);
            }
            $sql1 = "
                SELECT
                    CONCAT(YEAR(FROM_UNIXTIME($field)), ' ', QUARTER(FROM_UNIXTIME($field))) as year,
                    COUNT(*) as newitems
                FROM {" . $table . "}
                WHERE $wh
                GROUP BY CONCAT(YEAR(FROM_UNIXTIME($field)), ' ', QUARTER(FROM_UNIXTIME($field)))
                ORDER BY $field";
            $sql2 = "
                SELECT
                    TO_CHAR(TO_TIMESTAMP($field), 'YYYY Q') AS year,
                    COUNT(*) AS newitems
                FROM {" . $table . "}
                WHERE $wh
                GROUP BY 1
                ORDER BY 1";
            $sql = ($family === 'mysql' or $family === 'mssql') ? $sql1 : $sql2;
            if ($rows = $DB->get_records_sql($sql)) {
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
                $chart2 = new \core\chart_bar();
                $chart2->set_stacked(true);
                $series = new \core\chart_series('Total', $totals);
                $series->set_type(\core\chart_series::TYPE_LINE);
                $chart2->add_series($series);
                $series = new \core\chart_series('Q1', $quarter1);
                $chart2->add_series($series);
                $series = new \core\chart_series('Q2', $quarter2);
                $chart2->add_series($series);
                $series = new \core\chart_series('Q3', $quarter3);
                $chart2->add_series($series);
                $series = new \core\chart_series('Q4', $quarter4);
                $chart2->add_series($series);
                $chart2->set_labels($qlabels);
                return html_writer::table($tbl) . '<br>' . $OUTPUT->render($chart1, false) . '<br>' . $OUTPUT->render($chart2);
            }
        }
        return get_string('nostudentsfound', 'moodle', $title);
    }
}