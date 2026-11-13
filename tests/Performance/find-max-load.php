#!/usr/bin/env php
<?php

/*
 * Finds the highest number of concurrent users a running site handles before it falls over.
 *
 * It holds N requests in flight for a fixed time, then raises N and repeats. A stage fails when
 * the share of non-200 responses or the p95 latency goes over its limit; the last stage that
 * passed is the maximum load.
 *
 *   php tests/Performance/find-max-load.php --url=http://127.0.0.1:8000
 *
 * Options (all optional):
 *   --url=            site to hit (default http://127.0.0.1:8000)
 *   --paths=          comma separated paths (default /health,/login)
 *   --cookie=         Cookie header of a signed-in session, to load pages behind login
 *                     (e.g. --paths=/dashboard --cookie='readererer_session=...')
 *   --start=  --step=  --max=   concurrency ramp (default 5, 5, 500)
 *   --duration=       seconds per stage (default 15)
 *   --max-error-rate= percent of failed requests allowed (default 1)
 *   --max-p95=        p95 latency allowed in milliseconds (default 2000)
 *   --self-test       check the maths and exit
 *
 * This is a load generator: point it at local, dev or qa, never at production. `php artisan
 * serve` handles one request at a time unless PHP_CLI_SERVER_WORKERS is set, so set it (e.g. 8)
 * to measure something closer to a real server.
 */

function percentile(array $sorted, float $p): float
{
    return $sorted ? $sorted[(int) ceil($p / 100 * count($sorted)) - 1] : 0.0;
}

function run_stage(string $base, array $paths, int $concurrency, int $seconds, string $cookie): array
{
    $mh = curl_multi_init();
    $times = [];
    $failures = [];
    $started = 0;
    $inFlight = 0;
    $deadline = microtime(true) + $seconds;

    $add = function () use ($mh, $base, $paths, $cookie, &$started, &$inFlight) {
        $ch = curl_init($base.$paths[$started++ % count($paths)]);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $cookie ? ["Cookie: $cookie"] : [],
        ]);
        curl_multi_add_handle($mh, $ch);
        $inFlight++;
    };

    for ($i = 0; $i < $concurrency; $i++) {
        $add();
    }

    $begin = microtime(true);
    while ($inFlight > 0) {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 0.1);

        while ($done = curl_multi_info_read($mh)) {
            $ch = $done['handle'];
            $code = $done['result'] === CURLE_OK ? curl_getinfo($ch, CURLINFO_RESPONSE_CODE) : 0;
            $times[] = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000;
            if ($code !== 200) {
                $failures[$code] = ($failures[$code] ?? 0) + 1;
            }
            curl_multi_remove_handle($mh, $ch);
            $inFlight--;
            if (microtime(true) < $deadline) {
                $add();
            }
        }
    }
    $elapsed = microtime(true) - $begin;
    curl_multi_close($mh);

    sort($times);
    $failed = array_sum($failures);

    return [
        'requests' => count($times),
        'rps' => count($times) / $elapsed,
        'error_rate' => $failed / count($times) * 100,
        'failures' => $failures,
        'p50' => percentile($times, 50),
        'p95' => percentile($times, 95),
    ];
}

$o = getopt('', ['url::', 'paths::', 'cookie::', 'start::', 'step::', 'max::', 'duration::', 'max-error-rate::', 'max-p95::', 'self-test']);

if (isset($o['self-test'])) {
    assert(percentile([], 95) === 0.0);
    assert(percentile([10.0], 95) === 10.0);
    assert(percentile(range(1, 100), 95) === 95.0);
    echo "ok\n";
    exit(0);
}

$base = rtrim($o['url'] ?? 'http://127.0.0.1:8000', '/');
$paths = explode(',', $o['paths'] ?? '/health,/login');
$cookie = $o['cookie'] ?? '';
$start = (int) ($o['start'] ?? 5);
$step = (int) ($o['step'] ?? 5);
$max = (int) ($o['max'] ?? 500);
$duration = (int) ($o['duration'] ?? 15);
$maxErrorRate = (float) ($o['max-error-rate'] ?? 1);
$maxP95 = (float) ($o['max-p95'] ?? 2000);

printf("Target %s  paths %s  stage %ds  limits: errors <= %s%%, p95 <= %dms\n\n", $base, implode(' ', $paths), $duration, $maxErrorRate, $maxP95);
printf("%12s %9s %9s %8s %9s %9s  %s\n", 'concurrency', 'requests', 'req/s', 'errors', 'p50 ms', 'p95 ms', 'result');

$best = null;
$reason = null;
for ($n = $start; $n <= $max; $n += $step) {
    $r = run_stage($base, $paths, $n, $duration, $cookie);
    $reason = match (true) {
        $r['error_rate'] > $maxErrorRate => sprintf('errors %.1f%% (%s)', $r['error_rate'], http_build_query($r['failures'], '', ' ')),
        $r['p95'] > $maxP95 => sprintf('p95 %.0fms', $r['p95']),
        default => null,
    };
    printf("%12d %9d %9.1f %7.1f%% %9.0f %9.0f  %s\n", $n, $r['requests'], $r['rps'], $r['error_rate'], $r['p50'], $r['p95'], $reason ? "FAIL: $reason" : 'ok');

    if ($reason) {
        break;
    }
    $best = ['concurrency' => $n] + $r;
    sleep(2);
}

echo "\n";
if (! $best) {
    echo "Failed at the first stage ({$start} concurrent): {$reason}\n";
    exit(1);
}
printf("Maximum load: %d concurrent users, %.1f req/s, p95 %.0fms.%s\n", $best['concurrency'], $best['rps'], $best['p95'], $reason ? " Failed at the next stage: {$reason}." : " No limit found up to --max={$max}; raise it.");
