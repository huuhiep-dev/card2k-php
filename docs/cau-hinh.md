# Cấu hình, đọc dữ liệu, test

## Cấu hình

```php
$card2k = new Card2K\Client(getenv('CARD2K_API_KEY'), [
    'timeout' => 20,              // giây, khi SDK tự tạo Guzzle (mặc định 30)
    'max_retries' => 3,           // mặc định 2; 0 = không thử lại
    'max_retry_delay' => 10,      // Retry-After lâu hơn thì ném lỗi luôn (mặc định 20 giây)
    'user_agent' => 'shop-cua-toi/2.1',
    'logger' => $psr3Logger,      // ghi mỗi lần gọi: phương thức, đường dẫn, mã HTTP, thời gian, X-Request-Id
]);

// Hoặc dùng client HTTP (PSR-18) của bạn: proxy, log…
$card2k = new Card2K\Client($key, ['http_client' => new GuzzleHttp\Client(['timeout' => 20, 'proxy' => '…'])]);
```

- Shop chạy trên tên miền khác card2k.net thì thêm `'base_url' => 'https://ten-mien-shop'`.
- SDK cần một thư viện HTTP chuẩn PSR-18. Có Guzzle (Laravel có sẵn) thì SDK dùng Guzzle; không thì SDK tự tìm (Symfony HttpClient…).
- `logger` không bao giờ ghi key, body hay mã thẻ.
- Mã HTTP của lần gọi vừa rồi (201 tạo mới, 200 gửi lại): `$card2k->lastResponse()`.

## Đọc dữ liệu

Mọi đối tượng trả về (`Order`, `CardExchange`, `Deposit`…) **chỉ đọc**:

```php
$order->status;              // thuộc tính (IDE gợi ý đủ field)
$order['status'];            // như mảng
$order->get('field_moi');    // field shop mới thêm, SDK chưa khai báo
$order->toArray();           // nguyên JSON
json_encode($order);
```

API v3 chỉ **thêm** field, endpoint, mã lỗi, không đổi hay bỏ thứ đã có, nên bản SDK cũ vẫn chạy khi shop nâng cấp.

## Test code của bạn

Truyền một client PSR-18 giả để không gọi thật:

```php
$http = new Http\Mock\Client(); // php-http/mock-client
$http->addResponse(new Nyholm\Psr7\Response(201, [], json_encode(['data' => ['code' => 'DH1', 'status' => 'completed']])));

$card2k = new Card2K\Client('key-gia', ['http_client' => $http]);
```

Với Laravel, xem mục "Test" trong [laravel.md](laravel.md).
