<?php

namespace App\Http\Controllers\Integration\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Flujo común de "pedir enlace de acceso por correo" de los módulos protegidos:
 * (el correo ya llega validado por el FormRequest del módulo), comprueba que esté autorizado y envía un enlace firmado.
 */
trait SendsAccessLink
{
    /**
     * @param array<int,string> $allowedEmails correos autorizados (en minúscula)
     * @param string            $routeName     ruta que se firma
     * @param string            $moduleLabel   nombre del módulo para el mensaje de error
     * @param callable          $mailFactory   fn (string $url): Mailable
     * @param array|callable    $routeParams   parámetros de la ruta firmada, o fn (string $email): array
     */
    protected function sendAccessLink(
        Request $request,
        array $allowedEmails,
        string $routeName,
        string $moduleLabel,
        callable $mailFactory,
        array|callable $routeParams = []
    ): RedirectResponse {
        $email = strtolower(trim($request->input('email')));

        if (!in_array($email, $allowedEmails, true)) {
            return back()
                ->withErrors(['email' => "Este correo no tiene autorización para acceder a {$moduleLabel}."])
                ->withInput();
        }

        try {
            $url = URL::temporarySignedRoute(
                $routeName,
                now()->addHours(config('siigo.access.link_ttl_hours')),
                is_callable($routeParams) ? $routeParams($email) : $routeParams
            );

            /** @var Mailable $mail */
            $mail = $mailFactory($url);

            Mail::to($email)->send($mail);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['email' => 'No fue posible enviar el enlace de acceso. Intenta nuevamente en unos minutos.'])
                ->withInput();
        }

        return back()->with('status', 'Te enviamos el enlace de acceso a ' . $email . '. Revisa tu