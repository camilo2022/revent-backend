<?php
// app/Console/Commands/CacheProductsSiigoScheduled.php

namespace App\Console\Commands;

use App\Jobs\CacheProductsSiigoJob;
use Illuminate\Console\Command;

class CacheProductsSiigoScheduled extends Command
{
    protected $signature = 'siigo:cache-products-scheduled';

    protected $description = 'Despacha a la cola el job que recachea el catálogo completo de productos de Siigo (PRODUCTS-SIIGO)';

    public function handle(): int
    {
        CacheProductsSiigoJob::dispatch();
        
        return self::SUCCESS;
    }
}
