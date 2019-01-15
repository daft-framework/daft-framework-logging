<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase as Base;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SignpostMarv\DaftFramework\Logging\CatchingHttpHandler;
use SignpostMarv\DaftFramework\Tests\Utilities;
use SignpostMarv\DaftRouter\DaftSource;
use Symfony\Component\HttpFoundation\Request;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\PlainTextHandler;

class CatchingHttpHandlerTest extends Base
{
    public function __construct(string $name = '', array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->backupGlobals = false;
        $this->backupStaticAttributes = false;
        $this->runTestInSeparateProcess = false;
    }

    public function DataProviderLoggerArguments() : Generator
    {
        yield from [
            [
                NullLogger::class,
            ],
        ];
    }

    public function DataProviderFrameworkArguments() : Generator
    {
        yield from [
            [
                CatchingHttpHandler::class,
                [],
                'https://example.com/',
                realpath(__DIR__ . '/fixtures'),
                [
                    HandlerInterface::class => [
                        PlainTextHandler::class => [],
                    ],
                ],
            ],
        ];
    }

    public function DataProviderRouterArguments() : Generator
    {
        yield from [
            [
                [
                    'sources' => [
                        fixtures\Routes\Config::class,
                    ],
                    'cacheFile' => (__DIR__ . '/fixtures/catching-handler.fast-route.cache'),
                ],
                500,
                '/^.+: Dispatcher was not able to generate a response! in file .+Dispatcher\.php on line \d+/',
                '/?loggedin',
            ],
            [
                [
                    'sources' => [
                        fixtures\Routes\Config::class,
                    ],
                    'cacheFile' => (__DIR__ . '/fixtures/catching-handler.fast-route.cache'),
                ],
                500,
                '/^RuntimeException: foo in file .+Throws\.php on line \d+/',
                '/throws/runtime-exception/foo',
            ],
        ];
    }

    public function DataProviderTesting() : Generator
    {
        /**
        * @var iterable<array<int, mixed>>
        */
        $loggerSources = $this->DataProviderLoggerArguments();

        foreach ($loggerSources as $loggerArgs) {
            /**
            * @var iterable<array<int, mixed>>
            */
            $routerSources = $this->DataProviderRouterArguments();

            foreach ($routerSources as $routerArgs) {
                /**
                * @var iterable<array<int, mixed>>
                */
                $frameworkSources = $this->DataProviderFrameworkArguments();

                foreach ($frameworkSources as $frameworkArgs) {
                    /**
                    * @var scalar|array|object|null
                    */
                    $loggerImplementation = $loggerArgs[0];

                    if ( ! is_string($loggerImplementation)) {
                        static::assertInternalType('string', $loggerImplementation);

                        return;
                    } elseif (
                        ! class_exists($loggerImplementation) ||
                        ! is_a($loggerImplementation, LoggerInterface::class, true)
                    ) {
                        static::assertTrue(class_exists($loggerImplementation));
                        static::assertTrue(is_a(
                            $loggerImplementation,
                            LoggerInterface::class,
                            true
                        ));

                        return;
                    }

                    /**
                    * @var LoggerInterface
                    */
                    $logger = new $loggerImplementation(...array_slice($loggerArgs, 1));

                    /**
                    * @var string
                    * @var array<string, mixed[]> $postConstructionCalls
                    */
                    list($implementation, $postConstructionCalls) = $frameworkArgs;

                    /**
                    * @var string
                    */
                    $implementation = $implementation;

                    /**
                    * @var array<string, mixed[]>
                    */
                    $postConstructionCalls = $postConstructionCalls;

                    $frameworkArgs = array_slice($frameworkArgs, 2);

                    /**
                    * @var array<string, mixed>
                    */
                    $config = (array) $frameworkArgs[2];

                    $config[DaftSource::class] = (array) $routerArgs[0];
                    $frameworkArgs[2] = $config;

                    $frameworkArgs[] = $logger;

                    $instance = Utilities::ObtainHttpHandlerInstanceMixedArgs(
                        $this,
                        $implementation,
                        ...$frameworkArgs
                    );

                    Utilities::ConfigureFrameworkInstance(
                        $this,
                        $instance,
                        $postConstructionCalls
                    );

                    $yield = array_slice($routerArgs, 1);
                    array_unshift($yield, $instance);

                    yield $yield;
                }
            }
        }
    }

