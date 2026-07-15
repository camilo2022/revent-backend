<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable implements Auditable
{
    use HasFactory, Notifiable, HasApiTokens, Auditing, HasRoles, SoftDeletes;

    protected $table = 'users';

    protected $guard_name = 'api';

    protected $fillable = [
        'employee_id',
        'username',
        'password',
    ];

    protected $auditInclude = [
        'employee_id',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected static function  booted()
    {
        static::creating(function ($model) {
            $model->guard_name = 'api';
        });

        static::updating(function ($model) {
            $model->guard_name = 'api';
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere(function (Builder $sq) use ($term) {
                    $sq->orWhere('username', 'LIKE', '%' . $term . '%')
                        ->orWhereHas('employee.person', function (Builder $employeeQuery) use ($term) {
                            $employeeQuery->where('names', 'LIKE', "%{$term}%")
                                ->orWhere('last_names', 'LIKE', "%{$term}%");
                        });
                });
            }
        });
    }
}
