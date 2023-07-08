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
 * Tests for growth report events.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_growth\output;

use advanced_testcase;
/**
 * Class report_growth_renderers_testcase
 *
 * Class for tests related to growth report events.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \report_growth
 */
class renderers_test extends advanced_testcase {

    /** @var int courseid */
    private int $courseid;

    /**
     * Setup testcase.
     */
    public function setUp():void {
        $this->setAdminUser();
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $categoryid = $dg->create_category()->id;
        $dg->create_course(['category' => $categoryid]);
        $dg->create_course(['category' => $categoryid]);
        $dg->create_course(['category' => $categoryid]);
        $courseid = $dg->create_course(['category' => $categoryid, 'enablecompletion' => true])->id;
        $user = $dg->create_user();
        $dg->enrol_user($user->id, $courseid, 'student');
        $user = $dg->create_user(['country' => 'BE']);
        $dg->enrol_user($user->id, $courseid, 'student');
        $user = $dg->create_user(['country' => 'NL']);
        $dg->enrol_user($user->id, $courseid, 'student');
        $user = $dg->create_user(['country' => 'UG']);
        $dg->enrol_user($user->id, $courseid, 'student');
        $this->courseid = $courseid;
    }

    /**
     * Test course tables.
     * @covers \report_growth\output\course_renderer
     * @covers \report_growth\output\growth_renderer
     */
    public function test_course() {
        global $PAGE;
        $context = \context_course::instance($this->courseid);
        $output = new course_renderer($PAGE, 'general');
        $this->assertStringContainsString('No Activities found', $output->create_tabtree($context, 3));
        $this->assertStringContainsString('Show chart data', $output->table_enrolments());
        $this->assertStringContainsString('Show chart data', $output->table_countries());
    }

    /**
     * Test global tables.
     * @covers \report_growth\output\global_renderer
     * @covers \report_growth\output\growth_renderer
     */
    public function test_global() {
        global $PAGE;
        $output = new global_renderer($PAGE, 'general');
        $context = \context_system::instance();
        $this->assertStringContainsString(' ', $output->create_tabtree($context));
        $this->assertStringContainsString('Mobile services enabled (Yes)', $output->table_summary());
        $this->assertStringContainsString('>5</td>', $output->table_users('Users')); // Users + admin user.
        $this->assertStringContainsString('>2</td>', $output->table_courses('Courses'));
        $this->assertStringContainsString('>4</td>', $output->table_enrolments('Enrolments'));
        $this->assertEquals('No Mobile devices found', $output->table_mobiles('Mobile devices'));
        $this->assertEquals('No Payments found', $output->table_payments('Payments'));
        $this->assertStringContainsString('Show chart data', $output->table_countries());
    }

    /**
     * Test category tables.
     * @covers \report_growth\output\category_renderer
     * @covers \report_growth\output\growth_renderer
     */
    public function test_category() {
        global $PAGE;
        $course = get_course($this->courseid);
        $context = \context_coursecat::instance($course->category);
        $output = new global_renderer($PAGE, 'general');
        $this->assertStringContainsString(' ', $output->create_tabtree($context));
        $this->assertStringContainsString('>4</td>', $output->table_enrolments());
    }
}
