<?php

declare(strict_types=1);

namespace Wind\Telescope\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Utils\Context;
use Wind\Telescope\EntryType;
use Wind\Telescope\IncomingEntry;
use Wind\Telescope\Model\TelescopeEntryModel;

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
        // var_dump($request->getRemoteAddr(), get_class_methods($request));
        // var_dump($request->getUri(),$request->getMethod(),$request->getHeaders(), $request->getServerParams());
        // var_dump(get_class_methods($request));
        // var_dump($handler);
        $response = $handler->handle($request);
        
        

        if(strpos($request->getRequestTarget(), 'telescope') === false){
            $entry = IncomingEntry::make([
                'ip_address' => $request->getServerParams()['remote_addr'],
                'uri' => $request->getRequestTarget(),
                'method' => $request->getMethod(),
                // 'controller_action' => optional($event->request->route())->getActionName(),
                // 'middleware' => array_values(optional($event->request->route())->gatherMiddleware() ?? []),
                'headers' => $request->getHeaders(),
                'payload' => $request->getParsedBody(),
                'session' => '',
                'response_status' => $response->getStatusCode(),
                'response' => $this->response($response),
                // 'duration' => $startTime ? floor((microtime(true) - $startTime) * 1000) : null,
                'memory' => round(memory_get_peak_usage(true) / 1024 / 1025, 1),
            ]);
    
            $batchId = $entry->uuid;
            $entry->batchId($batchId)->type(EntryType::REQUEST);
    
            TelescopeEntryModel::create($entry->toArray());

            $arr = Context::get('query_listener', []);
            $optionSlow = 500;
            foreach($arr as [$event, $sql]) {
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
        }

        return $response;
    }

    protected function response(ResponseInterface $response)
    {
        $content = $response->getBody()->getContents();
        if (is_string($content) && $response->getContentType() == 'application/json') {
            if (is_array(json_decode($content, true)) &&
                json_last_error() === JSON_ERROR_NONE) {
                    return json_decode($content, true);
            }
        }

        return $content;
    }

}
