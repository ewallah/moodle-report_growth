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
 * The growth report viewed event.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_growth\event;

/**
 * The growth report viewed event.
 *
 * @package   report_growth
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_viewed extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventreportviewed', 'report_growth');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $tab = $this->other['tab'];
        $str = "The user with id '$this->userid' viewed tab '$tab' of the ";
        switch ($this->contextlevel) {
            case CONTEXT_COURSE:
                return $str . "growth report for the course with id '$this->courseid'.";
            case CONTEXT_COURSECAT:
                return $str . "growth report for the category with id '$this->contextinstanceid'.";
            default:
                return $str . "global growth report.";
        }
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $params = ['p' => $this->other['tab']];
        if ($this->contextlevel == CONTEXT_COURSE || $this->contextlevel == CONTEXT_COURSECAT) {
            $params['contextid'] = $this->contextid;
        }
        return new \moodle_url('/report/growth/index.php', $params);
    }
}
