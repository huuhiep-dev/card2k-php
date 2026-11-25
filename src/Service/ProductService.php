<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\Model\Product;

/**
 * Sản phẩm đang bán, giá theo rank của bạn.
 */
final class ProductService extends Service
{
    /**
     * Mọi sản phẩm đang bán, hoặc chỉ một loại. `GET /products`
     *
     * @param  string|null  $type  {@see \Card2K\ProductType}: `game`, `card`, `garena_queue`
     * @return list<Product>
     */
    public function list(?string $type = null): array
    {
        return Product::listFrom($this->rows($this->transport->send('GET', '/products', ['type' => $type])));
    }

    /**
     * Một sản phẩm (ô cần điền, các gói). `GET /products/{id}`
     */
    public function get(int $id): Product
    {
        return Product::from($this->data($this->transport->send('GET', '/products/'.self::segment($id))));
    }
}
