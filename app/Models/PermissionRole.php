<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermissionRole extends Pivot
{
    use SoftDeletes;

    protected $table = 'permission_role';

    /**
     * The pivot table now has its own auto-incrementing surrogate primary key.
     *
     * @var bool
     */
    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }
}
