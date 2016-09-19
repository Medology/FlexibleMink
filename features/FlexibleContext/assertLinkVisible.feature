Feature: Assert Link is visible
  In order to ensure that the link is visible
  As a developer
  I should have visible link assertion

  Background:
    Given I am on "/link.html"

  Scenario: When Multiple Matches are Found, First Visible Match is Used
    Then the "Text Link" link should be visible

  Scenario Outline: Visible Links Are Properly Found
    Then the "<locator>" link is visible

    Examples:
      | locator                   |
      | Visible Text Link w/ Href |
      | Visible Text Link no Href |
      | id-visible-href           |
      | id-visible-nohref         |
      | title-visible-href        |
      | title-visible-nohref      |
      | rel-visible-href          |
      | rel-visible-nohref        |
      | alt-visible-href          |
      | alt-visible-nohref        |

  Scenario Outline: Asserting Hidden Links are Visible Throws Exception
    When I assert that the "<locator>" link is visible
    Then the assertion should throw an ExpectationException
     And the assertion should fail with the message "No visible link found for '<locator>'"

    Examples:
      | locator                     |
      | Invisible Text Link w/ Href |
      | Invisible Text Link no Href |
      | id-invisible-href           |
      | id-invisible-nohref         |
      | title-invisible-href        |
      | title-invisible-nohref      |
      | rel-invisible-href          |
      | rel-invisible-nohref        |
      | alt-invisible-href          |
      | alt-invisible-nohref        |
