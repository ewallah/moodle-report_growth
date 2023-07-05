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

use moodle_url;
use html_writer;
use plugin_renderer_base;
use renderable;
use tabobject;
use core\{chart_bar, chart_line, chart_series};

/**
 * growth report renderer.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_renderer extends growth_renderer {

    /** @var int courseid. */
    private int $courseid;

    /**
     * Create Tabs.
     *
     * @param \stdClass $context Selected $coursecontext
     * @param int $p Selected tab
     * @return string
     */
    public function create_tabtree($context, $p = 1) {
        global $CFG;
        $this->courseid = $context->instanceid;
        $this->context = $context;
        $txt = get_strings(['badges', 'coursecompletions', 'managemodules']);
        $rows = [];
        $rows['enrolments'] = get_string('enrolments', 'enrol');
        $rows['resources'] = $txt->managemodules;
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
        $rows['countries'] = get_string('countries', 'report_growth');
        // Trigger a report viewed event.
        $this->trigger_page($p);
        return $this->render_page($rows, $p);
    }


    /**
     * Table enrolments.
     *
     * @param string $title Title
     * @return string
     */
    public function table_enrolments($title = ''): string {
        return $this->collect_course_table($title, 'enrol', 'user_enrolments', 'enrolid', 'timecreated');
    }

    /**
     * Table badges.
     *
     * @param string $title Title
     * @return string
     */
    public function table_badges($title = ''): string {
        return $this->collect_course_table($title, 'badge', 'badge_issued', 'badgeid', 'dateissued');
    }

    /**
     * Table completions.
     *
     * @param string $title Title
     * @return string
     */
    public function table_coursecompletions($title = ''): string {
        return $this->create_charts([], 'course_completions', $title, 'timecompleted', 'course = ' . $this->courseid);
    }

    /**
     * Table resources.
     *
     * @param string $title Title
     * @return string
     */
    public function table_resources($title = ''): string {
        return $this->create_charts([], 'course_modules', $title, 'added', 'course = ' . $this->courseid);
    }

    /**
     * Table certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_certificates($title = ''): string {
        return $this->create_charts([], 'certificate_issues', $title);
    }

    /**
     * Table custom certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_customcerts($title = ''): string {
        return $this->create_charts([], 'customcert_issues', $title);
    }

    /**
     * Table course certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_coursecertificates($title = ''): string {
        return $this->create_charts([], 'tool_certificate_issues', $title);
    }

    /**
     * Table country.
     *
     * @param string $title Title
     * @return string
     */
    public function table_countries($title = ''): string {
        global $DB;
        $title = get_string('users');
        $chart = '';
        $ids = $DB->get_fieldset_select('enrol', 'id', 'courseid = :courseid', ['courseid' => $this->context->instanceid]);
        if (count($ids) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal($ids);
            $insql = 'enrolid ' . $insql;
            $userids = $DB->get_fieldset_select('user_enrolments', 'userid', $insql, $inparams);
            if (count($userids) > 0) {
                list($insql, $inparams) = $DB->get_in_or_equal($userids);
                $insql = 'id ' . $insql;
                $sql = "SELECT country, COUNT(country) AS newusers FROM {user} WHERE $insql GROUP BY country ORDER BY country";
                $rows = $DB->get_records_sql($sql, $inparams);
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
        }
        return get_string('nostudentsfound', 'moodle', $title);
    }
}
