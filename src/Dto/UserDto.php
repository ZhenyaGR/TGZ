<?php

namespace ZhenyaGR\TGZ\Dto;

/**
 * Базовый DTO для объекта User из Telegram API.
 */
class UserDto
{
    public function __construct(
        public int $id,
        public bool $isBot,
        public string $firstName,
        public ?string $lastName,
        public ?string $username,
        public ?string $languageCode
    ) {
    }

    /**
     * Статический метод-фабрика для создания DTO из массива от Telegram.'
     *
     * @param array $data
     *
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id: $data['id'],
            isBot: $data['is_bot'],
            firstName: $data['first_name'],
            lastName: $data['last_name'] ?? null,
            username: $data['username'] ?? null,
            languageCode: $data['language_code'] ?? null
        );
    }
}