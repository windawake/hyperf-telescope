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
use Hyperf\Server\Server;
use Hyperf\Server\SwooleEvent;

return [
    'settings' => [
        'task_worker_num' => 1,
        'task_enable_coroutine' => false,
        // 静态资源
        'document_root' => BASE_PATH . '/vendor/windawake/hyperf-telescope/public',
        'enable_static_handler' => true,
    ],
    'callbacks' => [
        SwooleEvent::ON_TASK => [Hyperf\Framework\Bootstrap\TaskCallback::class, 'onTask'],
        SwooleEvent::ON_FINISH => [Hyperf\Framework\Bootstrap\FinishCallback::class, 'onFinish'],
    ],
];
