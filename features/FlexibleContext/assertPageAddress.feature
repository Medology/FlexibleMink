Feature: Assert Page Address Method
  In order to reliably check if I am on a page
  As a developer
  I need Behat to wait for the page to finish loading

  Background:
    Given I am on "/page-load-delay.html"

  Scenario: Behat Waits for Page to Finish Loading
    When I follow "Small Delay"
    Then I should be on "/index.html"

  Scenario: When Page Takes too Long, Behat Fails the Assertion
    When I follow "Big Delay"
     And I assert that I should be on "index.html"
    Then the assertion should throw an ExpectationException
