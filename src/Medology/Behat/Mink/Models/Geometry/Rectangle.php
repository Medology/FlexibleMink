<?php

namespace Medology\Behat\Mink\Models\Geometry;

/**
 * Class Rectangle.
 */
class Rectangle
{
    /** @var int Top left x position */
    public $corner1x = 0;

    /** @var int Top left y position */
    public $corner1y = 0;

    /** @var int Bottom right x position */
    public $corner3x = 0;

    /** @var int Bottom right y position */
    public $corner3y = 0;

    /**
     * Rectangle constructor.
     *
     * Corner one is the top left corner.
     * Corner three is the bottom left corner.
     *
     * @param int $corner1x Top left x position
     * @param int $corner1y Top left y position
     * @param int $corner3x Bottom right x position
     * @param int $corner3y Bottom right y position
     */
    public function __construct($corner1x, $corner1y, $corner3x, $corner3y)
    {
        $this->corner1x = $corner1x;
        $this->corner1y = $corner1y;
        $this->corner3x = $corner3x;
        $this->corner3y = $corner3y;
    }

    /**
     * Checks if this is|is not inside another rectangle.
     *
     * @param  self $Rectangle Rectangle to check if this one is inside of
     * @param  bool $not       Changes to not fully in
     * @return bool returns
     */
    public function isFullyIn(self $Rectangle, $not = false)
    {
        if (
            $not &&
            $this->corner1x >= $Rectangle->corner1x &&
            $this->corner3x <= $Rectangle->corner3x &&
            $this->corner1y >= $Rectangle->corner1y &&
            $this->corner3y <= $Rectangle->corner3y
        ) {
            return false;
        } elseif (
            !$not &&
            (
                $this->corner1x < $Rectangle->corner1x ||
                $this->corner3x > $Rectangle->corner3x ||
                $this->corner1y < $Rectangle->corner1y ||
                $this->corner3y > $Rectangle->corner3y
            )
        ) {
            return false;
        }

        return true;
    }
}
