Feature: Table Context
  In order to test HTML table structures and content
  As a developer
  I should have extendable table assertions

  Background:
    Given I am on "/table.html"

  Scenario: Developer can Test if a Table Exist
    Then I should see table "population-table"

  Scenario: Developer Can Test For Number of Table Rows and Columns
      Then the table "population-table" should have 4 columns
      Then the table "population-table" should have 3 rows

  Scenario: Developer Can Test for Table Column Titles
     Then the table "population-table" should have the following column titles:
       | Country           |
       | Female Population |
       | Male Population   |
       | Population        |

  Scenario: Developer Can Test for Cell Values in the Table
     Then the table "population-table" should have "Country" at (1,1) in the header
      And the table "population-table" should have "1,341,335,152" at (1,4) in the body
      And the table "population-table" should have "2,876,333,427" at (1,4) in the footer

  Scenario: Developer Can Test for Row Values in the Table
      Then the table "population-table" should have the following values:
        | Country           | India       |
        | Female Population | 592,067,546 |

  Scenario: Throw exception if the named table does not exist
    When I assert that I should see table "pupil-table"
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "Could not find table with name 'pupil-table'."

  Scenario: Throw exception if the named table is not visible
    When I assert that I should see table "voter-turnout-table"
    Then the assertion should throw an RuntimeException
     And the assertion should fail with the message "Found table 'voter-turnout-table', but it is not visible!"

  Scenario: Throw exception if the named table does not have a header
     When I assert that the table "headless-table" should have "Country" at (1,1) in the body
     Then the assertion should throw an ElementNotFoundException
      And the assertion should fail with the message "Tr not found."

  Scenario: Throw exception if the named table does not have the expected values
    When I assert that the table "population-table" should have the following values:
      | Country           | Pluto       |
      | Female Population | 412,064,436 |
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "A row matching the supplied values could not be found."

  Scenario: Developer Can Test for Row Contain HTML Select Input with Selected Option Parsed Correctly
    Then the table "table-with-select" should have the following values:
      | Name    | John |
      | Country | US   |
     And the table "table-with-select" should have the following values:
      | Name    | Zhang |
      | Country | China |

  Scenario: Developer can Test a table with a nested table within it
    Then there should be a table on the page with the following information:
      | Test Column                             |
      | Testing Column with Hidden Nested Table |
