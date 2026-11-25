<?php

declare(strict_types=1);

/*
 * So bản chụp resources/openapi.json với spec đang chạy trên shop.
 *
 *     php bin/check-spec.php [url]          # mặc định https://card2k.net/tai-lieu-api/openapi
 *     php bin/check-spec.php --update [url] # ghi đè bản chụp bằng bản trên shop
 *
 * Chỉ so `paths` và `components` (shop tự điền tên và địa chỉ vào `info`, `servers`).
 * Lệch thì thoát mã 1 và in endpoint / schema khác nhau: cập nhật bản chụp, chạy
 * `php bin/generate-models.php`, thêm method vào service nếu có endpoint mới, rồi chạy test.
 */

$arguments = array_slice($argv, 1);
$update = in_array('--update', $arguments, true);
$arguments = array_values(array_filter($arguments, static fn (string $argument): bool => $argument !== '--update'));
$url = $arguments[0] ?? 'https://card2k.net/tai-lieu-api/openapi';
$snapshotPath = __DIR__.'/../resources/openapi.json';

$context = stream_context_create(['http' => [
    'timeout' => 20,
    'header' => "Accept: application/json\r\nUser-Agent: card2k-php-spec-check\r\n",
]]);
$live = @file_get_contents($url, false, $context);
if ($live === false) {
    fwrite(STDERR, "Không tải được {$url}\n");
    exit(2);
}

$liveSpec = json_decode($live, true);
$snapshot = json_decode((string) file_get_contents($snapshotPath), true);
if (! is_array($liveSpec) || ! is_array($snapshot)) {
    fwrite(STDERR, "Spec không phải JSON.\n");
    exit(2);
}

/**
 * @param  array<mixed>  $spec
 * @return array<string, string> khóa => JSON của phần đó
 */
function parts(array $spec): array
{
    $parts = [];
    foreach (is_array($spec['paths'] ?? null) ? $spec['paths'] : [] as $path => $operations) {
        $parts['path '.$path] = (string) json_encode(sortKeys($operations));
    }
    $components = is_array($spec['components'] ?? null) ? $spec['components'] : [];
    foreach ($components as $group => $items) {
        foreach (is_array($items) ? $items : [] as $name => $item) {
            $parts["{$group} {$name}"] = (string) json_encode(sortKeys($item));
        }
    }

    return $parts;
}

function sortKeys(mixed $value): mixed
{
    if (! is_array($value)) {
        return $value;
    }
    if (! array_is_list($value)) {
        ksort($value);
    }

    return array_map('sortKeys', $value);
}

$have = parts($snapshot);
$want = parts($liveSpec);
$added = array_keys(array_diff_key($want, $have));
$removed = array_keys(array_diff_key($have, $want));
$changed = array_keys(array_filter(array_intersect_key($want, $have), static fn (string $json, string $key): bool => $json !== $have[$key], ARRAY_FILTER_USE_BOTH));

if ($added === [] && $removed === [] && $changed === []) {
    echo "Bản chụp khớp spec trên {$url}.\n";
    exit(0);
}

foreach (['Mới trên shop' => $added, 'Shop đã bỏ' => $removed, 'Đã đổi' => $changed] as $title => $keys) {
    if ($keys !== []) {
        echo "{$title}:\n  ".implode("\n  ", $keys)."\n";
    }
}

if ($update) {
    file_put_contents($snapshotPath, json_encode($liveSpec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
    echo "Đã cập nhật resources/openapi.json. Chạy tiếp: php bin/generate-models.php && composer check\n";
    exit(0);
}

echo "Bản chụp lệch spec trên shop: chạy `php bin/check-spec.php --update`.\n";
exit(1);
