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
require 'TGZ/autoload.php';
use ZhenyaGR\TGZ\TGZ as tg;

$tg = tg::create($token);
$tg->jsonMode(0);           // Сделать отправку json-запросов от телеграмм 
$tg->defaultParseMode('');  // Сделать parseMode() по умолчанию 

$update = $tg->getWebhookUpdate();
$tg->initVars($chat_id, $user_id, $text, $type, $callback_data, $callback_id, $msg_id);

if ($type == 'bot_command') {

    $img_url = 'https://12-kanal.ru/upload/iblock/62a/zb1mq2841smduhwwuv3jwjfv9eooyc50/fotograf3.jpg';

    if ($text == '/info' || $text == '/start') {
        $tg->msg("Самая лучшая библиотека для телеграмма – TG-Z!\n<a href=\"https://github.com/ZhenyaGR/TGZ\">Гитхаб</a>\n\nВ ней реализованы:\n1. Отправка сообщений c разным форматированием (/format)\n2. Создание кнопок и клавиатур (/buttons1 /buttons2)\n3. Отправка изображений двумя способами (/photo1, /photo2)\n4. Редактирование сообщений (/edit)\n5. Ответ на сообщение (/reply)\n6. Создание опросов (/poll)")
            ->params(['disable_web_page_preview' => true])
            ->parseMode("HTML")
            ->send();

    } else if ($text == '/buttons1') {
        $kbd = [
            [
                $tg->buttonCallback('Кнопка1', 'call1'),
                $tg->buttonCallback('Кнопка2', 'call2')
            ],
            [
                $tg->buttonUrl('Лучшая библиотека', "https://github.com/ZhenyaGR/TGZ")
            ]
        ];
        $tg->msg("Отправка кнопок с помощью kbd()")->kbd($kbd, inline: true)->send();

    } else if ($text == '/buttons2') {
        $kbd = [
            [$tg->buttonText('Кнопка')],
        ];
        $tg->msg("Отправка клавиатуры с помощью kbd()")->kbd($kbd, inline: false, one_time_keyboard: true)->send();

    } else if ($text == '/photo1') {
        $tg->msg("Отправка фотографии с использованием ссылки urlImg()")
            ->urlImg($img_url)
            ->send();

    }  else if ($text == '/photo2') {
        $tg->msg("Отправка фотографии с использованием sendPhoto img()")
            ->img($img_url)
            ->send();

    } else if ($text == '/photo3') {
        $tg->msg("Отправка нескольких фотографий с использованием sendMediaGroup img()")
            ->img([$img_url, $img_url, $img_url])
            ->send();
            
    } else if ($text == '/format') {

        $msg = "ВАРИАНТ С ИСПОЛЬЗОВАНИЕМ MarkdownV2\n\n*Жирный*\n\n_Курсив_\n\n__Подчёркнутый__\n\n`Моноширинный`\n\n[Ссылка](https://github.com/ZhenyaGR/TGZ)\n\n||Спойлер||";
        $tg->msg($msg . "\n\nparseMode\(\)")
            ->params(['disable_web_page_preview' => true])
            ->parseMode("MarkdownV2")->send();

        // $msg = "ВАРИАНТ С ИСПОЛЬЗОВАНИЕМ HTML\n\n<b>Жирный</b>\n\n<i>Курсив</i>\n\n<u>Подчёркнутый</u>\n\n<code>Моноширинный</code>\n\n<a href="https://github.com/ZhenyaGR/TGZ">Ссылка</a>\n\n <span class="tg-spoiler">Спойлер</span>";
        // $tg->msg($msg)->parseMode("HTML")->send();
    } else if ($text == '/edit') {
        $kbd = [
            [$tg->buttonCallback('Редактировать Сообщение', 'edit')]
        ];

        $tg->msg("Можно редактировать сообщения, используя конструктор")->kbd($kbd, inline: true)->send();

    } else if ($text == '/reply') {
        $tg->msg("Ответ на сообщение с помощью функции reply()")->reply()->send();

    } else if ($text == '/poll') {
        $tg->msg()
            ->poll('Создание опросов poll()')
            ->addAnswer('Ответ 1')
            ->addAnswer('Ответ 2')
            ->addAnswer('Ответ 3')
            ->isAnonymous(true)
            ->pollType('regular')
            ->send();
        // Остальные поля нужно прописывать самостоятельно в params()
    }

} else if ($type == 'text') {
    $tg->msg("Вы написали обычный текст")->send();

} elseif ($type == 'callback_query') {
    $tg->answerCallbackQuery($callback_id, ['text' => "Вы нажали кнопку!"]);

    if ($callback_data == 'call1') {
        $tg->msg("Вы нажали кнопку №1\nCallback data: $callback_data")->send();
    } else if ($callback_data == 'call2') {
        $tg->msg("Вы нажали кнопку №2\nCallback data: $callback_data")->send();
    } else if ($callback_data == 'edit') {
        $kbd = [
            [
                $tg->buttonCallback('Кнопка1', 'call1'),
                $tg->buttonCallback('Кнопка2', 'call2'),
            ]
        ];

        $tg->msg("Cообщение отредактировано sendEdit()\nСохраняется возможность отправить кнопки")->kbd($kbd, inline: true)->sendEdit();

    }

}


$tg->sendOK();

// Отправляем телеграмму "ok"
```
## Ссылка на этого бота @DemTGZ_bot

## Форматирование сообщений

### Вариант с ипользованием MarkdownV2  
\*Жирный\*  
\_Курсив\_  
\_\_Подчёркнутый\_\_  
\`Моноширинный\`  
\[Ссылка\]\(https://github.com/ZhenyaGR/TGZ\)  
\|\|Спойлер\|\|  

### Вариант с ипользованием HTML  
\<b\>Жирный\</b\>  
\<i\>Курсив\</i\>  
\<u\>Подчёркнутый\</u\>  
\<code\>Моноширинный\</code\>  
\<a href="https://github.com/ZhenyaGR/TGZ"\>Ссылка\</a\>  
\<span class="tg-spoiler"\>Спойлер\</span\>

test
