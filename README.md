# Card2K PHP SDK

[![Packagist](https://img.shields.io/packagist/v/card2k/card2k-php)](https://packagist.org/packages/card2k/card2k-php)
[![PHP](https://img.shields.io/packagist/dependency-v/card2k/card2k-php/php)](composer.json)
[![CI](https://github.com/huuhiep-dev/card2k-php/actions/workflows/ci.yml/badge.svg)](https://github.com/huuhiep-dev/card2k-php/actions/workflows/ci.yml)

Kết nối [Card2K](https://card2k.net) bằng PHP trong vài dòng: nạp game, mua thẻ cào, đổi thẻ cào, ví, bảo hành, webhook. **Không bao giờ trừ tiền hai lần.**

## Vì sao dùng SDK

| | Tự gọi API | Dùng SDK |
|---|---|---|
| Mạng lỗi giữa chừng | Tự lưu mã chống trùng, tự gửi lại, dễ trừ tiền 2 lần | Tự gửi lại với cùng mã, shop chỉ làm 1 lần |
| Gọi quá nhanh (429), shop bảo trì (503) | Tự đọc `Retry-After`, tự chờ | Tự chờ và thử lại |
| Webhook | Tự tính HMAC, kiểm thời gian, chống gửi lại | 1 dòng (Laravel: 1 dòng route) |
| Lỗi | Tự đọc JSON, dò mã | Exception theo loại: thiếu tiền, sai dữ liệu… |
| Lọc theo thời gian | Dễ lệch múi giờ | Tự đổi về UTC |
| Field của đơn, thẻ | Đoán tên | IDE gợi ý đủ |

## Bắt đầu trong 1 phút

1. Ở **Tài khoản → Kết nối API**, tạo kết nối loại **API v3**, bật quyền cần dùng, sao chép key.
2. Cài:

```bash
composer require card2k/card2k-php
```

3. Đặt đơn:

```php
$card2k = new Card2K\Client(getenv('CARD2K_API_KEY'));

$order = $card2k->orders->create(['package_id' => 12, 'fields' => [3 => 'ID game']], 'DON-1001');

echo $order->status; // pending → completed (qua webhook hoặc $card2k->orders->get($order->code))
```

`DON-1001` là mã đơn bên bạn: gửi lại cùng mã thì chỉ nhận lại đơn cũ, không trừ tiền lần nữa.

> Giữ key ở máy chủ (biến môi trường). Không đưa vào trình duyệt, app hay mã nguồn công khai.

## Laravel

Thêm key vào `.env` là dùng được, không cần cấu hình gì thêm:

```dotenv
CARD2K_API_KEY=key-cua-ban
```

```php
use Card2K\Laravel\Facades\Card2K;

$order = Card2K::orders()->create(['package_id' => 12, 'fields' => [3 => 'ID game']], $myOrder->id);
```

Webhook một dòng: SDK kiểm chữ ký, bỏ sự kiện trùng, rồi phát event Laravel để bạn viết listener.

```php
// routes/web.php
Route::card2kWebhook('/card2k/webhook');
```

Chi tiết: [docs/laravel.md](docs/laravel.md).

## SDK làm được gì

| Việc | Lệnh |
|---|---|
| Số dư, quyền của key | `$card2k->account->get()` |
| Sản phẩm, giá theo rank | `products->list()`, `products->get($id)` |
| Đặt đơn, tra đơn, đồng bộ | `orders->create()`, `orders->get()`, `orders->all()` |
| Đổi thẻ cào | `cardExchanges->rates()`, `create()`, `get()` |
| Nạp tiền, chuyển ví, lịch sử | `deposits->create()`, `wallet->transfer()`, `transactions->all()` |
| Bảo hành, phí ưu tiên Sò, mã giảm giá | `warrantyClaims->create()`, `orders->changeQueuePriority()`, `vouchers->check()` |
| Nhận webhook, lấy lại sự kiện lỡ | `Webhook::constructEvent()`, `events->all()` |

## Tài liệu

- [Các dịch vụ](docs/dich-vu.md): ví dụ cho từng việc ở bảng trên.
- [Webhook](docs/webhook.md)
- [Lỗi, thử lại, chống trùng](docs/loi-va-thu-lai.md)
- [Laravel](docs/laravel.md)
- [Cấu hình, đọc dữ liệu, test](docs/cau-hinh.md)
- [Tài liệu API của shop](https://card2k.net/tai-lieu-api)
- [Phát triển SDK](docs/phat-trien.md)

## Giấy phép

MIT.
