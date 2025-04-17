@iplus @report @report_growth
Feature: Growth checks for empty system

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher  | Teacher   | 1        |
      | manager  | Manager   | 1        |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "system role assigns" exist:
      | user    | course   | role    |
      | manager | C1       | manager |

  Scenario: Managers can see empty global growth report
    When I am on the "C1" "Course" page logged in as "manager"
    And I navigate to "Reports > Growth" in site administration
    Then I should see "Moodle release"
    And I follow "Courses."
    And I follow "Users."
    And I follow "Last access."
    And I follow "Enrolments."
    And I follow "Activities."
    And I follow "Activity completion."
    And I follow "Course completions."
    And I follow "Countries."
    And I follow "Payments."

  Scenario Outline: Managers and teachers can see empty course growth report
    When I am on the "C1" "Course" page logged in as <who>
    And I navigate to "Reports > Growth" in current page administration
    And I follow "Activities."
    And I follow "Activity completion."
    And I follow "Course completions."
    And I follow "Enrolments."
    And I follow "Countries."
    Examples:
      | who     |
      | manager |
      | teacher |

  Scenario: Managers can see empty category growth report
    When I am on the "C1" "Course" page logged in as "manager"
    And I go to the courses management page
    And I should see the "Course categories and courses" management page
    And I should see "Growth"
    And I navigate to "Growth" in current page administration
    And I follow "Activities."
    And I follow "Activity completion."
    And I follow "Course completions."
    And I follow "Enrolments."
    And I follow "Countries."
    And I follow "Course completions."
