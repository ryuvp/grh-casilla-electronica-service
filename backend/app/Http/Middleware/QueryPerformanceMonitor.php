<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QueryPerformanceMonitor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo monitorear en desarrollo o cuando se active específicamente
        if (!config('app.debug') && !$request->header('X-Monitor-Queries')) {
            return $next($request);
        }

        // Habilitar query log
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Ejecutar la petición
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        // Calcular métricas
        $executionTime = ($endTime - $startTime) * 1000; // en milisegundos
        $memoryUsage = ($endMemory - $startMemory) / 1024 / 1024; // en MB
        
        // Obtener queries ejecutadas
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalQueryTime = array_sum(array_column($queries, 'time'));
        
        // Detectar queries lentas (más de 100ms)
        $slowQueries = array_filter($queries, function($query) {
            return $query['time'] > 100;
        });
        
        // Preparar datos de performance
        $performanceData = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'execution_time' => round($executionTime, 2),
            'memory_usage' => round($memoryUsage, 2),
            'query_count' => $queryCount,
            'total_query_time' => round($totalQueryTime, 2),
            'slow_queries_count' => count($slowQueries),
            'status_code' => $response->getStatusCode()
        ];
        
        // Agregar headers de performance
        $response->headers->set('X-Execution-Time', $performanceData['execution_time'] . 'ms');
        $response->headers->set('X-Memory-Usage', $performanceData['memory_usage'] . 'MB');
        $response->headers->set('X-Query-Count', $performanceData['query_count']);
        $response->headers->set('X-Query-Time', $performanceData['total_query_time'] . 'ms');
        
        // Log si hay queries lentas o muchas queries
        if (count($slowQueries) > 0 || $queryCount > 20 || $executionTime > 1000) {
            Log::warning('Performance Warning', [
                'performance' => $performanceData,
                'slow_queries' => $slowQueries
            ]);
        }
        
        // Log general de performance (solo en debug)
        if (config('app.debug')) {
            Log::info('Request Performance', $performanceData);
        }
        
        DB::disableQueryLog();
        
        return $response;
    }
}
