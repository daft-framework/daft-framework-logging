<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests\fixtures\Routes;

use InvalidArgumentException;
use RuntimeException;
use SignpostMarv\DaftRouter\DaftRoute;
use SignpostMarv\DaftRouter\DaftRouterAutoMethodCheckingTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Throws implements DaftRoute
{
    use DaftRouterAutoMethodCheckingTrait;

    public static function DaftRouterHandleRequest(Request $request, array $args) : Response
    {
        throw new RuntimeException((string) $args['msg']);
    }

    public static function DaftRouterRoutes() : array
    {
        return [
            '/throws/runtime-exception/{msg:[^\/]+}' => ['GET'],
        ];
    }

    public static function DaftRouterHttpRoute(array $args, string $method = 'GET') : string
    {
        /**
        * @var array{msg:string}
        */
        $args = static::DaftRouterHttpRouteArgsTyped($args, $method);

        return sprintf('/throws/runtime-exception/%s', rawurlencode($args['msg']));
    }

    public static function DaftRouterHttpRouteArgsTyped(array $args, string $method) : array
    {
        return static::DaftRouterHttpRouteArgs($args, $method);
    }

    /**
    * @return array<string, string>
    */
    public static function DaftRouterHttpRouteArgs(array $args, string $method) : array
    {
        static::DaftRouterAutoMethodChecking($method);

        if ( ! isset($args['msg']) || ! is_string($args['msg'])) {
            throw new InvalidArgumentException('This route requires a msg argument!');
        }

        return ['msg' => $args['msg']];
    }
}
