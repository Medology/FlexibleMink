Feature: Assert Link is clickable
  In order to ensure that the link is clickable
  As a developer
  I should have link clickable assertion

  Background:
    Given I am on "/link.html"

  Scenario: When Multiple Matches are Found, First Visible Match is Used
    When I follow "Link"

  Scenario Outline: Visible Links are Clickable
    Then I follow "<locator>"

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

  Scenario Outline: Trying to Click Invisible Link Throws Exception
    When I assert that I follow "<locator>"
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
