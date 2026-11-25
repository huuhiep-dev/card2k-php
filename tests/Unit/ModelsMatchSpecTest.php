<?php

declare(strict_types=1);

namespace Card2K\Tests\Unit;

use Card2K\Tests\Support\ModelGenerator;
use Card2K\Tests\Support\Spec;
use PHPUnit\Framework\TestCase;

/**
 * src/Model phải đúng như bin/generate-models.php sinh ra từ resources/openapi.json.
 */
final class ModelsMatchSpecTest extends TestCase
{
    public function test_models_are_generated_from_the_current_spec(): void
    {
        foreach ((new ModelGenerator(Spec::load()))->files() as $class => $expected) {
            $path = __DIR__."/../../src/Model/{$class}.php";

            self::assertFileExists($path);
            self::assertSame(
                str_replace("\r\n", "\n", $expected),
                str_replace("\r\n", "\n", (string) file_get_contents($path)),
                "src/Model/{$class}.php lệch với spec: chạy `php bin/generate-models.php`.",
            );
        }
    }

    public function test_every_model_file_is_known_to_the_generator(): void
    {
        $files = array_map(fn (string $path) => basename($path, '.php'), glob(__DIR__.'/../../src/Model/*.php') ?: []);
        $known = [...array_keys(ModelGenerator::MODELS), 'Model'];
        sort($files);
        sort($known);

        self::assertSame($known, $files);
    }
}
