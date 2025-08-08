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
 * Growth category report renderer.
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
use core\{chart_bar, chart_line, chart_series};

/**
 * Growth category report renderer.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_renderer extends growth_renderer {
    /** @var int categoryid. */
    private int $categoryid;

    /** @var array courseids. */
    private array $courseids;

    /**
     * Create Tabs.
     *
     * @param \stdClass $context Selected $coursecontext
     * @param int $p Selected tab
     * @return string
     */
    public function create_tabtree($context, $p = 1) {
        global $CFG;
        $this->categoryid = $context->instanceid;
        $this->context = $context;
        $coursecat = \core_course_category::get($this->categoryid);
        $this->courseids = array_values($coursecat->get_courses(['recursive' => true, 'idonly' => true]));
        sort($this->courseids);
        $txt = get_strings(['activities', 'lastaccess', 'coursecompletions']);
        $rows = [
            'enrolments' => get_string('enrolments', 'enrol'),
            'lastaccess' => $txt->lastaccess,
            'activities' => $txt->activities, ];
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
     * @return string
     */
    public function table_enrolments($title = ''): string {
        return $this->collect_cat2($title, 'enrol', 'courseid', 'user_enrolments', 'enrolid', 'timecreated');
    }

    /**
     * Table last access.
     *
     * @param string $title Title
     * @return string
     */
    public function table_lastaccess($title = ''): string {
        return $this->collect_cat($title, 'user_lastaccess', 'courseid', 'timeaccess');
    }

    /**
     * Table activities.
     *
     * @param string $title Title
     * @return string
     */
    public function table_activities($title = ''): string {
        return $this->collect_cat($title, 'course_modules', 'course', 'added');
    }

    /**
     * Table activities completed.
     *
     * @param string $title Title
     * @return string
     */
    public function table_activitiescompleted($title = ''): string {
        return $this->collect_cat2(
            $title,
            'course_modules',
            'course',
            'course_modules_completion',
            'coursemoduleid',
            'timemodified'
        );
    }

    /**
     * Table completions.
     *
     * @param string $title Title
     * @return string
     */
    public function table_coursecompletions($title = ''): string {
        return $this->collect_cat($title, 'course_completions', 'course', 'timecompleted');
    }

    /**
     * Table badges.
     *
     * @param string $title Title
     * @return string
     */
    public function table_badges($title = ''): string {
        return $this->collect_cat2($title, 'badge', 'courseid', 'badge_issued', 'badgeid', 'dateissued');
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
            $s = $this->collect_cat2($title, 'certificate', 'course', 'certificate_issues', 'certificateid', 'timecreated');
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
            $s = $this->collect_cat2($title, 'customcert', 'course', 'customcert_issues', 'customcertid', 'timecreated');
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
            $s = $this->collect_cat($title, 'tool_certificate_issues', 'courseid', 'timecreated');
        }
        return $s;
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
        $out = get_string('nostudentsfound', 'moodle', $title);
        if (count($this->courseids) > 0) {
            [$insql, $inparams] = $this->insql($this->courseids, 'courseid', 'courseid');
            $ids = $DB->get_fieldset_select('enrol', 'id', $insql, $inparams);
            if (count($ids) > 0) {
                [$insql, $inparams] = $this->insql($ids, 'enrolid', 'enrolid');
                $userids = $DB->get_fieldset_select('user_enrolments', 'userid', $insql, $inparams);
                if (count($userids) > 0) {
                    [$insql, $inparams] = $this->insql($userids, 'id', 'id');
                    $sql = "SELECT country, COUNT(country) AS newusers FROM {user} WHERE $insql GROUP BY country ORDER BY country";
                    $rows = $DB->get_records_sql($sql, $inparams);
                    $out = $this->create_countries($rows, $title);
                }
            }
        }
        return $out;
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
    protected function collect_cat($title, $table, $fieldwhere, $fieldresult): string {
        [$insql, $inparams] = $this->insql($this->courseids, $fieldwhere, $fieldresult);
        return $this->create_charts($table, $title, $fieldresult, $insql, $inparams);
    }

    /**
     * Collect category table.
     *
     * @param string $title Title
     * @param string $table1 First table
     * @param string $field1 Where lookup
     * @param string $table2 Second table
     * @param string $field2 Where lookup
     * @param string $fieldresult The field that has to be calculated
     * @return string
     */
    protected function collect_cat2($title, $table1, $field1, $table2, $field2, $fieldresult): string {
        global $DB;
        if (count($this->courseids) > 0) {
            [$insql, $inparams] = $this->insql($this->courseids, $field1, $fieldresult);
            $ids = $DB->get_fieldset_select($table1, 'id', $insql, $inparams);
            if (count($ids) > 0) {
                sort($ids);
                [$insql, $inparams] = $this->insql($ids, $field2, $fieldresult);
                return $this->create_charts($table2, $title, $fieldresult, $insql, $inparams);
            }
        }
        return get_string('nostudentsfound', 'moodle', $title);
    }
}
