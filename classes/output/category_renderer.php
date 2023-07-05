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
        $rows = ['enrolments' => get_string('enrolments', 'enrol')];
        if (!empty($CFG->enablecompletion)) {
            $rows['coursecompletions'] = get_string('coursecompletions');
        }
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
        return $this->collect_category_table2($title, 'enrol', 'user_enrolments', 'enrolid', 'timecreated');
    }

    /**
     * Table completions.
     *
     * @param string $title Title
     * @return string
     */
    public function table_coursecompletions($title = ''): string {
        return $this->collect_category_table($title, 'course_completions', 'course', 'timecompleted');
    }
}
