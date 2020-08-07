<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * 
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Wind\Telescope\Listener;

use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use Hyperf\Utils\Context;
use Wind\Telescope\Event\RequestHandled;
use Hyperf\HttpServer\Router\Dispatched;
use Wind\Telescope\EntryType;
use Wind\Telescope\IncomingEntry;
use Wind\Telescope\Model\TelescopeEntryModel;
use Psr\Http\Message\ResponseInterface;

/**
 * @Listener
 */
class RequestHandledListener implements ListenerInterface
{

    public function listen(): array
    {
        return [
            RequestHandled::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event)
    {
        if ($event instanceof RequestHandled) {

            /**
             * @var \Hyperf\HttpMessage\Server\Request $psr7Request 
             */
            $psr7Request = $event->request;
            $psr7Response = $event->response;
            $middlewares = $event->middlewares;
            $startTime = Context::get('start_time');

            /** @var Dispatched $dispatched */
            $dispatched = $psr7Request->getAttribute(Dispatched::class);

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

                $arr = Context::get('query_record', []);
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
