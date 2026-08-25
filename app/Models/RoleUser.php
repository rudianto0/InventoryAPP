<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleUser extends Pivot
{
    use SoftDeletes;

    protected $table = 'role_user';

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
