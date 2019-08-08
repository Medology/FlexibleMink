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

  Scenario: Keys wrapped in single quote should not be converted to snake case.
    Given the following is stored as "Commission":
      | commissionId | commission-1234 |
      | orderId      | order-4321 |
     Then the "'commissionId'" of the "Commission" should contain "commission-1234"
      And the "'orderId'" of the "Commission" should contain "order-4321"

  Scenario: Keys wrapped in single quotes that don't exist should throw an exception
    Given the following is stored as "Commission":
      | commissionId | commission-1234 |
      | orderId      | order-4321 |
     When I assert that the "'commissionId'" of the "Commission" should contain "commission-4321"
     Then the assertion should throw an Exception
      And the assertion should fail with the message "Expected the 'commissionId' of the 'Commission' to contain 'commission-4321', but found 'commission-1234' instead"
     When I assert that the "'orderId'" of the "Commission" should contain "order-1234"
     Then the assertion should throw an Exception
      And the assertion should fail with the message "Expected the 'orderId' of the 'Commission' to contain 'order-1234', but found 'order-4321' instead"

  Scenario: Chained objects/arrays 2nd level retrieves successful
    Given the following is stored as "DataObject":
      | childData  | bar |
     And the following is stored as "ChildDataObject":
      | attribute  | foo |
     And "ChildDataObject" is stored as property "childData" of "DataObject"
    Then the "DataObject's childData's attribute" should be "foo"

  Scenario: Chained objects/arrays 3rd level retrieves successful
    Given the following is stored as "DataObject":
      | childData       | bar |
      And the following is stored as "ChildDataObject":
      | grandchildData  | foo |
      And the following is stored as "GrandChildDataObject":
      | attribute       | foo |
      And "GrandChildDataObject" is stored as property "grandchildData" of "ChildDataObject"
      And "ChildDataObject" is stored as property "childData" of "DataObject"
     Then the "DataObject's childData's grandchildData's attribute" should be "foo"

  Scenario: Chained objects/arrays non-object retrieval should throw an Exception
    Given the following is stored as "DataObject":
      | childData  | bar |
     When I assert that the "DataObject's childData's grandchildData's attribute" should be "foo"
     Then the assertion should throw an Exception
      And the assertion should fail with the message "Expected DataObject's childData's grandchildData's attribute to be 'foo', but it was NULL"

  Scenario: Data assigned to non-object/non-array property/key throw an Exception
    Given the value "dataValue" is stored as "data"
      And the value "bar" is stored as "foo"
     When I assert that "data" is stored as property "someProperty" of "foo"
     Then the assertion should throw an InvalidTypeException
      And the assertion should fail with the message "Expected type for 'foo' is array/object but 'string' given"

  Scenario: Non-complex key retrieves successful
    Given the value "dataValue" is stored as "data's foo"
     When I assert that the "data's foo" should be "dataValue"
     Then the assertion should pass
