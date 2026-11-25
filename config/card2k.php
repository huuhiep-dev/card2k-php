<?php

/*
 * Cấu hình SDK Card2K cho Laravel. Thường chỉ cần CARD2K_API_KEY trong .env.
 * Muốn sửa thêm: php artisan vendor:publish --tag=card2k-config
 */

return [

    // Key của kết nối loại "API v3" (Tài khoản → Kết nối API).
    'api_key' => env('CARD2K_API_KEY'),

    // Tên miền của shop. Bỏ trống: https://card2k.net
    'base_url' => env('CARD2K_BASE_URL'),

    // Số giây chờ mỗi request, và số lần thử lại khi lỗi mạng / 429 / 5xx.
    'timeout' => (float) env('CARD2K_TIMEOUT', 30),
    'max_retries' => (int) env('CARD2K_MAX_RETRIES', 2),

    // Kênh log ghi mỗi lần gọi API (phương thức, đường dẫn, mã HTTP, X-Request-Id). Bỏ trống: không ghi.
    'log_channel' => env('CARD2K_LOG_CHANNEL'),

    'webhook' => [
        // Lệch giờ tối đa (giây) giữa lúc shop gửi và giờ máy bạn.
        'tolerance' => 300,

        // Nhớ event_id đã xử lý bao nhiêu giờ, để bỏ qua sự kiện shop gửi lại.
        'remember_hours' => 72,
    ],

];
