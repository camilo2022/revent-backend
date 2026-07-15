<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as Auditing;

class File extends Model implements Auditable
{
    use Auditing;

    protected $table = 'files';

    protected $fillable = [
        'model_id',
        'model_type',
        'file_type_id',
        'name',
        'path',
        'mime',
        'extension',
        'size',
        'metadata',
        'settings'
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'file_type_id',
        'name',
        'path',
        'mime',
        'extension',
        'size',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'object'
    ];

    public function getPathAttribute($value): ?string
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function file_type(): BelongsTo
    {
        return $this->belongsTo(FileType::class);
    }
}
