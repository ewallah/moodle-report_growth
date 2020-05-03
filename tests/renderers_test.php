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

defined('MOODLE_INTERNAL') || die();

/**
 * Class report_growth_renderers_testcase
 *
 * Class for tests related to growth report events.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_growth_renderers_testcase extends advanced_testcase {

    /**
     * Setup testcase.
     */
    public function setUp() {
        $this->setAdminUser();
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $dg->create_course();
        $dg->create_course();
        $dg->create_course();
        $dg->create_user(['country' => 'BE']);
        $dg->create_user(['country' => 'NL']);
        $dg->create_user(['country' => 'UG']);
    }

    /**
     * Test summary report.
     *
     */
    public function test_summary() {
        global $PAGE;
        $output = $PAGE->get_renderer('report_growth');
        $this->assertContains('Mobile services enabled (Yes)', $output->table_summary());
    }

    /**
     * Test users report.
     *
     */
    public function test_users() {
        global $PAGE;
        $output = $PAGE->get_renderer('report_growth');
        $this->assertContains('Show chart data', $output->table_users());
    }

    /**
     * Test courses report.
     *
     */
    public function test_courses() {
        global $PAGE;
        $output = $PAGE->get_renderer('report_growth');
        $x = $output->table_courses();
        $this->assertContains('3', $x);
    }

    /**
     * Test enrolments report.
     *
     */
    public function test_enrolments() {
        global $PAGE;
        $output = $PAGE->get_renderer('report_growth');
        $x = $output->table_enrolments();
        $this->assertContains('0', $x);
    }

    /**
     * Test country report.
     *
     */
    public function test_country() {
        global $PAGE;
        $output = $PAGE->get_renderer('report_growth');
        $x = $output->table_country();
        $this->assertContains('Show chart data', $x);
    }
}