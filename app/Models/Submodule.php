<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Builder;

class Submodule extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'submodules';

    protected $fillable = [
        'name',
        'url',
        'icon',
        'module_id',
        'permission_id'
    ];

    protected $auditInclude = [
        'name',
        'url',
        'icon',
        'module_id',
        'permission_id'
    ];

    protected $auditEvents = [
        'created',
        'updated',
    ];

    protected $hidden = [
        'module_id',
        'permission_id',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('name', 'LIKE', '%' . $term . '%')
                        ->orWhere('url', 'LIKE', '%' . $term . '%')
                        ->orWhereHas('permission', function ($p) use ($term) {
                            $p->where('name', 'LIKE', "%$term%")
                                ->orWhere('title', 'LIKE', "%$term%");
                        });
                });
            }
        });
    }
}
