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
namespace Wind\Telescope\Core;

use Hyperf\ExceptionHandler\ExceptionHandlerDispatcher;
use Hyperf\Utils\Context;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Wind\Telescope\Event\RpcHandled;
use Wind\Telescope\Str;

class RpcServer extends \Hyperf\JsonRpc\TcpServer
{
    public function onReceive($server, int $fd, int $fromId, string $data): void
    {
        Context::set('start_time', microtime(true));
        $batchId = Str::orderedUuid();
        Context::set('sub_batch_id', $batchId);
        try {
            CoordinatorManager::until(Constants::WORKER_START)->yield();

            // Initialize PSR-7 Request and Response objects.
            Context::set(ServerRequestInterface::class, $request = $this->buildRequest($fd, $fromId, $data));
            Context::set(ResponseInterface::class, $this->buildResponse($fd, $server));

            // $middlewares = array_merge($this->middlewares, MiddlewareManager::get());
            $middlewares = $this->middlewares;

            $request = $this->coreMiddleware->dispatch($request);

            $response = $this->dispatcher->dispatch($request, $middlewares, $this->coreMiddleware);
        } catch (Throwable $throwable) {
            // Delegate the exception to exception handler.
            $exceptionHandlerDispatcher = $this->container->get(ExceptionHandlerDispatcher::class);
            $response = $exceptionHandlerDispatcher->dispatch($throwable, $this->exceptionHandlers);
        } finally {
            if (! $response || ! $response instanceof ResponseInterface) {
                $response = $this->transferToResponse($response);
            }
            if ($response) {
                $this->send($server, $fd, $response);
            }

            $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
            $eventDispatcher->dispatch(new RpcHandled($request, $response, $middlewares));
        }
    }
}
