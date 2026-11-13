<?php

namespace Tests\Feature\Integration;

use App\Mail\InvoicePurchaseOrderConfirmedSiigoMail;
use App\Models\Store;
use App\Support\Integration\ReceptionMailCopy;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Integration\Concerns\G1TestHelpers;
use Tests\TestCase;

/** La recepción de una orden de compra sale con copia al correo de la tienda que recibe (web y API). */
class ReceptionMailCopyTest extends TestCase
{
    use DatabaseTransactions, G1TestHelpers;

    private const CONFIRM = '/api/integration/purchase_order_reception/confirm';

    private const ALEGRA_USER = 735; // usuario de Siigo que solo está en la bodega 3 (Alegra)

    public function test_el_usuario_de_una_tienda_lleva_copia_al_correo_de_su_tienda(): void
    {
        $this->assertSame(['parquealegra@revent.com.co'], ReceptionMailCopy::forSiigoUser(self::ALEGRA_USER));
    }

    public function test_la_web_no_usa_la_base_de_datos_solo_el_array_de_bodegas(): void
    {
        Store::where('siigo_warehouse_id', 3)->update(['email' => 'tienda.alegra@revent.com.co']);

        $this->assertSame(['parquealegra@revent.com.co'], ReceptionMailCopy::forSiigoUser(self::ALEGRA_USER));
    }

    public function test_los_administradores_no_llevan_copia_y_el_destinatario_no_se_repite(): void
    {
        $this->assertSame([], ReceptionMailCopy::forSiigoUser(597)); // está en todas las bodegas
        $this->assertSame([], ReceptionMailCopy::forSiigoUser(self::ALEGRA_USER, ['PARQUEALEGRA@revent.com.co']));
    }

    public function test_dos_tiendas_pueden_compartir_el_mismo_correo(): void
    {
        Store::whereIn('siigo_warehouse_id', [3, 9])->update(['email' => 'compartido@revent.com.co']);

        $this->assertSame(2, Store::where('email', 'compartido@revent.com.co')->count());
    }

    public function test_la_api_envia_la_recepcion_con_copia_a_la_tienda_del_usuario(): void
    {
        Storage::fake('public');
        Mail::fake();
        $this->fakeSiigoUser();
        $this->actingWith(['integration.purchase_order_reception.confirm']); // usuario `alegra`

        $this->post(self::CONFIRM, [
            'token' => $this->jwt(),
            'receiving' => $this->receiving(),
            'imagenes' => [UploadedFile::fake()->image('e.jpg')],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.reception.cc', ['parquealegra@revent.com.co']);

        Mail::assertSent(InvoicePurchaseOrderConfirmedSiigoMail::class, fn ($mail) => $mail->hasTo(config('siigo.mail.purchase_reception')) && $mail->hasCc('parquealegra@revent.com.co'));
    }

    public function test_la_web_envia_la_recepcion_con_copia_segun_el_usuario_del_token(): void
    {
        Storage::fake('public');
        Mail::fake();
        $this->fakeSiigoUser(true, self::ALEGRA_USER);

        $this->withoutMiddleware(VerifyCsrfToken::class)->post('/siigo/invoice_purchase_order/confirmed', [
            'token' => $this->jwt(),
            'receivingData' => json_encode($this->receiving()),
            'imagenes' => [UploadedFile::fake()->image('e.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        Mail::assertSent(InvoicePurchaseOrderConfirmedSiigoMail::class, fn ($mail) => $mail->hasTo(config('siigo.mail.purchase_reception')) && $mail->hasTo('parquealegra@revent.com.co'));
    }

    private function receiving(): array
    {
        return [
            'Order' => ['ACEntryID' => 123, 'DocName' => 'OC-1-100', 'DocDate' => '2026-11-01', 'Warehouse' => '3 - ALEGRA'],
            'Items' => [['ProductCode' => 'P-1', 'WarehouseCode' => 3, 'Quantity' => 5, 'ReceivingQuantity' => 5, 'PendingQuantity' => 0]],
            'Totals' => ['Requested' => 5, 'Receiving' => 5, 'Pending' => 0],
            'Checklist' => [
                'cajas_master' => true, 'sellos' => true, 'estado_cajas' => true, 'documentos' => true, 'empaque' => true,
                'stickers' => true, 'producto' => true, 'exactitud' => true, 'variacion_diseno' => false, 'firma' => true,
                'reporte_whatsapp' => true, 'respaldo_whatsapp' => true, 'alerta' => false, 'venta' => false,
                'referencias_recibidas' => 'REF1', 'observaciones' => 'Todo bien',
                'responsable' => ['nombre' => 'Ana', 'cargo' => 'Auxiliar', 'departamento' => 'Bodega', 'celular' => '3000000000'],
            ],
        ];
    }
}
