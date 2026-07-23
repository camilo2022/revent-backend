<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class ProductionOrder extends Model implements Auditable
{
    use Auditing;

    public const PENDING = 'Pendiente';
    public const APPROVED = 'Aprobado';
    public const CANCELED = 'Cancelado';

    protected $table = 'production_orders';

    protected $guard_name = 'api';

    protected $fillable = [
        'consecutive',
        'due_date',
        'supplier_id',
        'vat_percentage',
        'delivery_note_percentage',
        'status'
    ];

    protected $auditInclude = [
        'consecutive',
        'due_date',
        'supplier_id',
        'vat_percentage',
        'delivery_note_percentage',
        'status'
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function production_order_details(): HasMany
    {
        return $this->hasMany(ProductionOrderDetail::class);
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

                    $sq->where('consecutive', 'LIKE', $like)
                        ->orWhere('due_date', 'LIKE', $like)
                        ->orWhere('vat_percentage', 'LIKE', $like)
                        ->orWhere('delivery_note_percentage', 'LIKE', $like)
                        ->orWhere('status', 'LIKE', $like)
                        ->orWhereHas('supplier', function (Builder $q) use ($like) {
                            $q->where('legal_name', 'LIKE', $like)
                            ->orWhere('trade_name', 'LIKE', $like);
                        });
                });
            }
        });
    }

    public static function statuses(): array
    {
        return [
            self::PENDING,
            self::APPROVED,
            self::CANCELED
        ];
    }
}
