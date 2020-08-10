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
namespace Wind\Telescope\Command;

use Hyperf\Command\Command;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class InstallCommand extends Command
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('telescope:install');

        $this->container = $container;
    }

    public function handle()
    {
        /** @var \Symfony\Component\Console\Application $application */
        $application = $this->container->get(\Hyperf\Contract\ApplicationInterface::class);
        $application->setAutoExit(false);

        $output = new NullOutput();

        $input = new ArrayInput(['command' => 'vendor:publish', 'package' => 'windawake/hyperf-telescope']);
        $exitCode = $application->run($input, $output);
        if (! $exitCode) {
            $this->info('publish successfully');
        } else {
            $this->error('publish failed');
        }

        $input = new ArrayInput(['command' => 'migrate']);
        $exitCode = $application->run($input, $output);
        if (! $exitCode) {
            $this->info('migrate successfully');
        } else {
            $this->error('migrate failed');
        }
    }
}
