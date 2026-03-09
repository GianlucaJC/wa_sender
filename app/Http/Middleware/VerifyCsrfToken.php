<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Escludiamo l'endpoint del webhook di Meta dalla protezione CSRF,
        // poiché la sua sicurezza è gestita tramite firma digitale (X-Hub-Signature-256).
        'webhooks/meta',
    ];
}
