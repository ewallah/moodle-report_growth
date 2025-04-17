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

namespace report_growth\event;

use advanced_testcase;
use context_course;
use context_system;
use moodle_url;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class report_growth_events_testcase
 *
 * Class for tests related to growth report events.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(report_viewed::class)]
final class event_test extends advanced_testcase {
    /**
     * Setup testcase.
     */
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->resetAfterTest();
        set_config('logguests', 1, 'logstore_standard');
    }

    /**
     * Test the report viewed event.
     *
     * It's not possible to use the moodle API to simulate the viewing of log report, so here we
     * simply create the event and trigger it.
     */
    public function test_report_viewed(): void {
        $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_course();
        $course = $this->getDataGenerator()->create_course();
        $context = context_system::instance();
        $event = report_viewed::create(['context' => $context, 'other' => ['tab' => 1]]);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);

        $this->assertInstanceOf('\report_growth\event\report_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEquals('Growth report viewed', $event->get_name());
        $this->assertEquals("The user with id '2' viewed tab '1' of the global growth report.", $event->get_description());
        $url = new moodle_url('/report/growth/index.php', ['p' => 1]);
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);

        $context = context_course::instance($course->id);
        $event = report_viewed::create(['context' => $context, 'other' => ['tab' => 1]]);
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);
        $this->assertInstanceOf('\report_growth\event\report_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEquals('Growth report viewed', $event->get_name());
        $this->assertEquals(
            "The user with id '2' viewed tab '1' of the growth report for the course with id '" . $course->id . "'.",
            $event->get_description()
        );
        $url = new moodle_url('/report/growth/index.php', ['p' => 1, 'contextid' => $context->id]);
        $this->assertEquals($url, $event->get_url());

        $category = $this->getDataGenerator()->create_category();
        $context = \context_coursecat::instance($category->id);
        $event = report_viewed::create(['context' => $context, 'other' => ['tab' => 2]]);
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $event = reset($events);
        $this->assertInstanceOf('\report_growth\event\report_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEquals('Growth report viewed', $event->get_name());
        $this->assertEquals(
            "The user with id '2' viewed tab '2' of the growth report for the category with id '" . $category->id . "'.",
            $event->get_description()
        );
        $url = new moodle_url('/report/growth/index.php', ['p' => 2, 'contextid' => $context->id]);
        $this->assertEquals($url, $event->get_url());
    }
}
