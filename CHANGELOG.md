# Thay đổi

Theo [Semantic Versioning](https://semver.org/lang/vi/). Trước 1.0.0, bản MINOR có thể đổi cách dùng.

## [Chưa phát hành]

## 0.2.1 (2026-11-26)

- **Sửa**: webhook Laravel bị chặn CSRF (HTTP 419) trên Laravel 13, vì Laravel 13 dùng middleware `PreventRequestForgery`. Route webhook giờ bỏ kiểm tra CSRF trên mọi bản Laravel 10–13.
- README một trang, có mục lục; bỏ thư mục `docs/`.

## 0.2.0 (2026-11-26)

- **Laravel**: tự nhận gói; client từ `CARD2K_API_KEY` trong `.env`; Facade `Card2K::orders()…`; `Route::card2kWebhook()` kiểm chữ ký, bỏ sự kiện trùng, phát event `OrderUpdated`, `CardExchangeUpdated`, `DepositCompleted`, `WebhookReceived`.
- Tùy chọn `logger` (PSR-3): mỗi lần gọi ghi phương thức, đường dẫn, mã HTTP, thời gian, `X-Request-Id`; không ghi key, body, mã thẻ.
- Các service gọi được cả dạng hàm: `$client->orders()` như `$client->orders`.
- README ngắn gọn (vì sao dùng SDK, bắt đầu trong 1 phút); tài liệu chi tiết chuyển vào `docs/`.
- Thêm thư viện bắt buộc `psr/log` (chuẩn PSR-3, rất nhỏ).

## 0.1.0 (2026-11-26)

Bản đầu tiên, đủ mọi endpoint của API v3 (spec 3.1.0):

- Tài khoản, sản phẩm, đặt / tra đơn (tự sang trang), đổi phí ưu tiên Sò, kiểm tra mã giảm giá.
- Đổi thẻ cào: bảng phí, gửi thẻ, tra thẻ, tìm theo `reference`.
- Ví: lịch sử giao dịch, nạp tiền, chuyển Ví đổi thẻ sang Ví mua hàng.
- Bảo hành: gửi và xem yêu cầu.
- Sự kiện webhook: đọc lại, kiểm chữ ký `X-Signature-V2` có chống gửi lại.
- Tự thử lại khi lỗi mạng / 429 / 5xx / `request_in_progress`, cùng Idempotency-Key.
- Lỗi theo `error.code`, mỗi mã HTTP một lớp exception.
- Mốc thời gian lọc (`updated_since`, `from`, `to`) truyền bằng `DateTimeInterface` được gửi dạng UTC (`…Z`), nên lọc đúng cả với shop bản trước v2.20.2 (bản đó so mốc `+07:00` lệch 7 tiếng).
