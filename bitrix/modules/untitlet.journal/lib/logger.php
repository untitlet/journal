<?php
/**
 * Основной класс для логирования согласий
 * 
 * @package Untitlet\Journal
 */

namespace Untitlet\Journal;

use Bitrix\Main\Context;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Untitlet\Journal\ORM\LogTable;
use Untitlet\Journal\ORM\TextVersionTable;

class Logger
{
    /**
     * Логирование согласия
     * 
     * @param array $params Параметры логирования:
     *   - user_id: int|null ID пользователя
     *   - consent_code: string Код типа согласия (privacy_policy, marketing, cookies)
     *   - form_id: string|null Идентификатор формы
     *   - consent_type: string Тип получения (banner, form_checkbox, profile_update, api)
     *   - meta: array|null Дополнительные метаданные
     *   - status: string Статус (granted, withdrawn)
     * 
     * @return int|false ID записи или false при ошибке
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function log(array $params)
    {
        // Проверка включения логирования
        if (Option::get('untitlet.journal', 'enable_logging') !== 'Y') {
            return false;
        }
        
        // Обязательные параметры
        if (empty($params['consent_code'])) {
            throw new \Bitrix\Main\ArgumentException('Параметр consent_code обязателен');
        }
        
        // Получаем активную версию текста
        $version = TextVersionTable::getActiveVersion($params['consent_code']);
        
        if (!$version) {
            // Если версии нет, создаем пустую по умолчанию
            $versionId = TextVersionTable::createVersion(
                $params['consent_code'],
                '<p>Текст согласия не был предоставлен</p>',
                'Текст согласия не был предоставлен'
            );
        } else {
            $versionId = (int)$version['ID'];
        }
        
        // Получаем данные запроса
        $request = Context::getCurrent()->getRequest();
        $server = Context::getCurrent()->getServer();
        
        $ip = self::getIpAddress();
        $sessionId = session_id() ?: $request->getSessionId() ?: '';
        $userAgent = $server->getHttpUserAgent() ?: '';
        $pageUrl = $request->getRequestUri() ?: '';
        
        // Формируем данные для записи
        $data = [
            'USER_ID' => isset($params['user_id']) ? (int)$params['user_id'] : null,
            'SESSION_ID' => $sessionId,
            'IP' => $ip,
            'TIMESTAMP' => new \Bitrix\Main\Type\DateTime(),
            'CONSENT_VERSION_ID' => $versionId,
            'FORM_ID' => isset($params['form_id']) ? substr($params['form_id'], 0, 128) : null,
            'PAGE_URL' => substr($pageUrl, 0, 512),
            'USER_AGENT' => substr($userAgent, 0, 65535),
            'CONSENT_TYPE' => $params['consent_type'] ?? 'form_checkbox',
            'STATUS' => $params['status'] ?? 'granted',
            'META' => isset($params['meta']) && is_array($params['meta']) ? $params['meta'] : null
        ];
        
        // Добавляем запись
        $result = LogTable::add($data);
        
        if ($result->isSuccess()) {
            return $result->getId();
        }
        
        // Логирование ошибки
        \Bitrix\Main\EventManager::getInstance()->sendEventImmediately(
            'main',
            'OnEventLogEntryAdd',
            [
                'MODULE_ID' => 'untitlet.journal',
                'EVENT_ID' => 'UNTITLET_JOURNAL_LOG_ERROR',
                'MESSAGE' => 'Ошибка логирования согласия: ' . implode(', ', $result->getErrorMessages()),
                'DETAILS' => serialize($data)
            ]
        );
        
        return false;
    }

    /**
     * Отзыв согласия (withdrawal)
     * 
     * @param array $params Параметры:
     *   - user_id: int|null ID пользователя
     *   - consent_code: string Код типа согласия
     *   - form_id: string|null Идентификатор формы
     * 
     * @return int|false ID записи об отзыве
     */
    public static function withdraw(array $params)
    {
        if (empty($params['consent_code'])) {
            throw new \Bitrix\Main\ArgumentException('Параметр consent_code обязателен');
        }
        
        $params['status'] = 'withdrawn';
        return self::log($params);
    }

    /**
     * Полный отзыв всех согласий пользователя (GDPR Art. 17)
     * 
     * @param int $userId ID пользователя
     * @param string|null $reason Причина отзыва
     * @return bool
     */
    public static function withdrawAll($userId, $reason = null)
    {
        global $APPLICATION;
        
        // Проверка прав
        if ($APPLICATION->GetGroupRight('untitlet.journal') < 'K') {
            return false;
        }
        
        // Создаем запись о полном отзыве
        self::log([
            'user_id' => $userId,
            'consent_code' => 'all',
            'consent_type' => 'api',
            'status' => 'withdrawn',
            'meta' => ['full_withdrawal' => true, 'reason' => $reason]
        ]);
        
        // Логирование в event_log
        \Bitrix\Main\EventManager::getInstance()->sendEventImmediately(
            'main',
            'OnEventLogEntryAdd',
            [
                'MODULE_ID' => 'untitlet.journal',
                'EVENT_ID' => 'UNTITLET_JOURNAL_FULL_WITHDRAWAL',
                'MESSAGE' => 'Полный отзыв согласий пользователем ID=' . (int)$userId,
                'DETAILS' => $reason ? serialize(['reason' => $reason]) : ''
            ]
        );
        
        return true;
    }

