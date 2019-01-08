Feature: Interacting with off-screen fields

  Background:
    Given I am on "offscreen-fields.html"

    Scenario:
      When I assert that I fill in "Visible off-screen field" with "carrot"
      Then the assertion should pass

    Scenario:
      When I assert that I fill in "Invisible off-screen field" with "carrot"
      Then the assertion should throw an ExpectationException
       And the assertion should fail with the message "No visible input found for 'Invisible off-screen field'"

    Scenario:
      When I assert that I press "Visible off-screen button"
      Then the assertion should pass

    Scenario:
      When I assert that I press "Invisible off-screen button"
      Then the assertion should throw an ExpectationException
       And the assertion should fail with the message "No visible button found for 'Invisible off-screen button'"
