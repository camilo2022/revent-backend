<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Person extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'names',
        'last_names',
        'document_type_id',
        'document',
        'gender_id',
        'birth_date',
        'blood_type_id',
        'location_id',
        'location_type',
        'address',
        'neighborhood',
        'phone_country_id',
        'phone',
        'email'
    ];

    protected $auditInclude = [
        'names',
        'last_names',
        'document_type_id',
        'document',
        'gender_id',
        'birth_date',
        'blood_type_id',
        'location_id',
        'location_type',
        'address',
        'neighborhood',
        'phone_country_id',
        'phone',
        'email'
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d'
    ];

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function document_type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function blood_type(): BelongsTo
    {
        return $this->belongsTo(BloodType::class);
    }

    public function location(): MorphTo
    {
        return $this->morphTo();
    }

    public function phone_country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'phone_country_id');
    }

    public function photo(): MorphOne
    {
        return $this->morphOne(File::class, 'model');
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('names', 'LIKE', '%' . $term . '%')
                        ->orWhere('last_names', 'LIKE', '%' . $term . '%')
                        ->orWhereHas('document_type', function (Builder $sq) use ($term) {
                            $sq->orWhere('name', 'LIKE', '%' . $term . '%')
                                ->orWhere('description', 'LIKE', '%' . $term . '%');
                        })
                        ->orWhere('document', 'LIKE', '%' . $term . '%')
                        ->orWhere('birth_date', 'LIKE', '%' . $term . '%')
                        ->orWhereHas('blood_type', function (Builder $sq) use ($term) {
                            $sq->orWhere('name', 'LIKE', '%' . $term . '%')
                                ->orWhere('description', 'LIKE', '%' . $term . '%');
                        })
                        ->orWhere('address', 'LIKE', '%' . $term . '%')
                        ->orWhere('neighborhood', 'LIKE', '%' . $term . '%')
                        ->orWhereHas('gender', function (Builder $sq) use ($term) {
                            $sq->orWhere('name', 'LIKE', '%' . $term . '%')
                                ->orWhere('description', 'LIKE', '%' . $term . '%');
                        })
                        ->orWhere('phone', 'LIKE', '%' . $term . '%')
                        ->orWhere('email', 'LIKE', '%' . $term . '%');
                });
            }
        });
    }
}
