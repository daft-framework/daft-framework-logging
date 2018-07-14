<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests\fixtures\Routes;

use InvalidArgumentException;
use RuntimeException;
use SignpostMarv\DaftRouter\DaftRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Throws implements DaftRoute
{
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
        if ( ! isset($args['msg']) || ! is_string($args['msg'])) {
            throw new InvalidArgumentException('This route requires a msg argument!');
        }

        return sprintf('/throws/runtime-exception/%s', rawurlencode($args['msg']));
    }
}
