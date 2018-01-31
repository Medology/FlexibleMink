Feature: Store Context
  In order to reference data for use later on in a test
  As a developer
  I need to be able to store data in contexts

  Scenario Outline: Variables can be stored and accessed
    Given the value <Value> is stored as "Thingtastic Thing"
     When I assert that the "Thingtastic Thing" should be <Value>
     Then the assertion should pass

    Examples:
      | Value |
      | 2     |
      | 0     |
      | 4.7   |
      | true  |
      | false |
      | "car" |

  Scenario: Variables can be aliased
    Given the value "Bob" is stored as "Thing"
     When I refer to the "Thing" as "Person"
     Then the "Person" should be "Bob"
      And the "Thing" should be "Bob"

  Scenario: Assertion fails if the stored thing is not what's expected
    Given the value "me, Dio!" is stored as "this"
     When I assert that the "this" should be "a normal adventure"
     Then the assertion should throw an Exception
      And the assertion should fail with the message "Expected this to be 'a normal adventure', but it was 'me, Dio!'"

  Scenario Outline: Nth stored value can be retrieved
    Given the value "Rowlett" is stored as "Starter"
      And the value "Litten" is stored as "Starter"
      And the value "Popplio" is stored as "Starter"
     Then the "<Key>" should be "<Value>"

    Examples:
      | Key         | Value   |
      | 1st Starter | Rowlett |
      | 2nd Starter | Litten  |
      | 3rd Starter | Popplio |

  Scenario: Latest stored thing is retrieved
    Given the value "Old" is stored as "Idea"
      And the value "New" is stored as "Idea"
     Then the "Idea" should be "New"

  Scenario: Can Assert The Property of Thing Contains Value
    Given the following is stored as "Slogan":
      | body | Eat more cake|
     Then the "body" of the "Slogan" should contain "cake"

  Scenario: Assertion fails if the property of stored thing does not contain expected value
    Given the following is stored as "Slogan":
      | body | Eat more cake|
     When I assert that the "body" of the "Slogan" should contain "candy"
     Then the assertion should throw an Exception
      And the assertion should fail with the message "Expected the 'body' of the 'Slogan' to contain 'candy', but found 'Eat more cake' instead"

  Scenario: Can Assert The Deep Property of Thing Contains Value
    Given the following complex object is stored as "Family":
      """
      {
        "name": "Mary",
        "parent": {
          "name": "Sam",
          "parent": {
            "name": "Fred"
          }
        }
      }
      """
    Then the "name" of the "Family" should contain "Mary"
     And the deep property "(the parent->name of the Family)" should contain "Sam"
     And the deep property "(the parent->parent->name of the Family)" should contain "Fred"
