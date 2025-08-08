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
 * Lib functions
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_growth_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/growth:viewcourse', $context) && $course->id > 1) {
        $url = new moodle_url('/report/growth/index.php', ['contextid' => $context->id]);
        $txt = get_string('growth', 'report_growth');
        $navigation->add($txt, $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Adds nodes to category navigation
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $context The context of the coursecategory
 * @return void|null return null if we don't want to display the node.
 */
function report_growth_extend_navigation_category_settings($navigation, $context) {
    if (has_capability('report/growth:viewcategory', $context)) {
        $url = new moodle_url('/report/growth/index.php', ['contextid' => $context->id]);
        $txt = get_string('growth', 'report_growth');
        $navigation->add($txt, $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function report_growth_page_type_list($pagetype, $parentcontext, $currentcontext) {
    return [
        '*' => get_string('page-x', 'pagetype'),
        'report-*' => get_string('page-report-x', 'pagetype'),
        'report-growth-*'     => get_string('page-report-growth-x', 'report_growth'),
        'report-growth-index' => get_string('page-report-growth-index', 'report_growth'),
    ];
}