    /**
    * @param mixed ...$requestArgs
    *
    * @dataProvider DataProviderTesting
    */
    public function testCachingHttpHandler(
        CatchingHttpHandler $framework,
        int $expectedStatus,
        string $expectedContentRegex,
        ...$requestArgs
    ) {
        $uri = (string) $requestArgs[0];
        $method = (string) ($requestArgs[1] ?? 'GET');
        $parameters = (array) ($requestArgs[2] ?? []);
        $cookies = (array) ($requestArgs[3] ?? []);
        $files = (array) ($requestArgs[4] ?? []);
        $server = (array) ($requestArgs[5] ?? []);

        /**
        * @var string|resource|null
        */
        $content = ($requestArgs[6] ?? null);

        $request = Request::create(
            $uri,
            $method,
            $parameters,
            $cookies,
            $files,
            $server,
            $content
        );

        $response = $framework->handle($request);

        static::assertSame($expectedStatus, $response->getStatusCode());
        static::assertRegExp($expectedContentRegex, $response->getContent());
    }

    public function DataProviderBadConfig() : Generator
    {
        yield from [
            [
                [],
                InvalidArgumentException::class,
                'Handlers are not configured',
            ],
            [
                [
                    HandlerInterface::class => null,
                ],
                InvalidArgumentException::class,
                'Handlers are not configured',
            ],
            [
                [
                    HandlerInterface::class => false,
                ],
                InvalidArgumentException::class,
                'Handlers were not specified via an array!',
            ],
            [
                [
                    HandlerInterface::class => [],
                ],
                InvalidArgumentException::class,
                'No handlers were specified!',
            ],
            [
                [
                    HandlerInterface::class => [1 => null],
                ],
                InvalidArgumentException::class,
                'Handler config keys must be strings!',
            ],
            [
                [
                    HandlerInterface::class => [static::class => null],
                ],
                InvalidArgumentException::class,
                sprintf(
                    'Handler config keys must refer to implementations of %s!',
                    HandlerInterface::class
                ),
            ],
            [
                [
                    HandlerInterface::class => [HandlerInterface::class => null],
                ],
                InvalidArgumentException::class,
                sprintf(
                    'Handler config keys must refer to implementations of %s, not the interface!',
                    HandlerInterface::class
                ),
            ],
            [
                [
                    HandlerInterface::class => [PlainTextHandler::class => null],
                ],
                InvalidArgumentException::class,
                'Handler arguments must be specifed as an array!',
            ],
        ];
    }

    public function DataProviderTestBadConfig() : Generator
    {
        /**
        * @var iterable<array<int, mixed>>
        */
        $loggerSources = $this->DataProviderLoggerArguments();

        foreach ($loggerSources as $loggerArgs) {
            /**
            * @var scalar|array|object|null
            */
            $loggerImplementation = $loggerArgs[0];

            if ( ! is_string($loggerImplementation)) {
                static::assertInternalType('string', $loggerImplementation);

                return;
            } elseif (
                ! class_exists($loggerImplementation) ||
                ! is_a($loggerImplementation, LoggerInterface::class, true)
            ) {
                static::assertTrue(class_exists($loggerImplementation));
                static::assertTrue(is_a(
                    $loggerImplementation,
                    LoggerInterface::class,
                    true
                ));

                return;
            }

            /**
            * @var iterable<array<int, mixed>>
            */
            $routerSources = $this->DataProviderRouterArguments();

            foreach ($routerSources as $routerArgs) {
                /**
                * @var iterable<array<int, scalar|array>>
                */
                $badConfigSources = $this->DataProviderBadConfig();

                foreach ($badConfigSources as $badConfigArgs) {
                    list(
                        $handlerConfigArgs,
                        $expectedExceptionType,
                        $expectedExceptionMessage
                    ) = $badConfigArgs;

                    static::assertInternalType('array', $handlerConfigArgs);
                    $handlerConfigArgs = (array) $handlerConfigArgs;

                    /**
                    * @var iterable<array<int, mixed>>
                    */
                    $frameworkSources = $this->DataProviderFrameworkArguments();

                    foreach ($frameworkSources as $frameworkArgs) {
                        /**
                        * @var LoggerInterface
                        */
                        $logger = new $loggerImplementation(...array_slice($loggerArgs, 1));

                        /**
                        * @var string
                        * @var array<string, mixed[]> $frameworkArgs
                        */
                        list($implementation, $postConstructionCalls) = $frameworkArgs;

                        $frameworkArgs = array_slice($frameworkArgs, 2);

                        /**
                        * @var array<string, mixed>
                        */
                        $config = (array) $frameworkArgs[2];

                        $config[DaftSource::class] = (array) $routerArgs[0];
                        $frameworkArgs[2] = $config;

                        if (isset($frameworkArgs[2][HandlerInterface::class])) {
                            unset($frameworkArgs[2][HandlerInterface::class]);
                        }

                        $frameworkArgs[2] = array_merge($frameworkArgs[2], $handlerConfigArgs);

                        $frameworkArgs[] = $logger;

                        yield [
                            $implementation,
                            $frameworkArgs,
                            $expectedExceptionType,
                            $expectedExceptionMessage,
                        ];
                    }
                }
            }
        }
    }

