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

defined('MOODLE_INTERNAL') || die();

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
class report_growth_index_testcase extends advanced_testcase {

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
        $this->setUser($user);
        chdir($CFG->dirroot . '/report/growth');
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (View growth report).');
        include($CFG->dirroot . '/report/growth/index.php');
    }

    /**
     * Test index general.
     */
    public function test_index_general() {
        $html = $this->test_page();
        $this->assertStringContainsString('Number of users (5)', $html);
    }

    /**
     * Test page 2.
     */
    public function test_page2() {
        $html = $this->test_page(2);
        $this->assertStringContainsString('Suspended', $html);
    }

    /**
     * Test page 3.
     */
    public function test_page3() {
        $html = $this->test_page(3);
        $this->assertStringContainsString(' ', $html);
    }

    /**
     * Test page 4.
     */
    public function test_page4() {
        $html = $this->test_page(4);
        $this->assertStringContainsString(' ', $html);
    }

    /**
     * Test page 5.
     */
    public function test_page5() {
        $html = $this->test_page(5);
        $this->assertStringContainsString(' ', $html);
    }

    /**
     * Test page 6.
     */
    public function test_page6() {
        $html = $this->test_page(6);
        $this->assertStringContainsString(' ', $html);
    }

    /**
     * Test page 7.
     */
    public function test_page7() {
        $html = $this->test_page(7);
        $this->assertStringContainsString(' ', $html);
    }

    /**
     * Test page 8.
     */
    public function test_page8() {
        $html = $this->test_page(8);
        $this->assertStringContainsString(' ', $html);
    }

    /**
     * Test page 9.
     */
    public function test_page9() {
        $html = $this->test_page(9);
        $this->assertStringContainsString(' ', $html);
    }

    /**
     * Test page 10.
     */
    public function test_page10() {
        $html = $this->test_page(10);
        $this->assertStringContainsString(' ', $html);
    }

    /**
     * Test page 11.
     */
    public function test_page11() {
        $html = $this->test_page(11);
        $this->assertStringContainsString(' ', $html);
    }

    /**
     * Test page 12.
     */
    public function test_page12() {
        $html = $this->test_page(12);
        $this->assertStringContainsString(' ', $html);
    }

    /**
     * Test page.
     *
     * @param int $pageid
     * @return string
     */
    public function test_page($pageid = 1) {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;
        chdir($CFG->dirroot . '/report/growth');
        $_POST['p'] = $pageid;
        ob_start();
        include($CFG->dirroot . '/report/growth/index.php');
        $html = ob_get_clean();
        return $html;
    }
}