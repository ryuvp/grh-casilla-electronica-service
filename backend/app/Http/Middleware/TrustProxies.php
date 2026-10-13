<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Proxies de confianza.
     *
     * Antes estaba en null: Laravel ignoraba X-Forwarded-For y $request->ip()
     * devolvia la IP del sidecar nginx / del ingress, identica para todas las
     * peticiones. Eso rompia dos cosas a la vez:
     *   - el fallback por IP del RateLimiter (un unico cupo global, ver
     *     RouteServiceProvider), y
     *   - la IP registrada en logs de auditoria y de autenticacion.
     *
     * El default confia solo en los rangos privados (RFC1918 + loopback +
     * link-local IPv6), que es por donde entra el trafico dentro del cluster.
     * No se usa '*' para que un cliente externo no pueda falsear su IP con una
     * cabecera X-Forwarded-For arbitraria. TRUSTED_PROXIES permite ajustarlo
     * por ambiente (lista separada por comas, o '*' si se acepta ese riesgo).
     */
    public function __construct()
    {
        $configured = trim((string) env('TRUSTED_PROXIES'));

        // '*' significa "creer la cabecera X-Forwarded-For de CUALQUIERA", y eso
        // permite que un cliente declare la IP que quiera: salta el cupo por IP
        // del RateLimiter cambiando de IP inventada en cada peticion y falsifica
        // la IP de los logs de auditoria. No se puede falsear a traves del
        // ingress (que sobreescribe la cabecera), pero si desde dentro del
        // cluster o golpeando el Service directamente.
        //
        // Se encontro '*' puesto en los ConfigMap de produccion de sgd y mpv, asi
        // que NO se honra a menos que alguien lo pida dos veces, de forma
        // explicita, con TRUSTED_PROXIES_ALLOW_ANY=true. Un '*' suelto deja un
        // aviso en el log y cae a los rangos privados, que es el comportamiento
        // correcto dentro de un cluster.
        if ($configured === '*' || $configured === '**') {
            if (filter_var(env('TRUSTED_PROXIES_ALLOW_ANY', false), FILTER_VALIDATE_BOOLEAN)) {
                $this->proxies = '*';

                return;
            }

            try {
                \Illuminate\Support\Facades\Log::warning(
                    "TrustProxies: se ignora TRUSTED_PROXIES='*' porque permite falsear la IP del cliente. "
                    . 'Se usan los rangos privados. Para forzarlo, TRUSTED_PROXIES_ALLOW_ANY=true.'
                );
            } catch (\Throwable) {
                // Un fallo de logging no debe impedir que la peticion se atienda.
            }

            $configured = '';
        }

        $this->proxies = $configured !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $configured))))
            : [
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
                '127.0.0.1',
                'fc00::/7',
                '::1',
            ];
    }
}
