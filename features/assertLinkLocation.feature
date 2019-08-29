@clearAlertsWhenFinished
Feature: Alert context
  In order to ensure that JavaScript alerts work as expected
  As a developer
  I need to be able to assert various states of alerts

  Scenario: Link Location
     When I am on "/assert-link-location.html"
     Then the canonical tag should point to "http://localhost.local/testing"
