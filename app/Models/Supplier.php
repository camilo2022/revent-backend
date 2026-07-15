<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Supplier extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'suppliers';

    protected $guard_name = 'api';

    protected $fillable = [
        'code',
        'legal_name',
        'trade_name',
        'document_type_id',
        'document',
        'location_id',
        'location_type',
        'address',
        'neighborhood',
        'phone_country_id',
        'phone',
        'email'
    ];

    protected $auditInclude = [
        'code',
        'legal_name',
        'trade_name',
        'document_type_id',
        'document',
        'location_id',
        'location_type',
        'address',
        'neighborhood',
        'phone_country_id',
        'phone',
        'email'
    ];

    public function document_type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function location(): MorphTo
    {
        return $this->morphTo();
    }

    public function phone_country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'phone_country_id');
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('legal_name', 'LIKE', '%' . $term . '%')
                        ->orWhere('trade_name', 'LIKE', '%' . $term . '%')
                        ->orWhereHas('document_type', function (Builder $sq) use ($term) {
                            $sq->orWhere('name', 'LIKE', '%' . $term . '%')
                                ->orWhere('description', 'LIKE', '%' . $term . '%');
                        })
                        ->orWhere('document', 'LIKE', '%' . $term . '%')
                        ->orWhere('address', 'LIKE', '%' . $term . '%')
                        ->orWhere('neighborhood', 'LIKE', '%' . $term . '%')
                        ->orWhere('phone', 'LIKE', '%' . $term . '%')
                        ->orWhere('email', 'LIKE', '%' . $term . '%');
                });
            }
        });
    }
}
