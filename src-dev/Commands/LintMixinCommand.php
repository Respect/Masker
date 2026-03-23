<?php

/*
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-License-Identifier: ISC
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\StringFormatter\DevTools\Commands;

use Respect\FluentGen\Config;
use Respect\FluentGen\Fluent\InterfaceConfig;
use Respect\FluentGen\Fluent\MethodBuilder;
use Respect\FluentGen\Fluent\MixinGenerator;
use Respect\FluentGen\NamespaceScanner;
use Respect\StringFormatter\Formatter;
use Respect\StringFormatter\FormatterBuilder;
use Respect\StringFormatter\Mixins\Chain;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_file;
use function is_readable;
use function sprintf;

#[AsCommand(
    name: 'lint:mixin',
    description: 'Apply linters to the generated mixin interfaces',
)]
final class LintMixinCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'fix',
            null,
            null,
            'Automatically fix files with issues.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $srcDir = dirname(__DIR__, 2) . '/src';

        $config = new Config(
            sourceDir: $srcDir,
            sourceNamespace: 'Respect\\StringFormatter',
            outputDir: $srcDir . '/Mixins',
            outputNamespace: 'Respect\\StringFormatter\\Mixins',
        );

        $scanner = new NamespaceScanner(
            nodeType: Formatter::class,
            excludedClassNames: ['FormatterBuilder'],
        );

        $generator = new MixinGenerator(
            config: $config,
            scanner: $scanner,
            methodBuilder: new MethodBuilder(classSuffix: 'Formatter'),
            interfaces: [
                new InterfaceConfig(
                    suffix: 'Builder',
                    returnType: Chain::class,
                    static: true,
                    rootComment: '@mixin FormatterBuilder',
                    rootUses: [FormatterBuilder::class],
                ),
                new InterfaceConfig(
                    suffix: 'Chain',
                    returnType: Chain::class,
                    rootExtends: [Formatter::class],
                ),
            ],
        );

        $files = $generator->generate();

        $updatableFiles = [];
        foreach ($files as $filename => $content) {
            $existingContent = '';
            if (is_file($filename) && is_readable($filename)) {
                $existingContent = file_get_contents($filename) ?: '';
            }

            if ($content === $existingContent) {
                continue;
            }

            $updatableFiles[$filename] = $content;
            $output->writeln(sprintf('--- a/%s', $filename));
            $output->writeln(sprintf('+++ b/%s', $filename));
        }

        if ($updatableFiles === []) {
            $output->writeln('<info>No changes needed.</info>');
        } else {
            $output->writeln(sprintf('<comment>Changes needed in %d files.</comment>', count($updatableFiles)));
        }

        if ($updatableFiles !== [] && !$input->getOption('fix')) {
            return Command::FAILURE;
        }

        foreach ($updatableFiles as $filename => $content) {
            file_put_contents($filename, $content);
        }

        return Command::SUCCESS;
    }
}
