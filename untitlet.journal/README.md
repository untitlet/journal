# untitlet.journal - Журнал согласий 152-ФЗ / GDPR

Модуль для платформы 1С-Битрикс, предназначенный для фиксации, версионирования, фильтрации, экспорта и регламентного удаления юридически значимых логов получения/отзыва согласий на обработку персональных данных.

## Соответствие требованиям

- **152-ФЗ** (ст. 6, 9, 18) — Федеральный закон РФ о персональных данных
- **GDPR** (Art. 7, 30) — Общий регламент по защите данных ЕС
- **Рекомендации Роскомнадзора** по доказательной базе

## Требования

- Bitrix: 20.0.0+
- PHP: 8.0+
- База данных: MySQL или PostgreSQL
- D7 ORM

## Установка

1. Скопируйте папку `untitlet.journal` в `/bitrix/modules/`
2. В административной панели перейдите в **Настройки → Настройки продукта → Модули**
3. Найдите модуль **«Журнал согласий 152-ФЗ / GDPR»** и нажмите **Установить**

## Структура модуля

```
untitlet.journal/
├── admin/                          # Административные страницы
│   └── untitlet_journal_list.php   # Список записей журнала
├── cron/                           # Cron-скрипты
│   └── cleanup.php                 # Автоочистка старых записей
├── install/                        # Файлы установки
│   ├── index.php                   # Установка/удаление модуля
│   ├── version.php                 # Версия модуля
│   └── options.php                 # Настройки модуля
├── lib/                            # Библиотека модуля (D7)
│   ├── orm/
│   │   ├── logtable.php            # ORM для таблицы логов
│   │   └── textversiontable.php    # ORM для версий текстов
│   ├── logger.php                  # Основной класс логирования
│   └── eventhandlers/
│       └── eventhandlers.php       # Обработчики событий Bitrix
├── lang/ru/                        # Переводы
├── images/                         # Иконки модуля
├── include.php                     # Точка входа модуля
└── README.md                       # Этот файл
```

## Использование

### PHP API

#### Логирование согласия

```php
\Untitlet\Journal\Logger::log([
    'user_id'       => $USER->GetID(),      // ID пользователя (0 для гостей)
    'consent_code'  => 'privacy_policy',    // Код согласия
    'form_id'       => 'main_feedback',     // Идентификатор формы
    'consent_type'  => 'form_checkbox',     // Тип: banner, form_checkbox, profile_update, api
    'status'        => 'granted',           // granted или withdrawn
    'meta'          => ['campaign' => 'spring2026']  // Дополнительные данные
]);
```

#### Отзыв согласия

```php
\Untitlet\Journal\Logger::withdraw(
    $userId,                               // ID пользователя
    'privacy_policy',                      // Код согласия
    ['form_id' => 'profile_settings']      // Дополнительные данные
);
```

### JavaScript интеграция

Для кастомных баннеров и форм используйте событие:

```javascript
BX.onCustomEvent('untitlet:consent:accepted', [{
    consent_code: 'cookies',
    form_id: 'cookie_banner_v2',
    consent_type: 'banner',
    meta: { banner_version: '2.1' }
}]);
```

Обработчик события (пример):

```javascript
BX.addCustomEvent('untitlet:consent:accepted', function(params) {
    BX.ajax({
        url: '/local/ajax/consent_logger.php',
        method: 'POST',
        data: params,
        onsuccess: function() {
            console.log('Consent logged');
        }
    });
});
```

## Настройки модуля

Перейдите в **Настройки → Журнал согласий → Настройки модуля**

| Параметр | Описание | По умолчанию |
|----------|----------|--------------|
| Срок хранения записей | Через сколько лет удалять записи (3 или 5) | 3 года |
| Анонимизация IP через (дней) | После скольких дней заменять IP на маску | 0 (не анонимизировать) |
| Включить логирование | Глобальное включение/выключение | Да |
| Email для уведомлений | Куда отправлять отчеты об очистке | — |

## Административный интерфейс

Путь: **Настройки → Журнал согласий → Записи журнала**

