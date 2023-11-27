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
 * growth report
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $CFG;
require_once($CFG->libdir.'/adminlib.php');

$p = optional_param('p', 1, PARAM_INT);
$contextid = optional_param('contextid', 1, PARAM_INT);
$context = context::instance_by_id($contextid);
require_login();
$str = get_string('growth', 'report_growth');
$pluginname = get_string('pluginname', 'report_growth');
$url = new moodle_url('/report/growth/index.php', ['p' => $p, 'contextid' => $context->id]);
$PAGE->set_url($url);
switch ($context->contextlevel) {
    case CONTEXT_COURSE:
        $course = get_course($context->instanceid);
        $PAGE->set_course($course);
        $str = format_text($course->fullname);
        require_capability('report/growth:viewcourse', $context);
        $output = new \report_growth\output\course_renderer($PAGE, 'general');
        break;
    case CONTEXT_COURSECAT:
        $PAGE->set_context($context);
        require_capability('report/growth:viewcategory', $context);
        core_course_category::page_setup();
        $output = new \report_growth\output\category_renderer($PAGE, 'general');
        break;
    default:
        $PAGE->set_context($context);
        require_capability('report/growth:view', $context);
        $output = new \report_growth\output\global_renderer($PAGE, 'general');
}
$PAGE->set_pagelayout('report');
$PAGE->set_title($str);
$PAGE->set_heading($str);
echo $output->header();
\core\report_helper::print_report_selector(get_string('growth', 'report_growth'));
echo $output->create_tabtree($context, $p);
echo $output->footer();
