<?php

/*
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-License-Identifier: ISC
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\StringFormatter\Mixins;

use Respect\StringFormatter\FormatterBuilder;
use Respect\StringFormatter\Modifier;

/** @mixin FormatterBuilder */
interface Builder
{
    public static function area(string $unit): Chain;

    public static function creditCard(): Chain;

    public static function date(string $format = 'Y-m-d H:i:s'): Chain;

    public static function imperialArea(string $unit): Chain;

    public static function imperialLength(string $unit): Chain;

    public static function imperialMass(string $unit): Chain;

    public static function lowercase(): Chain;

    public static function mask(string $range, string $replacement = '*'): Chain;

    public static function mass(string $unit): Chain;

    public static function metric(string $unit): Chain;

    public static function number(int $decimals = 0, string $decimalSeparator = '.', string $thousandsSeparator = ','): Chain;

    public static function pattern(string $pattern): Chain;

    /** @param array<string, mixed> $parameters */
    public static function placeholder(array $parameters, Modifier|null $modifier = null): Chain;

    public static function secureCreditCard(string $maskChar = '*'): Chain;

    public static function time(string $unit): Chain;

    /** @param 'both'|'left'|'right' $side */
    public static function trim(string $side = 'both', string|null $characters = null): Chain;

    public static function uppercase(): Chain;
}
