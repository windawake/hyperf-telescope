<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\View\Engine\BladeEngine;
use Hyperf\View\Mode;
use Wind\Telescope\TemplateEngine;

return [
    'engine' => TemplateEngine::class,
    'mode' => Mode::SYNC,
    'config' => [
        'view_path' => BASE_PATH . env('TELESCOPE_VENDOR') .'/storage/view/',
        'cache_path' => BASE_PATH . '/runtime/view/',
    ],
];
