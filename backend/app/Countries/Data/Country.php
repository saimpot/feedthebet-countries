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
