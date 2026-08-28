<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Deeper than the framework's own /up: that only proves the app booted.
 * This checks the actual dependencies a request needs to succeed - useful
 * for an uptime monitor or load balancer readiness probe, not meant to be
 * hit on every request.
 */
class HealthController extends Controller
{
    public function show(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('select 1')),
            'cache' => $this->check(function () {
                $key = 'health-check:'.Str::random(8);
                Cache::put($key, true, 5);

                if (Cache::get($key) !== true) {
                    throw new \RuntimeException('Cache round-trip failed.');
                }

                Cache::forget($key);
            }),
            'queue' => $this->check(function () {
                if (config('queue.default') === 'redis') {
                    Redis::connection(config('queue.connections.redis.connection', 'default'))->ping();
                }
            }),
            'storage' => $this->check(function () {
                $disk = Storage::disk(config('filesystems.default'));
                $path = 'health-check-'.Str::random(8).'.txt';
                $disk->put($path, 'ok');

                if (! $disk->exists($path)) {
                    throw new \RuntimeException('Storage round-trip failed.');
                }

                $disk->delete($path);
            }),
            'mail' => $this->check(function () {
                if (empty(config('mail.default'))) {
                    throw new \RuntimeException('No default mail driver configured.');
                }
            }),
        ];

        $healthy = ! in_array(false, array_column($checks, 'ok'), true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * @return array{ok: bool, error: string|null}
     */
    private function check(callable $probe): array
    {
        try {
            $probe();

            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
