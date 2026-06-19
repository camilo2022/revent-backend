<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Item extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'items';

    protected $fillable = [
        'name',
        'description',
        'settings'
    ];

    protected $auditInclude = [
        'name',
        'description',
        'settings'
    ];

    protected $casts = [
        'settings' => 'object'
    ];

    public function subitems(): HasMany
    {
        return $this->hasMany(Subitem::class);
    }
}
