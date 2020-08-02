<?php
namespace Wind\Telescope;

class Str
{
    public static function orderedUuid(){
        return session_create_id();
    }
}
