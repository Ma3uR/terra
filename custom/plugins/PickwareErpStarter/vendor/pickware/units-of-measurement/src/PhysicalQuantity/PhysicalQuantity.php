<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\UnitsOfMeasurement\PhysicalQuantity;

use InvalidArgumentException;

trait PhysicalQuantity
{
    /**
     * @var float
     */
    private $valueInBasicSiUnit;

    /**
     * @param float $value
     * @param string $unit
     */
    public function __construct(float $value, string $unit)
    {
        $this->valueInBasicSiUnit = $value * self::getUnitFactor($unit);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'value' => $this->valueInBasicSiUnit,
            'unit' => self::BASIC_SI_UNIT,
        ];
    }

    /**
     * @param array $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        return new self(floatval($array['value'] ?? 0.0), $array['unit']);
    }

    /**
     * @param string $unit
     * @return float
     */
    public function convertTo(string $unit): float
    {
        return $this->valueInBasicSiUnit / self::getUnitFactor($unit);
    }

    public function prettyPrint(int $significantDigits = 3): string
    {
        $minAbsLog = null;
        $bestUnit = null;
        $bestFactor = null;

        // Explanation of the algorithm:
        // The common logarithm (logarithm with base 10) returns the "length" of a number.
        // Examples:
        // log(1000.00) = 3
        // log(0.001) = -3
        // log(1.0) = 0
        // We want to find a representation of the physical quantity where the common logarithm is as close as possible
        // to zero, because then the number is not displayed with too many digits.
        // Example with 0.1 m
        // in mm: 100 mm => log(100) = 2;
        // in cm: 10 cm => log(10) = 1;
        // in dm; 1 dm => log (1) = 0;
        // in m: 0.1 m => log(0.1) = -1;
        // Therefore: Choose 1 dm

        foreach (self::UNITS as $unit => $factor) {
            $absLog = abs(log10($this->valueInBasicSiUnit / $factor) - $significantDigits + 1);
            if ($minAbsLog === null || $absLog < $minAbsLog) {
                $bestUnit = $unit;
                $bestFactor = $factor;
                $minAbsLog = $absLog;
            }
        }

        $displayValue = $this->valueInBasicSiUnit / $bestFactor;
        $decimalDigits = (int) floor($significantDigits - log10($displayValue));

        if ($decimalDigits < 0) {
            $decimalDigits = 0;
        }

        return sprintf('%01.' . $decimalDigits. 'f %s', $displayValue, $bestUnit);
    }

    /**
     * @param self ...$summands
     * @return self
     */
    public static function sum(self ...$summands): self
    {
        $totalValueInBasicSiUnit = array_sum(array_map(function (self $summand) {
            return $summand->valueInBasicSiUnit;
        }, $summands));

        return new self($totalValueInBasicSiUnit, self::BASIC_SI_UNIT);
    }

    /**
     * @param self ...$summands
     * @return self
     */
    public function add(self ...$summands): self
    {
        return self::sum($this, ...$summands);
    }

    /**
     * @deprecated 2.0.0. Use self::subtract instead
     * @param self ...$subtrahends
     * @return self
     */
    public function substract(self ...$subtrahends): self
    {
        return $this->subtract(...$subtrahends);
    }

    /**
     * @param self ...$subtrahends
     * @return self
     */
    public function subtract(self ...$subtrahends): self
    {
        $totalOfSubtrahends = array_sum(array_map(function (self $subtrahend) {
            return $subtrahend->valueInBasicSiUnit;
        }, $subtrahends));

        return new self($this->valueInBasicSiUnit - $totalOfSubtrahends, self::BASIC_SI_UNIT);
    }

    /**
     * @param PhysicalQuantity $otherQuantity
     * @return float
     */
    public function divideBy(self $otherQuantity): float
    {
        return $this->valueInBasicSiUnit / $otherQuantity->valueInBasicSiUnit;
    }

    /**
     * @param float $factor
     * @return self
     */
    public function multiplyWithScalar(float $factor): self
    {
        return new self($this->valueInBasicSiUnit * $factor, self::BASIC_SI_UNIT);
    }

    /**
     * @return self
     */
    public function absolute(): self
    {
        return new self(abs($this->valueInBasicSiUnit), self::BASIC_SI_UNIT);
    }

    /**
     * @param self $otherQuantity
     * @return bool
     */
    public function isGreaterThan(self $otherQuantity): bool
    {
        return $this->valueInBasicSiUnit > $otherQuantity->valueInBasicSiUnit;
    }

    /**
     * @param self $otherQuantity
     * @return bool
     */
    public function isLighterThan(self $otherQuantity): bool
    {
        return $this->valueInBasicSiUnit < $otherQuantity->valueInBasicSiUnit;
    }

    /**
     * @param self $otherQuantity
     * @param self $epsilon
     * @return bool
     */
    public function isEqualTo(self $otherQuantity, self $epsilon): bool
    {
        return abs($this->valueInBasicSiUnit - $otherQuantity->valueInBasicSiUnit) - abs($epsilon->valueInBasicSiUnit) <= PHP_FLOAT_EPSILON;
    }

    /**
     * @param self $otherQuantity
     * @return int
     */
    public function compareTo(self $otherQuantity): int
    {
        return $this->valueInBasicSiUnit <=> $otherQuantity->valueInBasicSiUnit;
    }

    /**
     * @param string $unit
     * @return float
     */
    private static function getUnitFactor(string $unit): float
    {
        if (!array_key_exists($unit, self::UNITS)) {
            throw new InvalidArgumentException(sprintf(
                '%s is not a valid unit for %s.',
                $unit,
                self::class
            ));
        }

        return self::UNITS[$unit];
    }
}
