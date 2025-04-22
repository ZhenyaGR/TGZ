# Подключение
### Используя composer
1\. Установить
```bash
composer require zhenyagr/tg-z:dev-main
```

2\. Подключить `autoload.php`
```php
require_once '/vendor/autoload.php';
```
### Вручную
1. Скачать последний релиз c [github](https://github.com/ZhenyaGR/TGZ)
2. Подключить `autoload.php`.  
> Вот так будет происходить подключение, если ваш бот находится в той же папке, что и папка `TGZ`
```php
require_once "TGZ/autoload.php";
```

### [Первоначальная настройка, создание бота и получение токена](TokenCreate.md)

---
# Примеры использования

### Инициализация переменных
```php
<?php
require 'TGZ/autoload.php';  // Подключаем библиотеку
use ZhenyaGR\TGZ\TGZ as tg;  // Используем основной класс

$tg = tg::create(BOT_TOKEN); // Создаем объект бота

$tg->initUserID($user_id)
    ->initChatID($chat_id)
    ->initText($text)
    ->initMsgID($msg_id)
    ->initType($type);
// Некоторые переменные можно инициализировать по отдельности

$tg->initVars($chat_id, $user_id, $text, $type, $callback_data, $callback_id, $msg_id, $is_bot, $is_command);
// Все переменные сразу одним методом
```


### Вызов любых методов BOT API. Например copyMessage
```php
<?php
require 'TGZ/autoload.php';  
use ZhenyaGR\TGZ\TGZ as tg;  

$tg = tg::create(BOT_TOKEN); 
$tg->initVars($chat_id, $user_id, $text, $type, $callback_data, $callback_id, $msg_id); 
// Инициализируем переменные

if ($type == 'text' || $type == 'bot_command') {
    $tg->copyMessage([
        'chat_id' => $chat_id, 
        'message_id' => $msg_id
    ]); 
    // Используем метод телеграма, передаем 2 параметра: chat_id и message_id
}
```
### Эхо-бот с конструктором сообщений
```php
<?php
require 'TGZ/autoload.php'; 
use ZhenyaGR\TGZ\TGZ as tg; 

$tg = tg::create(BOT_TOKEN);
$tg->initVars($chat_id, $user_id, $text, $type);

if ($type == 'text' || $type == 'bot_command') {
    $tg->msg($text)->send(); // Отправляем сообщение с таким-же текстом
}
```
### Отправка медиа-файлов
```php
<?php
require 'TGZ/autoload.php'; 
use ZhenyaGR\TGZ\TGZ as tg; 

$tg = tg::create(BOT_TOKEN);
$tg->initVars($chat_id, $user_id, $text, $type);

if ($type == 'text' || $type == 'bot_command') {
    switch ($text) {
    
        case '/fileid':
            $id = $tg->getFileID('file url', $chat_id, 'file type');
            // Получаем ID медиа-файла, загружая его на сервер телеграма
            $tg->msg($id)->send();
            // Этот File_id можно использовать для повторной отправки файла
            break;
    
        case '/img':
            $tg->msg()
                ->img('img.jpg') // Отправка Изображений
                ->send();
            break;
            
        case '/img_url':
            $tg->msg("Текст сообщения")
                ->urlImg($img_url)
                ->send();
            // Добавляет ссылку в текст сообщения, используя пробел нулевой ширины
            break;
            
        case '/gif':
            $tg->msg()
                ->gif('gif.gif') // Отправка Гиф
                ->send();
            break;
            
        case '/video':
            $tg->msg()
                ->video('video.mp4') // Отправка Видео
                ->send();
            break;
            
        case '/audio':
            $tg->msg()
                ->audio('audio.mp3') // Отправка Аудио
                ->send();
            break;
            
        case '/voice':
            $tg->msg()
                ->voice('voice.mp3') // Отправка Голосового сообщения
                ->send();
            break;
            
        case '/dice':
            $emoji = ["🎲", "🎯", "🏀", "⚽", "🎳", "🎰"];
            $tg->msg()
                ->dice($emoji[array_rand($emoji)]) // Отправка Интерактивного сообщения
                ->send();
            break;
            
        case '/doc':
            $tg->msg()
                ->doc('doc.txt') // Отправка Документа
                ->send();
            break;
            
        case '/sticker':
            $tg->msg()
                ->sticker('sticker_id')
                ->send(); // Отправка стикеров
            break;
            
        case '/combine':
            $tg->msg()
                ->img('img.jpg')
                ->video('video.mp4')
                ->send(); // Отправка нескольких файлов
            break;
    }
}
```
### Отправка клавиатуры и Кнопок
```php
<?php    
require 'TGZ/autoload.php'; 
use ZhenyaGR\TGZ\TGZ as tg; 

$tg = tg::create(BOT_TOKEN);
$tg->initVars($chat_id, $user_id, $text, $type, $callback_data, $callback_id, $msg_id);

if ($type == 'text' || $type == 'bot_command') {
    switch ($text) {
        case '/inline':
            // Создаем массив с кнопками
            $kbd = [
                [   // Создаем callback-кнопки
                    $tg->buttonCallback('Кнопка 1', 'call1'),
                    $tg->buttonCallback('Кнопка 2', 'call2')
                ],
                [
                    $tg->buttonCallback('Кнопка-Редактирование', 'edit')
                ],
                [   // Создаем url-кнопку
                    $tg->buttonUrl('GitHub Библиотеки', "https://github.com/ZhenyaGR/TGZ")
                ]
            ];
            $tg->msg("Текст сообщения")
                ->kbd($kbd, inline: true)
                ->send();
            // Отправляем с параметром inline: true
            break;

        case '/keyboard':
            // Создаем массив с кнопками
            $kbd = [ // Создаем текстовые кнопки
                [$tg->buttonText('Текстовая кнопка')],
                [$tg->buttonText('Удалить клавиатуру')]
            ];
            $tg->msg("Текст сообщения")
                ->kbd($kbd, inline: false, resize_keyboard: true, one_time_keyboard: false)
                ->send();
            // Отправляем с параметрами inline: false, resize_keyboard: true, one_time_keyboard: false
            break;
            
        case 'Текстовая кнопка':
            // Приходит как обычное сообщение
            $tg->msg("Вы нажали текстовую кнопку")->send();        
            break;
            
        case 'Удалить клавиатуру':
            $tg->msg("Клавиатура удалена")
                ->kbd(remove_keyboard: true)
                ->send();
            // Удаляем клавиатуру
            break;
    }
} else if ($type == 'callback_query') {
    $tg->answerCallbackQuery($callback_id, ['text' => "Вы нажали кнопку!"]);    
    // Отправляем уведомление об нажатии
    switch ($callback_data) {
        case 'call1':
            $tg->msg("Вы нажали кнопку №1")->send();
            break;
            
        case 'call2':
            $tg->msg("Вы нажали кнопку №2")->send();
            break;
        
        case 'edit':
            $tg->msg("Сообщение отредактировано")->sendEdit();
            // Редактируем сообщение
            break;
    }
}
```
### Отправка Опросов
```php
<?php    
require 'TGZ/autoload.php'; 
use ZhenyaGR\TGZ\TGZ as tg; 

$tg = tg::create(BOT_TOKEN);
$tg->initVars($chat_id, $user_id, $text, $type, $callback_data, $callback_id, $msg_id);

if ($type == 'text' || $type == 'bot_command') {
    switch ($text) {
        case '/poll':
            $tg->msg()
                ->poll('Вопрос опроса') // Отображаемый вопрос
                ->addAnswer('Ответ 1')  // Добавляем ответы
                ->addAnswer('Ответ 2')  
                ->addAnswer('Ответ 3')
                ->isAnonymous(true)     // Анонимный опрос
                ->pollType('regular')   // Тип опроса
                ->send();
            break;
    }
}
```
### Дополнительные функции конструктора сообщений
```php
<?php    
require 'TGZ/autoload.php'; 
use ZhenyaGR\TGZ\TGZ as tg; 

$tg = tg::create(BOT_TOKEN);
$tg->initVars($chat_id, $user_id, $text, $type, $callback_data, $callback_id, $msg_id);

if ($type == 'text' || $type == 'bot_command') {
    switch ($text) {
        case '/reply':
            $tg->msg('Ответ на сообщение')
                ->reply()
                ->send();
            break;
            
        case '/action':
            $send = $tg->msg('Отображение действий бота с помощью sendChatAction')
                ->action('typing');
                // Пока бот не отправит сообщение, будет отображаться "Печатает..."
            sleep(3);
            $send->send();
            break;
        
        case '/params':
            $tg->msg("Дополнительные параметры сообщения")
                ->params(['disable_web_page_preview' => true]) // Отключаем предпросмотр ссылок
                ->send();
            break;
    }
}
```
## Форматирование сообщений

```php
<?php
require 'TGZ/autoload.php';
use ZhenyaGR\TGZ\TGZ as tg;

$tg = tg::create(BOT_TOKEN);
$tg->initVars($chat_id, $user_id, $text, $type, $callback_data, $callback_id, $msg_id);

if ($type == 'text' || $type == 'bot_command') {    
    switch ($text) {
        case '/html':
            $tg->msg("<b>Отправка сообщений с форматированием HTML</b>")
                ->parseMode('HTML')
                ->send();
            break;
            
        case '/markdown':
            $tg->msg("*Отправка сообщений с форматированием Markdown*")
                ->parseMode('MarkdownV2')
                ->send();
            break;
    }
}
```
---
### **MarkdownV2**
```markdown
*Жирный*  
_Курсив_  
__Подчёркнутый__  
`Моноширинный`  
[Ссылка](https://github.com/ZhenyaGR/TGZ)  
||Спойлер||
```

### **HTML**
```html
<b>Жирный</b>  
<i>Курсив</i>  
<u>Подчёркнутый</u>  
<code>Моноширинный</code>  
<a href="https://github.com/ZhenyaGR/TGZ">Ссылка</a>  
<span class="tg-spoiler">Спойлер</span>
```

### Примечания:
Для MarkdownV2 экранируйте символы `_*[]()~>#+-=|{}.!` с помощью `\`, например: `\_некурсив\_`

## Ссылка на демонстрационного бота: https://t.me/DemTGZ_bot
