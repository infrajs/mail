# Отправка писем
**Disclaimer:** Module is not complete and not ready for use yet.

## Конфиг
Параметр **From** для хостингов, которые обязывают в качестве отправителя указывать адрес с доменом сайта, как 1gb.ru.
Кнопка "Ответить на письмо" работает, как обычно, согласно поля **Reply-To** куда подставляется указаный при вызове функций mail адрес.
```php
Mail::$conf['From']='noreplay@mydomain.ru';
```

```php
use infrajs\mail\Mail;

Mail::toAdmin($subject, $from, $body, $debug = false);
Mail::fromAdmin($subject, $to, $body);
Mail::check($email);//Проверка является ли строка почтовым адресом
```

Email адрес администратора определяется согласно данным [infrajs/access](https://github.com/infrajs/access)
