Feature: Interacting with off-screen fields

  Background:
    Given I am on "offscreen-fields.html"

    Scenario:
      When I fill in "Visible off-screen field" with "carrot"
      Then the "Visible off-screen field" field should contain "carrot"

    Scenario:
      When I assert that I fill in "Invisible off-screen field" with "carrot"
      Then the assertion should throw an ExpectationException
       And the assertion should fail with the message "No visible input found for 'Invisible off-screen field'"
