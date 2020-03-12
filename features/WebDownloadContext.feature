Feature: Web Download Context
  In order to test if file and image assets are retrievable
  As a developer
  I should have assertions to check if assets downloaded

  Background:
    Given I am on "/image-load-test.html"

  Scenario: Developer Can Test if an Image Link is Valid (Loads)
    Then I should see "/img/medology.png" image in "valid-image"

  Scenario: Developer Can Test if an Image Link is Invalid (Broken)
    Then I should not see an image in "invalid-image"

  Scenario: Developer Can Test if an Image loaded dynamically
    When I reload the page
    Then I should see "/img/medology.png" image in "dynamic-image"

  Scenario: Developer Can Test if an Image without src loaded dynamically
    When I reload the page
    Then I should see "/img/medology.png" image in "dynamic2-image"
