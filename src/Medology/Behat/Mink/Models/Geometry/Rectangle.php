<?php

namespace Medology\Behat\Mink\Models\Geometry;

/**
 * Class Rectangle.
 */
class Rectangle
{
    /** @var int left x position */
    public $left = 0;

    /** @var int top y position */
    public $top = 0;

    /** @var int right x position */
    public $right = 0;

    /** @var int bottom y position */
    public $bottom = 0;

    /**
     * Rectangle constructor.
     *
     *
     * @param int $left   left x position
     * @param int $top    Top y position
     * @param int $right  right x position
     * @param int $bottom Bottom y position
     */
    public function __construct($left, $top, $right, $bottom)
    {
        $this->left = $left;
        $this->top = $top;
        $this->right = $right;
        $this->bottom = $bottom;
    }

    /**
     * Checks if this is fully inside another rectangle.
     *
     * @param  Rectangle $rectangle Rectangle to check if this one is inside of
     */
    public function isFullyIn(self $rectangle)
    {
        return
            $this->left >= $rectangle->left &&
            $this->right <= $rectangle->right &&
            $this->top >= $rectangle->top &&
            $this->bottom <= $rectangle->bottom;
    }

    /**
     * Checks if X lines of (this)rectangle are in between the x lines of $rectangle
     *
     *  ______________________________
     * |                              |
     * |          $rectangle          |
     * |          Rectangle           |
     * |         ____________         |
     * |        |            |        |
     * |        |   ($this)  |        |
     * |  <-->  |  rectangle |  <-->  |
     * |        |            |        |
     * |        |____________|        |
     * |                              |
     * |                              |
     * |                              |
     * |______________________________|
     *
     *
     * @param  Rectangle $rectangle Rectangle to check if this one is inside of
     */
    private function isInX(self $rectangle)
    {
        return (
                $this->left >= $rectangle->left &&
                $this->left <= $rectangle->right
            ) || (
                $this->right <= $rectangle->right &&
                $this->right >= $rectangle->left
            ) || (
                $this->left <= $rectangle->left &&
                $this->right >= $rectangle->right
            );
    }

    /**
     * Checks if Y lines of (this)rectangle are in between the Y lines of $rectangle
     *
     *  ______________________________
     * |               ↑              |
     * | $rectangle    |              |
     * | Rectangle     ↓              |
     * |         ____________         |
     * |        |            |        |
     * |        |   ($this)  |        |
     * |        |  rectangle |        |
     * |        |            |        |
     * |        |____________|        |
     * |               ↑              |
     * |               |              |
     * |               ↓              |
     * |______________________________|
     *
     * @param  Rectangle $rectangle Rectangle to check if this one is inside of
     */
    private function isInY(self $rectangle)
    {
        return (
                $this->top >= $rectangle->top &&
                $this->top <= $rectangle->bottom
            ) || (
                $this->bottom <= $rectangle->bottom &&
                $this->bottom >= $rectangle->top
            ) || (
                $this->top <= $rectangle->top &&
                $this->bottom >= $rectangle->bottom
            );
    }

    /**
     * Checks if this is inside another rectangle.
     *
     * @param  Rectangle $rectangle Rectangle to check if this one is inside of
     */
    public function isIn(self $rectangle)
    {
        return $this->isInY($rectangle) && $this->isInX($rectangle);
    }
}
