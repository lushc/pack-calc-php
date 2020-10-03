<?php

use PHPUnit\Framework\TestCase;

final class PackCalcTest extends TestCase
{
    /**
     * @dataProvider packResultProvider
     */
    public function testCalculate(int $quantity, array $packSizes, array $expected): void
    {
        $packCalc = new PackCalc($quantity, $packSizes);

        $this->assertEquals($expected, $packCalc->calculate());
    }

    public function packResultProvider(): array
    {
        return [
            'default packs with 1' => [1, [250, 500, 1000, 2000, 5000], [250 => 1]],
            'default packs with 250' => [250, [250, 500, 1000, 2000, 5000], [250 => 1]],
            'default packs with 251' => [251, [250, 500, 1000, 2000, 5000], [500 => 1]],
            'default packs with 501' => [501, [250, 500, 1000, 2000, 5000], [250 => 1, 500 => 1]],
            'default packs with 501' => [12001, [250, 500, 1000, 2000, 5000], [250 => 1, 2000 => 1, 5000 => 2]],
            'prime packs with 32' => [32, [23, 31, 53, 151, 757], [23 => 2]],
            'prime packs with 500' => [500, [23, 31, 53, 151, 757], [23 => 4, 53 => 2, 151 => 2]],
            'prime packs with 758' => [758, [23, 31, 53, 151, 757], [23 => 4, 31 => 2, 151 => 4]],
            'off by one pack with 500' => [500, [1, 100, 200, 499], [1 => 1, 499 => 1]],
            'edge case pack permutation' => [3100, [200, 300, 1000], [200 => 1, 300 => 3, 1000 => 2]],
        ];
    }
}