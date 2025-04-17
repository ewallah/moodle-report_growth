@iplus @report @report_growth
Feature: View growth report on system, category and course level
  A growth report report can be seen on different levels.

  Background:
    Given the following "categories" exist:
      | name  | category 0 | idnumber |
      | cata  | 0          | cata     |
      | catb  | 0          | catb     |
    And the following "courses" exist:
      | fullname | shortname | startdate       | category | enablecompletion |
      | Course 1 | C1        | ##-99 months ## | cata     | 1                |
      | Course 2 | C2        | ##-99 months ## | 0        | 1                |
      | Course 3 | C3        | ##-99 months ## | catb     | 0                |
      | Course 4 | C4        | ##-99 months ## | cata     | 1                |
    And the following "activities" exist:
      | activity | name   | intro | course | idnumber | option             | completion | completionsubmit |
      | choice   | choice | intro | C1     | choice1  | Option 1, Option 2 | 2          | 1                |
      | choice   | choice | intro | C4     | choice4  | Option 1, Option 2 | 2          | 1                |
    And the following "users" exist:
      | username | firstname | lastname | country |
      | user1    | Username  | 1        |      BE |
      | user2    | Username  | 2        |      NL |
      | user3    | Username  | 3        |      UG |
      | user4    | Username  | 4        |      BE |
      | user5    | Username  | 5        |      NL |
      | user6    | Username  | 6        |      UG |
      | user7    | Username  | 7        |      NL |
      | user8    | Username  | 8        |      NL |
      | user9    | Username  | 9        |      UG |
      | teacher  | Teacher   | 3        |      UG |
      | manager  | Manager   | 4        |      BE |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | user1   | C1     | student        |
      | user1   | C2     | student        |
      | user2   | C1     | student        |
      | user2   | C4     | student        |
      | user3   | C1     | student        |
      | user4   | C1     | student        |
      | user5   | C1     | student        |
      | user6   | C1     | student        |
      | user7   | C1     | student        |
      | user8   | C1     | student        |
      | teacher | C1     | editingteacher |
      | teacher | C2     | editingteacher |
    And the following "system role assigns" exist:
      | user    | course   | role    |
      | manager | C1       | manager |
    And I am on the "C1" "Course" page logged in as "user1"
    And I choose "Option 1" from "choice1" choice activity
    And I log out
    And I am on the "C4" "Course" page logged in as "user2"
    And I choose "Option 1" from "choice1" choice activity
    And I log out
    And I am on the "C1" "Course" page logged in as "user3"
    And I choose "Option 1" from "choice1" choice activity
    And I log out
    And I am on the "C1" "Course" page logged in as "user4"
    And I choose "Option 1" from "choice1" choice activity
    And I log out
    And I am on the "C1" "Course" page logged in as "user5"
    And I choose "Option 1" from "choice1" choice activity
    And I log out

  @javascript
  Scenario: Managers can see global growth report
    When I am on the "C1" "Course" page logged in as "manager"
    And I navigate to "Reports > Growth" in site administration
    Then I should see "Moodle release"
    And I follow "Courses."
    And I should see "Course categories"
    And I should see "3"
    And "Show chart data" "link" should exist
    And I follow "Users."
    And I should see "Confirmed"
    And I should see "12"
    And "Show chart data" "link" should exist
    And I follow "Last access."
    And I follow "Show chart data"
    And I should see "4"
    And I follow "Enrolments."
    And I should see "Guest access"
    And I should see "12"
    And "Show chart data" "link" should exist
    And I follow "Activities."
    And I follow "Show chart data"
    And I should see "2"
    And I follow "Activity completion."
    And I follow "Show chart data"
    And I should see "2"
    And I follow "Course completions."
    And I should see "No Course completions found"
    And "Show chart data" "link" should not exist
    And I follow "Countries."
    And I follow "Show chart data"
    And I should see "4"
    And I follow "Payments."
    And I should see "No Payments found"

  @javascript
  Scenario Outline: Handle the course growth report
    When I am on the "C1" "Course" page logged in as <who>
    And I navigate to "Reports > Growth" in current page administration
    And I follow "Show chart data"
    Then I should see "##today##%Y##"
    And I should see "Show chart data"
    And I follow "Last access."
    And I follow "Show chart data"
    And I should see "##today##%Y##"
    And I follow "Activities."
    And I follow "Show chart data"
    And I should see "1"
    And I follow "Activity completion."
    And I follow "Show chart data"
    And I should see "##today##%Y##"
    And I follow "Course completions."
    And I should see "No Course completions found"
    And I follow "Countries."
    And I follow "Show chart data"
    And I should see "4"
    Examples:
      | who     |
      | manager |
      | teacher |

  @javascript
  Scenario: Managers can see category growth report
    When I am on the "C1" "Course" page logged in as "manager"
    And I go to the courses management page
    And I follow "cata"
    And I navigate to "Growth" in current page administration
    And I follow "Show chart data"
    Then I should see "##today##%Y##"
    And I follow "Last access."
    And I follow "Show chart data"
    And I should see "##today##%Y##"
    And I follow "Activities."
    And I follow "Show chart data"
    And I should see "2"
    And I follow "Activity completion."
    And I follow "Show chart data"
    And I should see "##today##%Y##"
    And I follow "Course completions."
    And I should see "No Course completions found"
    And I follow "Countries."
    And I follow "Show chart data"
    And I should see "4"
