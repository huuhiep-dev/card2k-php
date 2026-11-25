# Lỗi, thử lại, chống trùng

## Xử lý lỗi

Mọi lỗi kế thừa `Card2K\Exception\Card2KException`. Lỗi từ API là `ApiException` (và lớp con theo mã HTTP); xử lý theo `getErrorCode()`, không theo câu chữ.

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

## Thử lại

SDK tự thử lại tối đa 2 lần (đổi bằng `max_retries`) khi không kết nối được, khi bị 429, 500, 502, 503, 504, hoặc khi cùng mã đang được xử lý (409 `request_in_progress`). SDK chờ theo header `Retry-After`, không có thì chờ tăng dần 0,5 – 1 – 2 giây. `Retry-After` dài hơn `max_retry_delay` (20 giây) thì SDK ném lỗi luôn thay vì chờ.

## Chống trùng

Lần thử lại gửi **cùng** Idempotency-Key (đặt đơn, chuyển ví) hoặc `reference` (đổi thẻ), nên shop chỉ làm một lần.

Không truyền mã thì SDK tự tạo mã ngẫu nhiên: vẫn an toàn khi SDK tự thử lại, nhưng nếu tiến trình của bạn chết giữa chừng thì bạn không có mã để tra lại. Vì vậy nên truyền mã của bạn (ví dụ id đơn trong database).