    /**
     * Получить IP адрес с учетом анонимизации
     * 
     * @return string
     */
    private static function getIpAddress()
    {
        $request = Context::getCurrent()->getRequest();
        $ip = $request->getRemoteAddress();
        
        if (!$ip) {
            return '';
        }
        
        // Проверяем настройку анонимизации
        $maskAfterDays = (int)Option::get('untitlet.journal', 'ip_mask_after_days', 30);
        
        if ($maskAfterDays <= 0) {
            return $ip;
        }
        
        // Для новых записей возвращаем полный IP
        // Анонимизация применяется cron-скриптом постфактум
        return $ip;
    }

    /**
     * Анонимизация IP адресов для старых записей
     * 
     * @param int $days Количество дней, после которых анонимизировать
     * @return int Количество обновленных записей
     */
    public static function anonymizeOldIps($days = null)
    {
        global $DB;
        
        if ($days === null) {
            $days = (int)Option::get('untitlet.journal', 'ip_mask_after_days', 30);
        }
        
        if ($days <= 0) {
            return 0;
        }
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Маскируем IP (оставляем только последнюю часть)
        $sql = "
            UPDATE b_untitlet_journal_log
            SET IP = CONCAT('XXX.XXX.XXX.', SUBSTRING_INDEX(IP, '.', -1))
            WHERE TIMESTAMP < '{$cutoffDate}'
            AND IP IS NOT NULL
            AND IP NOT LIKE 'XXX.XXX.XXX.%'
        ";
        
        $DB->Query($sql, false);
        
        return $DB->AffectedRowsCount();
    }

    /**
     * Агент для автоочистки старых записей
     * 
     * @return string
     */
    public static function runCleanupAgent()
    {
        self::cleanup();
        
        // Возвращаем себя для следующего запуска
        return "\\Untitlet\\Journal\\Logger::runCleanupAgent();";
    }

    /**
     * Очистка старых записей
     * 
     * @param int|null $retentionYears Срок хранения в годах
     * @return int Количество удаленных записей
     */
    public static function cleanup($retentionYears = null)
    {
        global $DB;
        
        if ($retentionYears === null) {
            $retentionYears = (int)Option::get('untitlet.journal', 'retention_years', 3);
        }
        
        if ($retentionYears <= 0) {
            return 0;
        }
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionYears} years"));
        
        // Считаем количество записей до удаления
        $countResult = \Bitrix\Main\DB\Connection::getConnection()->query(
            "SELECT COUNT(*) as CNT FROM b_untitlet_journal_log 
             WHERE STATUS = 'granted' 
             AND TIMESTAMP < '{$cutoffDate}'"
        )->fetch();
        
        $deletedCount = (int)($countResult['CNT'] ?? 0);
        
        if ($deletedCount === 0) {
            return 0;
        }
        
        // Удаляем старые записи со статусом granted
        $sql = "
            DELETE FROM b_untitlet_journal_log
            WHERE STATUS = 'granted'
            AND TIMESTAMP < '{$cutoffDate}'
        ";
        
        $DB->Query($sql, false);
        
        // Сначала анонимизируем IP перед удалением (для аудита)
        self::anonymizeOldIps($retentionYears * 365);
        
        // Повторяем удаление после анонимизации
        $DB->Query($sql, false);
        
        // Логирование очистки
        \Bitrix\Main\EventManager::getInstance()->sendEventImmediately(
            'main',
            'OnEventLogEntryAdd',
            [
                'MODULE_ID' => 'untitlet.journal',
                'EVENT_ID' => 'UNTITLET_JOURNAL_CLEANUP',
                'MESSAGE' => "Автоматическая очистка журнала: удалено {$deletedCount} записей старше {$retentionYears} лет",
                'DETAILS' => serialize([
                    'deleted_count' => $deletedCount,
                    'cutoff_date' => $cutoffDate,
                    'retention_years' => $retentionYears
                ])
            ]
        );
        
        // Отправка уведомления если настроен email
        $notifyEmail = Option::get('untitlet.journal', 'notify_email');
        if ($notifyEmail && filter_var($notifyEmail, FILTER_VALIDATE_EMAIL)) {
            \Bitrix\Main\Mail\Event::send([
                'EVENT_NAME' => 'UNTITLET_JOURNAL_CLEANUP_NOTIFY',
                'LID' => SITE_ID,
                'FIELDS' => [
                    'DELETED_COUNT' => $deletedCount,
                    'RETENTION_YEARS' => $retentionYears,
                    'CLEANUP_DATE' => date('d.m.Y H:i:s')
                ],
                'TO' => $notifyEmail
            ]);
        }
        
        return $deletedCount;
    }

    /**
     * Получить статистику по логам
     * 
     * @param array $filter Фильтр
     * @return array
     */
    public static function getStatistics(array $filter = [])
    {
        $defaultFilter = [
            'LOG.TIMESTAMP_GREATER' => date('Y-m-d H:i:s', strtotime('-30 days'))
        ];
        
        $filter = array_merge($defaultFilter, $filter);
        
        $stats = LogTable::getList([
            'filter' => $filter,
            'select' => [
                'TOTAL' => new \Bitrix\Main\ORM\Fields\ExpressionField(
                    'TOTAL',
                    'COUNT(1)'
                ),
                'GRANTED' => new \Bitrix\Main\ORM\Fields\ExpressionField(
                    'GRANTED',
                    "COUNT(CASE WHEN #STATUS# = 'granted' THEN 1 END)"
                ),
                'WITHDRAWN' => new \Bitrix\Main\ORM\Fields\ExpressionField(
                    'WITHDRAWN',
                    "COUNT(CASE WHEN #STATUS# = 'withdrawn' THEN 1 END)"
                )
            ]
        ])->fetch();
        
        return $stats;
    }
}
