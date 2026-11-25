<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\Model\CardExchange;
use Card2K\Model\CardExchangeRates;
use Card2K\Page;
use Generator;

/**
 * Đổi thẻ cào: tiền nhận được cộng vào Ví đổi thẻ. Cần quyền `card_exchange`.
 */
final class CardExchangeService extends Service
{
    /**
     * Phí đổi theo rank của bạn và tiền nhận được, mỗi nhà mạng / mệnh giá. `GET /card-exchange/rates`
     */
    public function rates(): CardExchangeRates
    {
        return CardExchangeRates::from($this->data($this->transport->send('GET', '/card-exchange/rates')));
    }

    /**
     * Gửi một thẻ để đổi. `POST /card-exchanges`
     *
     * `reference` là mã của bạn cho thẻ này (1–64 ký tự `A-Z a-z 0-9 _ - . :`, duy nhất trên kết
     * nối): gửi lại cùng `reference` với cùng thẻ chỉ trả lại thẻ cũ. Bỏ trống thì SDK tự tạo
     * (lấy lại bằng `$card->reference`). Kết quả tới qua webhook `card_exchange.updated`, hoặc
     * tra bằng `get()`.
     *
     * @param  array{telco: string, value: int, serial: string, code: string, reference?: string}  $params  `telco`: {@see \Card2K\Telco}
     *
     * @throws \Card2K\Exception\ValidationException `telco_unavailable`, `invalid_card_format`, `card_already_submitted`, `reference_reused`, …
     * @throws \Card2K\Exception\LockedException `card_exchange_locked`
     */
    public function create(array $params): CardExchange
    {
        $params['reference'] ??= self::newKey();

        return CardExchange::from($this->data($this->transport->send('POST', '/card-exchanges', body: $params)));
    }

    /**
     * Một thẻ đã gửi qua kết nối này. `GET /card-exchanges/{id}`
     *
     * @param  string  $id  `CardExchange::$id`
     */
    public function get(string $id): CardExchange
    {
        return CardExchange::from($this->data($this->transport->send('GET', '/card-exchanges/'.self::segment($id))));
    }

    /**
     * Tìm thẻ theo `reference` của bạn, hoặc null.
     */
    public function findByReference(string $reference): ?CardExchange
    {
        return $this->list(['reference' => $reference, 'per_page' => 1])->data[0] ?? null;
    }

    /**
     * Một trang thẻ đã gửi. `GET /card-exchanges`
     *
     * @param  array{status?: string, telco?: string, reference?: string, updated_since?: \DateTimeInterface|string, page?: int, per_page?: int}  $filters
     * @return Page<CardExchange>
     */
    public function list(array $filters = []): Page
    {
        $query = self::pick($filters, ['status', 'telco', 'reference', 'updated_since', 'page', 'per_page']);

        return $this->page($this->transport->send('GET', '/card-exchanges', $query), CardExchange::class);
    }

    /**
     * Mọi thẻ khớp bộ lọc, tự sang trang.
     *
     * @param  array{status?: string, telco?: string, reference?: string, updated_since?: \DateTimeInterface|string, page?: int, per_page?: int}  $filters
     * @return Generator<int, CardExchange>
     */
    public function all(array $filters = []): Generator
    {
        return $this->eachPage($filters, fn (array $page): Page => $this->list($page));
    }
}
