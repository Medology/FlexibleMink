<?php

namespace Behat\FlexibleMink\Models\Geometry;

/**
 * Class Rectangle
 *
 * @package Behat\FlexibleMink\Models\Geometry
 */
class Rectangle
{

    public $corner1x = 0;
    public $corner1y = 0;
    public $corner3x = 0;
    public $corner3y = 0;

    /**
     * Rectangle constructor.
     *
     *
     * Corner one is the top left corner.
     * Corner two is the bottom left corner.
     *
     *
     * @param $corner1x
     * @param $corner1y
     * @param $corner3x
     * @param $corner3y
     */
    function __construct($corner1x,$corner1y,$corner3x,$corner3y)
    {
        $this->corner1x = $corner1x;
        $this->corner1y = $corner1y;
        $this->corner3x = $corner3x;
        $this->corner3y = $corner3y;
    }

    /**
     *
     * Checks if this is|is not inside another rectangle
     *
     *
     * @param $Rectangle Rectangle
     * @param $not boolean asserts not
     * @return boolean returns the opposite
     */
    function isIn(Rectangle $Rectangle, $not = false) {
        if(
            $not &&
            $this->corner1x >= $Rectangle->corner1x &&
            $this->corner3x <= $Rectangle->corner3x &&
            $this->corner1y >= $Rectangle->corner1y &&
            $this->corner3y <= $Rectangle->corner3y
        ) return false;

        elseif(
            !$not &&
            (
                $this->corner1x < $Rectangle->corner1x ||
                $this->corner3x > $Rectangle->corner3x ||
                $this->corner1y < $Rectangle->corner1y ||
                $this->corner3y > $Rectangle->corner3y
            )
        ) return false;

        return true;
    }
}
