<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\PlainTextHandler;
use Whoops\Run;
use Whoops\RunInterface;

class CatchingHttpHandler extends HttpHandler
{
    /**
    * @var array<string, mixed[]>
    */
    protected $handlers = [];

    public function __construct(
        LoggerInterface $logger,
        string $baseUrl,
        string $basePath,
        array $config = []
    ) {
        parent::__construct($logger, $baseUrl, $basePath, $config);

        /**
        * @var array<string, mixed[]>
        */
        $subConfig = array_filter(
            array_filter(
                (array) $config[HandlerInterface::class],
                'is_string',
                ARRAY_FILTER_USE_KEY
            ),
            function (string $maybe) : bool {
                return
                    is_a($maybe, HandlerInterface::class, true) &&
                    class_exists($maybe);
            },
            ARRAY_FILTER_USE_KEY
        );

        $this->handlers = $subConfig;
    }

    public function handle(Request $request) : Response
    {
        try {
            try {
                $whoops = $this->ObtainWhoopsRunner();
                $whoops->register();

                try {
                    return parent::handle($request);
                } catch (Throwable $e) {
                    $this->logger->critical($e->getMessage());

                    return new Response($whoops->handleException($e), 500);
                }
            } catch (Throwable $e) {
                $this->logger->critical($e->getMessage());

                return new Response('There was an internal error', 500);
            }
        } catch (Throwable $e) {
            return new Response('There was an internal error', 500);
        }
    }

    /**
    * @psalm-suppress InvalidStringClass
    */
    protected function ObtainWhoopsRunner() : RunInterface
    {
        $whoops = new Run();

        foreach ($this->handlers as $handler => $handlerArgs) {
            /**
            * @var HandlerInterface
            */
            $handlerInstance =
                (PlainTextHandler::class === $handler && count($handlerArgs) < 1)
                    ? new PlainTextHandler($this->logger)
                    : new $handler(...$handlerArgs);

            $whoops->pushHandler($handlerInstance);
        }
        $whoops->writeToOutput(false);
        $whoops->sendHttpCode(false);
        $whoops->allowQuit(false);

        return $whoops;
    }

    protected function ValidateConfig(array $config) : array
    {
        /**
        * @var array|null|string
        */
        $subConfig = $config[HandlerInterface::class] ?? null;

        if ( ! isset($subConfig)) {
            throw new InvalidArgumentException('Handlers are not configured!');
        } elseif ( ! is_array($subConfig)) {
            throw new InvalidArgumentException('Handlers were not specified via an array!');
        } elseif (count($subConfig) < 1) {
            throw new InvalidArgumentException('No handlers were specified!');
        }

        foreach ($subConfig as $handler => $handlerArgs) {
            $this->ValidateHandlerConfig($handler, $handlerArgs);
        }

        return parent::ValidateConfig($config);
    }

    /**
    * @param mixed $handler
    * @param mixed $handlerArgs
    */
    protected function ValidateHandlerConfig($handler, $handlerArgs) : void
    {
        if ( ! is_string($handler)) {
            throw new InvalidArgumentException('Handler config keys must be strings!');
        } elseif ( ! is_a($handler, HandlerInterface::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Handler config keys must refer to implementations of %s!',
                HandlerInterface::class
            ));
        } elseif (HandlerInterface::class === $handler) {
            throw new InvalidArgumentException(sprintf(
                'Handler config keys must refer to implementations of %s, not the interface!',
                HandlerInterface::class
            ));
        } elseif ( ! is_array($handlerArgs)) {
            throw new InvalidArgumentException(
                'Handler arguments must be specifed as an array!'
            );
        }
    }
}
