# Подключение
### Используя composer
1\. Установить
```bash
composer require zhenyagr/tg-z:dev-main
```

2\. Подключить `autoload.php`
```php
require_once __DIR__.'/vendor/autoload.php';
```
### Вручную
1. Скачать последний релиз c [github](https://github.com/ZhenyaGR/TGZ)
2. Подключить `autoload.php`.  
> Вот так будет происходить подключение, если ваш бот находится в той же папке, что и папка `tg-z`
```php
require_once "tg-z/autoload.php";
```

# Примеры ботов
```php
<?php

use ZhenyaGR\TGZ\TGZ as tg;

$token = 'API_TOKEN';
$tg = tg::create($token);

$update = $tg->getWebhookUpdate(); // Получаем обнновление
$tg->initVars($chat_id, $user_id, $text, $type, $callback_data, $callback_id, $msg_id);
// Создаем переменные

if ($type == 'bot_command') {

    if ($text == '/buttons') {
        $kbd = [
            [
                $tg->buttonCallback('Кнопка1', 'call1'),
                $tg->buttonCallback('Кнопка2', 'call2')
            ],
            [$tg->buttonUrl('Ссылка', "https://github.com/ZhenyaGR/TGZ")]
        ];
        $tg->msg("Библиотека в полной мере поддерживает кнопки телеграмма")->kbd($kbd, ['inline' => true])->send();

    } else if ($text == '/photo1') {
        $tg->msg("Отправка фотографии с использованием ссылки")->urlImg('https://12-kanal.ru/upload/iblock/62a/zb1mq2841smduhwwuv3jwjfv9eooyc50/fotograf3.jpg')->send();

    } else if ($text == '/photo2') {
        $tg->msg("Отправка фотографии с использованием sendPhoto")->img('https://12-kanal.ru/upload/iblock/62a/zb1mq2841smduhwwuv3jwjfv9eooyc50/fotograf3.jpg')->send();

    } else if ($text == '/format') {
        $twoEnter = "\n\n";
        $msg = 'ВАРИАНТ С ИСПОЛЬЗОВАНИЕМ MarkdownV2' . $twoEnter . '*Жирный*' . $twoEnter . '_Курсив_' . $twoEnter . '__Подчёркнутый__' . $twoEnter . '`Моноширинный`' . $twoEnter . '[Ссылка](https://vk.com/ternabot)' . $twoEnter . ' ||Спойлер||' . $twoEnter;
        $tg->msg($msg)->parseMode("MarkdownV2")->send();

        // $msg = 'ВАРИАНТ С ИСПОЛЬЗОВАНИЕМ HTML' . $twoEnter . '<b>Жирный</b>' . $twoEnter . '<i>Курсив</i>' . $twoEnter . '<u>Подчёркнутый</u>' . $twoEnter . '<code>Моноширинный</code>' . $twoEnter . '<a href="https://vk.com/ternabot">Ссылка</a>' . $twoEnter . ' <span class="tg-spoiler">Спойлер</span>' . $twoEnter;
        // $tg->msg($msg)->parseMode("HTML")->send();
    } else if ($text == '/audio') {
        $tg->msg("Отправка аудио файлов!")->audio('url')->send();

    } else if ($text = '/edit') {
        $kbd = [
            [$tg->buttonCallback('Редактировать Сообщение', 'edit')]
        ];

        $tg->msg("Можно редактировать сообщения, используя конструктор")->kbd($kbd, ["inline" => true])->send();

    }

} else if ($type == 'text') {
    $tg->msg("Вы написали обычный текст")->send();

} elseif ($type == 'callback_query') {
    $tg->answerCallbackQuery($callback_id, ['text' => "Вы нажали кнопку!"]); // Обязательно ответить при нажатии (текст не обязателен)

    if ($callback_data == 'call1') {
        $tg->msg("Вы нажали кнопку №1\nCallback data: $callback_data")->send();
    } else if ($callback_data == 'call2') {
        $tg->msg("Вы нажали кнопку №2\nCallback data: $callback_data")->send();
    } else if ($callback_data == 'edit') {
        $kbd = [
            [
                $tg->buttonCallback('Кнопка1', 'call1'),
                $tg->buttonCallback('Кнопка2', 'call2'),
            ],

        ];

        $tg->msg("Cообщение отредактировано\nПри этом все параметры можно точно так же использовать, например, клавиатура")->kbd($kbd, ['inline' => true])->sendEdit($msg_id);

    }

}


$tg->end_script();
// Отправляем телеграмму "ok"
