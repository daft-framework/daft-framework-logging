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
* @template-extends TypedArgs<T, T>
*/
class MessageArgs extends TypedArgs
{
	const TYPED_PROPERTIES = [
		'msg',
	];

	/**
	* @readonly
	*
	* @var string
	*/
	public $msg;

	/**
	* @param T $args
	*/
	public function __construct(array $args)
	{
		$this->msg = $args['msg'];
	}
}
