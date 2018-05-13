<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests;

use Generator;
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
    public function DataProviderGoodSources() : Generator
    {
        foreach (parent::DataProviderGoodSources() as $args) {
            foreach ($this->DataProviderLoggerArguments() as $loggerArgs) {
                $loggerImplementation = $loggerArgs[0];

                $logger = new $loggerImplementation(...array_slice($loggerArgs, 1));

                $implementation = array_shift($args);
                if (BaseFramework::class === $implementation) {
                    $implementation = Framework::class;
                } elseif (BaseHttpHandler::class === $implementation) {
                    $implementation = HttpHandler::class;
                }
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

    protected function extractDefaultFrameworkArgs(array $implementationArgs) : array
    {
        list(, $baseUrl, $basePath, $config) = $implementationArgs;

        return [$baseUrl, $basePath, $config];
    }
}