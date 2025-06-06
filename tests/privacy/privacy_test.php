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
 * Privacy tests for growth report.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_growth\privacy;

use core_privacy\tests\provider_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Privacy tests for growth report.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(provider::class)]
final class privacy_test extends provider_testcase {
    /**
     * Test returning metadata.
     */
    public function test_get_metadata(): void {
        $this->resetAfterTest(true);
        $collection = new \core_privacy\local\metadata\collection('report_growth');
        $reason = provider::get_reason($collection);
        $this->assertEquals($reason, 'privacy:metadata');
        $this->assertStringContainsString('plugin does not store any personal data', get_string($reason, 'report_growth'));
    }
}
