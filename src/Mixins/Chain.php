<?php

/*
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-License-Identifier: ISC
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\StringFormatter\Mixins;

use Respect\StringFormatter\Formatter;
use Respect\StringFormatter\Modifier;

interface Chain extends Formatter
{
    public function area(string $unit): Chain;

    public function creditCard(): Chain;

    public function date(string $format = 'Y-m-d H:i:s'): Chain;

    public function imperialArea(string $unit): Chain;

    public function imperialLength(string $unit): Chain;

    public function imperialMass(string $unit): Chain;

    public function lowercase(): Chain;

    public function mask(string $range, string $replacement = '*'): Chain;

    public function mass(string $unit): Chain;

    public function metric(string $unit): Chain;

    public function number(int $decimals = 0, string $decimalSeparator = '.', string $thousandsSeparator = ','): Chain;

    public function pattern(string $pattern): Chain;

    /** @param array<string, mixed> $parameters */
    public function placeholder(array $parameters, Modifier|null $modifier = null): Chain;

    public function secureCreditCard(string $maskChar = '*'): Chain;

    public function time(string $unit): Chain;

    /** @param 'both'|'left'|'right' $side */
    public function trim(string $side = 'both', string|null $characters = null): Chain;

    public function uppercase(): Chain;
}
