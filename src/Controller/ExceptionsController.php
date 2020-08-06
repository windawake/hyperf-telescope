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
namespace Wind\Telescope\Controller;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Wind\Telescope\EntryType;

/**
 * @Controller()
 */
class ExceptionsController extends EntryController
{

    /**
     * The entry type for the controller.
     *
     * @return string
     */
    protected function entryType()
    {
        return EntryType::EXCEPTION;
    }

    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher()
    {
        // return RequestWatcher::class;
        return null;
    }

    /**
     * @PostMapping(path="/telescope/telescope-api/exceptions")
     */
    public function list()
    {
        return $this->index();
    }

    /**
     * @GetMapping(path="/telescope/telescope-api/exceptions/{id}")
     */
    public function detail($id)
    {
        return $this->show($id);
    }
}
