<?php
/**
 * Обработчики событий Bitrix для автоматического логирования согласий
 */

namespace Untitlet\Journal;

use Bitrix\Main\Context;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Config\Option;

class EventHandlers
{
    /**
     * Обработчик события OnBeforeFormSubmit
     * Логирование согласия при отправке формы
     * 
     * @param Event $event
     * @return EventResult
     */
    public static function onBeforeFormSubmit(Event $event)
    {
        if (Option::get('untitlet.journal', 'enable_logging') !== 'Y') {
            return new EventResult();
        }
        
        $params = $event->getParameters();
        
        // Проверяем наличие чекбокса согласия в форме
        $request = Context::getCurrent()->getRequest();
        $postData = $request->getPostList();
        
        // Ищем поля согласия (consent, privacy, gdpr и т.п.)
        $consentFields = ['consent', 'privacy', 'gdpr', 'personal_data', 'marketing'];
        $foundConsent = false;
        $consentType = 'form_checkbox';
        
        foreach ($consentFields as $field) {
            if (isset($postData[$field]) && $postData[$field] === 'Y') {
                $foundConsent = true;
                break;
            }
            if (isset($postData[$field . '_check']) && $postData[$field . '_check'] === 'Y') {
                $foundConsent = true;
                break;
            }
        }
        
        // Также проверяем явные параметры формы
        if (isset($params['RESULT']['consent'])) {
            $foundConsent = true;
        }
        
        if (!$foundConsent) {
            return new EventResult();
        }
        
        // Определяем тип согласия по названию формы
        $formId = $params['WEB_FORM_ID'] ?? $params['FORM_ID'] ?? 'unknown_form';
        $consentCode = self::determineConsentCode($formId, $postData);
        
        global $USER;
        
        try {
            Logger::log([
                'user_id' => $params['USER_ID'] ?? ($USER->IsAuthorized() ? $USER->GetID() : null),
                'consent_code' => $consentCode,
                'form_id' => 'form_' . $formId,
                'consent_type' => 'form_checkbox',
                'meta' => [
                    'web_form_id' => $formId,
                    'post_data_keys' => array_keys($postData)
                ]
            ]);
        } catch (\Exception $e) {
            // Тихое игнорирование ошибок чтобы не ломать отправку формы
            \Bitrix\Main\Diag\Debug::writeToFile(
                'Untitlet Journal: ' . $e->getMessage(),
                '',
                '/upload/untitlet_journal_errors.log'
            );
        }
        
        return new EventResult();
    }

    /**
     * Обработчик события OnAfterUserRegister
     * Логирование согласия при регистрации пользователя
     * 
     * @param Event $event
     * @return EventResult
     */
    public static function onAfterUserRegister(Event $event)
    {
        if (Option::get('untitlet.journal', 'enable_logging') !== 'Y') {
            return new EventResult();
        }
        
        $params = $event->getParameters();
        $userId = $params['ID'] ?? $params['USER_ID'] ?? null;
        
        if (!$userId) {
            return new EventResult();
        }
        
        try {
            Logger::log([
                'user_id' => (int)$userId,
                'consent_code' => 'privacy_policy',
                'form_id' => 'user_registration',
                'consent_type' => 'profile_update',
                'meta' => [
                    'event' => 'user_registration'
                ]
            ]);
        } catch (\Exception $e) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                'Untitlet Journal: ' . $e->getMessage(),
                '',
                '/upload/untitlet_journal_errors.log'
            );
        }
        
        return new EventResult();
    }

    /**
     * Обработчик события OnUserAuthorize
     * Логирование при авторизации (опционально)
     * 
     * @param Event $event
     * @return EventResult
     */
    public static function onUserAuthorize(Event $event)
    {
        if (Option::get('untitlet.journal', 'enable_logging') !== 'Y') {
            return new EventResult();
        }
        
        // Эта настройка отключена по умолчанию, включается опционально
        if (Option::get('untitlet.journal', 'log_authorization') !== 'Y') {
            return new EventResult();
        }
        
        $params = $event->getParameters();
        $userId = $params['USER_ID'] ?? null;
        
        if (!$userId) {
            return new EventResult();
        }
        
        try {
            Logger::log([
                'user_id' => (int)$userId,
                'consent_code' => 'session_tracking',
                'form_id' => 'authorization',
                'consent_type' => 'api',
                'meta' => [
                    'event' => 'user_authorization'
                ]
            ]);
        } catch (\Exception $e) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                'Untitlet Journal: ' . $e->getMessage(),
                '',
                '/upload/untitlet_journal_errors.log'
            );
        }
        
        return new EventResult();
    }

    /**
     * Определение кода согласия на основе формы и данных
     * 
     * @param string|int $formId
     * @param array $postData
     * @return string
     */
    private static function determineConsentCode($formId, $postData)
    {
        $formIdStr = (string)$formId;
        
        // Проверяем явные указания в POST данных
        if (isset($postData['consent_type'])) {
            return strtolower($postData['consent_type']);
        }
        
        // Определяем по названию формы
        if (stripos($formIdStr, 'marketing') !== false) {
            return 'marketing';
        }
        if (stripos($formIdStr, 'cookie') !== false) {
            return 'cookies';
        }
        if (stripos($formIdStr, 'feedback') !== false || stripos($formIdStr, 'contact') !== false) {
            return 'privacy_policy';
        }
        if (stripos($formIdStr, 'order') !== false) {
            return 'personal_data';
        }
        
        // По умолчанию
        return 'privacy_policy';
    }
}