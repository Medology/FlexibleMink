Feature: Assert Field Contains
  In order to test content inside a field
  As a developer
  I should have a waitfor on the assertion

 Scenario: Developer can test for asserting a field that is updated with a delay
   Given assertions will retry for 5 seconds before failing
    When I am on "/assert-field-contains.html"
     And I press "Fill in delayed field with a 2 second delay"
    Then the "Delayed field" field should contain "delayed value"
