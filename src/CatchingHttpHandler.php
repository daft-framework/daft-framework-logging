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

/**
* @template CONFIG as array{
	SignpostMarv\DaftRouter\DaftSource: array{
		cacheFile:string,
		sources:array<int, string>
	},
	Whoops\Handler\HandlerInterface: array<
		class-string<HandlerInterface>,
		array<int, mixed>
	>
}
*
* @template-extends HttpHandler<CONFIG>
*/
class CatchingHttpHandler extends HttpHandler
{
	const INT_ARGS_IS_PLAINTEXT_HANDLER = 1;

	const BOOL_WHOOPS_NO_SENDING = false;

	/**
	* @var array<class-string<HandlerInterface>, array<int, mixed>>
	*/
	protected $handlers = [];

	/**
	* @param CONFIG $config
	*/
	public function __construct(
		string $baseUrl,
		string $basePath,
		array $config,
		LoggerInterface $logger
	) {
		parent::__construct($baseUrl, $basePath, $config, $logger);

		/**
		* @var array<class-string<HandlerInterface>, array<int, mixed>>
		*/
		$subConfig = $config[HandlerInterface::class];

		$subConfig = array_filter(
			$subConfig,
			/**
			* @param class-string<HandlerInterface> $maybe
			*/
			function (string $maybe) : bool {
				return
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

	protected function ObtainWhoopsRunner() : RunInterface
	{
		$whoops = new Run();

		foreach ($this->handlers as $handler => $handlerArgs) {
			/**
			* @var HandlerInterface
			*/
			$handlerInstance =
				(
					PlainTextHandler::class === $handler &&
					count($handlerArgs) < self::INT_ARGS_IS_PLAINTEXT_HANDLER
				)
					? new PlainTextHandler($this->logger)
					: new $handler(...$handlerArgs);

			$whoops->appendHandler($handlerInstance);
		}
		$whoops->writeToOutput(self::BOOL_WHOOPS_NO_SENDING);
		$whoops->sendHttpCode(self::BOOL_WHOOPS_NO_SENDING);
		$whoops->allowQuit(self::BOOL_WHOOPS_NO_SENDING);

		return $whoops;
	}

	protected function ValidateConfig(array $config) : array
	{
		/**
		* @var array|string|null
		*/
		$subConfig = $config[HandlerInterface::class] ?? null;

		if ( ! isset($subConfig)) {
			throw new InvalidArgumentException('Handlers are not configured!');
		} elseif ( ! is_array($subConfig)) {
			throw new InvalidArgumentException('Handlers were not specified via an array!');
		} elseif (count($subConfig) < 1) {
			throw new InvalidArgumentException('No handlers were specified!');
		}

		/**
		* @var array<int|string, scalar|array|object|null>
		*/
		$subConfig = $subConfig;

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
