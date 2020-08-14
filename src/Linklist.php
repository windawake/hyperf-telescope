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

class Node
{
    public $val;
    public $next;

    public function __construct( $val = null, $next = null )
    {
        $this->val  = $val;
        $this->next = $next;
    }
}

class Linklist
{
    public $head = null;
    public $size = 0;

    public function push( $value ){
        $this->head = new Node($value, $this->head );
        $this->size++;
    }

    public function pop(){
        if($this->size < 1) {
            return false;
        }

        $value = $this->head->val;

        $this->head = $this->head->next;
        $this->size--;

        return $value;
    }

}