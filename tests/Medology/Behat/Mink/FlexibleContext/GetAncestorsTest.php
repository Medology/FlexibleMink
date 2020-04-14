<?php

namespace Tests\Medology\Behat\Mink\FlexibleContext;

use Behat\Mink\Element\NodeElement;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class GetAncestorsTest extends FlexibleContextTest
{
    private $body;
    private $div;
    private $list;
    private $listItem;
    private $button;

    public function setUp()
    {
        parent::setUp();

        // Given I have an HTML body
        $this->body = $this->mockNode('body');

        // And the body has a div
        $this->div = $this->mockNode('div', $this->body);

        // And the div has an unordered list
        $this->list = $this->mockNode('ul', $this->div);

        // And the list has a list item
        $this->listItem = $this->mockNode('li', $this->list);

        // And the list item has a button
        $this->button = $this->mockNode('button', $this->listItem);
    }

    public function testAllAncestorsAreReturned()
    {
        // When I pass the button to allAncestors()
        $ancestors = $this->invokeMethod($this->flexible_context, 'getAncestors', [$this->button]);

        // Then all ancestors should be returned in the correct order
        $this->assertCount(4, $ancestors, 'Number of returned ancestors should be 4');
        $this->assertSame($this->listItem, $ancestors[0], "Button's first ancestor should be list item");
        $this->assertSame($this->list, $ancestors[1], "Button's second ancestor should be list");
        $this->assertSame($this->div, $ancestors[2], "Button's third ancestor should be div");
        $this->assertSame($this->body, $ancestors[3], "Button's fourth ancestor should be body");
    }

    public function testStopAtIsNotReturned()
    {
        // When I pass the button to allAncestors() and request that it stop at "body"
        $ancestors = $this->invokeMethod($this->flexible_context, 'getAncestors', [$this->button, 'body']);

        // Then all ancestors except body should be returned in the correct order
        $this->assertCount(3, $ancestors, 'Number of returned ancestors should be 3');
        $this->assertSame($this->listItem, $ancestors[0], "Button's first ancestor should be list item");
        $this->assertSame($this->list, $ancestors[1], "Button's second ancestor should be list");
        $this->assertSame($this->div, $ancestors[2], "Button's third ancestor should be div");
    }

    /**
     * Creates a mocked NodeElement with an optional parent.
     *
     * @param  string                 $tagName the type of node element to mock
     * @param  NodeElement|null       $parent  the optional parent for the node element
     * @return MockObject|NodeElement
     */
    protected function mockNode($tagName, NodeElement $parent = null)
    {
        $node = $this->createMock(NodeElement::class);
        $node->method('getTagName')->willReturn($tagName);
        $node->method('getParent')->willReturn($parent);

        return $node;
    }

    public function tearDown()
    {
        $this->body = null;
        $this->div = null;
        $this->list = null;
        $this->listItem = null;
        $this->button = null;

        parent::tearDown();
    }
}
