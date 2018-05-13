<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests;

use Generator;
use PHPUnit\Framework\TestCase as Base;
use Psr\Log\NullLogger;
use SignpostMarv\DaftFramework\Logging\CatchingHttpHandler;
use SignpostMarv\DaftFramework\Tests\Utilities;
use SignpostMarv\DaftRouter\DaftSource;
use Symfony\Component\HttpFoundation\Request;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\PlainTextHandler;

class CatchingHttpHandlerTest extends Base
{
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
        foreach ($this->DataProviderLoggerArguments() as $loggerArgs) {
            foreach ($this->DataProviderRouterArguments() as $routerArgs) {
                foreach ($this->DataProviderFrameworkArguments() as $frameworkArgs) {
                    $loggerImplementation = $loggerArgs[0];

                    $logger = new $loggerImplementation(...array_slice($loggerArgs, 1));

                    /**
                    * @var string $implementation
                    * @var array<string, mixed[]> $frameworkArgs
                    */
                    list($implementation, $postConstructionCalls) = $frameworkArgs;

                    $frameworkArgs = array_slice($frameworkArgs, 2);
                    $frameworkArgs[2][DaftSource::class] = $routerArgs[0];

                    array_unshift($frameworkArgs, $logger);

                    $instance = Utilities::ObtainHttpHandlerInstance(
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
    * @dataProvider DataProviderTesting
    */
    public function testCachingHttpHandler(
        CatchingHttpHandler $framework,
        int $expectedStatus,
        string $expectedContentRegex,
        ...$requestArgs
    ) : void {
        $request = Request::create(...$requestArgs);

        $response = $framework->handle($request);

        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertRegExp($expectedContentRegex, $response->getContent());
    }
}
