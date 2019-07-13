<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests\fixtures\Routes;

use SignpostMarv\DaftRouter\TypedArgs;

/**
* @psalm-type T = array{msg:string}
*
* @template-extends TypedArgs<T>
*/
class MessageArgs extends TypedArgs
{
	/**
	* @var T
	*/
	protected $typed;
}
