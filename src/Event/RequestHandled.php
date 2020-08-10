<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Wind\Telescope\Event;

use Hyperf\HttpMessage\Server\Request as Psr7Request;
use Hyperf\HttpMessage\Server\Response as Psr7Response;

class RequestHandled
{
    public $request;

    public $response;

    public $middlewares;

    public function __construct(Psr7Request $request, Psr7Response $response, array $middlewares)
    {
        $this->request = $request;
        $this->response = $response;
        $this->middlewares = $middlewares;
    }
}
