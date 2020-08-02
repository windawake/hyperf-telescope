<?php

declare(strict_types=1);

namespace Wind\Telescope\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class IncomingMiddleWare implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        // $entry = IncomingEntry::make([
        //     'ip_address' => $event->request->ip(),
        //     'uri' => str_replace($event->request->root(), '', $event->request->fullUrl()) ?: '/',
        //     'method' => $event->request->method(),
        //     'controller_action' => optional($event->request->route())->getActionName(),
        //     'middleware' => array_values(optional($event->request->route())->gatherMiddleware() ?? []),
        //     'headers' => $this->headers($event->request->headers->all()),
        //     'payload' => $this->payload($this->input($event->request)),
        //     'session' => $this->payload($this->sessionVariables($event->request)),
        //     'response_status' => $event->response->getStatusCode(),
        //     'response' => $this->response($event->response),
        //     'duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
        //     'memory' => round(memory_get_peak_usage(true) / 1024 / 1025, 1),
        // ]);

        return $response;
    }

}
