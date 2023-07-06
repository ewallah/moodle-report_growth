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
 * @copyright 2023 eWallah
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
 * @copyright 2023 eWallah
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
        $this->courseids = array_keys($coursecat->get_courses());
        sort($this->courseids);
        $rows = ['enrolments' => get_string('enrolments', 'enrol')];
        if (!empty($CFG->enablecompletion)) {
            $rows['coursecompletions'] = get_string('coursecompletions');
        }
        $rows = array_merge($rows, $this->certificate_tabs());
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
        return $this->collect_cat2($title, 'certificate', 'course', 'certificate_issues', 'certificateid', 'timecreated');
    }

    /**
     * Table custom certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_customcerts($title = ''): string {
        return $this->collect_cat2($title, 'customcert', 'course', 'customcert_issues', 'customcertid', 'timecreated');
    }

    /**
     * Table course certificates.
     *
     * @param string $title Title
     * @return string
     */
    public function table_coursecertificates($title = ''): string {
        return $this->collect_cat($title, 'tool_certificate_issues', 'courseid', 'timecreated');
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
    private function collect_cat($title, $table, $fieldwhere, $fieldresult): string {
        global $DB;
        $out = get_string('nostudentsfound', 'moodle', $title);
        if (count($this->courseids) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal($this->courseids);
            $insql = $fieldwhere. ' ' . $insql;
            $out = $this->create_charts([], $table, $title, $fieldresult, $insql, $inparams);
        }
        return $out;
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
    private function collect_cat2($title, $table1, $field1, $table2, $field2, $fieldresult): string {
        global $DB;
        if (count($this->courseids) > 0) {
            list($insql, $inparams) = $DB->get_in_or_equal($this->courseids);
            $insql = $field1 . ' ' . $insql;
            $ids = $DB->get_fieldset_select($table1, 'id', $insql, $inparams);
            if (count($ids) > 0) {
                sort($ids);
                list($insql, $inparams) = $DB->get_in_or_equal($ids);
                $insql = $field2 . ' ' . $insql;
                return $this->create_charts([], $table2, $title, $fieldresult, $insql, $inparams);
            }
        }
        return get_string('nostudentsfound', 'moodle', $title);
    }
}
