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

use Hyperf\HttpServer\Router\Router;
use Hyperf\Utils\ApplicationContext;

class ConfigProvider
{
    public function __invoke(): array
    {
        // /** @var ContainerInterface $container */
        // $container = ApplicationContext::getContainer();
        // $router = $container->get(Router::class);
        // $router->get('/demo', '\App\Controller\IndexController@index');

        return [
            'dependencies' => [
            ],
            'commands' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
