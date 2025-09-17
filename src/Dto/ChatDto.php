<?php

namespace ZhenyaGR\TGZ\Dto;

/**
 * DTO для объекта Chat из Telegram API.
 *
 * Объект представляет собой чат. Это может быть личный чат, группа,
 * супергруппа или канал.
 *
 * @see https://core.telegram.org/bots/api#chat
 */
class ChatDto
{
    /**
     * @param int     $id          Уникальный идентификатор чата.
     * @param string  $type        Тип чата: "private", "group", "supergroup"
     *                             или "channel".
     * @param ?string $title       Опционально. Название для супергрупп,
     *                             каналов и групповых чатов.
     * @param ?string $username    Опционально. Имя пользователя (для личных
     *                             чатов, супергрупп и каналов).
     * @param ?string $firstName   Опционально. Имя собеседника в личном чате.
     * @param ?string $lastName    Опционально. Фамилия собеседника в личном
     *                             чате.
     * @param ?string $description Опционально. Описание для групп, супергрупп
     *                             и каналов.
     * @param ?string $inviteLink  Опционально. Основная пригласительная ссылка
     *                             для групп, супергрупп и каналов.
     * @param ?bool   $isForum     Опционально. True, если супергруппа является
     *                             форумом.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly ?string $title,
        public readonly ?string $username,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $description,
        public readonly ?string $inviteLink,
        public readonly ?bool $isForum,
        // Можно добавить и другие поля по необходимости: photo, pinned_message, permissions и т.д.
    ) {}

    /**
     * Статический метод-фабрика для создания DTO из массива от Telegram.
     *
     * @param array $data Массив данных чата от Telegram API.
     *
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            id:             $data['id'],
            type:           $data['type'],
            title:          $data['title'] ?? null,
            username:       $data['username'] ?? null,
            firstName:      $data['first_name'] ?? null,
            lastName:       $data['last_name'] ?? null,
            description:    $data['description'] ?? null,
            inviteLink:     $data['invite_link'] ?? null,
            isForum:        $data['is_forum'] ?? null,
        );
    }
}