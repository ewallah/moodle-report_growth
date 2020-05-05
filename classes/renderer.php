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
     * Table summary.
     *
     * @return string
     */
    public function table_summary() {
        global $CFG;
        $site = get_site();
        $admin = get_admin();

        $siteinfo = \core\hub\registration::get_site_info([
            'name' => format_string($site->fullname, true, ['context' => context_course::instance(SITEID)]),
            'description' => $site->summary,
            'contactname' => fullname($admin, true),
            'contactemail' => $admin->email,
            'contactphone' => $admin->phone1,
            'street' => '',
            'countrycode' => $admin->country ?: $CFG->country,
            'regioncode' => '-', // Not supported yet.
            'language' => explode('_', current_language())[0],
            'geolocation' => '',
            'emailalert' => 1,
            'commnews' => 1,
            'policyagreed' => 0

        ]);
        $lis = \core\hub\registration::get_stats_summary($siteinfo);
        $lis = strip_tags($lis, '<ul><li>');
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
           [get_string('activeusers'), $DB->count_records('user', ['lastip' => ''])]];
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
        return $this->create_charts($arr, 'course', get_string('courses'));
    }

    /**
     * Table enrolments.
     *
     * @return string
     */
    public function table_enrolments() {
        global $DB;
        $enabled = enrol_get_plugins(true);
        $arr = [];
        foreach ($enabled as $key => $unused) {
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
    public function table_completions() {
        return $this->create_charts([], 'course_completions', get_string('coursecompletions'), 'timecompleted');
    }

    /**
     * Table questions.
     *
     * @return string
     */
    public function table_questions() {
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
     * Table country.
     *
     * @return string
     */
    public function table_country() {
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

        $tbl = new html_table();
        $tbl->attributes = ['class' => 'table table-sm table-hover w-50'];
        $tbl->colclasses = ['text-left', 'text-right'];
        $tbl->size = [null, '5rem'];
        $tbl->caption = $title;
        $tbl->data = $data;
        $tbl->data[] = [html_writer::tag('b', get_string('total')), $DB->count_records($table, [])];

        $wh = ($where == '') ? "$field  > 0" : "($field > 0) AND ($where)";
        $rows = $this->local_querry("
            SELECT
                 CONCAT(YEAR(FROM_UNIXTIME($field)), ' ', WEEKOFYEAR(FROM_UNIXTIME($field))) AS week,
                 COUNT(*) as newitems
            FROM {" . $table . "}
            WHERE $wh
            GROUP BY CONCAT(YEAR(FROM_UNIXTIME($field)), ' ', WEEKOFYEAR(FROM_UNIXTIME($field)))
            ORDER BY $field" , "
            SELECT
                TO_CHAR(TO_TIMESTAMP($field), 'YYYY \"week\" WW') AS week,
                COUNT(*) AS newitems
            FROM {" . $table . "}
            WHERE $wh
            GROUP BY 1
            ORDER BY 1");
        $chart1 = new \core\chart_line();
        $chart1->set_smooth(true);
        $series = $labels = $quarter1 = $quarter2 = $quarter3 = $quarter4 = $qlabels = [];
        $total = 0;
        foreach ($rows as $row) {
            $total += $row->newitems;
            $series[] = $total;
            $labels[] = $row->week;
        }
        $toyear = intval(date("Y"));
        $x = reset($rows);
        $fromyear = is_object($x) ? intval(explode(' ', $x->week)[0]) : $toyear - 7;
        $series = new core\chart_series($title, $series);
        $chart1->add_series($series);
        $chart1->set_labels($labels);

        $rows = $this->local_querry("
            SELECT
                CONCAT(YEAR(FROM_UNIXTIME($field)), ' ', QUARTER(FROM_UNIXTIME($field))) as year,
                COUNT(*) as newitems
            FROM {" . $table . "}
            WHERE $wh
            GROUP BY CONCAT(YEAR(FROM_UNIXTIME($field)), ' ', QUARTER(FROM_UNIXTIME($field)))
            ORDER BY $field" , "
            SELECT
                TO_CHAR(TO_TIMESTAMP($field), 'YYYY Q') AS year,
                COUNT(*) AS newitems
            FROM {" . $table . "}
            WHERE $wh
            GROUP BY 1
            ORDER BY 1");

        for ($i = $fromyear; $i <= $toyear; $i++) {
            $quarter1[] = array_key_exists("$i 1", $rows) ? $rows["$i 1"]->newitems : 0;
            $quarter2[] = array_key_exists("$i 2", $rows) ? $rows["$i 2"]->newitems : 0;
            $quarter3[] = array_key_exists("$i 3", $rows) ? $rows["$i 3"]->newitems : 0;
            $quarter4[] = array_key_exists("$i 4", $rows) ? $rows["$i 4"]->newitems : 0;
            $qlabels[] = $i;
        }
        $chart2 = new \core\chart_bar();
        $chart2->set_stacked(1);
        $series = new core\chart_series('Q1', $quarter1);
        $chart2->add_series($series);
        $series = new core\chart_series('Q2', $quarter2);
        $chart2->add_series($series);
        $series = new core\chart_series('Q3', $quarter3);
        $chart2->add_series($series);
        $series = new core\chart_series('Q4', $quarter4);
        $chart2->add_series($series);
        $chart2->set_labels($qlabels);
        return html_writer::table($tbl) . '<br>' . $OUTPUT->render($chart1, false) . '<br>' . $OUTPUT->render($chart2);
    }

    /**
     * Do sqlquerry.
     *
     * @param string $mysql
     * @param string $postgr
     * @return array
     */
    private function local_querry($mysql, $postgr) {
        global $DB;
        $family = $DB->get_dbfamily();
        $sql = ($family === 'mysql' or $family === 'mssql') ? $mysql : $postgr;
        return $DB->get_records_sql($sql);
    }
}