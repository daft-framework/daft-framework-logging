<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests\fixtures\Routes;

use SignpostMarv\DaftRouter\DaftSource;

class Config implements DaftSource
{
	public static function DaftRouterRouteAndMiddlewareSources() : array
	{
		return [
			Throws::class,
		];
	}
}
