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
 * Class for tests related to growth report events.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(category_renderer::class)]
#[CoversClass(global_renderer::class)]
#[CoversClass(growth_renderer::class)]
#[CoversClass(course_renderer::class)]
final class empty_test extends advanced_testcase {
    /**
     * Setup testcase.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->resetAfterTest();
    }

    /**
     * Test global tables.
     */
    public function test_empty_global(): void {
        global $PAGE;
        $output = new global_renderer($PAGE, 'general');
        $context = \context_system::instance();
        $this->assertStringContainsString(' ', $output->create_tabtree($context));
        $this->assertStringContainsString('Mobile services enabled (Yes)', $output->table_summary());
        $this->assertStringContainsString('No Users found', $output->table_users('Users'));
        $this->assertStringContainsString('No Courses found', $output->table_courses('Courses'));
        $this->assertStringContainsString('No Enrolments found', $output->table_enrolments('Enrolments'));
        $this->assertEquals('No Mobile devices found', $output->table_mobiles('Mobile devices'));
        $this->assertEquals('No Payments found', $output->table_payments('Payments'));
        $this->assertStringContainsString('Show chart data', $output->table_countries());
    }

    /**
     * Test empty course tables.
     */
    public function test_empty_course(): void {
        global $PAGE;
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $context = \context_course::instance($course->id);
        $output = new course_renderer($PAGE, 'general');
        $this->assertStringContainsString(' ', $output->create_tabtree($context));
        $this->assertStringContainsString('No Users found', $output->table_countries());
    }

    /**
     * Test category tables.
     */
    public function test_empty_category(): void {
        global $PAGE;
        $dg = $this->getDataGenerator();
        $categoryid = $dg->create_category()->id;
        $context = \context_coursecat::instance($categoryid);
        $output = new global_renderer($PAGE, 'general');
        $this->assertStringContainsString(' ', $output->create_tabtree($context));
        $this->assertStringContainsString(' ', $output->table_enrolments());
        $this->assertStringContainsString('No ', $output->table_coursecompletions('test'));
    }
}
