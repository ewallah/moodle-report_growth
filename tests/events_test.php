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
 * Class report_growth_events_testcase
 *
 * Class for tests related to growth report events.
 *
 * @package   report_growth
 * @copyright 2020 eWallah
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_growth_events_testcase extends advanced_testcase {

    /**
     * Setup testcase.
     */
    public function setUp():void {
        $this->setAdminUser();
        $this->resetAfterTest();
    }

    /**
     * Test the report viewed event.
     *
     * It's not possible to use the moodle API to simulate the viewing of log report, so here we
     * simply create the event and trigger it.
     */
    public function test_report_viewed() {
        $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_course();
        $course = $this->getDataGenerator()->create_course();
        $context = context_system::instance();
        $event = \report_growth\event\report_viewed::create(['context' => $context]);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        $this->assertInstanceOf('\report_growth\event\report_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEquals('Growth report viewed', $event->get_name());
        $url = new moodle_url('/report/growth/index.php');
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $context = context_course::instance($course->id);
        $this->expectException('coding_exception');
        $str = 'Coding error detected, it must be fixed by a programmer: Context level must be CONTEXT_SYSTEM.';
        $this->expectExceptionMessage($str);
        $event = \report_growth\event\report_viewed::create(['context' => $context]);
    }
}