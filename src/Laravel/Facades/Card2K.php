<?php

declare(strict_types=1);

namespace Card2K\Laravel\Facades;

use Card2K\Client;
use Illuminate\Support\Facades\Facade;

/**
 * `Card2K::orders()->create(…)`: client của ứng dụng (config/card2k.php).
 *
 * @method static \Card2K\Service\AccountService account()
 * @method static \Card2K\Service\ProductService products()
 * @method static \Card2K\Service\OrderService orders()
 * @method static \Card2K\Service\WarrantyClaimService warrantyClaims()
 * @method static \Card2K\Service\EventService events()
 * @method static \Card2K\Service\TransactionService transactions()
 * @method static \Card2K\Service\CardExchangeService cardExchanges()
 * @method static \Card2K\Service\VoucherService vouchers()
 * @method static \Card2K\Service\DepositService deposits()
 * @method static \Card2K\Service\WalletService wallet()
 * @method static \Psr\Http\Message\ResponseInterface|null lastResponse()
 *
 * @see Client
 */
final class Card2K extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
