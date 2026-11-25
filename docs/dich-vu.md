# Các dịch vụ

Mọi ví dụ dùng `$card2k = new Card2K\Client(getenv('CARD2K_API_KEY'));`.

- [Quyền của key](#quyền-của-key)
- [Tài khoản](#tài-khoản)
- [Sản phẩm và đặt đơn](#sản-phẩm-và-đặt-đơn)
- [Tra đơn](#tra-đơn)
- [Đổi thẻ cào](#đổi-thẻ-cào)
- [Ví, nạp tiền, giao dịch](#ví-nạp-tiền-giao-dịch)
- [Bảo hành](#bảo-hành)

## Quyền của key

Bật ở **Tài khoản → Kết nối API**, kết nối loại API v3.

| Quyền | Cho phép |
|---|---|
| `orders` | Đặt đơn, đổi phí ưu tiên Sò, kiểm tra mã giảm giá (bật sẵn) |
| `card_exchange` | Gửi thẻ cào để đổi (cần xác thực SĐT nếu shop yêu cầu) |
| `warranty` | Gửi yêu cầu bảo hành |
| `deposit` | Lấy thông tin chuyển khoản nạp Ví mua hàng |
| `wallet_transfer` | Chuyển Ví đổi thẻ sang Ví mua hàng |

Xem sản phẩm, tra đơn, lịch sử giao dịch, sự kiện thì không cần quyền riêng. Thiếu quyền thì SDK ném `PermissionException` mã `scope_not_granted`.

## Tài khoản

```php
$account = $card2k->account->get();
echo $account->balance;          // Ví mua hàng (VND)
echo $account->exchange_balance; // Ví đổi thẻ (VND)
print_r($account->scopes);       // ['orders', 'card_exchange', …]
```

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

Đặt đơn trừ tiền Ví mua hàng. Tham số thứ hai là **mã đơn bên bạn** (Idempotency-Key): gửi lại cùng mã thì chỉ nhận lại đơn cũ, không trừ tiền lần nữa.

```php
$order = $card2k->orders->create([
    'package_id' => 12,
    'quantity' => 1,
    'fields' => [3 => '123456789', 4 => 'Asia'], // [id ô => giá trị]
    'voucher_code' => 'GIAM10',                  // không bắt buộc
], 'DON-1001');

echo $order->code, ' ', $order->status, ' ', $order->total;

foreach ($order->cards as $card) { // thẻ cào giao ngay
    echo $card->serial, ' / ', $card->code, PHP_EOL;
}
```

Sò Garena hàng chờ thì thêm `queue`, và đổi phí ưu tiên sau khi đặt được:

```php
$order = $card2k->orders->create([
    'package_id' => 30,
    'queue' => ['account' => 'ten-tai-khoan', 'priority_percent' => 3], // hoặc 'top' => true
], 'DON-1002');

$result = $card2k->orders->changeQueuePriority($order->code, $order->items[0]->id, ['priority_percent' => 6]);
echo $result->difference; // dương: trừ thêm, âm: hoàn lại
```

Kiểm tra mã giảm giá trước khi đặt:

```php
$check = $card2k->vouchers->check(['package_id' => 12, 'voucher_code' => 'GIAM10']);
echo $check->discount, ' → còn ', $check->total;
```

## Tra đơn

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

## Đổi thẻ cào

Tiền nhận được cộng vào **Ví đổi thẻ**. `reference` là mã của bạn cho thẻ: gửi lại cùng `reference` thì chỉ nhận lại thẻ cũ.

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

Phản hồi và webhook **không bao giờ** chứa mã thẻ, chỉ có `serial` và `reference`.

## Ví, nạp tiền, giao dịch

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
