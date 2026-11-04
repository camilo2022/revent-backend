<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class TechnicalSheet extends Model implements Auditable
{
    use Auditing, SoftDeletes;

    public const PENDING = 'Pendiente';
    public const REVIEW = 'En revision';
    public const APPROVED = 'Aprobado';
    public const CANCELED = 'Cancelado';

    protected $table = 'technical_sheets';

    protected $guard_name = 'api';

    protected $fillable = [
        'product_id',
        'version',
        'trademark_id',
        'category_id',
        'subcategory_id',
        'process',
        'description',
        'observation',
        'specifications',
        'status',
        'approved_by',
        'approved_at'
    ];

    protected $auditInclude = [
        'product_id',
        'version',
        'trademark_id',
        'category_id',
        'subcategory_id',
        'process',
        'description',
        'observation',
        'specifications',
        'status',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'specifications' => 'array',
        'approved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function trademark(): BelongsTo
    {
        return $this->belongsTo(Trademark::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function production_orders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    // Solo la versión aprobada más reciente de cada producto
    public function scopeOnlyApproved(Builder $query): Builder
    {
        return $query->where('status', self::APPROVED)
            ->whereRaw(
                'version = (select max(ts.version) from technical_sheets ts where ts.product_id = technical_sheets.product_id and ts.status = ? and ts.deleted_at is null)',
                [self::APPROVED]
            );
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);
        if ($search === '') return $query;

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return $query->where(function (Builder $q) use ($terms) {

            foreach ($terms as $term) {

                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';

                $q->where(function (Builder $sq) use ($like) {

                    $sq->where('status', 'LIKE', $like)
                        ->orWhere('process', 'LIKE', $like)
                        ->orWhere('description', 'LIKE', $like)
                        ->orWhereHas('product', function (Builder $q) use ($like) {
                            $q->where('code', 'LIKE', $like)
                                ->orWhere('name', 'LIKE', $like);
                        });
                });
            }
        });
    }

    public static function statuses(): array
    {
        return [
            self::PENDING,
            self::REVIEW,
            self::APPROVED,
            self::CANCELED
        ];
    }
}
