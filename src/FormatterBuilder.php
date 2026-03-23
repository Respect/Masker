<?php

/*
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-License-Identifier: ISC
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\StringFormatter;

use Respect\Fluent\Attributes\FluentNamespace;
use Respect\Fluent\Builders\Append;
use Respect\Fluent\Factories\NamespaceLookup;
use Respect\Fluent\Resolvers\Suffix;
use Respect\StringFormatter\Mixins\Builder;

use function array_reduce;

/** @mixin Builder */
#[FluentNamespace(new NamespaceLookup(new Suffix('', 'Formatter'), Formatter::class, 'Respect\\StringFormatter'))]
final readonly class FormatterBuilder extends Append implements Formatter
{
    public function __construct(Formatter ...$formatters)
    {
        parent::__construct(static::factoryFromAttribute(), ...$formatters);
    }

    public function format(string $input): string
    {
        /** @var array<int, Formatter> $formatters */
        $formatters = $this->getNodes();

        if ($formatters === []) {
            throw new InvalidFormatterException('No formatters have been added to the builder');
        }

        return array_reduce(
            $formatters,
            static fn(string $carry, Formatter $formatter) => $formatter->format($carry),
            $input,
        );
    }
}
