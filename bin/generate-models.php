<?php

declare(strict_types=1);

/*
 * Sinh src/Model/*.php từ resources/openapi.json (bản chụp spec API v3).
 *
 *     php bin/generate-models.php
 *
 * Danh sách model ở tests/Support/ModelGenerator.php. Chạy lại sau khi cập nhật
 * resources/openapi.json; test ModelsMatchSpecTest báo khi quên.
 */

use Card2K\Tests\Support\ModelGenerator;
use Card2K\Tests\Support\Spec;

require __DIR__.'/../vendor/autoload.php';

foreach ((new ModelGenerator(Spec::load()))->files() as $class => $content) {
    file_put_contents(__DIR__."/../src/Model/{$class}.php", $content);
    echo "src/Model/{$class}.php\n";
}
