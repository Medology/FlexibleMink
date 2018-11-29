Feature:  Assert Checkbox
  In order to ensure that elements are interactive
  As a developer
  I should have assertions for elements covering others

  Background:
    Given I am on "/covering-elements.html"

  Scenario Outline: Assert Element is not covered
    When I assert that the "<id>" element should not be covered by another
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message 'An element is above an interacting element.'

    Examples:
      | id           |
      | testedDiv    |
      | testedDiv_tl |
      | testedDiv_tr |
      | testedDiv_bl |
      | testedDiv_br |

  Scenario: Assert Calling Method on Covering Method Passes
    When I assert that the "coveringDiv" element should not be covered by another
    Then the assertion should pass

  Scenario: Assert Uncovered Element Doesn't Throw Exception
    When I assert that the "testedDiv_sbs" element should not be covered by another
    Then the assertion should pass
