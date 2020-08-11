<?php

namespace AdrHumphreys\Fixtures\Tests;


use AdrHumphreys\Fixtures\KahnSorter;
use PHPUnit_Framework_TestCase;

class KhanSorterTest extends PHPUnit_Framework_TestCase
{
    public function testSorter()
    {
        $items = [
            [
                'name' => 'bigOlStew',
                'dependencies' => ['thingy', 'pig', 'cheeseDanish', 'chicken']
            ],
            [
                'name' => 'cheeseDanish',
                'dependencies' => ['flour', 'butter', 'egg', 'vanilla', 'creamCheese', 'sugar']],
            [
                'name' => 'butter',
                'dependencies' => ['milk', 'salt']
            ],
            [
                'name' => 'thingy',
                'dependencies' => ['iron', 'apple', 'vanilla']
            ],
            [
                'name' => 'creamCheese',
                'dependencies' => ['milk', 'salt']
            ],
            [
                'name' => 'chicken',
                'dependencies' => ['worm']
            ],
            [
                'name' => 'worm',
                'dependencies' => ['apple']
            ],
            [
                'name' => 'egg',
                'dependencies' => ['chicken']
            ],
            [
                'name' => 'milk',
                'dependencies' => ['cow']
            ],
            [
                'name' => 'cow',
                'dependencies' => ['grass']
            ],
            [
                'name' => 'pig',
                'dependencies' => ['apple', 'worm']
            ],
        ];

        $sorter = new KahnSorter($items);
        $results = $sorter->sort();

        $this->assertEquals([
            'iron',
            'apple',
            'vanilla',
            'thingy',
            'worm',
            'pig',
            'flour',
            'grass',
            'cow',
            'milk',
            'salt',
            'butter',
            'chicken',
            'egg',
            'creamCheese',
            'sugar',
            'cheeseDanish',
            'bigOlStew',
        ], $results);
    }
}
