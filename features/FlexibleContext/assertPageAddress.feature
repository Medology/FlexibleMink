Feature: Assert Page Address Method
  In order to reliably check if I am on a page
  As a developer
  I need Behat to wait for the page to finish loading

  Scenario: Page address with query parameters
    Given I am on "/index.html?param1=test1&param2=test2"
     Then I should be on "/index.html" with the following query parameters:
        | param1 | test1 |
        | param2 | test2 |

  Scenario Outline: Page address with invalid query parameters
    Given I am on "/index.html?key=test"
     When I assert that I should be on "/index.html" with the following query parameters:
        | <Param> | <Value> |
     Then the assertion should throw an ExpectationException
      And the assertion should fail with the message '<Error Message>'

    Examples:
        | Param      | Value        | Error Message                                                  |
        | invalidKey | test         | Query did not contain a invalidKey parameter                   |
        | key        | invalidTest  | Expected query parameter key to be invalidTest, but found test |

  Scenario: External URL address
    When I go to "https://www.google.com"
    Then I should be on "https://www.google.com/"
