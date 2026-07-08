<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
use Spatie\Permission\Traits\HasRoles;

class Provider extends Model implements Auditable
{
    use Auditing, HasRoles, SoftDeletes;

    protected $table = 'providers';

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'legal_name',
        'document_type_id',
        'document',
        'location_id',
        'location_type',
        'email',
        'country_id',
        'phone',
        'address',
        'neighborhood',
        'description',
    ];

    protected $auditInclude = [
        'name',
        'business_name',
        'document_type_id',
        'document',
        'location_id',
        'location_type',
        'email',
        'country_id',
        'phone',
        'address',
        'neighborhood',
        'description',
    ];

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';

                $q->where(function (Builder $sq) use ($like) {
                    $sq->where('name', 'LIKE', $like)
                        ->orWhere('business_name', 'LIKE', $like)
                        ->orWhere('document', 'LIKE', $like)
                        ->orWhere('email', 'LIKE', $like)
                        ->orWhere('phone', 'LIKE', $like)
                        ->orWhere('address', 'LIKE', $like)
                        ->orWhere('neighborhood', 'LIKE', $like)
                        ->orWhere('description', 'LIKE', $like);
                });
            }
        });
    }
}
