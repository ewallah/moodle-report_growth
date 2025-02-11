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
 * @copyright eWallah (www.eWallah.net)
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
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class global_renderer extends growth_renderer {
    /**
     * Create Tabs.
     *
     * @param \stdClass $context Selected $coursecontext
     * @param int $p Selected tab
     */
    public function create_tabtree($context, $p = 1) {
        global $CFG;
        $this->context = $context;
        $txt = get_strings(['summary', 'courses', 'users', 'activities', 'lastaccess', 'coursecompletions', 'files', 'payments']);
        $rows = ['summary' => $txt->summary, 'courses' => $txt->courses, 'users' => $txt->users, 'lastaccess' => $txt->lastaccess];
        $rows['enrolments'] = get_string('enrolments', 'enrol');
        $rows['logguests'] = get_string('policydocaudience2', 'tool_policy');
        $rows['activities'] = $txt->activities;
        if (!empty($CFG->enablecompletion)) {
            $rows['activitiescompleted'] = get_string('activitiescompleted', 'completion');
            $rows['coursecompletions'] = $txt->coursecompletions;
        }
        if (!empty($CFG->enablemobilewebservice)) {
            $rows['mobiles'] = get_string('mobile', 'report_growth');
        }
        $rows = array_merge($rows, $this->certificate_tabs());
        $rows['payments'] = $txt->payments;
        $rows['questions'] = get_string('questions', 'question');
        $rows['files'] = $txt->files;
        $rows['messages'] = get_string('messages', 'message');
        $rows['countries'] = get_string('countries', 'report_growth');
        // Trigger a report viewed event.
        $this->trigger_page($p);
        return $this->render_page($rows, $p);
    }

    /**
     * Table summary.
     *
     * @param string $title Title
     * @return string
     */
    public function table_summary($title = ''): string {
        $siteinfo = \core\hub\registration::get_site_info([]);
        $lis = strip_tags(\core\hub\registration::get_stats_summary($siteinfo), '<ul><li>');
        return \html_writer::tag('h3', $title) . str_replace(get_string('sendfollowinginfo_help', 'hub'), '', $lis);
    }

    /**
     * Table last access.
     *
     * @param string $title Title
     * @return string
     */
    public function table_lastaccess($title = ''): string {
        return $this->create_charts('user_lastaccess', $title, 'timeaccess');
    }

    /**
     * Table users.
     *
     * @param string $title Title
     * @return string
     */
    public function table_users($title = ''): string {
        global $DB;
        $arr = [
           [get_string('deleted'), $DB->count_records('user', ['deleted' => 1])],
           [get_string('suspended'), $DB->count_records('user', ['suspended' => 1])],
           [get_string('confirmed', 'admin'), $DB->count_records('user', ['confirmed' => 1]) - 1],
           [get_string('activeusers'), $DB->count_records_select('user', 'lastip <> ?', [''])], ];
        return $this->create_intro($arr, $title) . $this->create_charts('user', $title);
    }

    /**
     * Table courses.
     *
     * @param string $title Title
     * @return string
     */
    public function table_courses($title = ''): string {
        global $DB;
        $arr = [[get_string('categories'), $DB->count_records('course_categories', [])]];
        return $this->create_intro($arr, $title) . $this->create_charts('course', $title, 'timecreated', 'id > 1');
    }

    /**
     * Table enrolments.
     *
     * @param string $title Title
     * @return string
     */
    public function table_enrolments($title = ''): string {
        global $DB;
        $enabled = array_keys(enrol_get_plugins(true));
        $arr = [];
        foreach ($enabled as $key) {
            $ids = $DB->get_fieldset_select('enrol', 'id', "enrol = '$key'");
            if (count($ids) > 0) {
                [$insql, $inparams] = $DB->get_in_or_equal($ids);
                $cnt = $DB->count_records_sql("SELECT COUNT('x') FROM {user_enrolments} WHERE enrolid {$insql}", $inparams);
                $arr[] = [get_string('pluginname', 'enrol_' . $key), $cnt];
            }
        }
        return $this->create_intro($arr, $title) . $this->create_charts('user_enrolments', $title);
    }

    /**
     * Table payments.
     *
     * @param string $title Title
     * @return string
     */
    public function table_payments($title = ''): string {
        return $this->create_charts('payments', $title);
    }

    /**
     * Table mobile.
     *
     * @param string $title Title
     * @return string
     */
    public function table_mobiles($title = ''): string {
        return $this->create_charts('user_devices', $title);
    }

    /**
     * Table badges.
     *
     * @param string $title Title
     * @return string
     */
    public function table_badges($title = ''): string {
        return $this->create_charts('badge_issued', $title, 'dateissued');
    }

    /**
     * Table activities.
     *
     * @param string $title Title
     * @return string
     */
    public function table_activities($title = ''): string {
        return $this->create_charts('course_modules', $title, 'added');
    }

    /**
     * Table Activities completed.
     *
     * @param string $title Title
     * @return string
     */
    public function table_activitiescompleted($title = ''): string {
        return $this->create_charts('course_modules_completion', $title, 'timemodified');
    }

    /**
     * Table completions.
     *
     * @param string $title Title
     * @return string
     */
    public function table_coursecompletions($title = ''): string {
        return $this->create_charts('course_completions', $title, 'timecompleted');
    }

    /**
     * Table questions.
     *
     * @param string $title Title
     * @return string
     */
    public function table_questions($title = ''): string {
        return $this->create_charts('question', $title);
    }

    /**
     * Table guests.
     *
     * @param string $title Title
     * @return string
     */
    public function table_logguests($title = ''): string {
        return $this->create_charts('logstore_standard_log', $title, 'timecreated', 'userid = 1');
    }

    /**
     * Table certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_certificates($title = ''): string {
        global $CFG;
        $s = '';
        if (file_exists($CFG->dirroot . '/mod/certificate')) {
            $s = $this->create_charts('certificate_issues', $title);
        }
        return $s;
    }

    /**
     * Table custom certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_customcerts($title = ''): string {
        global $CFG;
        $s = '';
        if (file_exists($CFG->dirroot . '/mod/customcert')) {
            $s = $this->create_charts('customcert_issues', $title);
        }
        return $s;
    }

    /**
     * Table course certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_coursecertificates($title = ''): string {
        global $CFG;
        $s = '';
        if (file_exists($CFG->dirroot . '/mod/coursecertificate')) {
            $s = $this->create_charts('tool_certificate_issues', $title);
        }
        return $s;
    }

    /**
     * Table files.
     *
     * @param string $title Title
     * @return string
     */
    public function table_files($title = ''): string {
        return $this->create_charts('files', $title);
    }

    /**
     * Table messages.
     *
     * @param string $title Title
     * @return string
     */
    public function table_messages($title = ''): string {
        return $this->create_charts('messages', $title);
    }

    /**
     * Table country.
     *
     * @param string $title Title
     * @return string
     */
    public function table_countries($title = ''): string {
        global $DB;
        $sql = "SELECT country, COUNT(country) AS newusers FROM {user} GROUP BY country ORDER BY country";
        $rows = $DB->get_records_sql($sql);
        return $this->create_countries($rows, $title);
    }
}
