<?php

declare(strict_types=1);

namespace App\Countries\Data;

final readonly class Country
{
    public function __construct(
        public string $code,
        public string $name,
        public string $flagUrl,
        public ?string $flagAlt = null,
    ) {}

    /**
     * Builds a country from untrusted values; null when a required one is not a non-empty string.
     */
    public static function tryCreate(mixed $code, mixed $name, mixed $flagUrl, mixed $flagAlt = null): ?self
    {
        foreach ([$code, $name, $flagUrl] as $required) {
            if (! is_string($required) || $required === '') {
                return null;
            }
        }

        return new self($code, $name, $flagUrl, is_string($flagAlt) && $flagAlt !== '' ? $flagAlt : null);
    }

    /**
     * @param  array{code: string, name: string, flagUrl: string, flagAlt?: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['code'], $data['name'], $data['flagUrl'], $data['flagAlt'] ?? null);
    }

    /**
     * @return array{code: string, name: string, flagUrl: string, flagAlt: string|null}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'flagUrl' => $this->flagUrl,
            'flagAlt' => $this->flagAlt,
        ];
    }
}
