# Card2K PHP SDK

[![Packagist](https://img.shields.io/packagist/v/card2k/card2k-php)](https://packagist.org/packages/card2k/card2k-php)
[![PHP](https://img.shields.io/packagist/dependency-v/card2k/card2k-php/php)](composer.json)
[![CI](https://github.com/huuhiep-dev/card2k-php/actions/workflows/ci.yml/badge.svg)](https://github.com/huuhiep-dev/card2k-php/actions/workflows/ci.yml)

Kết nối [Card2K](https://card2k.net) bằng PHP trong vài dòng: nạp game, mua thẻ cào, đổi thẻ cào, ví, bảo hành, webhook. **Không bao giờ trừ tiền hai lần.** Có sẵn hỗ trợ Laravel.

```php
$card2k = new Card2K\Client(); // key từ biến môi trường CARD2K_API_KEY

$order = $card2k->orders->create(['package_id' => 216, 'fields' => [7 => '123456789']], 'DON-1001');
```

## Mục lục

- [Vì sao dùng SDK](#vì-sao-dùng-sdk)
- [Cài đặt](#cài-đặt)
- [Bắt đầu nhanh](#bắt-đầu-nhanh)
- [Laravel](#laravel)
  - [Gọi API](#gọi-api)
  - [Webhook với Laravel](#webhook-với-laravel)
- [Dịch vụ](#dịch-vụ)
  - [Quyền của key](#quyền-của-key)
  - [Tài khoản](#tài-khoản)
  - [Sản phẩm và đặt đơn](#sản-phẩm-và-đặt-đơn)
  - [Tra đơn](#tra-đơn)
  - [Đổi thẻ cào](#đổi-thẻ-cào)
  - [Ví và nạp tiền](#ví-và-nạp-tiền)
  - [Bảo hành](#bảo-hành)
- [Webhook](#webhook)
- [Xử lý lỗi](#xử-lý-lỗi)
- [Thử lại và chống trùng](#thử-lại-và-chống-trùng)
- [Cấu hình](#cấu-hình)
- [Đọc dữ liệu](#đọc-dữ-liệu)
- [Test](#test)
- [Phát triển SDK](#phát-triển-sdk)

## Vì sao dùng SDK

| | Tự gọi API | Dùng SDK |
|---|---|---|
| Mạng lỗi giữa chừng | Tự lưu mã chống trùng, tự gửi lại, dễ trừ tiền 2 lần | Tự gửi lại với cùng mã, shop chỉ làm 1 lần |
| Gọi quá nhanh (429), shop bảo trì (503) | Tự đọc `Retry-After`, tự chờ | Tự chờ và thử lại |
| Webhook | Tự tính HMAC, kiểm thời gian, chống gửi lại | 1 dòng (Laravel: 1 dòng route) |
| Lỗi | Tự đọc JSON, dò mã | Exception theo loại: thiếu tiền, sai dữ liệu… |
| Lọc theo thời gian | Dễ lệch múi giờ | Tự đổi về UTC |
| Field của đơn, thẻ | Đoán tên | IDE gợi ý đủ |

## Cài đặt

```bash
composer require card2k/card2k-php
```

Cần PHP 8.1+. Guzzle được cài kèm (Laravel vốn có sẵn), không phải cài thêm gì.

## Bắt đầu nhanh

1. Ở **Tài khoản → Kết nối API**, tạo kết nối loại **API v3**, bật quyền cần dùng, sao chép key.
2. Lưu key vào biến môi trường `CARD2K_API_KEY` (file `.env`, cấu hình hosting…). SDK tự đọc; muốn truyền tay thì `new Card2K\Client('key')`.
3. Gọi API:

```php
$card2k = new Card2K\Client();

echo $card2k->account->get()->balance; // số dư Ví mua hàng

$order = $card2k->orders->create(['package_id' => 216, 'fields' => [7 => '123456789']], 'DON-1001');
echo $order->status; // pending → completed (qua webhook hoặc $card2k->orders->get($order->code))
```

`216` là id gói, `7` là id ô cần điền (xem [Sản phẩm và đặt đơn](#sản-phẩm-và-đặt-đơn)). `DON-1001` là mã đơn bên bạn: gửi lại cùng mã thì chỉ nhận lại đơn cũ, không trừ tiền lần nữa.

> Giữ key ở máy chủ (biến môi trường). Không đưa vào trình duyệt, app hay mã nguồn công khai.

## Laravel

Laravel tự nhận SDK, không cần đăng ký gì. Hỗ trợ Laravel 10 trở lên (test trên 11, 12, 13).

```dotenv
CARD2K_API_KEY=key-cua-ban
# Không bắt buộc:
CARD2K_LOG_CHANNEL=stack   # ghi mỗi lần gọi API (không ghi key, body, mã thẻ)
CARD2K_TIMEOUT=30
CARD2K_MAX_RETRIES=2
```

Muốn sửa thêm: `php artisan vendor:publish --tag=card2k-config`.

### Gọi API

```php
use Card2K\Laravel\Facades\Card2K;

$order = Card2K::orders()->create(['package_id' => 12, 'fields' => [3 => $uid]], (string) $myOrder->id);
```

Hoặc nhận client qua constructor (dễ test hơn):

```php
final class PlaceCard2KOrder
{
    public function __construct(private Card2K\Client $card2k) {}

    public function handle(Order $myOrder): void
    {
        $this->card2k->orders->create(['package_id' => 12, 'fields' => [3 => $myOrder->uid]], (string) $myOrder->id);
    }
}
```

### Webhook với Laravel

Một dòng route (ở `routes/web.php` hay `routes/api.php`; SDK tự bỏ kiểm tra CSRF cho route này):

```php
Route::card2kWebhook('/card2k/webhook');
// dùng PHPStan / Larastan: Card2K\Laravel\WebhookRoute::register('/card2k/webhook');
```

SDK kiểm chữ ký (sai thì trả 400), bỏ qua `event_id` đã xử lý, rồi phát event Laravel:

| Event | Khi nào |
|---|---|
| `Card2K\Laravel\Events\OrderUpdated` (`$order`, `$event`) | Đơn đổi trạng thái |
| `Card2K\Laravel\Events\CardExchangeUpdated` (`$card`, `$event`) | Thẻ có kết quả |
| `Card2K\Laravel\Events\DepositCompleted` (`$deposit`, `$event`) | Tiền nạp đã tới |
| `Card2K\Laravel\Events\WebhookReceived` (`$event`) | Mọi webhook, kể cả `webhook.test` |

```php
use Card2K\Laravel\Events\OrderUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SyncCard2KOrder implements ShouldQueue
{
    public function handle(OrderUpdated $e): void
    {
        Order::where('id', $e->order->reference)->update(['status' => $e->order->status]);
    }
}
```

Listener nên chạy hàng đợi (`ShouldQueue`) để trả 200 nhanh. Listener chạy ngay mà lỗi thì SDK trả 500 và không nhớ `event_id`: shop gửi lại sau, không mất sự kiện.

## Dịch vụ

Các ví dụ dùng `$card2k = new Card2K\Client();` (Laravel: `Card2K::orders()`… thay cho `$card2k->orders`…). Dữ liệu mẫu chỉ giữ các field chính; đủ mọi field ở [tài liệu API](https://card2k.net/tai-lieu-api).

### Quyền của key

Bật ở **Tài khoản → Kết nối API**. Thiếu quyền thì SDK ném `PermissionException` mã `scope_not_granted`.

| Quyền | Cho phép |
|---|---|
| `orders` | Đặt đơn, đổi phí ưu tiên Sò, kiểm tra mã giảm giá (bật sẵn) |
| `card_exchange` | Gửi thẻ cào để đổi (cần xác thực SĐT nếu shop yêu cầu) |
| `warranty` | Gửi yêu cầu bảo hành |
| `deposit` | Lấy thông tin chuyển khoản nạp Ví mua hàng |
| `wallet_transfer` | Chuyển Ví đổi thẻ sang Ví mua hàng |

Xem sản phẩm, tra đơn, lịch sử giao dịch, sự kiện thì không cần quyền riêng.

### Tài khoản

```php
$account = $card2k->account->get();
echo $account->balance;          // Ví mua hàng (VND)
echo $account->exchange_balance; // Ví đổi thẻ (VND)
print_r($account->scopes);       // ['orders', 'card_exchange', …]
```

### Sản phẩm và đặt đơn

**Bước 1: xem gói và các ô cần điền.** Mỗi sản phẩm có các gói (`packages`, đặt đơn bằng `id` gói) và các ô khách phải điền (`fields`: ID game, server…; gửi theo `id` ô).

```php
use Card2K\ProductType;

$products = $card2k->products->list(ProductType::GAME); // mọi sản phẩm nạp game
$product = $card2k->products->get(21);                   // một sản phẩm
```

<details>
<summary>Xem dữ liệu trả về</summary>

```json
{
  "id": 21,
  "name": "Liên Quân Mobile (Garena)",
  "type": "game",
  "fields": [
    { "id": 7, "label": "UID người chơi", "type": "text", "required": true, "options": [] }
  ],
  "packages": [
    { "id": 216, "name": "40 Quân Huy", "price": 19400, "list_price": 19400, "in_stock": true },
    { "id": 217, "name": "102 Quân huy", "price": 48500, "list_price": 48500, "in_stock": true }
  ]
}
```

</details>

**Bước 2: đặt đơn.** `fields` là `[id ô => giá trị]`; thẻ cào thì không có ô nào.

```php
$order = $card2k->orders->create([
    'package_id' => 216,
    'quantity' => 1,
    'fields' => [7 => '123456789'],
    'voucher_code' => 'GIAM10', // không bắt buộc
], 'DON-1001');

foreach ($order->cards as $card) { // thẻ cào giao ngay
    echo $card->serial, ' / ', $card->code, PHP_EOL;
}
```

<details>
<summary>Xem dữ liệu trả về</summary>

```json
{
  "code": "DH8K2M4PQX",
  "reference": "DON-1001",
  "status": "completed",
  "product": { "id": 21, "name": "Liên Quân Mobile (Garena)", "type": "game" },
  "package": { "id": 216, "name": "40 Quân Huy" },
  "quantity": 1,
  "total": 19400,
  "fields": [{ "id": 7, "label": "UID người chơi", "value": "123456789" }],
  "items": [{ "id": 10429, "status": "completed", "note": null, "cards": [] }],
  "cards": [],
  "created_at": "2026-11-25T18:06:48+00:00",
  "completed_at": "2026-11-25T18:09:10+00:00"
}
```

</details>

Đơn nạp game thường `pending` rồi mới `completed` (hoặc `refunded` kèm `note`): nhận qua [webhook](#webhook) hoặc tra lại bằng `orders->get()`.

Sò Garena hàng chờ thì thêm `queue`, đổi phí ưu tiên được sau khi đặt; kiểm tra mã giảm giá trước khi đặt:

```php
$order = $card2k->orders->create([
    'package_id' => 30,
    'queue' => ['account' => 'ten-tai-khoan', 'priority_percent' => 3], // hoặc 'top' => true
], 'DON-1002');

$result = $card2k->orders->changeQueuePriority($order->code, $order->items[0]->id, ['priority_percent' => 6]); // items[].id của đơn
echo $result->difference; // dương: trừ thêm, âm: hoàn lại

$check = $card2k->vouchers->check(['package_id' => 12, 'voucher_code' => 'GIAM10']);
echo $check->discount, ' → còn ', $check->total;
```

### Tra đơn

```php
use Card2K\OrderStatus;

$order = $card2k->orders->get('DH123456');

$page = $card2k->orders->list(['status' => OrderStatus::COMPLETED, 'per_page' => 50]);
foreach ($page as $order) { /* … */ }

// Mọi đơn đổi trạng thái từ một thời điểm (SDK tự sang trang): đồng bộ khi lỡ webhook
foreach ($card2k->orders->all(['updated_since' => new DateTimeImmutable('-1 hour')]) as $order) {
    // cập nhật đơn $order->reference (= mã đơn bên bạn) theo $order->status
}
```

### Đổi thẻ cào

Tiền nhận được cộng vào **Ví đổi thẻ**. `reference` là mã của bạn cho thẻ: gửi lại cùng `reference` thì chỉ nhận lại thẻ cũ. Phản hồi và webhook **không bao giờ** chứa mã thẻ.

```php
use Card2K\CardExchangeStatus;
use Card2K\Telco;

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

// Kết quả tới qua webhook card_exchange.updated, hoặc tra lại:
$card = $card2k->cardExchanges->get($card->id);
if ($card->status === CardExchangeStatus::SUCCESS) {
    echo 'Nhận ', $card->amount, 'đ';
}
```

<details>
<summary>Xem dữ liệu trả về (thẻ đã có kết quả)</summary>

```json
{
  "id": "457406653011",
  "reference": "THE-5001",
  "status": "success",
  "telco": "VIETTEL",
  "serial": "10012345678901",
  "declared_value": 50000,
  "value": 50000,
  "fee_percent": 18.7,
  "amount": 40650,
  "message": null,
  "completed_at": "2026-11-25T18:12:03+00:00"
}
```

</details>

`status`: `pending` (chờ nhà mạng), `success`, `wrong_value` (sai mệnh giá, bị phạt), `invalid`, `failed`. `amount` là tiền cộng vào Ví đổi thẻ.

### Ví và nạp tiền

```php
use Card2K\Wallet;

// Chuyển Ví đổi thẻ → Ví mua hàng (một chiều). Tham số 2 là mã chống trùng của bạn.
$transfer = $card2k->wallet->transfer(100000, 'CHUYEN-2026-11-26');
echo $transfer->balance, ' / ', $transfer->exchange_balance;

// Thông tin chuyển khoản để nạp Ví mua hàng (mã đang hiện được dùng lại)
$deposit = $card2k->deposits->create();
echo 'Nội dung chuyển khoản: ', $deposit->transfer_content, PHP_EOL;
foreach ($deposit->accounts as $bank) {
    echo $bank->bank_label, ' ', $bank->account_number, ' ', $bank->account_name, PHP_EOL;
}

// Lịch sử giao dịch
foreach ($card2k->transactions->all(['wallet' => Wallet::EXCHANGE, 'from' => new DateTimeImmutable('today')]) as $tx) {
    echo $tx->created_at, ' ', $tx->amount, ' ', $tx->description, PHP_EOL;
}
```

<details>
<summary>Xem dữ liệu trả về của deposits->create()</summary>

```json
{
  "code": "AB12CD",
  "transfer_content": "NAPAB12CD",
  "status": "pending",
  "min_amount": 10000,
  "max_amount": 50000000,
  "expires_at": "2026-11-25T18:40:00+00:00",
  "accounts": [
    { "bank_label": "Vietcombank", "account_number": "0123456789", "account_name": "NGUYEN VAN A", "bin": "970436" }
  ]
}
```

</details>

Chuyển khoản đúng `transfer_content` tới một trong `accounts` là Ví mua hàng được cộng (webhook `deposit.completed`).

### Bảo hành

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

Đặt **địa chỉ webhook** (https) cho kết nối ở trang Kết nối API. Dùng Laravel thì xem [Webhook với Laravel](#webhook-với-laravel).

| Sự kiện | Khi nào | `$event->data` |
|---|---|---|
| `order.updated` | Đơn đổi trạng thái | `Card2K\Model\Order` |
| `card_exchange.updated` | Thẻ có kết quả | `Card2K\Model\CardExchange` |
| `deposit.completed` | Tiền nạp đã tới | `Card2K\Model\Deposit` |
| `webhook.test` | Bạn bấm "Gửi webhook thử" | mảng `{partner_id, message}` |

<details>
<summary>Xem body shop gửi tới (rút gọn: `data` là đơn đầy đủ như `orders->get()`)</summary>

```json
{
  "event": "order.updated",
  "event_id": "9b2f6a4e-3c1d-4f8a-9e7b-1a2b3c4d5e6f",
  "created_at": "2026-11-25T18:09:10+00:00",
  "data": { "code": "DH8K2M4PQX", "reference": "DON-1001", "status": "completed", "total": 19400 }
}
```

</details>

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

if (daXuLy($event->event_id)) { // shop có thể gửi lại một sự kiện
    exit;
}

if ($event->data instanceof Order) {
    capNhatDon($event->data->reference, $event->data->status);
} elseif ($event->data instanceof CardExchange) {
    capNhatThe($event->data->reference, $event->data->status, $event->data->amount);
}
```

SDK kiểm `X-Signature-V2` và từ chối webhook lệch giờ quá 5 phút (nhớ đồng bộ giờ máy chủ). Trả HTTP 2xx thật nhanh; việc nặng thì đưa vào hàng đợi.

Lỡ webhook (máy chủ tắt, lỗi…)? Đọc lại sự kiện 30 ngày gần nhất:

```php
foreach ($card2k->events->all($eventIdDaLuu) as $event) { // null lần đầu
    xuLy($event);
    luuEventId($event->event_id);
}
```

## Xử lý lỗi

Mọi lỗi kế thừa `Card2K\Exception\Card2KException`. Lỗi từ API là `ApiException` (và lớp con theo mã HTTP); xử lý theo `getErrorCode()`, không theo câu chữ.

<details>
<summary>Xem lỗi API trả về (SDK đổi thành InsufficientBalanceException)</summary>

```http
HTTP/1.1 402 Payment Required
X-Request-Id: 012a1b24-2511-4b19-b4e9-bf7a5e7c70be

{
  "error": {
    "code": "insufficient_balance",
    "message": "Số dư không đủ, cần 19.400đ.",
    "required": 19400,
    "balance": 0
  }
}
```

</details>

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

Danh sách mã đầy đủ: `Card2K\ErrorCode`. Shop có thể thêm mã mới, nên luôn có nhánh mặc định. Khi hỏi shop về một lỗi, gửi kèm `getRequestId()`.

## Thử lại và chống trùng

- SDK tự thử lại tối đa 2 lần (`max_retries`) khi không kết nối được, khi bị 429, 500, 502, 503, 504, hoặc 409 `request_in_progress`. Chờ theo `Retry-After`, không có thì 0,5 – 1 – 2 giây; `Retry-After` quá 20 giây (`max_retry_delay`) thì ném lỗi luôn.
- Lần thử lại gửi **cùng** Idempotency-Key (đặt đơn, chuyển ví) hoặc `reference` (đổi thẻ), nên shop chỉ làm một lần.
- Không truyền mã thì SDK tự tạo mã ngẫu nhiên: vẫn an toàn khi SDK tự thử lại, nhưng nếu tiến trình của bạn chết giữa chừng thì không có mã để tra lại. Nên truyền mã của bạn (ví dụ id đơn trong database).

## Cấu hình

```php
$card2k = new Card2K\Client(options: [ // key vẫn từ CARD2K_API_KEY; hoặc new Card2K\Client('key', [...])
    'timeout' => 20,              // giây, khi SDK tự tạo Guzzle (mặc định 30)
    'max_retries' => 3,           // mặc định 2; 0 = không thử lại
    'max_retry_delay' => 10,      // Retry-After lâu hơn thì ném lỗi luôn (mặc định 20 giây)
    'user_agent' => 'shop-cua-toi/2.1',
    'logger' => $psr3Logger,      // ghi mỗi lần gọi: phương thức, đường dẫn, mã HTTP, thời gian, X-Request-Id
    'http_client' => $psr18Client, // client HTTP (PSR-18) của bạn (proxy…), thay cho Guzzle SDK tự tạo
]);
```

Shop chạy trên tên miền khác card2k.net thì thêm `'base_url' => 'https://ten-mien-shop'`. Mã HTTP của lần gọi vừa rồi (201 tạo mới, 200 gửi lại): `$card2k->lastResponse()`.

## Đọc dữ liệu

Mọi đối tượng trả về (`Order`, `CardExchange`, `Deposit`…) **chỉ đọc**:

```php
$order->status;              // thuộc tính (IDE gợi ý đủ field)
$order['status'];            // như mảng
$order->get('field_moi');    // field shop mới thêm, SDK chưa khai báo
$order->toArray();           // nguyên JSON
```

API v3 chỉ **thêm** field, endpoint, mã lỗi, không đổi hay bỏ thứ đã có, nên bản SDK cũ vẫn chạy khi shop nâng cấp.

## Test

Truyền một client PSR-18 giả để không gọi thật:

```php
$http = new Http\Mock\Client(); // php-http/mock-client
$http->addResponse(new Nyholm\Psr7\Response(201, [], json_encode(['data' => ['code' => 'DH1', 'status' => 'completed']])));

$card2k = new Card2K\Client('key-gia', ['http_client' => $http]);
// Laravel: $this->app->instance(Card2K\Client::class, $card2k);
```

## Phát triển SDK

```bash
composer install
composer check                 # PHPStan + PHPUnit
php bin/check-spec.php         # so resources/openapi.json với spec trên card2k.net (--update để cập nhật)
php bin/generate-models.php    # sinh lại src/Model từ spec
```

Commit theo [Conventional Commits](https://www.conventionalcommits.org/), phiên bản theo [SemVer](https://semver.org/lang/vi/) (`Client::VERSION`, [CHANGELOG.md](CHANGELOG.md)).

## Giấy phép

MIT.