    /**
    * @dataProvider DataProviderTestBadConfig
    *
    * @depends testCachingHttpHandler
    */
    public function testBadConfig(
        string $implementation,
        array $frameworkArgs,
        string $expectedExceptionType,
        string $expectedExceptionMessage
    ) {
        $this->expectException($expectedExceptionType);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $instance = Utilities::ObtainHttpHandlerInstanceMixedArgs(
            $this,
            $implementation,
            ...$frameworkArgs
        );
    }

    public function DataProviderTestBadLogger() : Generator
    {
        /**
        * @var iterable<array<int, mixed>>
        */
        $loggerSources = $this->DataProviderLoggerArguments();

        foreach ($loggerSources as $loggerArgs) {
            /**
            * @var iterable<array<int, mixed>>
            */
            $routerSources = $this->DataProviderRouterArguments();

            foreach ($routerSources as $routerArgs) {
                /**
                * @var array<int, int>
                */
                $range = range(1, 2);

                foreach ($range as $throwUnderLogCount) {
                    /**
                    * @var iterable<array<int, mixed>>
                    */
                    $frameworkSources = $this->DataProviderFrameworkArguments();

                    foreach ($frameworkSources as $frameworkArgs) {
                        $logger = new fixtures\Log\ThrowingLogger($throwUnderLogCount, 'testing');

                        /**
                        * @var string
                        * @var array<string, mixed[]> $postConstructionCalls
                        */
                        list($implementation, $postConstructionCalls) = $frameworkArgs;

                        /**
                        * @var string
                        */
                        $implementation = $implementation;

                        /**
                        * @var array<string, mixed[]>
                        */
                        $postConstructionCalls = $postConstructionCalls;

                        $frameworkArgs = array_slice($frameworkArgs, 2);

                        static::assertInternalType('string', $frameworkArgs[0]);
                        static::assertInternalType('string', $frameworkArgs[1]);
                        static::assertInternalType('array', $frameworkArgs[2]);

                        /**
                        * @var array{0:string, 1:string, 2:array}
                        */
                        $frameworkArgs = $frameworkArgs;

                        $frameworkArgs[2][DaftSource::class] = (array) $routerArgs[0];

                        $frameworkArgs[] = $logger;

                        static::assertInstanceOf(
                            LoggerInterface::class,
                            $frameworkArgs[3] ?? null
                        );

                        /**
                        * @var array{0:string, 1:string, 2:array, 3:LoggerInterface}
                        */
                        $frameworkArgs = $frameworkArgs;

                        $instance = Utilities::ObtainHttpHandlerInstanceMixedArgs(
                            $this,
                            $implementation,
                            ...$frameworkArgs
                        );

                        Utilities::ConfigureFrameworkInstance(
                            $this,
                            $instance,
                            $postConstructionCalls
                        );

                        $yield = array_slice($routerArgs, 1);
                        array_unshift($yield, $instance);

                        yield $yield;
                    }
                }
            }
        }
    }

    /**
    * @param mixed ...$requestArgs
    *
    * @dataProvider DataProviderTestBadLogger
    */
    public function testBadLogger(
        CatchingHttpHandler $framework,
        int $expectedStatus,
        string $expectedContentRegex,
        ...$requestArgs
    ) {
        $uri = (string) $requestArgs[0];
        $method = (string) ($requestArgs[1] ?? 'GET');
        $parameters = (array) ($requestArgs[2] ?? []);
        $cookies = (array) ($requestArgs[3] ?? []);
        $files = (array) ($requestArgs[4] ?? []);
        $server = (array) ($requestArgs[5] ?? []);

        /**
        * @var string|resource|null
        */
        $content = ($requestArgs[6] ?? null);

        $request = Request::create(
            $uri,
            $method,
            $parameters,
            $cookies,
            $files,
            $server,
            $content
        );

        $response = $framework->handle($request);

        static::assertSame(500, $response->getStatusCode());
        static::assertSame('There was an internal error', $response->getContent());
    }
}
