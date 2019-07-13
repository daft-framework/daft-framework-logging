<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests\fixtures;

use SignpostMarv\DaftFramework\Symfony\Console\DaftConsoleSource;
use SignpostMarv\DaftFramework\Tests\fixtures\Console\Command;
use SignpostMarv\DaftRouter\DaftSource;

return [
	DaftConsoleSource::class => [
		Command\TestCommand::class,
		Command\DisabledTestCommand::class,
		\SignpostMarv\DaftFramework\Symfony\Console\Command\FastRouteCacheCommand::class,
	],
	DaftSource::class => [
		'sources' => [
		],
		'cacheFile' => realpath(__DIR__ . '/') . 'fast-route.cache',
	],
];
