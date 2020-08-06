<?php

declare(strict_types=1);

namespace Wind\Telescope;

class Str
{
    public static function orderedUuid()
    {
        return session_create_id();
    }
}
