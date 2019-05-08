<?php
/*
 * @copyright Copyright (c) 2018 Ubiquiti Networks, Inc.
 * @see https://www.ubnt.com/
 */

namespace AppBundle\Util;

class AvatarColors
{
    private const COLORS = [
        '#ef5350',
        '#e53935',
        '#c62828',
        '#e91e63',
        '#d81b60',
        '#ad1457',
        '#9c27b0',
        '#8e24aa',
        '#6a1b9a',
        '#673ab7',
        '#5e35b1',
        '#4527a0',
        '#3f51b5',
        '#3949ab',
        '#283593',
        '#2196f3',
        '#1e88e5',
        '#1565c0',
        '#03a9f4',
        '#039be5',
        '#0277bd',
        '#00bcd4',
        '#00acc1',
        '#00838f',
        '#009688',
        '#00897b',
        '#00695c',
        '#4caf50',
        '#43a047',
        '#2e7d32',
        '#8bc34a',
        '#7cb342',
        '#558b2f',
        '#cddc39',
        '#c0ca33',
        '#9e9d24',
        '#f1df43',
        '#fdd835',
        '#f9a825',
        '#ffc107',
        '#ffb300',
        '#ff8f00',
        '#ff9800',
        '#fb8c00',
        '#ef6c00',
        '#ff5722',
        '#f4511e',
        '#d84315',
        '#607d8b',
        '#546e7a',
    ];

    public static function getRandom(): string
    {
        $color = array_rand(array_flip(self::COLORS), 1);
        assert(is_string($color));

        return $color;
    }

    public static function getRandomSQL(): string
    {
        $colors = self::COLORS;
        shuffle($colors);
        $max = count($colors) - 1;
        $colors = implode(',', $colors);

        return sprintf(
            '(\'[0:%d]={%s}\'::text[])[trunc(random()*%d)]',
            $max,
            $colors,
            $max + 1
        );
    }
}
