<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integration\Credentials\CredentialsCheckRequest;
use App\Http\Resources\Api\Integration\Credentials\CredentialsCheckResource;
use App\Rules\Integration\SiigoMonolithCookie;
use App\Rules\Integration\SiigoUserToken;
use App\Traits\ApiFailure;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Validation\ValidationRule;

class CredentialsController extends Controller
{
    use ApiMessage, ApiResponser, ApiFailure;

    /** Prueba el token y/o la cookie de Siigo y responde para cada uno si sirve y a nombre de quién. */
    public function check(CredentialsCheckRequest $request)
    {
        try {
            $result = [];

            if ($request->filled('token')) {
                $rule = new SiigoUserToken();
                $result['token'] = $this->evaluate($rule, 'token', $request->input('token'), fn () => $rule->user());
            }

            if ($request->filled('cookie')) {
                $rule = new SiigoMonolithCookie();
                $result['cookie'] = $this->evaluate($rule, 'cookie', $request->input('cookie'), fn () => $rule->user());
            }

            return $this->successResponse(new CredentialsCheckResource($result), $this->getMessage('Success'), 200);
        } catch (\Throwable $e) {
            return $this->failure($e);
        }
    }

    private function evaluate(ValidationRule $rule, string $attribute, string $value, callable $user): array
    {
        $message = null;

        $rule->validate($attribute, $value, function (string $error) use (&$message) {
            $message = $error;
        });

        return [
            'valid' => $message === null,
            'message' => $message,
            'user' => $message === null ? $user() : null,
        ];
    }
}
