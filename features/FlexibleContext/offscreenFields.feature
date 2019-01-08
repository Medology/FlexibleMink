Feature: Interacting with off-screen fields
  In order to avoid having to manually scroll to every element
  As a scenario writer
  I should be able to trust that the browser will scroll to fields when I say to interact with them

  Background:
    Given I am on "offscreen-fields.html"

    Scenario: Browser scrolls to fields before filling them in
      When I assert that I fill in "Visible off-screen field" with "carrot"
      Then the assertion should pass

    Scenario: Browser ignores invisible fields
      When I assert that I fill in "Invisible off-screen field" with "carrot"
      Then the assertion should throw an ExpectationException
       And the assertion should fail with the message "No visible input found for 'Invisible off-screen field'"

    Scenario: Browser scrolls to buttons before interacting with them
      When I assert that I press "Visible off-screen button"
      Then the assertion should pass

    Scenario: Browser ignores invisible buttons
      When I assert that I press "Invisible off-screen button"
      Then the assertion should throw an ExpectationException
       And the assertion should fail with the message "No visible button found for 'Invisible off-screen button'"

    Scenario: Browser scrolls to checkboxes before checking them
      When I assert that I check "Visible off-screen checkbox"
      Then the assertion should pass

    Scenario: Browser ignores invisible checkboxes
      When I assert that I check "Invisible off-screen checkbox"
      Then the assertion should throw an ExpectationException
       And the assertion should fail with the message "No visible option found for 'Invisible off-screen checkbox'"

    Scenario: Browser scrolls to radio buttons before clicking them
      When I assert that I check radio button "Visible off-screen radio"
      Then the assertion should pass

    Scenario: Browser ignores invisible radio buttons
      When I assert that I check radio button "Invisible off-screen radio"
      Then the assertion should throw an ExpectationException
       And the assertion should fail with the message "No Visible Radio Button was found on the page"

    Scenario: Browser scrolls to links before following them
      When I assert that I follow "Visible off-screen link"
      Then the assertion should pass

    Scenario: Browser ignores invisible links
      When I assert that I follow "Invisible off-screen link"
      Then the assertion should throw an ExpectationException
       And the assertion should fail with the message "No visible link found for 'Invisible off-screen link'"
