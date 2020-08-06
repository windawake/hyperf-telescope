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

use Hyperf\HttpServer\MiddlewareManager;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Context;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Throwable;
use Wind\Telescope\EntryType;
use Wind\Telescope\IncomingEntry;
use Wind\Telescope\Model\TelescopeEntryModel;
use Psr\Http\Message\ResponseInterface;

class Server extends \Hyperf\HttpServer\Server
{
    public function onRequest(SwooleRequest $request, SwooleResponse $response): void
    {
        $startTime = microtime(true);
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
            if (!isset($psr7Response)) {
                return;
            }
            if (!isset($psr7Request) || $psr7Request->getMethod() === 'HEAD') {
                $this->responseEmitter->emit($psr7Response, $response, false);
            } else {
                $this->responseEmitter->emit($psr7Response, $response, true);


                /**
                 * @var \Hyperf\HttpMessage\Server\Request $psr7Request 
                 */
                if (strpos($psr7Request->getRequestTarget(), 'telescope') === false) {
                    $entry = IncomingEntry::make([
                        'ip_address' => $psr7Request->getServerParams()['remote_addr'],
                        'uri' => $psr7Request->getRequestTarget(),
                        'method' => $psr7Request->getMethod(),
                        'controller_action' => $dispatched->handler->callback,
                        'middleware' => $middlewares,
                        'headers' => $psr7Request->getHeaders(),
                        'payload' => $psr7Request->getParsedBody(),
                        'session' => '',
                        'response_status' => $psr7Response->getStatusCode(),
                        'response' => $this->response($psr7Response),
                        'duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
                        'memory' => round(memory_get_peak_usage(true) / 1024 / 1025, 1),
                    ]);

                    $batchId = $entry->uuid;
                    $entry->batchId($batchId)->type(EntryType::REQUEST);

                    TelescopeEntryModel::create($entry->toArray());

                    $arr = Context::get('query_listener', []);
                    $optionSlow = 500;
                    foreach ($arr as [$event, $sql]) {
                        $entry = IncomingEntry::make([
                            'connection' => $event->connectionName,
                            'bindings' => [],
                            'sql' => $sql,
                            'time' => number_format($event->time, 2, '.', ''),
                            'slow' => $event->time >= $optionSlow,
                            // 'file' => $caller['file'],
                            // 'line' => $caller['line'],
                            'hash' => md5($sql),
                        ]);

                        $entry->batchId($batchId)->type(EntryType::QUERY);

                        TelescopeEntryModel::create($entry->toArray());
                    }

                    $exception = Context::get('exception_record');
                    if ($exception) {
                        $trace = collect($exception->getTrace())->map(function ($item) {
                            return Arr::only($item, ['file', 'line']);
                        })->toArray();

                        $entry = IncomingEntry::make([
                            'class' => get_class($exception),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                            'message' => $exception->getMessage(),
                            'context' => null,
                            'trace' => $trace,
                            'line_preview' => $this->getContext($exception),
                        ]);

                        $entry->batchId($batchId)->type(EntryType::EXCEPTION);

                        TelescopeEntryModel::create($entry->toArray());
                    }
                }
            }
        }
    }

    protected function response(ResponseInterface $response)
    {
        $content = $response->getBody()->getContents();
        if (!$this->contentWithinLimits($content)) {
            return "Purged By Telescope";
        }

        if (is_string($content) && strpos($response->getContentType(), 'application/json') !== false) {
            if (
                is_array(json_decode($content, true)) &&
                json_last_error() === JSON_ERROR_NONE
            ) {
                return json_decode($content, true);
            }
        }
        return $content;
    }

    protected function getContext($exception)
    {
        if (strpos($exception->getFile(), "eval()'d code")) {
            return [
                $exception->getLine() => "eval()'d code",
            ];
        }
        return collect(explode("\n", file_get_contents($exception->getFile())))
            ->slice($exception->getLine() - 10, 20)
            ->mapWithKeys(function ($value, $key) {
                return [$key + 1 => $value];
            })->all();
    }

    protected function contentWithinLimits($content)
    {
        $limit = 64;

        return mb_strlen($content) / 1000 <= $limit;
    }
}
