<?php

namespace ZhenyaGR\TGZ\Dto;

/**
 * Базовый DTO для объекта User из Telegram API.
 */
class UserDto
{
    public function __construct(
        public readonly int $id,
        public readonly bool $isBot,
        public readonly string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $username,
        public readonly ?string $languageCode
    ) {
    }

    /**
     * Статический метод-фабрика для создания DTO из массива от Telegram.
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