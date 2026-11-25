<?php

declare(strict_types=1);

namespace Card2K\Tests\Support;

use InvalidArgumentException;
use JsonException;

/**
 * Đọc resources/openapi.json: tìm schema, đổi kiểu sang kiểu PHP (cho bin/generate-models.php),
 * dựng dữ liệu mẫu đúng schema (cho test), liệt kê endpoint.
 */
final class Spec
{
    /**
     * @param  array<string, mixed>  $document
     */
    private function __construct(private readonly array $document)
    {
    }

    /**
     * @throws JsonException
     */
    public static function load(?string $path = null): self
    {
        $json = (string) file_get_contents($path ?? __DIR__.'/../../resources/openapi.json');
        /** @var array<string, mixed> $document */
        $document = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self($document);
    }

    /**
     * @return array<string, mixed>
     */
    public function document(): array
    {
        return $this->document;
    }

    /**
     * Schema theo đường dẫn: `Order`, `Deposit.accounts[]`, `QueuePriorityResponse.data`.
     *
     * @return array<string, mixed>
     */
    public function schemaAt(string $path): array
    {
        $parts = explode('.', $path);
        $schema = $this->resolve(['$ref' => '#/components/schemas/'.array_shift($parts)]);

        foreach ($parts as $part) {
            $isList = str_ends_with($part, '[]');
            $name = $isList ? substr($part, 0, -2) : $part;
            $properties = $schema['properties'] ?? [];
            if (! is_array($properties) || ! isset($properties[$name]) || ! is_array($properties[$name])) {
                throw new InvalidArgumentException("Spec không có {$path}.");
            }
            $schema = $this->resolve($properties[$name]);
            if ($isList) {
                $items = $schema['items'] ?? null;
                if (! is_array($items)) {
                    throw new InvalidArgumentException("{$path}: {$name} không phải danh sách.");
                }
                $schema = $this->resolve($items);
            }
        }

        return $schema;
    }

    /**
     * Theo `$ref` (và `allOf` một phần tử) tới schema thật.
     *
     * @param  array<mixed>  $schema
     * @return array<string, mixed>
     */
    public function resolve(array $schema): array
    {
        while (true) {
            if (isset($schema['$ref']) && is_string($schema['$ref'])) {
                $name = substr($schema['$ref'], strlen('#/components/schemas/'));
                $components = $this->document['components'] ?? [];
                $schemas = is_array($components) ? ($components['schemas'] ?? []) : [];
                if (! is_array($schemas) || ! isset($schemas[$name]) || ! is_array($schemas[$name])) {
                    throw new InvalidArgumentException("Spec không có schema {$name}.");
                }
                $schema = $schemas[$name];

                continue;
            }
            if (isset($schema['allOf']) && is_array($schema['allOf']) && count($schema['allOf']) === 1 && is_array($schema['allOf'][0])) {
                $schema = $schema['allOf'][0];

                continue;
            }

            /** @var array<string, mixed> $schema */
            return $schema;
        }
    }

    /**
     * Các kiểu JSON của một schema (`type` có thể là chuỗi hoặc danh sách).
     *
     * @param  array<string, mixed>  $schema
     * @return list<string>
     */
    public function types(array $schema): array
    {
        $schema = $this->resolve($schema);
        $type = $schema['type'] ?? [];

        return array_values(array_map('strval', (array) $type));
    }

    /**
     * Kiểu PHP (dạng PHPDoc) của một schema.
     *
     * @param  array<string, mixed>  $schema
     */
    public function phpType(array $schema): string
    {
        $schema = $this->resolve($schema);
        $types = $this->types($schema);
        $nullable = in_array('null', $types, true);
        $types = array_values(array_diff($types, ['null']));

        $php = match ($types[0] ?? null) {
            'integer' => 'int',
            'number' => 'float',
            'boolean' => 'bool',
            'string' => 'string',
            'array' => 'list<'.$this->phpType(is_array($schema['items'] ?? null) ? $schema['items'] : []).'>',
            'object' => $this->objectShape($schema),
            default => 'mixed',
        };

        return $nullable && $php !== 'mixed' ? $php.'|null' : $php;
    }

    /**
     * Dữ liệu mẫu đúng schema: đủ mọi field (cả field không bắt buộc), giá trị khác null.
     *
     * @param  array<string, mixed>  $schema
     */
    public function sample(array $schema): mixed
    {
        $schema = $this->resolve($schema);
        if (isset($schema['enum']) && is_array($schema['enum'])) {
            return $schema['enum'][0];
        }
        $types = array_values(array_diff($this->types($schema), ['null']));

        return match ($types[0] ?? null) {
            'integer' => is_int($schema['minimum'] ?? null) ? max(1, $schema['minimum']) : 1,
            'number' => 1.5,
            'boolean' => true,
            'string' => match ($schema['format'] ?? null) {
                'date-time' => '2026-11-26T10:00:00+07:00',
                'uuid' => '9b2f6a4e-3c1d-4f8a-9e7b-1a2b3c4d5e6f',
                default => 'x',
            },
            'array' => [$this->sample(is_array($schema['items'] ?? null) ? $schema['items'] : [])],
            'object' => $this->sampleObject($schema),
            default => null,
        };
    }

    /**
     * Mọi endpoint: `['GET /orders', 'POST /orders', …]`.
     *
     * @return list<string>
     */
    public function operations(): array
    {
        $operations = [];
        $paths = $this->document['paths'] ?? [];
        foreach (is_array($paths) ? $paths : [] as $path => $methods) {
            foreach (is_array($methods) ? array_keys($methods) : [] as $method) {
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    $operations[] = strtoupper($method).' '.$path;
                }
            }
        }

        return $operations;
    }

    /**
     * Schema của phản hồi thành công một endpoint (`data` bên trong nếu có).
     *
     * @return array<string, mixed>
     */
    public function responseSchema(string $method, string $path): array
    {
        $paths = $this->document['paths'] ?? [];
        $operation = is_array($paths) && is_array($paths[$path] ?? null) ? ($paths[$path][strtolower($method)] ?? null) : null;
        $responses = is_array($operation) && is_array($operation['responses'] ?? null) ? $operation['responses'] : [];
        foreach (['200', '201'] as $status) {
            $content = is_array($responses[$status] ?? null) ? ($responses[$status]['content'] ?? null) : null;
            $json = is_array($content) ? ($content['application/json'] ?? null) : null;
            if (is_array($json) && is_array($json['schema'] ?? null)) {
                return $this->resolve($json['schema']);
            }
        }

        throw new InvalidArgumentException("Spec không có phản hồi thành công cho {$method} {$path}.");
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function objectShape(array $schema): string
    {
        $properties = $schema['properties'] ?? null;
        if (! is_array($properties) || $properties === []) {
            return 'array<string, mixed>';
        }
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];
        $parts = [];
        foreach ($properties as $name => $property) {
            $optional = in_array($name, $required, true) ? '' : '?';
            $parts[] = $name.$optional.': '.$this->phpType(is_array($property) ? $property : []);
        }

        return 'array{'.implode(', ', $parts).'}';
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function sampleObject(array $schema): array
    {
        $properties = $schema['properties'] ?? null;
        if (! is_array($properties)) {
            return ['key' => 'value'];
        }
        $object = [];
        foreach ($properties as $name => $property) {
            $object[(string) $name] = $this->sample(is_array($property) ? $property : []);
        }

        return $object;
    }
}
