# Что такое TGZ?
**TGZ** — это современная PHP-библиотека для создания ботов в Telegram.
Позволяет быстро и удобно работать с Bot API, поддерживает Webhook и LongPoll, а также все типы сообщений и клавиатур.

## Поддерживаемые возможности
* `Webhook API`
* `LongPoll API`
* Работа с `BOT API`
* Все виды клавиатур и кнопок, включая опросы
* Работа с голосовыми сообщениями, документами и другими медиа-файлами

## [Полная документация](https://zhenyagr.github.io/TGZ-Doc/)

# Подключение Библиотеки
## Используя composer
1. Установить
```bash
composer require zhenyagr/tgz:dev-main
```

2. Подключить `autoload.php`
```php
require_once __DIR__ . 'vendor/autoload.php';
```
## Вручную
1. Скачать последний релиз c [github](https://github.com/ZhenyaGR/TGZ)
2. Подключить `autoload.php`.  
> Вот так будет происходить подключение, если ваш бот находится в той же папке, что и папка `TGZ`
```php
require_once "TGZ/autoload.php";
```

## Первоначальная настройка, создание бота и получение токена
Более подробно описано в [файле](TokenCreate.md)

# Примеры использования

## Получение переменных (WEBHOOK)
```php
<?php
require_once __DIR__ . 'vendor/autoload.php';  // Подключаем библиотеку
use ZhenyaGR\TGZ\TGZ;  // Используем основной класс

$tg = TGZ::create(BOT_TOKEN); // Создаем объект бота

$user_id = $tg->getUserId();
$chat_id = $tg->getChatId();
$text = $tg->getText();
$msg_id = $tg->getMsgId();
$type = $tg->getType();
// Некоторые переменные можно получить по отдельности

$tg->initVars($chat_id, $user_id, $text, $type, $callback_data, $query_id, $msg_id, $is_bot, $is_command);
// Все переменные сразу одним методом
```

## Получение переменных (LONGPOLL)
```php
<?php
require_once __DIR__ . 'vendor/autoload.php';  // Подключаем библиотеку
use ZhenyaGR\TGZ\LongPoll;   // Используем класс LongPoll
use ZhenyaGR\TGZ\TGZ;

$lp = LongPoll::create(BOT_TOKEN); 
// Создаем объект бота 

$lp->listen(function(TGZ $tg) {
        
    $user_id = $tg->getUserId();
    $chat_id = $tg->getChatId();
    $text = $tg->getText();
    $msg_id = $tg->getMsgId();
    $type = $tg->getType();
    // Некоторые переменные можно получить по отдельности
    
    $tg->initVars($chat_id, $user_id, $text, $type, $callback_data, $query_id, $msg_id, $is_bot, $is_command);
    // Все переменные сразу одним методом
});
```

## Вызов любых методов BOT API. Например copyMessage (WEBHOOK)
```php
<?php
require_once __DIR__ . 'vendor/autoload.php';  // Подключаем библиотеку
use ZhenyaGR\TGZ\TGZ;  

$tg = TGZ::create(BOT_TOKEN); 
$tg->initVars($chat_id, $user_id, $text, $type, msg_id: $msg_id); 
// Инициализируем переменные

if ($type == 'text' || $type == 'bot_command') {
    $tg->copyMessage([
        'chat_id' => $chat_id, 
        'from_chat_id' => $chat_id,
        'message_id' => $msg_id
    ]); 
    // Используем метод телеграма, в который передаем 3 параметра:
    // chat_id, from_chat_id и message_id
}
```

## Эхо-бот с конструктором сообщений (WEBHOOK)
```php
<?php
require_once __DIR__ . 'vendor/autoload.php';  // Подключаем библиотеку
use ZhenyaGR\TGZ\TGZ; 

$tg = TGZ::create(BOT_TOKEN);
$tg->initVars(text: $text, type: $type);

if ($type == 'text' || $type == 'bot_command') {
    $tg->msg($text)->send(); 
    // Отправляем сообщение с таким-же текстом
}
```

## Вызов любых методов BOT API. Например copyMessage (LONGPOLL)
```php
<?php
require_once __DIR__ . 'vendor/autoload.php';  // Подключаем библиотеку
use ZhenyaGR\TGZ\LongPoll;  // Меняем класс
use ZhenyaGR\TGZ\TGZ; 

$lp = LongPoll::create(BOT_TOKEN); 

$lp->listen(function(TGZ $tg) {
    // Ждём новый update
    
    $tg->initVars($chat_id, $user_id, $text, $type, msg_id: $msg_id); 
    // Инициализируем переменные
    
    if ($type == 'text' || $type == 'bot_command') {
        $tg->copyMessage([
            'chat_id' => $chat_id, 
            'from_chat_id' => $chat_id,
            'message_id' => $msg_id,
        ]); 
        // Используем метод телеграма, в который передаем 3 параметра:
        // chat_id, from_chat и message_id
    }
});
```

## Эхо-бот с конструктором сообщений (LONGPOLL)
```php
<?php
require_once __DIR__ . 'vendor/autoload.php';  // Подключаем библиотеку
use ZhenyaGR\TGZ\LongPoll; 
use ZhenyaGR\TGZ\TGZ; 

$lp = LongPoll::create(BOT_TOKEN);

$lp->listen(function(TGZ $tg) {

    $tg->initVars(text: $text, type: $type);
    
    if ($type == 'text' || $type == 'bot_command') {
        $tg->msg($text)->send(); // Отправляем сообщение с таким-же текстом
    }
});
```