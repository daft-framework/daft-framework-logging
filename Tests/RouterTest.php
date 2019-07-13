<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests;

use Generator;
use SignpostMarv\DaftRouter\Tests\ImplementationTest as Base;

class RouterTest extends Base
{
	public function DataProviderRoutesWithKnownArgs() : Generator
	{
		yield from [
			[
				fixtures\Routes\Throws::class,
				['msg' => 'foo'],
				['msg' => 'foo'],
				'GET',
				'/throws/runtime-exception/foo',
			],
		];
	}
}
