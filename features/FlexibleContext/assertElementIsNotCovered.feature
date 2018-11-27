Feature:  Assert Checkbox
  In order to ensure that elements are interactive
  As a developer
  I should have assertions for elements covering others

  Background:
    Given I am on "/covering-elements.html"

  Scenario: Assert element is not covered
    When I assert that the "testedDiv" element should not be covered by another
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message 'An element is above an interacting element.'

