Feature:  Assert Checkbox
  In order to ensure that checkbox elements behave as expected
  As a developer
  I should have checkbox behavior assertions

  Background:
    Given I am on "/checkboxes.html"

  Scenario: Assert the checkbox checked
    Then the "Checked" checkbox should be checked

  Scenario: Assert the checkbox not checked
    Then the "Not Checked" checkbox should not be checked

  Scenario: Assertion fails reliably if checked checkbox is expected as unchecked
    When I assert that the "Checked" checkbox should not be checked
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message 'Checkbox "Checked" is checked, but it should not be.'

  Scenario: Assertion fails reliably if unchecked checkbox is expected as checked
    When I assert that the "Not Checked" checkbox should be checked
    Then the assertion should throw an ExpectationException
    And the assertion should fail with the message 'Checkbox "Not Checked" is not checked, but it should be.'

  Scenario: Assert the checkbox by injected values
    Given the following is stored as "Page":
      | first_checkbox  | Checked   |
      | second_checkbox | Not Checked   |
     Then the "(the first_checkbox of the Page)" checkbox should be checked
      And the "(the second_checkbox of the Page)" checkbox should not be checked

  Scenario: Assert the checkbox can be checked
    Given the "Not Checked" checkbox should not be checked
     When I check "Not Checked"
     Then the "Not Checked" checkbox should be checked

  Scenario: Assert the checkbox within QA element can be checked
    Then the "QA Not Checked" checkbox should not be checked
    When I check the checkbox "QA Not Checked" in the "Checkboxes in QA"
    Then the "QA Not Checked" checkbox should be checked

  Scenario: Assert the checkbox within QA element can be unchecked
     Then the "QA Checked" checkbox should be checked
     When I uncheck the checkbox "QA Checked" in the "Checkboxes in QA"
     Then the "QA Checked" checkbox should not be checked

  Scenario: Assert the checkbox can be checked by injected values
    Given the following is stored as "Page":
      | first_checkbox  | Checked       |
      | second_checkbox | Not Checked   |
      And the "(the second_checkbox of the Page)" checkbox should not be checked
     When I check "(the second_checkbox of the Page)"
     Then the "(the second_checkbox of the Page)" checkbox should be checked

  Scenario: Assert the checkbox can be unchecked by injected values
    Given the following is stored as "Page":
      | first_checkbox  | Checked       |
      | second_checkbox | Not Checked   |
    Given the "(the first_checkbox of the Page)" checkbox should be checked
     When I uncheck "(the first_checkbox of the Page)"
     Then the "(the first_checkbox of the Page)" checkbox should not be checked
