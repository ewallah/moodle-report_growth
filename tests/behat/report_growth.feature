@iplus @report @report_growth
Feature: Growth checks

  Background:
    Given the following "courses" exist:
      | fullname | shortname | startdate  | enddate    |
      | Course 1 | C1        | 957139200  | 960163200  |
      | Course 2 | C2        | 2524644000 | 2529741600 |
    And the following "users" exist:
      | username | firstname | lastname | country |
      | user1    | Username  | 1        |      BE |
      | user2    | Username  | 2        |      NL |
      | teacher  | Teacher   | 3        |      UG |
      | manager  | Manager   | 4        |      NL |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | user1   | C1     | student        |
      | user1   | C2     | student        |
      | user2   | C1     | student        |
      | teacher | C1     | editingteacher |
      | teacher | C2     | editingteacher |
    And the following "system role assigns" exist:
      | user    | course   | role    |
      | manager | C1       | manager |

  Scenario: Managers can see growth report
    When I am on the "C1" "Course" page logged in as "manager"
    And I navigate to "Reports > Growth" in site administration
    Then I should see "Moodle release"
    And I follow "Users"
    And I should see "Confirmed"
    And I follow "Courses"
    And I should see "Course categories"
    And I follow "Enrolments"
    And I should see "Guest access"
    And I follow "Countries"
    And I should see "Show chart data"