Возможности:
- Просмотр всех записей с пагинацией
- Фильтрация по:
  - Пользователю
  - Форме
  - Статусу (granted/withdrawn)
  - Типу согласия
  - Дате
- Экспорт в CSV (UTF-8 с BOM для Excel)
- Экспорт в PDF (требуется mPDF)

## Автоматическая очистка

### Настройка cron

Добавьте в crontab:

```bash
0 2 * * * /usr/bin/php /path/to/bitrix/modules/untitlet.journal/cron/cleanup.php
```

Очистка происходит ежедневно в 2:00 ночи.

### Резервный агент Bitrix

Если cron недоступен, модуль может использовать агенты Bitrix (настраивается отдельно).

## Таблицы базы данных

### b_untitlet_journal_log

Основная таблица логов.

| Поле | Тип | Описание |
|------|-----|----------|
| ID | int | Первичный ключ |
| USER_ID | int | ID пользователя (0 для гостей) |
| SESSION_ID | varchar(64) | ID сессии Bitrix |
| IP | varchar(45) | IPv4/IPv6 адрес |
| TIMESTAMP | datetime | Время получения согласия |
| CONSENT_VERSION_ID | int | Ссылка на версию текста |
| FORM_ID | varchar(128) | Идентификатор формы |
| PAGE_URL | varchar(512) | URL страницы |
| USER_AGENT | text | Браузер/ОС |
| CONSENT_TYPE | enum | banner, form_checkbox, profile_update, api |
| STATUS | enum | granted, withdrawn |
| META | json | Дополнительные данные |

### b_untitlet_journal_text_version

Версии текстов согласий.

| Поле | Тип | Описание |
|------|-----|----------|
| ID | int | Первичный ключ |
| CODE | varchar(64) | Код согласия (privacy_policy, cookies, marketing) |
| VERSION | int | Номер версии |
| TEXT_HTML | text | HTML версия текста |
| TEXT_PLAIN | text | Plain text версия |
| IS_ACTIVE | boolean | Флаг активной версии |
| CREATED_AT | datetime | Дата создания |

## Безопасность

- **Неизменяемость**: Записи только добавляются (INSERT only). UPDATE/DELETE запрещены на уровне ORM.
- **Анонимизация IP**: Опциональная маска IP после N дней.
- **Минимизация ПДн**: Не сохраняются email, телефон, имя. Только внутренний user_id.
- **Контроль доступа**: Только для групп с правами untitlet_journal_admin.
- **Защита от CSRF/XSS/SQLi**: Через стандартные механизмы D7.

## GDPR Art. 17 — Право на удаление

Для полного отзыва согласия пользователя:

```php
// Получить все записи пользователя
$rs = \Untitlet\Journal\ORM\LogTable::getList([
    'filter' => ['=USER_ID' => $userId]
]);

// Анонимизировать или удалить (с аудит-логом)
while ($row = $rs->fetch()) {
    // Логирование действия
    \Bitrix\Main\EventManager::getInstance()->sendEvent(
        'main',
        'OnEventLogEntryAdd',
        [
            'MODULE_ID' => 'untitlet.journal',
            'EVENT_ID' => 'GDPR_DATA_ERASURE',
            'MESSAGE' => 'User data erased per GDPR Art. 17',
        ]
    );
}
```

## Интеграция с формами

### main.feedback

```php
// В обработчике формы
if ($_POST['consent_accepted'] === 'Y') {
    \Untitlet\Journal\Logger::log([
        'form_id'      => 'main_feedback',
        'consent_code' => 'privacy_policy',
        'consent_type' => 'form_checkbox',
    ]);
}
```

### Кастомные формы

Добавьте чекбокс согласия:

```html
<label>
    <input type="checkbox" name="consent_accepted" value="Y" required />
    Я согласен на обработку <a href="/privacy/">персональных данных</a>
</label>
```

## Лицензия

© Untitlet. Все права защищены.

## Поддержка

- Документация: `/docs/`
- Примеры кода: `/examples/`
- Вопросы: support@untitlet.ru
