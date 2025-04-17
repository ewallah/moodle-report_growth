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
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_growth\output;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class report_growth_renderers_testcase
 *
 * Class for tests related to growth report events.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \report_growth)]
 */
#[CoversClass(global_renderer::class)]
#[CoversClass(growth_renderer::class)]
#[CoversClass(category_renderer::class)]
#[CoversClass(course_renderer::class)]
final class renderers_test extends advanced_testcase {
    /** @var int courseid */
    private int $courseid;

    /**
     * Setup testcase.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $categoryid = $dg->create_category()->id;
        $dg->create_course(['category' => $categoryid]);
        $dg->create_course(['category' => $categoryid]);
        $dg->create_course(['category' => $categoryid]);
        $course = $dg->create_course(['category' => $categoryid, 'enablecompletion' => true]);
        $dg->create_and_enrol($course, 'student');
        $dg->create_and_enrol($course, 'student', ['country' => 'BE']);
        $dg->create_and_enrol($course, 'student', ['country' => 'NL']);
        $dg->create_and_enrol($course, 'student', ['country' => 'UG']);
        $user = $dg->create_and_enrol($course, 'editingteacher');
        $this->setUser($user->id);
        $params = ['context' => \context_course::instance($course->id), 'objectid' => $course->id];
        \core\event\course_information_viewed::create($params)->trigger();
        $this->courseid = $course->id;
        $this->setUser(null);
    }

    /**
     * Test course tables.
     */
    public function test_course(): void {
        global $PAGE;
        $context = \context_course::instance($this->courseid);
        $output = new course_renderer($PAGE, 'general');
        $this->assertStringContainsString('No Activities found', $output->create_tabtree($context, 3));
        $this->assertStringContainsString('Show chart data', $output->table_enrolments());
        $this->assertStringContainsString('Show chart data', $output->table_countries());
        $output->table_certificates();
        // TODO: Why are there no teacher logs.
        $this->assertStringContainsString('No teachers found', $output->table_teachers('teachers'));
    }

    /**
     * Test global tables.
     */
    public function test_global(): void {
        global $PAGE;
        $output = new global_renderer($PAGE, 'general');
        $context = \context_system::instance();
        $this->assertStringContainsString(' ', $output->create_tabtree($context));
        $this->assertStringContainsString('Mobile services enabled (Yes)', $output->table_summary());
        $this->assertStringContainsString('>6</td>', $output->table_users('Users')); // Users + admin user.
        // Some plugins can create courses.
        $this->assertStringContainsString('</td>', $output->table_courses('Courses'));
        $this->assertStringContainsString('>5</td>', $output->table_enrolments('Enrolments'));
        $this->assertEquals('No Mobile devices found', $output->table_mobiles('Mobile devices'));
        $this->assertEquals('No Payments found', $output->table_payments('Payments'));
        $this->assertStringContainsString('Show chart data', $output->table_countries());
        $output->table_certificates();
    }

    /**
     * Test category tables.
     */
    public function test_category(): void {
        global $PAGE;
        $course = get_course($this->courseid);
        $context = \context_coursecat::instance($course->category);
        $output = new global_renderer($PAGE, 'general');
        $this->assertStringContainsString(' ', $output->create_tabtree($context));
        $this->assertStringContainsString('>5</td>', $output->table_enrolments());
        $output->table_certificates();
    }
}
