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

    public function testNoPackSizes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PackCalc(0, []);
    }

    public function testInvalidPackSize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PackCalc(0, [0]);
    }

    public function testPackSizesAreSorted(): void
    {
        $packCalc = new PackCalc(501, [2000, 500, 1000, 5000, 250]);

        $this->assertEquals(array_keys([250 => 1, 500 => 1]), array_keys($packCalc->calculate()));
    }

    /**
     * Dataset format:
     *
     * [
     *  quantity,
     *  [pack sizes],
     *  [pack size => expected count]
     * ]
     */
    public function packResultProvider(): array
    {
        return [
            'default packs with 1' => [1, [250, 500, 1000, 2000, 5000], [250 => 1]],
            'default packs with 250' => [250, [250, 500, 1000, 2000, 5000], [250 => 1]],
            'default packs with 251' => [251, [250, 500, 1000, 2000, 5000], [500 => 1]],
            'default packs with 501' => [501, [250, 500, 1000, 2000, 5000], [250 => 1, 500 => 1]],
            'default packs with 12001' => [12001, [250, 500, 1000, 2000, 5000], [250 => 1, 2000 => 1, 5000 => 2]],
            'prime packs with 32' => [32, [23, 31, 53, 151, 757], [23 => 2]],
            'prime packs with 500' => [500, [23, 31, 53, 151, 757], [23 => 4, 53 => 2, 151 => 2]],
            'prime packs with 758' => [758, [23, 31, 53, 151, 757], [23 => 4, 31 => 2, 151 => 4]],
            'off by one pack with 500' => [500, [1, 100, 200, 499], [1 => 1, 499 => 1]],
            'edge case pack permutation' => [3100, [200, 300, 1000], [200 => 1, 300 => 3, 1000 => 2]],
            'choose smallest pack count' => [508, [3, 23, 31, 53, 151, 757], [3 => 3, 23 => 2, 151 => 3]],
            'single pack divisible' => [500, [50], [50 => 10]],
            'single pack undivisible' => [500, [33], [33 => 16]],
            'zero quantity' => [0, [1], []],
            'negative quantity' => [-1, [1], []],
            'prime stress test' => [500000, [23, 31, 53, 151, 757], [23 => 4, 31 => 1, 53 => 2, 151 => 1, 757 => 660]],
            'prime stress test with 3 sizes' => [500000, [23, 31, 53], [23 => 2, 31 => 7, 53 => 9429]],
            'prime stress test with 2 sizes' => [500000, [23, 31], [23 => 27, 31 => 16109]],
        ];
    }
}
