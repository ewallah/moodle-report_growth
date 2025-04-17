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
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_growth;

use advanced_testcase;
use moodle_exception;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};

/**
 * Class report_growth_index_testcase
 *
 * Class for tests related to growth report events.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(output\category_renderer::class)]
#[CoversClass(output\global_renderer::class)]
#[CoversClass(output\growth_renderer::class)]
#[CoversClass(output\course_renderer::class)]
final class index_test extends advanced_testcase {
    /**
     * Setup testcase.
     */
    public function setUp(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/badgeslib.php');
        require_once($CFG->dirroot . '/badges/lib.php');
        parent::setUp();

        $CFG->enablecompletion = true;
        set_config('logguests', 1, 'logstore_standard');
        $this->setAdminUser();
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['enablecompletion' => true]);
        $user = $dg->create_user(['country' => 'BE']);
        $course = $dg->create_course(['enablecompletion' => true]);
        $dg->create_module('page', ['course' => $course->id]);
        $user = $dg->create_user(['country' => 'NL']);
        $dg->enrol_user($user->id, $course->id);
        $course = $dg->create_course(['enablecompletion' => true]);
        $user = $dg->create_user(['country' => 'UG']);
        $dg->enrol_user($user->id, $course->id);
        $fordb = new \stdClass();
        $fordb->id = null;
        $fordb->name = 'Test badge';
        $fordb->description = 'Testing badges';
        $fordb->timecreated = time();
        $fordb->timemodified = time();
        $fordb->usercreated = $user->id;
        $fordb->usermodified = $user->id;
        $fordb->issuername = "Test issuer";
        $fordb->issuerurl = "http://issuer-url.domain.co.nz";
        $fordb->issuercontact = "issuer@example.com";
        $fordb->expiredate = null;
        $fordb->expireperiod = null;
        $fordb->type = BADGE_TYPE_SITE;
        $fordb->version = 1;
        $fordb->language = 'en';
        $fordb->courseid = null;
        $fordb->messagesubject = "Test message subject";
        $fordb->message = "Test message body";
        $fordb->attachment = 1;
        $fordb->notification = 0;
        $fordb->imageauthorname = "Image Author 1";
        $fordb->imageauthoremail = "author@example.com";
        $fordb->imageauthorurl = "http://author-url.example.com";
        $fordb->imagecaption = "Test caption image";
        $fordb->status = BADGE_STATUS_INACTIVE;
        $DB->insert_record('badge', $fordb, true);

        // Set the default Issuer (because OBv2 needs them).
        set_config('badges_defaultissuername', $fordb->issuername);
        set_config('badges_defaultissuercontact', $fordb->issuercontact);

        $completionauto = ['completion' => COMPLETION_TRACKING_AUTOMATIC];
        $dg->create_module('forum', ['course' => $course->id], $completionauto);

        // Build badge and criteria.
        $fordb->type = BADGE_TYPE_COURSE;
        $fordb->courseid = $course->id;
        $fordb->status = BADGE_STATUS_ACTIVE;
        $DB->insert_record('badge', $fordb, true);
    }

    /**
     * Test settings.
     */
    public function test_settings(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $admin = admin_get_root(true, true);
        $this->assertNotEmpty($admin);
    }

    /**
     * Test index with wrong permission.
     */
    public function test_index_wrong_permissions(): void {
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
     */
    #[DataProvider('pageprovider')]
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
     * @return array
     */
    public static function pageprovider(): array {
        return [
            [1, 'Number of users (5)'],
            [2, '</td>'], // There is a course category.
            [3, 'Suspended'], // There are no users suspended.
            [4, 'No Last '], // No one there.
            [5, '>2</td>'], // There are 2 enrolments.
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
     */
    #[DataProvider('courseprovider')]
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
     * @return array
     */
    public static function courseprovider(): array {
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
     */
    #[DataProvider('courseprovider')]
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
