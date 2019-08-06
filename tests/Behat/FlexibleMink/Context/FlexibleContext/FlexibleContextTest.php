<?php namespace Tests\Behat\FlexibleMink\Context\FlexibleContext;

use Behat\FlexibleMink\Context\FlexibleContext;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Session;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

/**
 * Instantiates the FlexibleContext so it can be used in Unit Tests functions.
 */
abstract class FlexibleContextTest extends PHPUnit_Framework_TestCase
{
    /** @var Session|PHPUnit_Framework_MockObject_MockObject */
    protected $sessionMock;

    /** @var DocumentElement|PHPUnit_Framework_MockObject_MockObject */
    protected $pageMock;

    /** @var FlexibleContext|PHPUnit_Framework_MockObject_MockObject */
    protected $flexible_context;

    protected $flexible_context_mocked_methods = [
        'getSession',
        'fixStepArgument',
        'scrollWindowToFirstVisibleElement',
        'assertNodeElementVisibleInViewport',
        'scrollToButton',
    ];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->flexible_context = $this->getMock(FlexibleContext::class, $this->flexible_context_mocked_methods);
        $this->sessionMock = $this->getMock(Session::class, ['getPage'], [], '', false);
        $this->pageMock = $this->getMock(DocumentElement::class, ['findAll'], [], '', false);
        $this->flexible_context->method('getSession')->willReturn($this->sessionMock);
        $this->sessionMock->method('getPage')->willReturn($this->pageMock);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
        $this->pageMock = null;
        $this->sessionMock = null;
        $this->flexible_context = null;
    }
}
