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
     * @param Rectangle $rectangle Rectangle to check against this one
     *
     * @return bool
     */
    public function isContainedIn(self $rectangle)
    {
        return
            $this->left >= $rectangle->left &&
            $this->right <= $rectangle->right &&
            $this->top >= $rectangle->top &&
            $this->bottom <= $rectangle->bottom;
    }

    /**
     * Checks if the specified rectangle overlaps with this rectangle.
     *
     * @param Rectangle $rectangle Rectangle to check against this one
     *
     * @return bool
     */
    public function overlaps(self $rectangle)
    {
        return $this->overlapsInY($rectangle) && $this->overlapsInX($rectangle);
    }

    /**
     * Checks if the specified rectangle overlaps with this rectangle on the X-axis.
     *
     * @param Rectangle $rectangle Rectangle to check against this one
     *
     * @return bool
     */
    private function overlapsInX(self $rectangle)
    {
        return
            //If overlaps on the left
            $this->right <= $rectangle->right && $this->right >= $rectangle->left ||
            //If overlaps on the right
            $this->left >= $rectangle->left && $this->left <= $rectangle->right ||
            //If overlaps on the left and right
            $this->left <= $rectangle->left && $this->right >= $rectangle->right;
    }

    /**
     * Checks if the specified rectangle overlaps with this rectangle on the Y-axis.
     *
     * @param Rectangle $rectangle Rectangle to check against this one
     *
     * @return bool
     */
    private function overlapsInY(self $rectangle)
    {
        return
            //If the top overlaps
            $this->top >= $rectangle->top && $this->top <= $rectangle->bottom ||
            //If the bottom overlaps
            $this->bottom <= $rectangle->bottom && $this->bottom >= $rectangle->top ||
            //If the top and bottom overlaps
            $this->top <= $rectangle->top && $this->bottom >= $rectangle->bottom;
    }
}
