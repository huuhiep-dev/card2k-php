<?php

declare(strict_types=1);

namespace Card2K\Tests\Support;

/**
 * Dựng nội dung các file src/Model/*.php từ spec. bin/generate-models.php ghi ra file;
 * ModelsMatchSpecTest so với file đang có để báo khi quên chạy lại.
 */
final class ModelGenerator
{
    /**
     * Model => [đường dẫn schema trong spec, mô tả, field là đối tượng con].
     *
     * @var array<string, array{0: string, 1: string, 2: array<string, string>}>
     */
    public const MODELS = [
        'Account' => ['Account', 'Tài khoản của key: số dư hai ví, rank, quyền, giới hạn mỗi đơn.', []],
        'Product' => ['Product', 'Một sản phẩm đang bán, giá theo rank của bạn.', ['fields' => '[ProductField]', 'packages' => '[Package]']],
        'ProductField' => ['ProductField', 'Một ô khách phải điền khi đặt (ID game, server…), gửi trong `fields` theo `id`.', []],
        'Package' => ['Package', 'Một gói của sản phẩm; đặt đơn bằng `id` này.', []],
        'Order' => ['Order', 'Một đơn đặt qua kết nối này.', ['items' => '[OrderItem]', 'cards' => '[Card]']],
        'OrderItem' => ['OrderItem', 'Một dòng của đơn; `id` dùng để bảo hành và đổi phí ưu tiên Sò.', ['cards' => '[Card]']],
        'Card' => ['Card', 'Một thẻ cào đã giao (mã thẻ và serial).', []],
        'Event' => ['Event', 'Một sự kiện webhook. `data` là `Order`, `CardExchange` hoặc `Deposit` theo `event`, còn `webhook.test` là mảng `{partner_id, message}`. Webhook nhận trực tiếp không có `delivery`.', []],
        'Transaction' => ['Transaction', 'Một biến động số dư của tài khoản (cả trên web).', []],
        'CardExchange' => ['CardExchange', 'Một thẻ đã gửi đổi. Không bao giờ có mã thẻ.', []],
        'CardExchangeRates' => ['CardExchangeRatesResponse.data', 'Phí đổi thẻ theo rank của bạn.', ['rates' => '[CardExchangeRate]']],
        'CardExchangeRate' => ['CardExchangeRatesResponse.data.rates[]', 'Phí và tiền nhận được của một nhà mạng, một mệnh giá.', []],
        'WarrantyClaim' => ['WarrantyClaim', 'Một yêu cầu bảo hành.', []],
        'QueuePriorityResult' => ['QueuePriorityResponse.data', 'Kết quả đổi phí ưu tiên Sò: `difference` (dương: đã trừ thêm, âm: đã hoàn) và đơn sau khi đổi.', ['order' => 'Order']],
        'VoucherCheck' => ['VoucherCheckResponse.data', 'Mã giảm giá giảm bao nhiêu cho một gói (chưa đặt đơn).', []],
        'Deposit' => ['Deposit', 'Một mã nạp tiền: chuyển khoản đúng `transfer_content` tới một trong `accounts` để nạp Ví mua hàng.', ['accounts' => '[DepositAccount]']],
        'DepositAccount' => ['Deposit.accounts[]', 'Một tài khoản ngân hàng nhận tiền nạp.', []],
        'WalletTransfer' => ['WalletTransferResponse.data', 'Một lần chuyển Ví đổi thẻ sang Ví mua hàng, với số dư hai ví sau khi chuyển.', []],
    ];

    public function __construct(private readonly Spec $spec)
    {
    }

    /**
     * @return array<string, string> tên lớp => nội dung file
     */
    public function files(): array
    {
        $files = [];
        foreach (self::MODELS as $class => [$path, $description, $nested]) {
            $files[$class] = $this->render($class, $path, $description, $nested);
        }

        return $files;
    }

    /**
     * @param  array<string, string>  $nested
     */
    private function render(string $class, string $path, string $description, array $nested): string
    {
        $schema = $this->spec->schemaAt($path);
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];
        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $lines = [];

        foreach ($properties as $name => $property) {
            $name = (string) $name;
            $property = is_array($property) ? $property : [];
            $type = isset($nested[$name]) ? $this->nestedType($nested[$name], $property) : $this->spec->phpType($property);
            if ($class === 'Event' && $name === 'data') {
                $type = 'Order|CardExchange|Deposit|array<string, mixed>|null';
            }
            if ($class === 'Event' && $name === 'delivery') {
                $type .= '|null';
            }
            if (! in_array($name, $required, true) && ! str_contains($type, 'null')) {
                $type .= '|null';
            }
            $note = is_string($property['description'] ?? null) ? ' '.trim((string) preg_replace('/\s+/', ' ', $property['description'])) : '';
            $lines[] = ' * @property-read '.$type.' $'.$name.$note;
        }

        $body = $class === 'Event' ? self::eventBody() : self::nestedConst($nested);

        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace Card2K\\Model;\n\n"
            ."/**\n * ".wordwrap($description, 96, "\n * ")."\n *\n"
            ." * Sinh từ resources/openapi.json (`{$path}`) bằng bin/generate-models.php.\n *\n"
            .implode("\n", $lines)."\n */\n"
            ."final class {$class} extends Model\n{\n".$body."}\n";
    }

    /**
     * @param  array<string, mixed>  $property
     */
    private function nestedType(string $target, array $property): string
    {
        $nullable = in_array('null', $this->spec->types($property), true);
        $type = str_starts_with($target, '[') ? 'list<'.trim($target, '[]').'>' : $target;

        return $nullable ? $type.'|null' : $type;
    }

    /**
     * @param  array<string, string>  $nested
     */
    private static function nestedConst(array $nested): string
    {
        if ($nested === []) {
            return '';
        }

        $entries = [];
        foreach ($nested as $name => $target) {
            $entries[] = str_starts_with($target, '[')
                ? "        '{$name}' => [".trim($target, '[]').'::class],'
                : "        '{$name}' => {$target}::class,";
        }

        return "    protected const NESTED = [\n".implode("\n", $entries)."\n    ];\n";
    }

    private static function eventBody(): string
    {
        return <<<'PHP'
                /**
                 * `data` thành `Order`, `CardExchange` hoặc `Deposit` theo `event`; loại lạ giữ nguyên mảng.
                 *
                 * @param  array<string, mixed>  $raw
                 * @return class-string<Model>|null
                 */
                protected function nestedType(string $key, array $raw): ?string
                {
                    if ($key !== 'data') {
                        return null;
                    }

                    return match ($raw['event'] ?? null) {
                        'order.updated' => Order::class,
                        'card_exchange.updated' => CardExchange::class,
                        'deposit.completed' => Deposit::class,
                        default => null,
                    };
                }

            PHP;
    }
}
