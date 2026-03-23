<?php

/*
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-License-Identifier: ISC
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\StringFormatter\Internal;

use Respect\StringFormatter\InvalidFormatterException;

use function array_keys;
use function count;
use function implode;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function preg_match;
use function preg_match_all;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function substr;

use const PREG_OFFSET_CAPTURE;

final class CompiledPattern
{
    private const array FILTERS = [
        '#' => '.',
        '0' => '\p{N}',
        'A' => '\p{Lu}',
        'a' => '\p{Ll}',
        'C' => '\p{L}',
        'W' => '\p{L}|\p{N}',
    ];

    private const array TRANSFORM_MAP = ['l' => 'lower', 'u' => 'upper', 'i' => 'invert'];

    /** @var array<string, CompiledPattern> */
    private static array $compiledPatterns = [];

    /** @var array<string, string> */
    private static array $compiledQualifiers = [];

    /** @param array<int, array{filter: string, transform: string|null}> $instructions */
    private function __construct(
        private(set) readonly string $pattern,
        private(set) readonly string $search,
        private(set) readonly string $replacement,
        private(set) readonly array $instructions,
    ) {
    }

    public static function compile(string $pattern): self
    {
        if (isset(self::$compiledPatterns[$pattern])) {
            return self::$compiledPatterns[$pattern];
        }

        if ($pattern === '') {
            throw new InvalidFormatterException('Pattern cannot be empty');
        }

        $search = '';
        $replacement = '';
        $instructions = [];
        $groupIndex = 1;

        $transformState = null;
        $nextTransform = null;

        preg_match_all(sprintf(
            '/(?:\\\\.|[%1$s]|(?:\{[^}]*\}|[*+?])|[^\\\%1$s{}+*?]+|.)/u',
            implode('', array_keys(self::FILTERS)),
        ), $pattern, $tokens, PREG_OFFSET_CAPTURE);

        $tokenList = $tokens[0];
        $count = count($tokenList);

        for ($i = 0; $i < $count; $i++) {
            [$tokenText, $offset] = $tokenList[$i];

            if (str_starts_with($tokenText, '\\')) {
                if ($tokenText === '\\') {
                    throw new InvalidFormatterException('Incomplete escape sequence at end of pattern');
                }

                $char = mb_substr($tokenText, 1);

                if ($char === 'd') {
                    $inner = '.';
                    $search .= sprintf('((?:.*?%s){0,1})', $inner);
                    $replacement .= sprintf('%%%d$', $groupIndex);
                    $instructions[$groupIndex] = ['filter' => sprintf('/%s/u', $inner), 'transform' => 'delete'];
                    $groupIndex++;
                    continue;
                }

                if ($char === 'E') {
                    $transformState = null;
                    continue;
                }

                if (isset(self::TRANSFORM_MAP[$char])) {
                    $nextTransform = self::TRANSFORM_MAP[$char];
                    continue;
                }

                $lowerChar = strtolower($char);
                if (isset(self::TRANSFORM_MAP[$lowerChar]) && $char !== $lowerChar) {
                    $transformState = self::TRANSFORM_MAP[$lowerChar];
                    continue;
                }

                $replacement .= $char;
                continue;
            }

            if (isset(self::FILTERS[$tokenText])) {
                $filterChar = $tokenText;
                $regexQuantifier = '{0,1}';

                if (isset($tokenList[$i + 1]) && preg_match('/^(?:\{[^}]*\}|[*+?])$/u', $tokenList[$i + 1][0])) {
                    $i++;
                    $regexQuantifier = self::compileQualifier($tokenList[$i][0], $tokenList[$i][1]);
                }

                $inner = self::FILTERS[$filterChar];
                $search .= sprintf('((?:.*?%s)%s)', $inner, $regexQuantifier);

                $replacement .= sprintf('%%%d$', $groupIndex);
                $instructions[$groupIndex] = [
                    'filter' => sprintf('/%s/u', $inner),
                    'transform' => $nextTransform ?? $transformState,
                ];

                $groupIndex++;
                $nextTransform = null;
                continue;
            }

            if (preg_match('/^(?:\{[^}]*\}|[*+?])$/u', $tokenText)) {
                throw new InvalidFormatterException(
                    sprintf('Quantifier "%s" must follow a filter pattern at position %d', $tokenText[0], $offset),
                );
            }

            if (str_starts_with($tokenText, '{')) {
                 throw new InvalidFormatterException(
                     sprintf('Invalid or malformed quantifier at position %d', $offset),
                 );
            }

            $replacement .= $tokenText;
        }

        return self::$compiledPatterns[$pattern] = new self(
            $pattern,
            '/^' . $search . '/us',
            $replacement,
            $instructions,
        );
    }

    public static function transform(string $val, string|null $transform): string
    {
        return match ($transform) {
            'delete' => '',
            'lower' => mb_strtolower($val),
            'upper' => mb_strtoupper($val),
            'invert' => mb_strtolower($val) ^ mb_strtoupper($val) ^ $val,
            default => $val,
        };
    }

    private static function compileQualifier(string $token, int $offset): string
    {
        if (isset(self::$compiledQualifiers[$token])) {
            return self::$compiledQualifiers[$token];
        }

        if ($token === '*') {
            return '*';
        }

        if ($token === '+') {
            return '{1,}';
        }

        $content = substr($token, 1, -1);
        if ($content === '' || $content === ',' || !preg_match('/^(\d+(?:,\d*)?|,\d+)$/', $content)) {
            throw new InvalidFormatterException(sprintf('Invalid or malformed quantifier at position %d', $offset));
        }

        preg_match('/^\{(\d*)(?:,(\d*))?\}$/', $token, $m);
        $max = $m[2] ?? $m[1] ?? '';

        return self::$compiledQualifiers[$token] = $max === '' ? '*' : sprintf('{0,%s}', $max);
    }
}
