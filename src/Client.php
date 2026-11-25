<?php

declare(strict_types=1);

namespace Card2K;

use Card2K\Http\Transport;
use Card2K\Service\AccountService;
use Card2K\Service\CardExchangeService;
use Card2K\Service\DepositService;
use Card2K\Service\EventService;
use Card2K\Service\OrderService;
use Card2K\Service\ProductService;
use Card2K\Service\TransactionService;
use Card2K\Service\VoucherService;
use Card2K\Service\WalletService;
use Card2K\Service\WarrantyClaimService;
use Closure;
use GuzzleHttp\Client as GuzzleClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Client của API v3.
 *
 * ```php
 * $card2k = new Card2K\Client(getenv('CARD2K_API_KEY'));
 * $order = $card2k->orders->create(['package_id' => 12, 'fields' => [3 => '123456789']], 'DON-1001');
 * ```
 *
 * Giữ key ở máy chủ của bạn (biến môi trường), không đưa vào trình duyệt / app, không ghi log.
 */
final class Client
{
    public const VERSION = '0.2.0';

    public const DEFAULT_BASE_URL = 'https://card2k.net';

    public readonly AccountService $account;

    public readonly ProductService $products;

    public readonly OrderService $orders;

    public readonly WarrantyClaimService $warrantyClaims;

    public readonly EventService $events;

    public readonly TransactionService $transactions;

    public readonly CardExchangeService $cardExchanges;

    public readonly VoucherService $vouchers;

    public readonly DepositService $deposits;

    public readonly WalletService $wallet;

    private readonly Transport $transport;

    /**
     * @param  string  $apiKey  key của kết nối loại "API v3" (Tài khoản → Kết nối API)
     * @param  array{base_url?: string, http_client?: ClientInterface, request_factory?: RequestFactoryInterface, stream_factory?: StreamFactoryInterface, timeout?: float|int, max_retries?: int, max_retry_delay?: float|int, sleep?: callable(float): void, user_agent?: string, logger?: LoggerInterface|null}  $options
     *   - `base_url`: tên miền của shop, mặc định https://card2k.net (bắt buộc https, trừ localhost)
     *   - `http_client`: client PSR-18 của bạn; bỏ trống thì SDK dùng Guzzle nếu có, không thì tự tìm (Symfony…)
     *   - `timeout`: số giây chờ mỗi request khi SDK tự tạo Guzzle, mặc định 30 (kết nối tối đa 10 giây)
     *   - `max_retries`: số lần thử lại khi lỗi mạng / 429 / 5xx, mặc định 2
     *   - `max_retry_delay`: chờ tối đa bao nhiêu giây trước một lần thử lại, mặc định 20
     *   - `user_agent`: thêm vào sau User-Agent của SDK, ví dụ tên hệ thống của bạn
     *   - `logger`: logger PSR-3: mỗi lần gọi ghi phương thức, đường dẫn, mã HTTP, thời gian, X-Request-Id (không ghi key, body, mã thẻ)
     */
    public function __construct(string $apiKey, array $options = [])
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new InvalidArgumentException('Thiếu API key (Tài khoản → Kết nối API, loại API v3).');
        }

        $maxRetries = $options['max_retries'] ?? 2;
        if ($maxRetries < 0) {
            throw new InvalidArgumentException('max_retries phải từ 0 trở lên.');
        }

        $sleep = $options['sleep'] ?? static function (float $seconds): void {
            usleep((int) round($seconds * 1_000_000));
        };

        $userAgent = 'card2k-php/'.self::VERSION.' PHP/'.PHP_VERSION;
        if (isset($options['user_agent']) && trim($options['user_agent']) !== '') {
            $userAgent .= ' '.trim($options['user_agent']);
        }

        $this->transport = new Transport(
            $apiKey,
            self::apiUrl($options['base_url'] ?? self::DEFAULT_BASE_URL),
            $options['http_client'] ?? self::defaultHttpClient((float) ($options['timeout'] ?? 30)),
            $options['request_factory'] ?? Psr17FactoryDiscovery::findRequestFactory(),
            $options['stream_factory'] ?? Psr17FactoryDiscovery::findStreamFactory(),
            $maxRetries,
            (float) ($options['max_retry_delay'] ?? 20),
            Closure::fromCallable($sleep),
            $userAgent,
            $options['logger'] ?? null,
        );

        $this->account = new AccountService($this->transport);
        $this->products = new ProductService($this->transport);
        $this->orders = new OrderService($this->transport);
        $this->warrantyClaims = new WarrantyClaimService($this->transport);
        $this->events = new EventService($this->transport);
        $this->transactions = new TransactionService($this->transport);
        $this->cardExchanges = new CardExchangeService($this->transport);
        $this->vouchers = new VoucherService($this->transport);
        $this->deposits = new DepositService($this->transport);
        $this->wallet = new WalletService($this->transport);
    }

    /**
     * Các service dạng hàm, cho Facade của Laravel (`Card2K::orders()->create(…)`).
     */
    public function account(): AccountService
    {
        return $this->account;
    }

    public function products(): ProductService
    {
        return $this->products;
    }

    public function orders(): OrderService
    {
        return $this->orders;
    }

    public function warrantyClaims(): WarrantyClaimService
    {
        return $this->warrantyClaims;
    }

    public function events(): EventService
    {
        return $this->events;
    }

    public function transactions(): TransactionService
    {
        return $this->transactions;
    }

    public function cardExchanges(): CardExchangeService
    {
        return $this->cardExchanges;
    }

    public function vouchers(): VoucherService
    {
        return $this->vouchers;
    }

    public function deposits(): DepositService
    {
        return $this->deposits;
    }

    public function wallet(): WalletService
    {
        return $this->wallet;
    }

    /**
     * Phản hồi HTTP gần nhất (kể cả lỗi): mã HTTP (201 tạo mới / 200 gửi lại), header `X-Request-Id`…
     */
    public function lastResponse(): ?ResponseInterface
    {
        return $this->transport->lastResponse();
    }

    /**
     * Guzzle có thời gian chờ (Guzzle mặc định chờ mãi), không có Guzzle thì tự tìm client PSR-18.
     */
    private static function defaultHttpClient(float $timeout): ClientInterface
    {
        if (class_exists(GuzzleClient::class)) {
            return new GuzzleClient(['timeout' => $timeout, 'connect_timeout' => min(10.0, $timeout)]);
        }

        return Psr18ClientDiscovery::find();
    }

    /**
     * `https://ten-mien` → `https://ten-mien/api/v3`.
     */
    private static function apiUrl(string $baseUrl): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        $parts = parse_url($baseUrl);
        $scheme = is_array($parts) ? strtolower($parts['scheme'] ?? '') : '';
        $host = is_array($parts) ? strtolower($parts['host'] ?? '') : '';

        if ($host === '' || ! in_array($scheme, ['https', 'http'], true)) {
            throw new InvalidArgumentException("base_url không hợp lệ: {$baseUrl}");
        }

        $local = in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.test');
        if ($scheme === 'http' && ! $local) {
            throw new InvalidArgumentException('base_url phải dùng https (key gửi trong header).');
        }

        return str_ends_with($baseUrl, '/api/v3') ? $baseUrl : $baseUrl.'/api/v3';
    }
}
