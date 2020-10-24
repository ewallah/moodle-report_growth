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

    /** @var object renderer */
    protected $output;

    /**
     * Setup testcase.
     */
    public function setUp():void {
        global $PAGE;
        $this->setAdminUser();
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $dg->create_course();
        $dg->create_course();
        $dg->create_course();
        $course = $dg->create_course();
        $dg->create_user(['country' => 'BE']);
        $dg->create_user(['country' => 'NL']);
        $user = $dg->create_user(['country' => 'UG']);
        $dg->enrol_user($user->id, $course->id, 'student');
        $this->output = $PAGE->get_renderer('report_growth');
    }

    /**
     * Test tables.
     *
     */
    public function test_tables() {
        $this->assertStringContainsString('Mobile services enabled (Yes)', $this->output->table_summary());
        $this->assertStringContainsString('>3</td>', $this->output->table_users());
        $this->assertStringContainsString('>4</td>', $this->output->table_courses());
        $this->assertStringContainsString('>1</td>', $this->output->table_enrolments());
        $this->assertEquals('No Mobile devices found', $this->output->table_mobiles('Mobile devices'));
        $this->assertStringContainsString('Show chart data', $this->output->table_countries());
    }
}