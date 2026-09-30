<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class SiigoProductsCache
{
    public const PAGE_PREFIX = 'PRODUCTS-SIIGO:PAGE:';
    public const META_KEY = 'PRODUCTS-SIIGO:META';

    public function all(): array
    {
        ini_set('memory_limit', '-1');
        
        $meta = Cache::get(self::META_KEY);

        if (!is_array($meta)) {
            return [];
        }

        $products = [];

        for ($page = 1; $page <= (int) $meta['pages']; $page++) {
            $pageProducts = Cache::get(self::PAGE_PREFIX . $page);

            if (!is_array($pageProducts)) {
                throw new RuntimeException("Falta la página {$page} del catálogo Siigo.");
            }

            foreach ($pageProducts as $product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    public function keyedByProductId(): Collection
    {
        return collect($this->all())->keyBy('productID');
    }

    public function findByProductId(string|int $productId): ?array
    {
        return $this->keyedByProductId()->get($productId);
    }

    public function page(int $page): array
    {
        return Cache::get(self::PAGE_PREFIX . $page, []);
    }

    public function total(): int
    {
        return (int) (Cache::get(self::META_KEY)['total'] ?? 0);
    }
}
