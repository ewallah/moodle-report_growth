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

use core\chart_bar;
use core\chart_line;
use core\chart_series;

/**
 * growth report renderer.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_growth_renderer extends \plugin_renderer_base {


    /**
     * Create Tabs.
     *
     * @param int $p Selected tab
     * @return string
     */
    public function create_tabtree($p = 1) {
        global $CFG;
        $txt = get_strings(['summary', 'users', 'badges', 'coursecompletions', 'courses', 'resources', 'files']);
        $rows = ['summary' => $txt->summary, 'users' => $txt->users];
        if (isset($CFG->logguests) && $CFG->logguests) {
            $rows['logguests'] = get_string('policydocaudience2', 'tool_policy');
        }
        if (!empty($CFG->enablemobilewebservice)) {
            $rows['mobiles'] = get_string('mobile', 'report_growth');
        }
        if (!empty($CFG->enablebadges)) {
            $rows['badges'] = $txt->badges;
        }
        if (!empty($CFG->enablecompletion)) {
            $rows['coursecompletions'] = get_string('coursecompletions');
        }
        if (file_exists($CFG->dirroot . '/mod/certificate')) {
            $rows['certificates'] = get_string('modulenameplural', 'mod_certificate');
        }
        if (file_exists($CFG->dirroot . '/mod/customcert')) {
            $rows['customcerts'] = get_string('modulenameplural', 'mod_customcert');
        }
        if (file_exists($CFG->dirroot . '/mod/coursecertificate')) {
            $rows['coursecertificates'] = get_string('modulenameplural', 'mod_coursecertificate');
        }
        $rows['courses'] = $txt->courses;
        $rows['enrolments'] = get_string('enrolments', 'enrol');
        $rows['questions'] = get_string('questions', 'question');
        $rows['resources'] = $txt->resources;
        $rows['files'] = $txt->files;
        $rows['messages'] = get_string('messages', 'message');
        $rows['countries'] = get_string('countries', 'report_growth');
        $p = ($p > count($rows) || $p == 0) ? 1 : $p;
        $i = 1;
        $tabs = [];
        $func = 'table_';
        $fparam = '';
        foreach ($rows as $key => $value) {
            $tabs[] = new \tabobject($i, new \moodle_url('/report/growth/index.php', ['p' => $i]), $value);
            if ($i == $p) {
                $func .= $key;
                $fparam = $value;
            }
            $i++;
        }
        // Trigger a report viewed event.
        $context = \context_system::instance();
        $event = \report_growth\event\report_viewed::create(['context' => $context,  'other' => ['tab' => $p]]);
        $event->trigger();
        return $this->output->tabtree($tabs, $p) . \html_writer::tag('div', $this->$func($fparam), ['class' => 'p-3']);
    }


    /**
     * Table summary.
     *
     * @param string $title Title
     * @return string
     */
    public function table_summary($title = ''):string {
        $siteinfo = \core\hub\registration::get_site_info([]);
        $lis = strip_tags(\core\hub\registration::get_stats_summary($siteinfo), '<ul><li>');
        return \html_writer::tag('h3', $title) . str_replace(get_string('sendfollowinginfo_help', 'hub') , '', $lis);
    }

    /**
     * Table users.
     *
     * @param string $title Title
     * @return string
     */
    public function table_users($title = ''):string {
        global $DB;
        $arr = [
           [get_string('deleted'), $DB->count_records('user', ['deleted' => 1])],
           [get_string('suspended'), $DB->count_records('user', ['suspended' => 1])],
           [get_string('confirmed', 'admin'), $DB->count_records('user', ['confirmed' => 1])],
           [get_string('activeusers'), $DB->count_records_select('user', 'lastip <> ?', [''])]];
        return $this->create_charts($arr, 'user', $title);
    }


    /**
     * Table courses.
     *
     * @param string $title Title
     * @return string
     */
    public function table_courses($title = ''):string {
        global $DB;
        $arr = [[get_string('categories'), $DB->count_records('course_categories', [])]];
        return $this->create_charts($arr, 'course', $title, 'timecreated', 'id > 1');
    }

    /**
     * Table enrolments.
     *
     * @param string $title Title
     * @return string
     */
    public function table_enrolments($title = ''):string {
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
        return $this->create_charts($arr, 'user_enrolments', $title);
    }

    /**
     * Table mobile.
     *
     * @param string $title Title
     * @return string
     */
    public function table_mobiles($title = ''):string {
        return $this->create_charts([], 'user_devices', $title);
    }

    /**
     * Table badges.
     *
     * @param string $title Title
     * @return string
     */
    public function table_badges($title = ''):string {
        return $this->create_charts([], 'badge_issued', $title, 'dateissued');
    }

    /**
     * Table completions.
     *
     * @param string $title Title
     * @return string
     */
    public function table_coursecompletions($title = ''):string {
        return $this->create_charts([], 'course_completions', $title, 'timecompleted');
    }

    /**
     * Table questions.
     *
     * @param string $title Title
     * @return string
     */
    public function table_questions($title = ''):string {
        return $this->create_charts([], 'question', $title);
    }

    /**
     * Table resources.
     *
     * @param string $title Title
     * @return string
     */
    public function table_resources($title = ''):string {
        return $this->create_charts([], 'course_modules', $title, 'added');
    }

    /**
     * Table guests.
     *
     * @param string $title Title
     * @return string
     */
    public function table_logguests($title = ''):string {
        return $this->create_charts([], 'logstore_standard_log', $title, 'timecreated', 'userid = 1');
    }

    /**
     * Table certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_certificates($title = ''):string {
        return $this->create_charts([], 'certificate_issues', $title);
    }

    /**
     * Table custom certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_customcerts($title = ''):string {
        return $this->create_charts([], 'customcert_issues', $title);
    }

    /**
     * Table course certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_coursecertificates($title = ''):string {
        return $this->create_charts([], 'tool_certificate_issues', $title);
    }

    /**
     * Table files.
     *
     * @param string $title Title
     * @return string
     */
    public function table_files($title = ''):string {
        return $this->create_charts([], 'files', $title);
    }

    /**
     * Table messages.
     *
     * @param string $title Title
     * @return string
     */
    public function table_messages($title = ''):string {
        return $this->create_charts([], 'messages', $title);
    }

    /**
     * Table country.
     *
     * @param string $title Title
     * @return string
     */
    public function table_countries($title = ''):string {
        global $DB;
        $title = get_string('users');
        $sql = "SELECT country, COUNT(country) AS newusers FROM {user} GROUP BY country ORDER BY country";
        $rows = $DB->get_records_sql($sql);
        $chart = new chart_bar();
        $chart->set_horizontal(true);
        $series = [];
        $labels = [];
        foreach ($rows as $row) {
            if (empty($row->country) || $row->country == '') {
                continue;
            }
            $series[] = $row->newusers;
            $labels[] = get_string($row->country, 'countries');
        }
        $series = new chart_series($title, $series);
        $chart->add_series($series);
        $chart->set_labels($labels);
        return $this->output->render($chart);
    }

    /**
     * Create charts.
     *
     * @param array $data
     * @param string $table
     * @param string $title
     * @param string $field optional
     * @param string $where optional
     * @return string
     */
    private function create_charts($data, $table, $title, $field = 'timecreated', $where = ''):string {
        global $DB;
        $toyear = intval(date("Y"));

        $tbl = new \html_table();
        $tbl->attributes = ['class' => 'table table-sm table-hover w-50'];
        $tbl->colclasses = ['text-left', 'text-right'];
        $tbl->size = [null, '5rem'];
        $tbl->caption = $title;
        $tbl->data = $data;
        $wh = ($where == '') ? "$field  > 0" : "($field > 0) AND ($where)";
        $cnt = $DB->count_records_select($table, $wh);
        if ($cnt > 0) {
            $tbl->data[] = [\html_writer::tag('b', get_string('total')), $cnt];
            if ($rows = $this->get_sql($field, $table, $wh)) {
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
            if ($rows = $this->get_sql($field, $table, $wh, false)) {
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
                return \html_writer::table($tbl) . '<br/>' . $this->output->render($chart1, false) .
                   '<br/>' . $this->output->render($chart2);
            }
        }
        return get_string('nostudentsfound', 'moodle', $title);
    }

    /**
     * Create charts.
     *
     * @param string $field
     * @param string $table
     * @param string $wh
     * @param bool $weeks optional
     * @return bool/array
     */
    private function get_sql(string $field, string $table, string $wh, bool $weeks = true) {
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
            case 'postgres':
                $func = $weeks ? 'YYYY WW' : 'YYYY Q';
                $sql = "SELECT TO_CHAR(TO_TIMESTAMP($field), '$func') AS week, COUNT(*) AS newitems FROM {" . $table . "}
                        WHERE $wh GROUP BY 1 ORDER BY 1";
                break;
            case 'oracle':
                $func = $weeks ? 'YYYY WW' : 'YYYY Q';
                $sql = "SELECT TO_CHAR(TO_DATE('1970-01-01','YYYY-MM-DD') + $field / 86400, '$func') week,
                        COUNT(*) newitems FROM {" . $table . "}
                        WHERE $wh GROUP BY TO_CHAR(TO_DATE('1970-01-01','YYYY-MM-DD') + $field / 86400, '$func')
                        ORDER BY week";
                break;
            default:
                debugging("Database family $family not (yet) supported by this plugin", DEBUG_DEVELOPER);
                return false;
        }
        return $DB->get_records_sql($sql);
    }
}
