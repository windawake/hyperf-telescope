<?php

declare(strict_types=1);

namespace Wind\Telescope\Model;

use Hyperf\DbConnection\Model\Model;

class TelescopeEntryTagModel extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'telescope_entries_tags';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'entry_uuid',
        'tag',
    ];

    const CREATED_AT = null;
    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = null;
}
