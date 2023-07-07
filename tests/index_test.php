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
 * Tests for growth report index page.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_growth;

use advanced_testcase;
use moodle_exception;

/**
 * Class report_growth_index_testcase
 *
 * Class for tests related to growth report events.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index_test extends advanced_testcase {

    /**
     * Setup testcase.
     */
    public function setUp():void {
        set_config('logguests', 1, 'logstore_standard');
        $this->setAdminUser();
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $user = $dg->create_user(['country' => 'BE']);
        $course = $dg->create_course();
        $dg->create_module('page', ['course' => $course->id]);
        $user = $dg->create_user(['country' => 'NL']);
        $dg->enrol_user($user->id, $course->id);
        $course = $dg->create_course();
        $user = $dg->create_user(['country' => 'UG']);
        $dg->enrol_user($user->id, $course->id);
    }

    /**
     * Test settings.
     * @covers \report_growth\output\global_renderer
     * @covers \report_growth\output\growth_renderer
     */
    public function test_settings() {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $admin = admin_get_root(true, true);
        $this->assertNotEmpty($admin);
    }

    /**
     * Test index with wrong permission.
     * @covers \report_growth\output\global_renderer
     * @covers \report_growth\output\growth_renderer
     */
    public function test_index_wrong_permissions() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $user1 = $DB->get_record('user', ['id' => $user->id]);
        $this->assertSame($user->id, $user1->id);
        $this->setUser($user);
        chdir($CFG->dirroot . '/report/growth');
        $this->assertNotEmpty($OUTPUT);
        $this->assertNotEmpty($PAGE);
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (View growth report).');
        include($CFG->dirroot . '/report/growth/index.php');
    }

    /**
     * Test a global page.
     *
     * @dataProvider pageprovider
     * @param int $x
     * @param string $expected
     * @covers \report_growth\output\global_renderer
     * @covers \report_growth\output\growth_renderer
     */
    public function test_page_x($x, $expected): void {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        chdir($CFG->dirroot . '/report/growth');
        $user = $DB->get_record('user', ['id' => $USER->id]);
        $this->assertSame($user->id, $USER->id);
        $this->assertNotEmpty($OUTPUT);
        $this->assertNotEmpty($PAGE);
        $_POST['p'] = $x;
        ob_start();
        include($CFG->dirroot . '/report/growth/index.php');
        $html = ob_get_clean();
        $this->assertStringContainsString($expected, $html);
    }

    /**
     * Test pages.
     */
    public function pageprovider() {
        return [
            [1, 'Number of users (5)'],
            [2, '>3</td>'],   // There are 3 courses.
            [3, 'Suspended'], // There are no users suspended.
            [4, 'No Last '],  // No one there.
            [5, '>2</td>'],   // There are 2 enrolments.
            [6, '.'],
            [7, '.'],
            [8, '.'],
            [9, '.'],
            [10, '.'],
            [11, '.'],
            [12, '.'],
            [13, '.'],
            [14, '.'],
            [15, '.'],
            [16, '.'],
            [17, '.'],
            [18, '.'],
            [19, '.'],
            [20, '.'],
            [0, '.'],
            [99999, '.'],
        ];
    }

    /**
     * Test a course page.
     *
     * @dataProvider courseprovider
     * @param int $x
     * @param string $expected
     * @covers \report_growth\output\course_renderer
     * @covers \report_growth\output\growth_renderer
     */
    public function test_course_x($x, $expected): void {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        chdir($CFG->dirroot . '/report/growth');
        $user = $DB->get_record('user', ['id' => $USER->id]);
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $this->assertSame($user->id, $USER->id);
        $this->assertNotEmpty($OUTPUT);
        $this->assertNotEmpty($PAGE);
        $_POST['p'] = $x;
        $_POST['contextid'] = $context->id;
        ob_start();
        include($CFG->dirroot . '/report/growth/index.php');
        $html = ob_get_clean();
        $this->assertStringContainsString($expected, $html);
    }

    /**
     * Test pages.
     */
    public function courseprovider() {
        return [
            [1, '.'],
            [2, '.'],
            [3, '.'],
            [4, '.'],
            [5, '.'],
            [6, '.'],
            [7, '.'],
            [8, '.'],
            [9, '.'],
            [10, '.'],
            [11, '.'],
            [12, '.'],
            [13, '.'],
            [14, '.'],
            [15, '.'],
            [16, '.'],
            [0, '.'],
            [99999, '.'],
        ];
    }

    /**
     * Test a course page.
     *
     * @dataProvider courseprovider
     * @param int $x
     * @param string $expected
     * @covers \report_growth\output\category_renderer
     * @covers \report_growth\output\growth_renderer
     */
    public function test_category_x($x, $expected): void {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        chdir($CFG->dirroot . '/report/growth');
        $category = $this->getDataGenerator()->create_category();
        $context = \context_coursecat::instance($category->id);
        $user = $DB->get_record('user', ['id' => $USER->id]);
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->assertSame($user->id, $USER->id);
        $this->assertNotEmpty($OUTPUT);
        $this->assertNotEmpty($PAGE);
        $_POST['p'] = $x;
        $_POST['contextid'] = $context->id;
        ob_start();
        include($CFG->dirroot . '/report/growth/index.php');
        $html = ob_get_clean();
        $this->assertStringContainsString($expected, $html);
    }
}
