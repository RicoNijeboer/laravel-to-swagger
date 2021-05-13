<?php

namespace RicoNijeboer\Swagger\Tests\app\Http\Controllers;

use Illuminate\Routing\Controller;

/**
 * Class TestController
 *
 * @package RicoNijeboer\Swagger\Tests\app\Http\Controllers
 */
class TestController extends Controller
{
    private static $responses = [];

    public static function setResponse(string $method, $response): void
    {
        self::$responses[$method] = $response;
    }

    public static function reset(): void
    {
        self::$responses = [];
    }

    public function index()
    {
        $response = static::$responses['index'] ?? [];

        if (is_callable($response)) {
            $response = app()->call($response);
        }

        return $response;
    }
}
