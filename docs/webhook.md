# Webhook

Đặt **địa chỉ webhook** (https) cho kết nối ở trang Kết nối API. Shop gửi `POST` JSON khi:

| Sự kiện | Khi nào | `$event->data` |
|---|---|---|
| `order.updated` | Đơn đổi trạng thái | `Card2K\Model\Order` |
| `card_exchange.updated` | Thẻ có kết quả | `Card2K\Model\CardExchange` |
| `deposit.completed` | Tiền nạp đã tới | `Card2K\Model\Deposit` |
| `webhook.test` | Bạn bấm "Gửi webhook thử" | mảng `{partner_id, message}` |

Dùng Laravel? Xem [laravel.md](laravel.md): một dòng route là xong, SDK tự kiểm chữ ký và bỏ sự kiện trùng.

## PHP thuần

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

SDK kiểm `X-Signature-V2` (HMAC-SHA256 của `timestamp.body` bằng key) và từ chối webhook lệch giờ quá 5 phút. Nhớ đồng bộ giờ máy chủ (NTP). Trả HTTP 2xx thật nhanh; việc nặng thì đưa vào hàng đợi.

## Lấy lại sự kiện đã lỡ

Máy chủ bạn tắt hay lỗi lúc shop gửi? Đọc lại sự kiện 30 ngày gần nhất:

```php
$after = docEventIdDaLuu(); // null lần đầu
foreach ($card2k->events->all($after) as $event) {
    xuLy($event);
    luuEventId($event->event_id);
}
```
