<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging\Tests\fixtures\Routes;

use RuntimeException;
use SignpostMarv\DaftRouter\DaftRouteAcceptsOnlyTypedArgs;
use SignpostMarv\DaftRouter\DaftRouterHttpRouteDefaultMethodGet;
use SignpostMarv\DaftRouter\TypedArgs;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
* @psalm-type T1 = array{msg:string}
* @psalm-type T2 = MessageArgs
* @psalm-type R_TYPED = Response
*
* @template-extends DaftRouteAcceptsOnlyTypedArgs<T1, T1, T2, R_TYPED, 'GET', 'GET'>
*/
class Throws extends DaftRouteAcceptsOnlyTypedArgs
{
    use DaftRouterHttpRouteDefaultMethodGet;

    /**
    * @param T2 $args
    */
    public static function DaftRouterHandleRequestWithTypedArgs(Request $request, TypedArgs $args) : Response
    {
        static::DaftRouterAutoMethodChecking($request->getMethod());

        throw new RuntimeException($args->msg);
    }

    /**
    * @return array<string, array<int, 'GET'>>
    */
    public static function DaftRouterRoutes() : array
    {
        return [
            '/throws/runtime-exception/{msg:[^\/]+}' => ['GET'],
        ];
    }

    /**
    * @param T2 $args
    * @param 'GET'|null $method
    */
    public static function DaftRouterHttpRouteWithTypedArgs(
        TypedArgs $args,
        string $method = null
    ) : string {
        static::DaftRouterAutoMethodChecking(
            $method ?? static::DaftRouterHttpRouteDefaultMethod()
        );

        return sprintf('/throws/runtime-exception/%s', rawurlencode($args->msg));
    }

    /**
    * @param T1 $args
    * @param 'GET'|null $method
    *
    * @return T2
    */
    public static function DaftRouterHttpRouteArgsTyped(
        array $args,
        string $method = null
    ) : TypedArgs {
        return new MessageArgs($args);
    }
}
