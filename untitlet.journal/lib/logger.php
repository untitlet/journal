<?php
namespace Untitlet\Journal;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\EventManager;
use Untitlet\Journal\ORM\LogTable;
use Untitlet\Journal\ORM\TextVersionTable;

/**
 * Logger class for consent management
 * 
 * Usage:
 * \Untitlet\Journal\Logger::log([
 *     'user_id'       => $USER->GetID(),
 *     'consent_code'  => 'privacy_policy',
 *     'form_id'       => 'main_feedback',
 *     'consent_type'  => 'form_checkbox',
 *     'meta'          => ['campaign' => 'spring2026']
 * ]);
 */
class Logger
{
    /**
     * Log consent
     * 
     * @param array $data
     * @return bool|\Bitrix\Main\ORM\Data\AddResult
     * @throws \Exception
     */
    public static function log(array $data)
    {
        try {
            $context = Context::getCurrent();
            $request = $context->getRequest();
            $server = $context->getServer();
            
            // Get or create user session ID
            $sessionId = session_id();
            if (empty($sessionId)) {
                $sessionId = uniqid('sess_', true);
            }
            
            // Get IP address
            $ip = self::getIpAddress();
            
            // Get consent version
            $consentCode = $data['consent_code'] ?? 'privacy_policy';
            $versionData = TextVersionTable::getActiveVersion($consentCode);
            
            if (!$versionData) {
                throw new \Exception("No active version found for consent code: {$consentCode}");
            }
            
            // Prepare log data
            $logData = [
                'USER_ID'           => (int)($data['user_id'] ?? 0),
                'SESSION_ID'        => substr($sessionId, 0, 64),
                'IP'                => $ip,
                'CONSENT_VERSION_ID'=> (int)$versionData['ID'],
                'FORM_ID'           => substr($data['form_id'] ?? 'unknown', 0, 128),
                'PAGE_URL'          => substr($request->getRequestUri() ?? $server->getRequestUri(), 0, 512),
                'USER_AGENT'        => substr($server->getHttpUserAgent() ?? 'unknown', 0, 255),
                'CONSENT_TYPE'      => $data['consent_type'] ?? 'form_checkbox',
                'STATUS'            => $data['status'] ?? 'granted',
                'META'              => $data['meta'] ?? null,
            ];
            
            return LogTable::add($logData);
            
        } catch (\Exception $e) {
            // Log error to event log
            \Bitrix\Main\EventManager::getInstance()->sendEvent(
                'main',
                'OnException',
                [$e]
            );
            
            // In production, we might want to silently fail to not break user experience
            if (defined('BX_DEBUG') && BX_DEBUG) {
                throw $e;
            }
            
            return false;
        }
    }
    
    /**
     * Withdraw consent for a user
     * 
     * @param int $userId
     * @param string $consentCode
     * @param array $data Additional data
     * @return bool
     */
    public static function withdraw(int $userId, string $consentCode, array $data = [])
    {
        try {
            $context = Context::getCurrent();
            $request = $context->getRequest();
            $server = $context->getServer();
            
            $sessionId = session_id() ?: uniqid('sess_', true);
            $ip = self::getIpAddress();
            
            $versionData = TextVersionTable::getActiveVersion($consentCode);
            
            if (!$versionData) {
                return false;
            }
            
            $logData = [
                'USER_ID'           => $userId,
                'SESSION_ID'        => substr($sessionId, 0, 64),
                'IP'                => $ip,
                'CONSENT_VERSION_ID'=> (int)$versionData['ID'],
                'FORM_ID'           => substr($data['form_id'] ?? 'withdrawal_form', 0, 128),
                'PAGE_URL'          => substr($request->getRequestUri() ?? $server->getRequestUri(), 0, 512),
                'USER_AGENT'        => substr($server->getHttpUserAgent() ?? 'unknown', 0, 255),
                'CONSENT_TYPE'      => $data['consent_type'] ?? 'profile_update',
                'STATUS'            => 'withdrawn',
                'META'              => $data['meta'] ?? null,
            ];
            
            return LogTable::add($logData);
            
        } catch (\Exception $e) {
            if (defined('BX_DEBUG') && BX_DEBUG) {
                throw $e;
            }
            
            return false;
        }
    }
    
    /**
     * Get client IP address with optional masking
     * 
     * @return string
     */
    private static function getIpAddress(): string
    {
        $context = Context::getCurrent();
        $server = $context->getServer();
        
        $ip = $server->getRemoteAddr() ?? '0.0.0.0';
        
        // Check if IP masking is enabled
        $maskAfterDays = (int)\Bitrix\Main\Config\Option::get(
            'untitlet.journal',
            'ip_mask_after_days',
            0
        );
        
        if ($maskAfterDays > 0) {
            // For now, return full IP. Masking will be done by cron job
            return $ip;
        }
        
        return $ip;
    }
    
    /**
     * Mask IP address after N days
     * 
     * @param string $ip
     * @return string
     */
    public static function maskIpAddress(string $ip): string
    {
        // IPv4 masking - keep last octet
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return 'XXX.XXX.XXX.' . $parts[3];
            }
        }
        
        // IPv6 masking - keep last hextet
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            if (count($parts) >= 1) {
                return 'XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:XXXX:' . end($parts);
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Hash IP address for GDPR compliance
     * 
     * @param string $ip
     * @return string
     */
    public static function hashIpAddress(string $ip): string
    {
        return hash('sha256', $ip);
    }
}
