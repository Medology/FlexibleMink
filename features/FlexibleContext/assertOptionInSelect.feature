Feature:  Assert Option in Select
  In order to ensure that select elements behave as expected
  As a developer
  I should have select behavior assertions

  Background:
    Given I am on "/select-option.html"

  Scenario: Developer can assert select has matching options
    Then the "Country" select should only have the following options:
      | US     |
      | China  |
      | Canada |

  Scenario: Assertion fails reliably if select has the same options but in the wrong order
    When I assert that the "Country" select should only have the following options:
      | China  |
      | Canada |
      | US     |
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "Options in select match expected but not in expected order"

  Scenario: Assertion fails reliably if select has less option than expected
    When I assert that the "Country" select should only have the following options:
      | US     |
      | China  |
      | Canada |
      | Mexico |
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "Select has less option then expected"

  Scenario: Assertion fails reliably if select has more option than expected
    When I assert that the "Country" select should only have the following options:
      | US     |
      | China  |
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "Select has more option then expected"

  Scenario: Assertion fails reliably if select has totally different option than expected
    When I assert that the "Country" select should only have the following options:
      | US     |
      | China  |
      | French |
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "Expecting 3 matching option(s), found 2"

  Scenario: Assertion fails reliably if select has totally different option than expected
    When I assert that the "Country" select should only have the following options:
      | Japan  |
      | German |
      | French |
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "Expecting 3 matching option(s), found 0"

  Scenario: Assertion fails reliably if no option in the select
    When I assert that the "State" select should only have the following options:
      | Texas |
      | Ohio  |
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "No option found in the select"

  Scenario: Assertion fails reliably if option given in the wrong format
    When I assert that the "State" select should only have the following options:
     | Texas | Ohio |
    Then the assertion should throw an InvalidArgumentException
     And the assertion should fail with the message "Arguments must be a single-column list of items"

  Scenario: Assertion fails when expected select dropdown is not present
    When I assert that the "Not Present" drop down should have the "US" selected
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "No visible input found for 'Not Present'"

  Scenario: Assertion fails when expected option is not present in the dropdown
    When I assert that the "Country" drop down should have the "Not Present" selected
    Then the assertion should throw an ElementNotFoundException
     And the assertion should fail with the message 'Select option field with id|name|label|value "Not Present" not found.'

  Scenario: Assertion fails when expected option is not present in the dropdown
    When I assert that the "Country" drop down should have the "Canada" selected
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message 'Select option field with value|text "Canada" is not selected in the select "Country"'

  Scenario: Assertion passes when expected option is selected in the dropdown
    Then the "Country" drop down should have the "US" selected