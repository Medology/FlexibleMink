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
