Feature:  Assert Fields visibility
  In order to ensure that an input is visible
  As a developer
  I should have field visibility assertions

  Background:
    Given I am on "/assert-field-visibility.html"

  Scenario: Developer Can Test an input is not visible
    Then the field "nonVisible" should not be visible

  Scenario: Developer Can Test an input is visible
    Then the field "visible" should be visible

  Scenario: Assertion fails reliably if visibility is not found
    When  I assert that the field "nonVisible" should be visible
    Then the assertion should throw an ExpectationException
