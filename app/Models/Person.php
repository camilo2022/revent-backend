<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class Person extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'document',
        'names',
        'last_names',
        'gender_id',
        'birth_date',
        'blood_type_id',
        'address',
        'phone'
    ];

    protected $auditInclude = [
        'document',
        'names',
        'last_names',
        'gender_id',
        'birth_date',
        'blood_type_id',
        'address',
        'phone'
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d'
    ];

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function blood_type(): BelongsTo
    {
        return $this->belongsTo(BloodType::class);
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
                    $sq->orWhere('document', 'LIKE', '%' . $term . '%')
                        ->orWhere('names', 'LIKE', '%' . $term . '%')
                        ->orWhere('last_names', 'LIKE', '%' . $term . '%')
                        ->orWhere('birth_date', 'LIKE', '%' . $term . '%')
                        ->orWhere('phone', 'LIKE', '%' . $term . '%')
                        ->orWhereHas('gender', function (Builder $genderQuery) use ($term) {
                            $genderQuery->where('description', 'LIKE', "%{$term}%");
                        })->orWhereHas('blood_type', function (Builder $bloodQuery) use ($term) {
                            $bloodQuery->where('name', 'LIKE', "%{$term}%");
                        });
                });
            }
        });
    }
}
