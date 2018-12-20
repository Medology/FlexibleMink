<?php

namespace Behat\FlexibleMink\Models\Geometry;

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
     * @param Rectangle $rectangle Rectangle to check if this one is inside of
     */
    public function contains(self $rectangle)
    {
        return
            $this->left >= $rectangle->left &&
            $this->right <= $rectangle->right &&
            $this->top >= $rectangle->top &&
            $this->bottom <= $rectangle->bottom;
    }

    /**
     * Checks if X lines of (this)rectangle overlaps the x lines of $rectangle.
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
     * @param Rectangle $rectangle Rectangle to check if this one is inside of
     */
    private function overlapsInX(self $rectangle)
    {
        return (
                //Right overlap
                $this->left >= $rectangle->left &&
                $this->left <= $rectangle->right
            ) || (
                //Left overlap
                $this->right <= $rectangle->right &&
                $this->right >= $rectangle->left
            ) || (
                //Left and right overlap
                $this->left <= $rectangle->left &&
                $this->right >= $rectangle->right
            );
    }

    /**
     * Checks if Y lines of (this)rectangle overlaps the Y lines of $rectangle.
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
     * @param Rectangle $rectangle Rectangle to check if this one is inside of
     */
    private function overlapsInY(self $rectangle)
    {
        return (
                //Top overlap
                $this->top >= $rectangle->top &&
                $this->top <= $rectangle->bottom
            ) || (
                //Bottom overlap
                $this->bottom <= $rectangle->bottom &&
                $this->bottom >= $rectangle->top
            ) || (
                //Top and bottom overlap
                $this->top <= $rectangle->top &&
                $this->bottom >= $rectangle->bottom
            );
    }

    /**
     * Checks if this is inside another rectangle.
     *
     * @param Rectangle $rectangle Rectangle to check if this one is inside of
     */
    public function overlaps(self $rectangle)
    {
        return $this->overlapsInY($rectangle) && $this->overlapsInX($rectangle);
    }
}
