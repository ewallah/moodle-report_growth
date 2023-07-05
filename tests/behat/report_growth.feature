@iplus @report @report_growth
Feature: Growth checks

  Background:
    Given the following "categories" exist:
      | name  | category 0 | idnumber |
      | cata  | 0          | cata     |
      | catb  | 0          | catb     |
    And the following "courses" exist:
      | fullname | shortname | startdate       | category |
      | Course 1 | C1        | ##-99 months ## | 0        |
      | Course 2 | C2        | ##-99 months ## | 0        |
      | Course 3 | C3        | ##-99 months ## | cata     |
      | Course 4 | C4        | ##-99 months ## | catb     |
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

  Scenario: Managers can see global growth report
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
    And I follow "Payments"
    And I should see "No Payments found"

  Scenario: Managers can see course growth report
    When I am on the "C1" "Course" page logged in as "manager"
    And I navigate to "Reports > Growth" in current page administration
    # There are 4 users enrolled.
    Then I should see "4"
    And I should see "Show chart data"
    And I follow "Badges"
    And I should see "No Badges found"

  Scenario: Teachers can see course growth report
    When I am on the "C1" "Course" page logged in as "teacher"
    And I navigate to "Reports > Growth" in current page administration
    # There are 3 users enrolled.
    Then I should see "3"
    And I should see "Show chart data"
    And I follow "Badges"
    And I should see "No Badges found"

  Scenario: Managers can see category growth report
    When I am on the "C1" "Course" page logged in as "manager"
    And I go to the courses management page
    And I should see the "Course categories and courses" management page
    And I should see "Growth"
    And I navigate to "Growth" in current page administration
    Then I should see "4"
    And I should see "Show chart data"
    And I follow "Course completions"
    And I should see "No Course completions found"
