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
     * @param int $left left x position
     * @param int $top Top y position
     * @param int $right right x position
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
     * Checks if this is inside another rectangle.
     *
     * @param  Rectangle $rectangle Rectangle to check if this one is inside of
     * @return bool      returns
     */
    public function isFullyIn(Rectangle $rectangle)
    {
        return !(
            $this->left < $rectangle->left ||
            $this->right > $rectangle->right ||
            $this->top < $rectangle->top ||
            $this->bottom > $rectangle->bottom
        );
    }

    /**
     * Checks if this is not inside another rectangle.
     *
     * @param  Rectangle $rectangle Rectangle to check if this one is not inside of
     * @return bool      returns
     */
    public function isNotFullyIn(Rectangle $rectangle)
    {
        return !(
                $this->left >= $rectangle->left &&
                $this->right <= $rectangle->right &&
                $this->top >= $rectangle->top &&
                $this->bottom <= $rectangle->bottom
            );
    }
}
