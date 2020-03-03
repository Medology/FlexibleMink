<?php

namespace Tests\Medology\Behat\Mink\FlexibleContext;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Session;
use Medology\Behat\Mink\FlexibleContext;
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

    protected $flexible_context_mocked_methods = ['getSession'];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->flexible_context = $this->createPartialMock(FlexibleContext::class, $this->flexible_context_mocked_methods);
        $this->sessionMock = $this->createMock(Session::class);
        $this->pageMock = $this->createMock(DocumentElement::class);
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
