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
     */
    public function test_settings() {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $admin = admin_get_root(true, true);
        $this->assertNotEmpty($admin);
    }

    /**
     * Test index with wrong permission.
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
     * Test a page.
     *
     * @dataProvider pageprovider
     * @param int $x
     * @param string $expected
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
            [2, 'Suspended'],
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
            [0, '.'],
            [99999, '.'],
        ];
    }
}
