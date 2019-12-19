<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging;

use Psr\Log\LoggerInterface;

trait Logger
{
	protected LoggerInterface $logger;

	public function ObtainLogger() : LoggerInterface
	{
		return $this->logger;
	}
}
