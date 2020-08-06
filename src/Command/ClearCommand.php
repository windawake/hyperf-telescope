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

namespace Wind\Telescope\Command;

use Hyperf\Command\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

class ClearCommand extends Command
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('telescope:clear');

        $this->container = $container;
    }

    public function handle()
    {
        Db::table('telescope_entries')->truncate();
        Db::table('telescope_entries_tags')->truncate();
        Db::table('telescope_monitoring')->truncate();
    }
}
