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

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Database\Schema\Schema;
use Hyperf\HttpServer\MiddlewareManager;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Rpc\Context as RpcContext;
use Hyperf\Utils\Context;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Throwable;
use Wind\Telescope\Event\RequestHandled;
use Wind\Telescope\Str;

class Server extends \Hyperf\HttpServer\Server
{
    public static $telescopeEmitter = null;

    public function onRequest(SwooleRequest $request, SwooleResponse $response): void
    {
        $batchId = Str::orderedUuid();
        Context::set('batch_id', $batchId);
        (new RpcContext())->set('batch_id', $batchId);

        try {
            CoordinatorManager::until(Constants::WORKER_START)->yield();

            [$psr7Request, $psr7Response] = $this->initRequestAndResponse($request, $response);

            $psr7Request = $this->coreMiddleware->dispatch($psr7Request);
            /** @var Dispatched $dispatched */
            $dispatched = $psr7Request->getAttribute(Dispatched::class);
            $middlewares = $this->middlewares;
            if ($dispatched->isFound()) {
                $registedMiddlewares = MiddlewareManager::get($this->serverName, $dispatched->handler->route, $psr7Request->getMethod());
                $middlewares = array_merge($middlewares, $registedMiddlewares);
            }

            $psr7Response = $this->dispatcher->dispatch($psr7Request, $middlewares, $this->coreMiddleware);
        } catch (Throwable $throwable) {
            // Delegate the exception to exception handler.
            $psr7Response = $this->exceptionHandlerDispatcher->dispatch($throwable, $this->exceptionHandlers);
        } finally {
            // Send the Response to client.
            if (! isset($psr7Response)) {
                return;
            }
            if (! isset($psr7Request) || $psr7Request->getMethod() === 'HEAD') {
                $this->responseEmitter->emit($psr7Response, $response, false);
            } else {
                $this->responseEmitter->emit($psr7Response, $response, true);

                if (is_null(self::$telescopeEmitter)) {
                    if (Schema::hasTable('telescope_entries')) {
                        self::$telescopeEmitter = true;
                    } else {
                        self::$telescopeEmitter = false;
                        $stdout = $this->container->get(StdoutLoggerInterface::class);
                        $stdout->warning('if you want to use telescope. Please enter command to install: php ./bin/hyperf.php telescope:install');
                    }
                }

                if (self::$telescopeEmitter) {
                    $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
                    $eventDispatcher->dispatch(new RequestHandled($psr7Request, $psr7Response, $middlewares));
                }
            }
        }
    }
}
