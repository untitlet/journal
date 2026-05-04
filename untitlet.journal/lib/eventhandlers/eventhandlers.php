<?php
namespace Untitlet\Journal;

use Bitrix\Main\Context;
use Bitrix\Main\Config\Option;

/**
 * Event handlers for Bitrix events
 */
class EventHandlers
{
    /**
     * Handle OnBeforeFormSubmit event
     * Logs consent when a form with consent checkbox is submitted
     * 
     * @param array $arParams
     * @return void
     */
    public static function onBeforeFormSubmit(array $arParams)
    {
        if (!self::isLoggingEnabled()) {
            return;
        }
        
        $request = Context::getCurrent()->getRequest();
        $formId = $arParams['WEB_FORM_ID'] ?? $arParams['FORM_ID'] ?? 'unknown';
        
        // Check if form has consent checkbox
        $consentValue = $request->get('consent_accepted') ?? $request->get('privacy_consent');
        
        if ($consentValue === 'Y' || $consentValue === '1' || $consentValue === 'on') {
            Logger::log([
                'form_id'      => 'form_' . $formId,
                'consent_code' => 'privacy_policy',
                'consent_type' => 'form_checkbox',
                'status'       => 'granted',
                'meta'         => [
                    'form_name' => $arParams['FORM_NAME'] ?? null,
                ],
            ]);
        }
    }
    
    /**
     * Handle OnAfterUserRegister event
     * Logs consent when user registers
     * 
     * @param array $arParams
     * @return void
     */
    public static function onAfterUserRegister(array $arParams)
    {
        if (!self::isLoggingEnabled()) {
            return;
        }
        
        $userId = (int)$arParams['USER_ID'];
        
        if ($userId > 0) {
            Logger::log([
                'user_id'      => $userId,
                'form_id'      => 'user_registration',
                'consent_code' => 'privacy_policy',
                'consent_type' => 'profile_update',
                'status'       => 'granted',
            ]);
        }
    }
    
    /**
     * Handle OnUserAuthorize event
     * Can be used to log login consent if required
     * 
     * @param array $arParams
     * @return void
     */
    public static function onUserAuthorize(array $arParams)
    {
        // Optional: Log authorization events if needed
        // Currently disabled by default
    }
    
    /**
     * Check if logging is enabled in module settings
     * 
     * @return bool
     */
    private static function isLoggingEnabled(): bool
    {
        return Option::get('untitlet.journal', 'enable_logging', 'Y') === 'Y';
    }
}
