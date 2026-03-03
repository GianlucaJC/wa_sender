<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Throwable;
use Illuminate\Support\Facades\Log;

class VerifyJwtToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Il token può essere passato come parametro GET 'token' (es. /?token=...)
        $tokenString = $request->query('token');

        if (!$tokenString) {
            // O come header 'Authorization: Bearer <token>'
            $tokenString = $request->bearerToken();
        }

        if (!$tokenString) {
            return abort(401, 'Token di autenticazione non fornito.');
        }

        $secret = config('jwt.secret');
        if (!$secret) {
            Log::error('La chiave segreta JWT non è configurata correttamente nel file .env.');
            abort(500, 'Errore di configurazione del server.');
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secret)
        );

        try {
            $token = $config->parser()->parse($tokenString);

            // 1. Valida la firma del token
            $constraints = [
                new SignedWith($config->signer(), $config->signingKey()),
            ];

            if (!$config->validator()->validate($token, ...$constraints)) {
                throw new \Exception('Validazione della firma del token fallita.');
            }

            // 2. Controlla la data di scadenza
            if ($token->isExpired(new \DateTimeImmutable())) {
                throw new \Exception('Token scaduto.');
            }

            // 3. Estrai i dati (claims) dal token
            $claims = $token->claims();

            // Il gestionale deve passare 'wa_id' nel payload del token. 'is_admin' è opzionale.
            $wa_id = $claims->get('wa_id');
            $is_admin = $claims->get('is_admin', false); // Default a false se non presente

            if (!$wa_id) {
                throw new \Exception('Il claim "wa_id" è obbligatorio e mancante nel token.');
            }

            // 4. Salva i dati in sessione per l'uso nei controller
            session([
                'wa_id' => $wa_id,
                'is_admin' => (bool) $is_admin,
                'jwt_token' => $tokenString,
            ]);

        } catch (Throwable $e) {
            Log::error('Errore di validazione JWT: ' . $e->getMessage());
            return abort(403, 'Token non valido o scaduto.');
        }

        return $next($request);
    }
}
