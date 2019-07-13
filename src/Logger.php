<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging;

use Psr\Log\LoggerInterface;

trait Logger
{
	/**
	* @var LoggerInterface
	*/
	protected $logger;

	public function ObtainLogger() : LoggerInterface
	{
		return $this->logger;
	}
}
