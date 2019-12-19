<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests\fixtures\Log;

use Psr\Log\NullLogger as Base;
use RuntimeException;

class ThrowingLogger extends Base
{
	protected int $loggingCalls = 0;

	protected int $throwUnderLogCount = 0;

	protected string $exceptionMessage;

	protected int $exceptionCode = 0;

	public function __construct(
		int $throwUnderLogCount,
		string $exceptionMessage,
		int $exceptionCode = 0
	) {
		$this->throwUnderLogCount = $throwUnderLogCount;

		$this->exceptionMessage = $exceptionMessage;
		$this->exceptionCode = $exceptionCode;
	}

	/**
	* @param int|mixed $level
	* @param string $message
	*/
	public function log($level, $message, array $context = []) : void
	{
		++$this->loggingCalls;

		if ($this->loggingCalls <= $this->throwUnderLogCount) {
			throw new RuntimeException($this->exceptionMessage, $this->exceptionCode);
		}

		parent::log($level, $message, $context);
	}
}
