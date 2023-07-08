@iplus @report @report_growth
Feature: Growth checks for empty system

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher  | Teacher   | 1        |
      | manager  | Manager   | 1        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "system role assigns" exist:
      | user    | course   | role    |
      | manager | C1       | manager |

  @javascript
  Scenario: Managers can see empty global growth report
    When I am on the "C1" "Course" page logged in as "manager"
    And I navigate to "Reports > Growth" in site administration
    Then I should see "Moodle release"
    And I follow "Courses"
    And I should see "No Courses found"
    And I follow "Users"
    And I should see "Confirmed"
    And I should see "2"
    And I follow "Last access"
    And I follow "Show chart data"
    And I should see "4"
    And I follow "Enrolments"
    And I should see "Guest access"
    And I should see "6"
    And I follow "Guests"
    And I should see "No Guests found"
    And I follow "Activities"
    And I follow "Show chart data"
    And I should see "2"
    And I follow "Activity completion"
    And I follow "Show chart data"
    And I should see "2"
    And I follow "Course completions"
    And I should see "No Course completions found"
    And I follow "Countries"
    And I follow "Show chart data"
    And I should see "4"
    And I follow "Payments"
    And I should see "No Payments found"

  Scenario: Managers can see empty course growth report
    When I am on the "C1" "Course" page logged in as "manager"
    And I navigate to "Reports > Growth" in current page administration
    # There are 4 users enrolled.
    Then I should see "4"
    And I should see "Show chart data"
    And I follow "Activities"
    And I should see "Show chart data"

  Scenario: Teachers can see empty course growth report
    When I am on the "C1" "Course" page logged in as "teacher"
    And I navigate to "Reports > Growth" in current page administration
    # There are 3 users enrolled.
    Then I should see "3"
    And I should see "Show chart data"
    And I follow "Activities"
    And I should see "Show chart data"

  Scenario: Managers can see empty category growth report
    When I am on the "C1" "Course" page logged in as "manager"
    And I go to the courses management page
    And I should see the "Course categories and courses" management page
    And I should see "Growth"
    And I navigate to "Growth" in current page administration
    Then I should see "4"
    And I should see "Show chart data"
    And I follow "Course completions"
    And I should see "No Course completions found"
