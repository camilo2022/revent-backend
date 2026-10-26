<?php

namespace App\Services\Siigo;

/** Enlaces a documentos dentro de Siigo Nube. Las plantillas viven en config/siigo.php. */
class SiigoLinks
{
    public static function payment(int|string $id): string
    {
        return self::make('payment', $id);
    }

    public static function purchaseInvoice(int|string $id): string
    {
        return self::make('purchase_invoice', $id);
    }

    public static function salesInvoice(int|string $id): string
    {
        return self::make('sales_invoice', $id);
    }

    public static function transferNote(int|string $id): string
    {
        return self::make('transfer_note', $id);
    }

    public static function inventoryEntry(int|string $id): string
    {
        return self::make('inventory_entry', $id);
    }

    public static function creditNote(int|string $id): string
    {
        return self::make('credit_note', $id);
    }

    /** Orden de compra: Siigo la abre con una ruta ASPX codificada en base64. */
    public static function purchaseOrder(int|string $id): string
    {
        $tab = config('siigo.links.purchase_order_tab');
        $encoded = base64_encode("Default.aspx?TabID={$tab}&ERPDocumentID={$id}");

        return config('siigo.urls.nube') . "/#/asp/{$encoded}?TabID={$tab}";
    }

    private static function make(string $key, int|string $id): string
    {
        return config('siigo.urls.nube') . str_replace('{id}', (string) $id, config("siigo.links.{$key}"));
    }
}
