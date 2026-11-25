# Card2K PHP SDK

SDK PHP chính thức cho **API v3** của [Card2K](https://card2k.net): nạp game, mua thẻ cào, Sò Garena hàng chờ, đổi thẻ cào, ví (nạp tiền, chuyển ví, lịch sử giao dịch), bảo hành, mã giảm giá và webhook.

- PHP 8.1 trở lên.
- Tự thử lại khi lỗi mạng, bị giới hạn lượt gọi (429) hay shop bảo trì (503), không bao giờ trừ tiền hai lần.
- Lỗi theo mã ổn định (`insufficient_balance`, `scope_not_granted`…), không phải dò câu chữ.
- Kiểm tra chữ ký webhook có chống gửi lại.
- IDE gợi ý đủ field của đơn, thẻ, mã nạp…

Tài liệu đầy đủ của API: https://card2k.net/tai-lieu-api

## Cài đặt

```bash
composer require card2k/card2k-php guzzlehttp/guzzle
```

SDK cần một thư viện HTTP chuẩn PSR-18. Dự án đã có Guzzle (Laravel có sẵn) thì không cần cài thêm. Dùng thư viện khác (Symfony HttpClient…) thì SDK tự nhận.

## Bắt đầu

1. Vào **Tài khoản → Kết nối API**, tạo kết nối loại **API v3 (REST)**, sao chép key.
2. Bật đúng các **quyền của key** hệ thống của bạn cần (mặc định chỉ có Đặt đơn).
3. Lưu key vào biến môi trường, ví dụ `CARD2K_API_KEY`.

```php
use Card2K\Client;

$card2k = new Client(getenv('CARD2K_API_KEY'));

$account = $card2k->account->get();
echo $account->balance;          // Ví mua hàng (VND)
echo $account->exchange_balance; // Ví đổi thẻ (VND)
print_r($account->scopes);       // ['orders', 'card_exchange', …]
```

> Giữ key ở máy chủ của bạn. Không đưa key vào trình duyệt, app điện thoại hay mã nguồn công khai, không ghi key vào log.

## Quyền của key

| Quyền | Cho phép |
|---|---|
| `orders` | Đặt đơn, đổi phí ưu tiên Sò, kiểm tra mã giảm giá |
| `card_exchange` | Gửi thẻ cào để đổi (cần xác thực SĐT nếu shop yêu cầu) |
| `warranty` | Gửi yêu cầu bảo hành |
| `deposit` | Lấy thông tin chuyển khoản nạp Ví mua hàng |
| `wallet_transfer` | Chuyển Ví đổi thẻ sang Ví mua hàng |

Xem sản phẩm, tra đơn, lịch sử giao dịch, sự kiện thì không cần quyền riêng. Gọi lệnh khi chưa bật quyền thì SDK ném `PermissionException` với mã `scope_not_granted`.

## Sản phẩm và đặt đơn

```php
use Card2K\ProductType;

foreach ($card2k->products->list(ProductType::GAME) as $product) {
    echo $product->id, ' ', $product->name, PHP_EOL;
    foreach ($product->packages as $package) {
        echo '  gói ', $package->id, ': ', $package->name, ' – ', $package->price, 'đ', PHP_EOL;
    }
    foreach ($product->fields as $field) {
        echo '  cần điền ô ', $field->id, ': ', $field->label, PHP_EOL;
    }
}
```

Đặt đơn trừ tiền Ví mua hàng. Tham số thứ hai là **mã đơn bên bạn** (Idempotency-Key): gửi lại cùng mã thì chỉ nhận lại đơn cũ, không trừ tiền lần nữa. Nên luôn truyền mã này, ví dụ id đơn trong database của bạn.

```php
$order = $card2k->orders->create([
    'package_id' => 12,
    'quantity' => 1,
    'fields' => [3 => '123456789', 4 => 'Asia'], // [id ô => giá trị]
    'voucher_code' => 'GIAM10',                  // không bắt buộc
], 'DON-1001');

echo $order->code, ' ', $order->status, ' ', $order->total;
```

Thẻ cào giao ngay thì nằm trong `$order->cards`:

```php
foreach ($order->cards as $card) {
    echo $card->serial, ' / ', $card->code, PHP_EOL;
}
```

Sò Garena hàng chờ thì thêm `queue`:

```php
$order = $card2k->orders->create([
    'package_id' => 30,
    'queue' => ['account' => 'ten-tai-khoan', 'priority_percent' => 3], // hoặc 'top' => true
], 'DON-1002');

// Đổi phí ưu tiên sau khi đặt (tiền chênh lệch trừ thêm hoặc hoàn lại)
$result = $card2k->orders->changeQueuePriority($order->code, $order->items[0]->id, ['priority_percent' => 6]);
echo $result->difference;
```

Kiểm tra mã giảm giá trước khi đặt:

```php
$check = $card2k->vouchers->check(['package_id' => 12, 'voucher_code' => 'GIAM10']);
echo $check->discount, ' → còn ', $check->total;
```

### Tra đơn

```php
use Card2K\OrderStatus;

$order = $card2k->orders->get('DH123456');

$page = $card2k->orders->list(['status' => OrderStatus::COMPLETED, 'per_page' => 50]);
foreach ($page as $order) { /* … */ }

// Mọi đơn đổi trạng thái từ một thời điểm (SDK tự sang trang), để đồng bộ khi lỡ webhook
foreach ($card2k->orders->all(['updated_since' => new DateTimeImmutable('-1 hour')]) as $order) {
    // cập nhật đơn $order->reference (= mã đơn bên bạn) theo $order->status
}
```

## Đổi thẻ cào

Tiền nhận được cộng vào **Ví đổi thẻ**. `reference` là mã của bạn cho thẻ đó: gửi lại cùng `reference` thì chỉ nhận lại thẻ cũ.

```php
use Card2K\Telco;
use Card2K\CardExchangeStatus;

$rates = $card2k->cardExchanges->rates();
foreach ($rates->rates as $rate) {
    echo $rate->telco, ' ', $rate->value, ': phí ', $rate->fee_percent, '%, nhận ', $rate->receive_amount, PHP_EOL;
}

$card = $card2k->cardExchanges->create([
    'telco' => Telco::VIETTEL,
    'value' => 50000,           // mệnh giá khai báo
    'serial' => '10012345678901',
    'code' => '212345678901234',
    'reference' => 'THE-5001',
]);

echo $card->id, ' ', $card->status; // pending

// Kết quả tới qua webhook card_exchange.updated, hoặc tra lại:
$card = $card2k->cardExchanges->get($card->id);
if ($card->status === CardExchangeStatus::SUCCESS) {
    echo 'Nhận ', $card->amount, 'đ';
}
```

Phản hồi và webhook **không bao giờ** chứa mã thẻ, chỉ có `serial` và `reference`.

## Ví, nạp tiền, giao dịch

```php
// Chuyển Ví đổi thẻ → Ví mua hàng (một chiều). Tham số 2 là mã chống trùng của bạn.
$transfer = $card2k->wallet->transfer(100000, 'CHUYEN-2026-11-26');
echo $transfer->balance, ' / ', $transfer->exchange_balance;

// Lấy thông tin chuyển khoản để nạp Ví mua hàng (mã đang hiện được dùng lại)
$deposit = $card2k->deposits->create();
echo 'Chuyển khoản với nội dung: ', $deposit->transfer_content, PHP_EOL;
foreach ($deposit->accounts as $bank) {
    echo $bank->bank_label, ' ', $bank->account_number, ' ', $bank->account_name, PHP_EOL;
}

// Lịch sử giao dịch
use Card2K\Wallet;

foreach ($card2k->transactions->all(['wallet' => Wallet::EXCHANGE, 'from' => new DateTimeImmutable('today')]) as $tx) {
    echo $tx->created_at, ' ', $tx->amount, ' ', $tx->description, PHP_EOL;
}
```

## Bảo hành

```php
$claim = $card2k->warrantyClaims->create('DH123456', [
    'item_ids' => [$order->items[0]->id],
    'zalo_phone' => '0900000000',
    'note' => 'Mã báo đã dùng',
]);

foreach ($card2k->warrantyClaims->all(['order_code' => 'DH123456']) as $claim) {
    echo $claim->status, ' ', $claim->reply, PHP_EOL;
}
```

## Webhook

Đặt **địa chỉ webhook** (https) cho kết nối ở trang Kết nối API. Shop gửi `POST` JSON khi đơn đổi trạng thái (`order.updated`), thẻ có kết quả (`card_exchange.updated`), tiền nạp tới (`deposit.completed`), hoặc khi bạn bấm "Gửi webhook thử" (`webhook.test`).

PHP thuần:

```php
use Card2K\Model\CardExchange;
use Card2K\Model\Order;
use Card2K\Webhook\SignatureVerificationException;
use Card2K\Webhook\Webhook;

try {
    $event = Webhook::constructEvent(file_get_contents('php://input'), getallheaders(), getenv('CARD2K_API_KEY'));
} catch (SignatureVerificationException $e) {
    http_response_code(400);
    exit;
}

// Shop có thể gửi lại một sự kiện: bỏ qua event_id đã xử lý.
if (daXuLy($event->event_id)) {
    http_response_code(200);
    exit;
}

if ($event->data instanceof Order) {
    capNhatDon($event->data->reference, $event->data->status);
} elseif ($event->data instanceof CardExchange) {
    capNhatThe($event->data->reference, $event->data->status, $event->data->amount);
}

http_response_code(200);
```

Laravel (route webhook không qua CSRF):

```php
Route::post('/card2k/webhook', function (Illuminate\Http\Request $request) {
    try {
        $event = Card2K\Webhook\Webhook::constructEvent($request->getContent(), $request->headers->all(), config('services.card2k.key'));
    } catch (Card2K\Webhook\SignatureVerificationException) {
        abort(400);
    }

    ProcessCard2KEvent::dispatch($event->toArray()); // xử lý trong hàng đợi, trả 200 ngay

    return response()->noContent();
});
```

SDK kiểm `X-Signature-V2` (HMAC-SHA256 của `timestamp.body` bằng key) và từ chối webhook lệch giờ quá 5 phút. Nhớ đồng bộ giờ máy chủ (NTP).

Webhook không tới được (máy chủ bạn tắt, lỗi…)? Đọc lại sự kiện 30 ngày gần nhất:

```php
$after = docEventIdDaLuu(); // null lần đầu
foreach ($card2k->events->all($after) as $event) {
    xuLy($event);
    luuEventId($event->event_id);
}
```

## Xử lý lỗi

Mọi lỗi đều kế thừa `Card2K\Exception\Card2KException`. Lỗi từ API là `ApiException` (và lớp con theo mã HTTP); xử lý theo `getErrorCode()`.

```php
use Card2K\ErrorCode;
use Card2K\Exception\ApiException;
use Card2K\Exception\InsufficientBalanceException;
use Card2K\Exception\NetworkException;
use Card2K\Exception\ValidationException;

try {
    $order = $card2k->orders->create(['package_id' => 12, 'fields' => [3 => '123']], 'DON-1001');
} catch (InsufficientBalanceException $e) {
    echo 'Thiếu tiền: cần ', $e->required(), ', còn ', $e->balance();
} catch (ValidationException $e) {
    if ($e->getErrorCode() === ErrorCode::INVALID_FIELDS) {
        echo 'Ô ', $e->getDetail('field_id'), ' sai: ', $e->getMessage();
    }
    print_r($e->fields()); // lỗi theo từng ô khi validation_failed
} catch (NetworkException $e) {
    // Không biết shop đã nhận chưa: gửi lại đúng lệnh với cùng mã DON-1001 là an toàn.
} catch (ApiException $e) {
    error_log("Card2K {$e->getStatusCode()} {$e->getErrorCode()} (request {$e->getRequestId()})");
}
```

| Lớp | HTTP | Ví dụ mã |
|---|---|---|
| `BadRequestException` | 400 | `idempotency_key_required` |
| `AuthenticationException` | 401 | `missing_api_key`, `invalid_api_key` |
| `InsufficientBalanceException` | 402 | `insufficient_balance` |
| `PermissionException` | 403 | `scope_not_granted`, `ip_not_allowed`, `phone_verification_required` |
| `NotFoundException` | 404 | `order_not_found`, `card_not_found` |
| `ConflictException` | 409 | `warranty_claim_open` |
| `ValidationException` | 422 | `validation_failed`, `invalid_fields`, `reference_reused` |
| `LockedException` | 423 | `card_exchange_locked` (xem `lockedUntil()`) |
| `RateLimitException` | 429 | `rate_limited` (xem `retryAfter()`) |
| `ServiceUnavailableException` | 503 | `maintenance`, `deposit_unavailable` |
| `ServerException` | 5xx | `server_error` |
| `NetworkException` | — | không kết nối được |

Danh sách mã đầy đủ: `Card2K\ErrorCode`. Shop có thể thêm mã mới, nên luôn có nhánh mặc định.

## Thử lại và chống trùng

SDK tự thử lại tối đa 2 lần (đổi bằng `max_retries`) khi không kết nối được, khi bị 429, 500, 502, 503, 504, hoặc khi cùng mã đang được xử lý (409 `request_in_progress`). SDK chờ theo header `Retry-After` của shop, hoặc chờ tăng dần 0,5 – 1 – 2 giây. Lần thử lại gửi **cùng** Idempotency-Key / reference nên shop chỉ làm một lần.

Không truyền mã chống trùng thì SDK tự tạo một mã ngẫu nhiên. Cách này vẫn an toàn khi SDK tự thử lại, nhưng nếu tiến trình của bạn chết giữa chừng thì bạn không biết mã để tra lại. Vì vậy nên truyền mã của bạn.

## Cấu hình

```php
$card2k = new Client(getenv('CARD2K_API_KEY'), [
    'timeout' => 20,              // giây, khi SDK tự tạo Guzzle (mặc định 30)
    'max_retries' => 3,           // mặc định 2; 0 = không thử lại
    'max_retry_delay' => 10,      // Retry-After lâu hơn thì ném lỗi luôn (mặc định 20 giây)
    'user_agent' => 'shop-cua-toi/2.1',
]);

// Hoặc dùng client HTTP của bạn (proxy, log…)
$card2k = new Client($key, ['http_client' => new GuzzleHttp\Client(['timeout' => 20, 'proxy' => '…'])]);
```

Shop chạy trên tên miền khác card2k.net thì thêm `'base_url' => 'https://ten-mien-shop'`.

Muốn xem mã HTTP của lần gọi vừa rồi (201 tạo mới, 200 gửi lại), dùng `$card2k->lastResponse()`.

## Đọc dữ liệu

Mọi đối tượng trả về (`Order`, `CardExchange`, `Deposit`…) **chỉ đọc**:

```php
$order->status;              // thuộc tính
$order['status'];            // như mảng
$order->get('field_moi');    // field shop mới thêm, SDK chưa khai báo
$order->toArray();           // nguyên JSON
json_encode($order);
```

API v3 chỉ **thêm** field, endpoint, mã lỗi, không đổi hay bỏ thứ đã có, nên bản SDK cũ vẫn chạy được khi shop nâng cấp.

## Test code của bạn

Truyền một client PSR-18 giả để không gọi thật:

```php
$http = new Http\Mock\Client(); // php-http/mock-client
$http->addResponse(new Nyholm\Psr7\Response(201, [], json_encode(['data' => ['code' => 'DH1', 'status' => 'completed']])));

$card2k = new Card2K\Client('key-gia', ['http_client' => $http]);
```

## Phát triển SDK

```bash
composer install
composer check                 # PHPStan + PHPUnit
php bin/check-spec.php         # so resources/openapi.json với spec trên card2k.net
php bin/check-spec.php --update
php bin/generate-models.php    # sinh lại src/Model từ spec
```

`resources/openapi.json` là bản chụp spec API v3 (bản gốc: https://card2k.net/tai-lieu-api/openapi). CI so bản chụp với card2k.net mỗi ngày.

## Giấy phép

MIT.
