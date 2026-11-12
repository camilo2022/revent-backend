<?php

namespace Tests\Feature\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CredentialsCheckTest extends TestCase
{
    use DatabaseTransactions;

    private const URL = '/api/integration/credentials/check';

    private const JWT = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.firma-de-prueba';

    public function test_sin_sesion_responde_401(): void
    {
        $this->postJson(self::URL, ['token' => self::JWT])->assertStatus(401);
    }

    public function test_sin_permiso_responde_403(): void
    {
        // Las tiendas sembradas reciben integration.credentials; un usuario sin ese permiso se queda fuera
        $user = User::where('username', 'alegra')->firstOrFail();
        $user->revokePermissionTo('integration.credentials.check');
        $user->removeRole('integration.credentials');
        Sanctum::actingAs($user->fresh());

        $this->postJson(self::URL, ['token' => self::JWT])->assertStatus(403);
    }

    public function test_con_permiso_prueba_el_token_sin_repetirlo(): void
    {
        Http::fake(['*' => Http::response(['userID' => 597, 'userName' => 'operaciones@revent.com.co', 'UserOptions' => ['userPrincipalName' => 'Operaciones']])]);
        Sanctum::actingAs(User::where('username', 'alegra')->firstOrFail());

        $response = $this->postJson(self::URL, ['token' => self::JWT])->assertOk();

        $response->assertJsonPath('data.credentials.token.valid', true)
            ->assertJsonPath('data.credentials.token.user.id', 597);
        $this->assertStringNotContainsString(self::JWT, $response->getContent());
    }

    public function test_token_rechazado_por_siigo_responde_200_con_valid_false(): void
    {
        Http::fake(['*' => Http::response([], 401)]);
        Sanctum::actingAs(User::where('username', 'alegra')->firstOrFail());

        $this->postJson(self::URL, ['token' => self::JWT])
            ->assertOk()
            ->assertJsonPath('data.credentials.token.valid', false);
    }

    public function test_cookie_incompleta_es_invalida_y_no_llama_a_siigo(): void
    {
        Http::fake();
        Sanctum::actingAs(User::where('username', 'alegra')->firstOrFail());

        $this->postJson(self::URL, ['cookie' => 'TKNSGRDDREVENTCALZADOSAS=' . self::JWT])
            ->assertOk()
            ->assertJsonPath('data.credentials.cookie.valid', false);

        Http::assertNothingSent();
    }

    public function test_exige_enviar_token_o_cookie(): void
    {
        Sanctum::actingAs(User::where('username', 'alegra')->firstOrFail());

        $this->postJson(self::URL, [])->assertStatus(422);
    }
}
