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
namespace Wind\Telescope;

class ConfigProvider
{
    public function __invoke(): array
    {
        if (env('TELESCOPE_ENABLED') === false) {
            return [];
        }

        $config = [
            'dependencies' => [
                \Hyperf\HttpServer\Server::class => \Wind\Telescope\Core\Server::class,
            ],
            'commands' => [
                \Wind\Telescope\Command\ClearCommand::class,
                \Wind\Telescope\Command\InstallCommand::class,
            ],
            'view' => [
                'engine' => \Wind\Telescope\TemplateEngine::class,
                'mode' => \Hyperf\View\Mode::SYNC,
                'config' => [
                    'view_path' => BASE_PATH . '/vendor/windawake/hyperf-telescope/storage/view/',
                    'cache_path' => BASE_PATH . '/runtime/view/',
                ],
            ],
            'server' => [
                'settings' => [
                    // 静态资源
                    'document_root' => BASE_PATH . '/vendor/windawake/hyperf-telescope/public',
                    'enable_static_handler' => true,
                ],
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'exceptions' => [
                'handler' => [
                    'http' => [
                        \Wind\Telescope\Exception\ErrorRecord::class,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'telescope',
                    'description' => 'hyperf telescope', // 描述
                    // 建议默认配置放在 publish 文件夹中，文件命名和组件名称相同
                    'source' => __DIR__ . '/../migrations/2020_08_03_064816_telescope_entries.php',  // 对应的配置文件路径
                    'destination' => BASE_PATH . '/migrations/2020_08_03_064816_telescope_entries.php', // 复制为这个路径下的该文件
                ],
            ],
            'processes' => [
                \Hyperf\Crontab\Process\CrontabDispatcherProcess::class,
            ],
            'crontab' => [
                'enable' => true,
                // 通过配置文件定义的定时任务
                'crontab' => [
                    // Callback类型定时任务（默认）
                    (new \Hyperf\Crontab\Crontab())->setName('Process')->setRule('*/3 * * * * *')->setCallback([\Wind\Telescope\Task\DatabaseTask::class, 'execute'])->setMemo('每三秒执行一次写入数据库'),
                ],
            ],
            'linklist' => (new Linklist()),
        ];

        if (class_exists(\Hyperf\JsonRpc\TcpServer::class)) {

            $config['dependencies'][\Hyperf\JsonRpc\TcpServer::class] = \Wind\Telescope\Core\RpcServer::class;

            $config['exceptions']['handler']['jsonrpc'] = [
                \Wind\Telescope\Exception\RpcErrorRecord::class,
            ];
        }

        return $config;
    }
}
