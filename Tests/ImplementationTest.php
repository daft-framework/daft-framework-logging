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

    /**
    * @psalm-return Generator<int, array{0:class-string<Framework>, 1:array<string, array<int, mixed>>, 2:string, 3:string, 4:array}, mixed, void>
    */
    public function DataProviderGoodSources() : Generator
    {
        foreach (parent::DataProviderGoodSources() as $args) {
            foreach ($this->DataProviderLoggerArguments() as $loggerArgs) {
                $loggerImplementation = $loggerArgs[0];

                $logger = new $loggerImplementation(...array_slice($loggerArgs, 1));

                $implementation = array_shift($args);

                /**
                * @var string
                */
                $implementation = self::RemapFrameworks[$implementation] ?? $implementation;

                /**
                * @var array<string, mixed[]>
                */
                $postConstructionCalls = array_shift($args);

                array_unshift($args, $implementation, $postConstructionCalls);

                $args[] = $logger;

                /**
                * @psalm-var array{0:class-string<Framework>|class-string<HttpHandler>, 1:array<string, array<int, mixed>>, 2:string, 3:string, 4:array}
                */
                $args = $args;

                yield $args;

                if (HttpHandler::class === $args[0]) {
                    $args[0] = CatchingHttpHandler::class;

                    foreach ($this->DataProviderWhoopsHandlerArguments() as $whoopsArguments) {
                        $args4 = (array) $args[4];

                        $args4[HandlerInterface::class] = $whoopsArguments[0];

                        $args[4] = $args4;

                        /**
                        * @psalm-var array{0:class-string<HttpHandler>, 1:array<string, array<int, mixed>>, 2:string, 3:string, 4:array}
                        */
                        $args = $args;

                        yield $args;
                    }
                }
            }
        }
    }

    /**
    * @psalm-return Generator<int, array{0:class-string<LoggerInterface>}, mixed, void>
    */
    public function DataProviderLoggerArguments() : Generator
    {
        yield from [
            [
                NullLogger::class,
            ],
        ];
    }

    /**
    * @psalm-return Generator<int, array{0:array<class-string<HandlerInterface>, mixed[]>}, mixed, void>
    */
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
    * @param mixed ...$implementationArgs
    *
    * @dataProvider DataProviderGoodSources
    */
    public function testEverythingInitialisesFine(
        string $implementation,
        array $postConstructionCalls,
        ...$implementationArgs
    ) : BaseFramework {
        list(, , , $logger) = $implementationArgs;

        static::assertInstanceOf(LoggerInterface::class, $logger);

        $instance = $this->ObtainFrameworkInstance($implementation, ...$implementationArgs);
        $this->ConfigureFrameworkInstance($instance, $postConstructionCalls);

        static::assertTrue(
            (
                ($instance instanceof Framework) ||
                ($instance instanceof HttpHandler)
            )
        );

        /**
        * @var Framework
        */
        $instance = $instance;

        static::assertSame($logger, $instance->ObtainLogger());

        return $instance;
    }

    protected function extractDefaultFrameworkArgs(array $implementationArgs) : array
    {
        list(, $baseUrl, $basePath, $config) = $implementationArgs;

        return [$baseUrl, $basePath, $config];
    }
}
