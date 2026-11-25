<?php

declare(strict_types=1);

namespace Card2K\Webhook;

use Card2K\Model\Event;
use JsonException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Kiểm tra webhook shop gửi tới địa chỉ webhook của kết nối.
 *
 * Shop ký `X-Signature-V2: sha256=HMAC-SHA256("{X-Webhook-Timestamp}.{body}", api_key)`. SDK kiểm
 * chữ ký (so sánh hằng thời gian) và thời điểm gửi (mặc định lệch tối đa 5 phút, chống gửi lại
 * webhook cũ), rồi trả về sự kiện.
 *
 * ```php
 * try {
 *     $event = Card2K\Webhook\Webhook::constructEvent(file_get_contents('php://input'), getallheaders(), $apiKey);
 * } catch (Card2K\Webhook\SignatureVerificationException) {
 *     http_response_code(400);
 *     exit;
 * }
 * ```
 *
 * Shop có thể gửi một sự kiện nhiều lần (khi không nhận được 2xx): dùng `event_id` để bỏ qua
 * sự kiện đã xử lý. Trả 2xx thật nhanh, xử lý nặng thì đưa vào hàng đợi.
 */
final class Webhook
{
    /** Số giây lệch tối đa giữa `X-Webhook-Timestamp` và giờ máy bạn. */
    public const DEFAULT_TOLERANCE = 300;

    private function __construct()
    {
    }

    /**
     * Kiểm tra rồi giải mã một webhook.
     *
     * @param  string  $payload  body nguyên văn (đừng `json_decode` rồi `json_encode` lại)
     * @param  array<array-key, mixed>  $headers  header của request: `getallheaders()`, `$_SERVER`, hoặc mảng của framework
     * @param  string  $apiKey  key của kết nối
     * @param  int  $tolerance  số giây lệch tối đa; 0 = không kiểm thời điểm (không khuyên dùng)
     *
     * @throws SignatureVerificationException
     */
    public static function constructEvent(string $payload, array $headers, string $apiKey, int $tolerance = self::DEFAULT_TOLERANCE, ?int $now = null): Event
    {
        self::verify($payload, $headers, $apiKey, $tolerance, $now);

        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new SignatureVerificationException('Body webhook không phải JSON.');
        }

        if (! is_array($decoded) || ! is_string($decoded['event'] ?? null)) {
            throw new SignatureVerificationException('Body webhook không có `event`.');
        }

        $raw = [];
        foreach ($decoded as $key => $value) {
            $raw[(string) $key] = $value;
        }

        return Event::from($raw);
    }

    /**
     * Như {@see constructEvent()}, đọc body và header từ một request PSR-7 (Laravel, Symfony, Slim…).
     *
     * @throws SignatureVerificationException
     */
    public static function constructEventFromRequest(ServerRequestInterface $request, string $apiKey, int $tolerance = self::DEFAULT_TOLERANCE, ?int $now = null): Event
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[(string) $name] = array_values(array_map('strval', $values));
        }

        return self::constructEvent((string) $request->getBody(), $headers, $apiKey, $tolerance, $now);
    }

    /**
     * Chỉ kiểm tra chữ ký và thời điểm.
     *
     * @param  array<array-key, mixed>  $headers
     *
     * @throws SignatureVerificationException
     */
    public static function verify(string $payload, array $headers, string $apiKey, int $tolerance = self::DEFAULT_TOLERANCE, ?int $now = null): void
    {
        if ($apiKey === '') {
            throw new SignatureVerificationException('Thiếu API key để kiểm chữ ký.');
        }

        $headers = self::normalize($headers);
        $timestamp = $headers['x-webhook-timestamp'] ?? '';
        $signature = $headers['x-signature-v2'] ?? '';

        if ($signature === '' || ! ctype_digit($timestamp)) {
            throw new SignatureVerificationException('Thiếu header X-Signature-V2 hoặc X-Webhook-Timestamp.');
        }

        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$payload, $apiKey);
        if (! hash_equals($expected, $signature)) {
            throw new SignatureVerificationException('Chữ ký webhook không đúng.');
        }

        if ($tolerance > 0 && abs(($now ?? time()) - (int) $timestamp) > $tolerance) {
            throw new SignatureVerificationException('Webhook quá cũ hoặc giờ máy lệch quá '.$tolerance.' giây.');
        }
    }

    /**
     * Tên header chữ thường, `HTTP_X_SIGNATURE_V2` (kiểu `$_SERVER`) thành `x-signature-v2`.
     *
     * @param  array<array-key, mixed>  $headers
     * @return array<string, string>
     */
    private static function normalize(array $headers): array
    {
        $result = [];
        foreach ($headers as $name => $value) {
            $name = strtolower((string) $name);
            if (str_starts_with($name, 'http_')) {
                $name = str_replace('_', '-', substr($name, 5));
            }
            if (is_array($value)) {
                $value = $value[0] ?? '';
            }
            if (is_string($value)) {
                $result[$name] = trim($value);
            }
        }

        return $result;
    }
}
