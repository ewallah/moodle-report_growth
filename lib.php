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
 * @copyright 2020 eWallah
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

