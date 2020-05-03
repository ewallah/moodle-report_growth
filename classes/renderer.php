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
        global $DB, $OUTPUT;
        $table = new html_table();
        $table->attributes = ['class' => 'table table-sm table-hover w-50'];
        $table->colclasses = ['text-left', 'text-right'];
        $table->size = [null, '5rem'];
        $table->caption = get_string('users');
        $url = html_writer::link(new moodle_url('/admin/user.php'), get_string('total'));
        $table->data[] = [$url, $DB->count_records('user', [])];
        $table->data[] = [get_string('deleted'), $DB->count_records('user', ['deleted' => 1])];
        $table->data[] = [get_string('suspended'), $DB->count_records('user', ['suspended' => 1])];
        $table->data[] = [get_string('confirmed', 'admin'), $DB->count_records('user', ['confirmed' => 1])];
        $table->data[] = [get_string('statslogins'), $DB->count_records('user', ['lastip' => ''])];
        $family = $DB->get_dbfamily();
        if ($family === 'mysql' or $family === 'mssql') {
            $sql = "
               SELECT CONCAT(YEAR(FROM_UNIXTIME(timecreated)), ' ', WEEKOFYEAR(FROM_UNIXTIME(timecreated))) AS week,
                  COUNT(*) as newusers FROM {user}
               WHERE timecreated > 0 GROUP BY CONCAT(YEAR(FROM_UNIXTIME(timecreated)), ' ', WEEKOFYEAR(FROM_UNIXTIME(timecreated)))
               ORDER BY timecreated";
        } else {
            $sql = "
               SELECT TO_CHAR(TO_TIMESTAMP(timecreated / 1000), 'WW YY') AS week, count(*) AS newusers
               FROM {user} WHERE timecreated > 0
               GROUP BY 1 ORDER BY 1;";
        }
        $rows = $DB->get_records_sql($sql);
        $chart1 = new \core\chart_line();
        $chart1->set_smooth(true);
        $series = [];
        $labels = [];
        $total = 0;
        foreach ($rows as $row) {
            $total += $row->newusers;
            $series[] = $total;
            $labels[] = $row->week;
        }
        $series = new core\chart_series(get_string('users'), $series);
        $chart1->add_series($series);
        $chart1->set_labels($labels);

        if ($family === 'mysql' or $family === 'mssql') {
            $sql = "
               SELECT YEAR(FROM_UNIXTIME(timecreated)) AS year, COUNT(*) as newusers
               FROM {user}
               WHERE timecreated > 0
               GROUP BY YEAR(FROM_UNIXTIME(timecreated))
               ORDER BY timecreated";
        } else {
            $sql = "
               SELECT TO_CHAR(TO_TIMESTAMP(timecreated / 1000), 'YY') AS year, count(*) AS newusers
               FROM {user}
               WHERE timecreated > 0
               GROUP BY 1
               ORDER BY 1;";
        }
        $rows = $DB->get_records_sql($sql);
        $chart2 = new \core\chart_bar();
        $series = [];
        $labels = [];
        $total = 0;
        foreach ($rows as $row) {
            $series[] = $row->newusers;
            $labels[] = $row->year;
        }
        $series = new core\chart_series(get_string('year'), $series);
        $chart2->add_series($series);
        $chart2->set_labels($labels);
        return html_writer::table($table) . '<br>' . $OUTPUT->render($chart1) . '<br>' . $OUTPUT->render($chart2);
    }


    /**
     * Table courses.
     *
     * @return string
     */
    public function table_courses() {
        global $DB;
        $table = new html_table();
        $table->attributes = ['class' => 'table table-sm table-hover w-50'];
        $table->colclasses = ['text-left', 'text-right'];
        $table->size = [null, '5rem'];
        $table->caption = get_string('courses');
        $i = $DB->count_records('course', []);
        $table->data[] = [get_string('total'), --$i];
        $table->data[] = [get_string('categories'), $DB->count_records('course_categories', [])];
        return html_writer::table($table);
    }

    /**
     * Table enrolments.
     *
     * @return string
     */
    public function table_enrolments() {
        global $DB;
        $enabled = enrol_get_plugins(true);
        $table = new html_table();
        $table->attributes = ['class' => 'table table-sm table-hover w-50'];
        $table->colclasses = ['text-left', 'text-right'];
        $table->size = [null, '5rem'];
        $table->caption = get_string('enrolments', 'enrol');
        $table->data[] = [get_string('total'), $DB->count_records('user_enrolments', [])];
        foreach ($enabled as $key => $unused) {
            $ids = $DB->get_fieldset_select('enrol', 'id', "enrol = '$key'");
            if (count($ids) > 0) {
                list($insql, $inparams) = $DB->get_in_or_equal($ids);
                $cnt = $DB->count_records_sql("SELECT COUNT('x') FROM {user_enrolments} WHERE enrolid {$insql}", $inparams);
                $table->data[] = [get_string('pluginname', 'enrol_'. $key), $cnt];
            }
        }
        return html_writer::table($table);
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
}