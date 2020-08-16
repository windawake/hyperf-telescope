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
            'databases' => [
                'telescope' => [
                    'driver' => env('DB_DRIVER', 'mysql'),
                    'host' => env('DB_HOST', 'localhost'),
                    'database' => env('DB_DATABASE', 'hyperf'),
                    'port' => env('DB_PORT', 3306),
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD', ''),
                    'charset' => env('DB_CHARSET', 'utf8'),
                    'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
                    'prefix' => env('DB_PREFIX', ''),
                    'pool' => [
                        'min_connections' => 1,
                        'max_connections' => 10,
                        'connect_timeout' => 10.0,
                        'wait_timeout' => 3.0,
                        'heartbeat' => -1,
                        'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
                    ]
                ],
            ]
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
