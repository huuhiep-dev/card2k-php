# Laravel

Laravel tự nhận SDK, không cần đăng ký gì. Hỗ trợ Laravel 10 trở lên (test trên Laravel 11, 12, 13).

## Cài và cấu hình

```bash
composer require card2k/card2k-php
```

```dotenv
CARD2K_API_KEY=key-cua-ban          # key kết nối API v3
# Không bắt buộc:
CARD2K_LOG_CHANNEL=stack            # ghi mỗi lần gọi API (không ghi key, body, mã thẻ)
CARD2K_TIMEOUT=30
CARD2K_MAX_RETRIES=2
```

Muốn sửa thêm (thời gian lệch cho webhook…): `php artisan vendor:publish --tag=card2k-config`.

## Gọi API

Qua Facade:

```php
use Card2K\Laravel\Facades\Card2K;

$order = Card2K::orders()->create(['package_id' => 12, 'fields' => [3 => $uid]], (string) $myOrder->id);
$account = Card2K::account()->get();
```

Hoặc nhận client qua constructor (dễ test hơn):

```php
use Card2K\Client;

final class PlaceCard2KOrder
{
    public function __construct(private Client $card2k) {}

    public function handle(Order $myOrder): void
    {
        $order = $this->card2k->orders->create([...], (string) $myOrder->id);
    }
}
```

Mọi dịch vụ và cách xử lý lỗi giống hệt PHP thuần: xem [dich-vu.md](dich-vu.md), [loi-va-thu-lai.md](loi-va-thu-lai.md).

## Webhook

Một dòng route (đặt ở `routes/web.php` hay `routes/api.php` đều được, SDK tự bỏ kiểm tra CSRF cho route này):

```php
Route::card2kWebhook('/card2k/webhook');
// hoặc, nếu dùng PHPStan / Larastan: Card2K\Laravel\WebhookRoute::register('/card2k/webhook');
```

Đặt địa chỉ đó (https) cho kết nối ở trang Kết nối API. Với mỗi webhook, SDK:

1. kiểm chữ ký và thời điểm gửi (sai thì trả 400);
2. bỏ qua `event_id` đã xử lý (shop có thể gửi lại);
3. phát event Laravel, rồi trả 200.

| Event | Khi nào |
|---|---|
| `Card2K\Laravel\Events\OrderUpdated` (`$order`, `$event`) | Đơn đổi trạng thái |
| `Card2K\Laravel\Events\CardExchangeUpdated` (`$card`, `$event`) | Thẻ có kết quả |
| `Card2K\Laravel\Events\DepositCompleted` (`$deposit`, `$event`) | Tiền nạp đã tới |
| `Card2K\Laravel\Events\WebhookReceived` (`$event`) | Mọi webhook, kể cả `webhook.test` |

Viết listener như bình thường. Nên cho listener chạy hàng đợi (`ShouldQueue`) để trả 200 thật nhanh:

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

Listener chạy ngay (không hàng đợi) mà lỗi thì SDK trả HTTP 500 và **không** nhớ `event_id`, nên shop gửi lại sau: sự kiện không bị mất.

## Test

Thay client bằng bản dùng HTTP giả:

```php
$this->app->instance(Card2K\Client::class, new Card2K\Client('key-gia', ['http_client' => $mockHttp]));
```

Hoặc `Event::fake()` rồi gửi webhook ký bằng key test tới route của bạn.
