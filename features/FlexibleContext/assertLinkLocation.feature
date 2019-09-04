Feature: Link Context
  In order to ensure that Canonical Links are present
  As a developer
  I need to be able to assert that the canonical tag contains the proper url

  Scenario: Link Location
    When I am on "/assert-link-location.html"
    Then the canonical tag should point to "http://localhost.local/testing"
