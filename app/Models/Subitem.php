<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Subitem extends Model implements Auditable
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
