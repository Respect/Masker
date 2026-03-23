<?php

/*
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-License-Identifier: ISC
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\StringFormatter\Test\Integration;

use ArgumentCountError;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Respect\StringFormatter\FormatterBuilder;
use Respect\StringFormatter\InvalidFormatterException;
use Respect\StringFormatter\MaskFormatter;
use Respect\StringFormatter\PatternFormatter;
use Respect\StringFormatter\PlaceholderFormatter;

use function sprintf;

#[CoversClass(FormatterBuilder::class)]
final class FormatterBuilderTest extends TestCase
{
    #[Test]
    public function itShouldFormatWithSingleFormatter(): void
    {
        $input = '1234123412341234';
        $range = '1-3,8-12';
        $maskFormatter = new MaskFormatter($range);
        $expected = $maskFormatter->format($input);

        $builder = new FormatterBuilder();

        $actual = $builder->mask($range)->format($input);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function itShouldFormatWithMultipleFormatters(): void
    {
        $input = '1234123412341234';
        $range = '1-3,8-12';
        $pattern = '#### #### #### ####';
        $maskFormatter = new MaskFormatter($range);
        $patternFormatter = new PatternFormatter($pattern);
        $expected = $patternFormatter->format($maskFormatter->format($input));

        $builder = new FormatterBuilder();

        $actual = $builder->mask($range)->pattern($pattern)->format($input);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function itShouldThrowExceptionWhenFormattingWithoutFormatters(): void
    {
        $builder = new FormatterBuilder();

        $this->expectException(InvalidFormatterException::class);
        $this->expectExceptionMessage('No formatters have been added to the builder');

        $builder->format('test');
    }

    #[Test]
    public function itShouldAllowCallingSameFormatterMultipleTimes(): void
    {
        $input = '1234567890';
        $firstRange = '1-3';
        $secondRange = '5-7';
        $firstMaskFormatter = new MaskFormatter($firstRange);
        $secondMaskFormatter = new MaskFormatter($secondRange);
        $expected = $secondMaskFormatter->format($firstMaskFormatter->format($input));

        $builder = new FormatterBuilder();
        $builder = $builder->mask($firstRange)->mask($secondRange);

        $actual = $builder->format($input);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function itShouldCreateMaskFormatterUsingStaticFactory(): void
    {
        $input = '1234567890';
        $range = '1-3';
        $maskFormatter = new MaskFormatter($range);
        $expected = $maskFormatter->format($input);

        $actual = FormatterBuilder::mask($range)->format($input);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function itShouldCreatePatternFormatterUsingStaticFactory(): void
    {
        $input = '1234567890';
        $pattern = '###-###-####';
        $patternFormatter = new PatternFormatter($pattern);
        $expected = $patternFormatter->format($input);

        $actual = FormatterBuilder::pattern($pattern)->format($input);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function itShouldCreatePlaceholderFormatterUsingStaticFactory(): void
    {
        $input = 'Hello, {{name}}!';
        $parameters = ['name' => 'World'];
        $placeholderFormatter = new PlaceholderFormatter($parameters);
        $expected = $placeholderFormatter->format($input);

        $actual = FormatterBuilder::placeholder($parameters)->format($input);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function itShouldUsePlaceholderFormatter(): void
    {
        $input = 'Hello, {{name}}! Your balance is {{amount}}.';
        $parameters = [
            'name' => 'John',
            'amount' => 100.5,
        ];
        $expected = (new PlaceholderFormatter($parameters))->format($input);

        $builder = new FormatterBuilder();
        $actual = $builder->placeholder($parameters)->format($input);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function itShouldBuildFormatterWithMultipleArguments(): void
    {
        $input = '1234567890';
        $range = '1-3,7-9';
        $replacement = 'X';
        $maskFormatter = new MaskFormatter($range, $replacement);
        $expected = $maskFormatter->format($input);

        $builder = new FormatterBuilder();
        $actual = $builder->mask($range, $replacement)->format($input);

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function itShouldThrowExceptionWhenFormatterIsNotInstantiable(): void
    {
        $builder = new FormatterBuilder();

        $this->expectException(InvalidArgumentException::class);

        $builder->__call('', []);
    }

    #[Test]
    public function itShouldThrowExceptionWhenFormatterArgumentIsMissing(): void
    {
        $builder = new FormatterBuilder();

        $this->expectException(ArgumentCountError::class);
        $this->expectExceptionMessage(sprintf(
            'Too few arguments to function %s::__construct(), 0 passed and exactly 1 expected',
            PatternFormatter::class,
        ));

        /** @phpstan-ignore arguments.count */
        $builder->pattern();
    }

    #[Test]
    public function itShouldThrowExceptionWhenFormatterDoesNotExist(): void
    {
        $builder = new FormatterBuilder();

        $this->expectException(InvalidArgumentException::class);

        /** @phpstan-ignore method.notFound */
        $builder->nonexistent();
    }
}
