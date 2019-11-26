Feature:  Assert Fields exists
  In order to ensure that select/checkbox elements exists
  As a developer
  I should have field exists assertions

  Background:
    Given I am on "/assert-field-exists.html"

  Scenario: Developer Can Test for option in the select input
    Then the "Texas" option exists in the State select

  Scenario: Assertion fails reliably if option in select is not found
    When I assert that the "Utopia" option exists in the State select
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "The option 'Utopia' does not exist in the select"

  Scenario: Assertion fails reliably if element is not found
    When I assert that I should see the following fields:
       | Utopia |
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "No visible input found for 'Utopia'"

  Scenario: Assertion fails reliably if checkbox element is not visible
    When I assert that I should see the following fields:
       | Kale Salad |
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "No visible input found for 'Kale Salad'"

  Scenario: Assertion fails reliably if checkbox element has no label
    When I assert that I should see the following fields:
       | Sushi |
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "No visible input found for 'Sushi'"

  Scenario: Field assertions should retry before failing
     Then I should see the following fields:
        | Ice Cream |

  Scenario: Developer Can Test for option with varying input/label setup
    Then I should see the following fields:
       | Pizza     |
       | Hamburger |
       | Hot Dog   |
    When I check "Pizza"
     And I check "Hamburger"
     And I check "Hot Dog"
     And I press "Submit Favorites"
    Then I should see "Selected: Pizza, Hamburger, Hot Dog"

  Scenario: Fields With Duplicate Label Names Should Modify the First Visible
    Then I should see the following fields:
       | Text Input: |
    When I fill in "Text Input:" with "test"
     And I press "Submit Favorites"
    Then I should see "invisibleInput: , visibleInput: test"
