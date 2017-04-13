Feature: Assert Page Contains Texts
  In order to ensure that multiple texts appear in a page
  As a developer
  I should be able to assert multiple texts in one step

  Background:
    Given I am on "/order.html"

  Scenario: Step passes if lines are present
    Then I should see the following:
      | Line one   |
      | Line two   |
      | Line three |

  Scenario: Step injects values properly
    Given the following is stored as "list":
      | first_entry  | Line one   |
      | second_entry | Line two   |
      | third_entry  | Line three |
      | fourth_entry | Line four  |
     Then I should see the following:
      | (the first entry of the list)  |
      | (the second entry of the list) |
      | (the third entry of the list)  |
      And I should not see the following:
      | (the fourth entry of the list)  |

  Scenario: Assertion fails reliably if a given line is not present
    When I assert that I should see the following:
     | Line two |
     | Megatron |
    Then the assertion should throw an ResponseTextException
     And the assertion should fail with the message 'The text "Megatron" was not found anywhere in the text of the current page.'
