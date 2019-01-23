Feature: Assert Page Address Method
  In order to reliably check if I am on a page
  As a developer
  I need Behat to wait for the page to finish loading

  Scenario: Page address with query parameters
    Given I am on "/index.html?param1=test1&param2=test2"
     Then I should be on "/index.html" with the following query parameters:
        | param1 | test1 |
        | param2 | test2 |
