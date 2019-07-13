<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging;

use Psr\Log\LoggerInterface;
use SignpostMarv\DaftFramework\Framework as Base;

class Framework extends Base
{
	use Logger;

	public function __construct(
		string $baseUrl,
		string $basePath,
		array $config,
		LoggerInterface $logger
	) {
		parent::__construct($baseUrl, $basePath, $config);

		$this->logger = $logger;
	}
}
