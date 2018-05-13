<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests;

use Generator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SignpostMarv\DaftFramework\Framework as BaseFramework;
use SignpostMarv\DaftFramework\HttpHandler as BaseHttpHandler;
use SignpostMarv\DaftFramework\Logging\CatchingHttpHandler;
use SignpostMarv\DaftFramework\Logging\Framework;
use SignpostMarv\DaftFramework\Logging\HttpHandler;
use SignpostMarv\DaftFramework\Tests\ImplementationTest as Base;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\PlainTextHandler;

class ImplementationTest extends Base
{
    const RemapFrameworks = [
        BaseFramework::class => Framework::class,
        BaseHttpHandler::class => HttpHandler::class,
    ];

    public function DataProviderGoodSources() : Generator
    {
        foreach (parent::DataProviderGoodSources() as $args) {
            foreach ($this->DataProviderLoggerArguments() as $loggerArgs) {
                $loggerImplementation = $loggerArgs[0];

                $logger = new $loggerImplementation(...array_slice($loggerArgs, 1));

                $implementation = array_shift($args);
                $implementation = self::RemapFrameworks[$implementation] ?? $implementation;

                $postConstructionCalls = array_shift($args);

                array_unshift($args, $implementation, $postConstructionCalls, $logger);

                yield $args;

                if (HttpHandler::class === $args[0]) {
                    $args[0] = CatchingHttpHandler::class;

                    foreach ($this->DataProviderWhoopsHandlerArguments() as $whoopsArguments) {
                        $args[5][HandlerInterface::class] = $whoopsArguments[0];

                        yield $args;
                    }
                }
            }
        }
    }

    public function DataProviderLoggerArguments() : Generator
    {
        yield from [
            [
                NullLogger::class,
            ],
        ];
    }

    public function DataProviderWhoopsHandlerArguments() : Generator
    {
        yield from [
            [
                [
                    PlainTextHandler::class => [],
                ],
            ],
        ];
    }

    /**
    * @param array<string, array<int, mixed>> $postConstructionCalls
    *
    * @dataProvider DataProviderGoodSources
    */
    public function testEverythingInitialisesFine(
        string $implementation,
        array $postConstructionCalls,
        ...$implementationArgs
    ) : BaseFramework {
        /**
        * @var Framework
        */
        $instance = parent::testEverythingInitialisesFine(
            $implementation,
            $postConstructionCalls,
            ...$implementationArgs
        );

        list($logger) = $implementationArgs;

        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $this->assertTrue(
            is_a($instance, Framework::class) ||
            is_a($instance, HttpHandler::class)
        );

        $this->assertSame($logger, $instance->ObtainLogger());

        return $instance;
    }

    protected function extractDefaultFrameworkArgs(array $implementationArgs) : array
    {
        list(, $baseUrl, $basePath, $config) = $implementationArgs;

        return [$baseUrl, $basePath, $config];
    }
}
