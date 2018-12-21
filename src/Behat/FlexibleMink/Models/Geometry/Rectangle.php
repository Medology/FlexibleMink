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
     * @param Rectangle $rectangle Rectangle to check against this one
     * @return bool
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
     * Checks if the specified rectangle overlaps with this rectangle.
     *
     * @param Rectangle $rectangle Rectangle to check against this one
     * @return bool
     */
    public function overlaps(self $rectangle)
    {
        return $this->overlapsInY($rectangle) && $this->overlapsInX($rectangle);
    }

    /**
     * Checks if the specified rectangle overlaps with this rectangle on the X-axis.
     *
     * @param  Rectangle $rectangle Rectangle to check against this one
     * @return bool
     */
    private function overlapsInX(self $rectangle)
    {
        /** @var bool $leftOverlap If overlaps on the left */
        $leftOverlap         = $this->right <= $rectangle->right && $this->right >= $rectangle->left;

        /** @var bool $leftOverlap If overlaps on the right */
        $rightOverlap        = $this->left  >= $rectangle->left  && $this->left  <= $rectangle->right;

        /** @var bool $leftOverlap If overlaps on the left and right */
        $leftAndRightOverlap = $this->left  <= $rectangle->left  && $this->right >= $rectangle->right;

        return $leftOverlap || $rightOverlap || $leftAndRightOverlap;
    }

    /**
     * Checks if the specified rectangle overlaps with this rectangle on the Y-axis.
     *
     * @param Rectangle $rectangle Rectangle to check against this one
     * @return bool
     */
    private function overlapsInY(self $rectangle)
    {
        /** @var bool $topOverlap If the top overlaps */
        $topOverlap = $this->top >= $rectangle->top && $this->top <= $rectangle->bottom;

        /** @var bool $isOverlappingAtBottom If the bottom overlaps */
        $isOverlappingAtBottom = $this->bottom <= $rectangle->bottom && $this->bottom >= $rectangle->top;

        /** @var bool $isOverlappingAtTopAndBottom If the top and bottom overlaps */
        $isOverlappingAtTopAndBottom = $this->top <= $rectangle->top && $this->bottom >= $rectangle->bottom;

        return $topOverlap || $isOverlappingAtBottom || $isOverlappingAtTopAndBottom;
    }
}
