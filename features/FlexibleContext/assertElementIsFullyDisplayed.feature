Feature: Assert element is fully displayed or not fully displayed
  In order to ensure an element is fully displayed
  As a developer
  I should be able to assert an element is fully displayed

  Background:
    Given I am on "/ScrollableDivs.html"

  Scenario: Assert Test Text is fully displayed
    Then "All Centered 1" should be fully displayed
     And "All Centered 2" should be fully displayed
     And "All Centered 3" should be fully displayed
     And "All Centered 4" should be fully displayed
     And "Partial Left" should not be fully displayed
     And "Partial Right" should not be fully displayed
     And "Partial Top" should not be fully displayed
     And "Partial Bottom" should not be fully displayed
     And "Out to left" should not be fully displayed
     And "Out to right" should not be fully displayed
     And "Out to top" should not be fully displayed
     And "Out to bottom" should not be fully displayed
     And "Visible" should be fully displayed
     And "Invisible" should not be fully displayed
     And "Invisible 2" should not be fully displayed
     And "Visible 3" should not be fully displayed
