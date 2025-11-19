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

use moodle_url;
use html_writer;
use plugin_renderer_base;
use renderable;
use tabobject;
use stdClass;
use core\{chart_bar, chart_line, chart_series};

/**
 * growth report renderer.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_renderer extends growth_renderer {
    /** @var int courseid. */
    private int $courseid;

    /**
     * Create Tabs.
     *
     * @param stdClass $context Selected $coursecontext
     * @param int $p Selected tab
     */
    public function create_tabtree(stdClass $context, int $p = 1): string {
        global $CFG;
        $this->courseid = $context->instanceid;
        $this->context = $context;
        $txt = get_strings(['activities', 'lastaccess', 'coursecompletions', 'defaultcourseteachers']);
        $rows = [
            'enrolments' => get_string('enrolments', 'enrol'),
            'lastaccess' => $txt->lastaccess,
            'activities' => $txt->activities,
            'teachers' => $txt->defaultcourseteachers, ];
        if (!empty($CFG->enablecompletion)) {
            $rows['activitiescompleted'] = get_string('activitiescompleted', 'completion');
            $rows['coursecompletions'] = $txt->coursecompletions;
        }

        $rows = array_merge($rows, $this->certificate_tabs());
        $rows['countries'] = get_string('countries', 'report_growth');
        // Trigger a report viewed event.
        $this->trigger_page($p);
        return $this->render_page($rows, $p);
    }

    /**
     * Table enrolments.
     *
     * @param string $title Title
     */
    public function table_enrolments(string $title = ''): string {
        return $this->collect_course_table($title, 'enrol', 'user_enrolments', 'courseid', 'enrolid', 'timecreated');
    }

    /**
     * Table last access.
     *
     * @param string $title Title
     */
    public function table_lastaccess(string $title = ''): string {
        return $this->create_charts('user_lastaccess', $title, 'timeaccess', 'courseid = ' . $this->courseid);
    }

    /**
     * Table activities.
     *
     * @param string $title Title
     */
    public function table_activities(string $title = ''): string {
        return $this->create_charts('course_modules', $title, 'added', 'course = ' . $this->courseid);
    }

    /**
     * Table Activities completed.
     *
     * @param string $title Title
     */
    public function table_activitiescompleted(string $title = ''): string {
        return $this->collect_course_table($title, 'course_modules', 'course_modules_completion', 'course', 'coursemoduleid');
    }

    /**
     * Table completions.
     *
     * @param string $title Title
     */
    public function table_coursecompletions(string $title = ''): string {
        return $this->create_charts('course_completions', $title, 'timecompleted', 'course = ' . $this->courseid);
    }

    /**
     * Table badges.
     *
     * @param string $title Title
     */
    public function table_badges(string $title = ''): string {
        return $this->collect_course_table($title, 'badge', 'badge_issued', 'courseid', 'badgeid', 'dateissued');
    }

    /**
     * Table teacher logs.
     *
     * @param string $title Title
     */
    public function table_teachers(string $title = ''): string {
        global $DB;
        $out = get_string('nostudentsfound', 'moodle', $title);
        $teachers = get_users_by_capability($this->context, 'moodle/course:viewhiddenactivities', 'u.id', 'u.id');
        if ($teachers && count($teachers) > 0) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($teachers));
            $insql .= ' AND courseid = ? AND contextlevel = ? AND contextinstanceid = ?';
            $inparams[] = $this->courseid;
            $inparams[] = $this->context->contextlevel;
            $inparams[] = $this->context->instanceid;
            $out = $this->create_charts('logstore_standard_log', $title, 'timecreated', 'userid ' . $insql, $inparams);
        }

        return $out;
    }

    /**
     * Table certificates.
     *
     * @param string $title Title
     */
    public function table_certificates(string $title = ''): string {
        $s = 'certificate';
        $t = 'certificate_issues';
        return $this->dir_exists($s) ? $this->collect_course_table($title, $s, $t, 'course', 'certificateid', 'timecreated') : '';
    }

    /**
     * Table custom certificates.
     *
     * @param string $title Title
     */
    public function table_customcerts(string $title = ''): string {
        $s = 'customcert';
        $t = 'customcert_issues';
        return $this->dir_exists($s) ? $this->collect_course_table($title, $s, $t, 'course', 'customcertid', 'timecreated') : '';
    }

    /**
     * Table course certificates.
     *
     * @param string $title Title
     */
    public function table_coursecertificates(string $title = ''): string {
        $s = 'coursecertificate';
        $t = 'tool_certificate_issues';
        return $this->dir_exists($s) ? $this->create_charts($t, $title, 'timecreated', 'courseid = ' . $this->courseid) : '';
    }

    /**
     * Table country.
     *
     * @param string $title Title
     */
    public function table_countries(string $title = ''): string {
        global $DB;
        $title = get_string('users');
        $out = get_string('nostudentsfound', 'moodle', $title);
        $ids = $DB->get_fieldset_select('enrol', 'id', 'courseid = :courseid', ['courseid' => $this->context->instanceid]);
        if (count($ids) > 0) {
            [$insql, $inparams] = $this->insql($ids, 'enrolid', 'enrolid');
            $userids = $DB->get_fieldset_select('user_enrolments', 'userid', $insql, $inparams);
            if (count($userids) > 0) {
                [$insql, $inparams] = $this->insql($userids, 'id', 'id');
                $sql = "SELECT country, COUNT(country) AS newusers FROM {user} WHERE {$insql} GROUP BY country ORDER BY country";
                $rows = $DB->get_records_sql($sql, $inparams);
                $out = $this->create_countries($rows, $title);
            }
        }

        return $out;
    }
}
