<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // 'remoteauth' no usa el guard nativo de Laravel: no llama a Auth::login(),
            // solo hace $request->merge(['auth_user' => ...]). Por eso $request->user()
            // siempre es null aquí y este limiter caía siempre al fallback por IP,
            // agrupando a todos los usuarios de una misma sede (NAT) en un solo cupo.
            $authUserId = $request->get('auth_user')['id'] ?? null;
            // El prefijo 'casilla:' aísla el contador de este servicio: si comparte Redis y prefijo de
            // caché con otro servicio, un mismo usuario no comparte el cupo entre ambos.

            return Limit::perMinute(600)->by('casilla:' . ($authUserId ?: $request->ip()));
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
