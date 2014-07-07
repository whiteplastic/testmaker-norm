<?php

/* This file is part of testMaker.

testMaker is free software; you can redistribute it and/or modify
it under the terms of version 2 of the GNU General Public License as
published by the Free Software Foundation.

testMaker is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */


/**
 * @package Library
 */

/**
 * Determines the color of a gradient at a specific position
 *
 * The color channels are expected as decimal values.
 * It does not really matter how many channels each color has.
 * The returned color has as many channels as the color with the lowest channel count.
 *
 * In praxis, none of the above matters, as you will probably provide the colors as arrays
 * of three decimal RGB values (e.g. <kbd>array(255, 204, 0)</kbd>)
 *
 * @param array Start color of the gradient (e.g. <kbd>array(0, 51, 102)</kbd>)
 * @param array End color of the gradient (e.g. <kbd>array(102, 153, 204)</kbd>)
 * @param float Position along the gradient (0-1, e.g. 0.5)
 * @return array The intermediate color (e.g. <kbd>array(51, 102, 153)</kbd>)
 */
function getGradientColor($startColor, $endColor, $position)
{
	$midColor = array();
	for ($i = 0; $i < min(count($startColor), count($endColor)); $i++) {
		$midColor[] = $startColor[$i] + ($startColor[$i] > $endColor[$i] ? -1 : 1) * $position * (max($startColor[$i], $endColor[$i]) - min($startColor[$i], $endColor[$i]));
	}
	return $midColor;
}

