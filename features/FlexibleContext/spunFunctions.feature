Feature: Overridden Functions Using Spinners
  In order to properly test pages that may have delayed loads
  As a developer
  I need basic page assertions to retry themselves automatically

  Scenario Outline: Basic functions spin properly
    Given assertions will retry for 5 seconds before failing
      And I am on "index.html"
      And I will be on "basic-content.html" in 3 seconds
     When I assert that <Assertion>
     Then the assertion should pass

    Examples:
      | Assertion                                                 |
      | I should see "some content"                               |
      | I should not see "Don't check for this text!"             |
      | I should see ".a-class content" in the ".a-class" element |

  Scenario Outline: Spun functions eventually fail if the condition remains unmet
    # My favorite part about this is the test can't fail if the above passes; it can only freeze
    Given I am on "index.html"
     When I assert that <Assertion>
     Then the assertion should throw a <Exception>

    Examples:
      | Assertion                                                 | Exception                |
      | I should see "some content"                               | ResponseTextException    |
      | I should not see "Don't check for this text!"             | ResponseTextException    |
      | I should see ".a-class content" in the ".a-class" element | ElementNotFoundException |
