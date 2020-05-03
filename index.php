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
require_once($CFG->libdir.'/adminlib.php');

$p = optional_param('p', 1, PARAM_INT);
$context = context_system::instance();
require_login();
require_capability('report/growth:view', $context);

$ur = '/report/growth/index.php';
$str = get_string('pluginname', 'report_growth');
$url = new moodle_url($ur, ['p' => $p]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title($str);
$PAGE->set_heading($str);
$output = $PAGE->get_renderer('report_growth');

echo $output->header();
$rows = [
    new tabobject(1, new moodle_url($ur, ['p' => 1]), get_string('summary')),
    new tabobject(2, new moodle_url($ur, ['p' => 2]), get_string('users')),
    new tabobject(3, new moodle_url($ur, ['p' => 3]), get_string('courses')),
    new tabobject(4, new moodle_url($ur, ['p' => 4]), get_string('enrolments', 'enrol')),
    new tabobject(5, new moodle_url($ur, ['p' => 5]), get_string('country')),
    ];
echo $OUTPUT->tabtree($rows, $p);
switch ($p) {
    case 2:
        echo $output->table_users();
        break;
    case 3:
        echo $output->table_courses();
        break;
    case 4:
        echo $output->table_enrolments();
        break;
    case 5:
        echo $output->table_country();
        break;
    default:
        echo $output->table_summary();
        break;
}

echo $output->footer();

// Trigger a report viewed event.
$event = \report_growth\event\report_viewed::create(['context' => $context]);
$event->trigger();
