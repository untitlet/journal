<?php
/**
 * Cron script for automatic cleanup of old consent logs
 * 
 * Usage: php /bitrix/modules/untitlet.journal/cron/cleanup.php
 * Or add to crontab: 0 2 * * * /usr/bin/php /path/to/bitrix/modules/untitlet.journal/cron/cleanup.php
 */

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Untitlet\Journal\ORM\LogTable;
use Bitrix\Main\Mail\Event as MailEvent;

try {
    // Get retention period from settings
    $retentionYears = (int)Option::get('untitlet.journal', 'retention_years', 3);
    
    if ($retentionYears <= 0) {
        echo "Cleanup disabled (retention_years = {$retentionYears})\n";
        exit(0);
    }
    
    // Calculate cutoff date
    $cutoffDate = new \DateTime();
    $cutoffDate->modify("-{$retentionYears} years");
    
    echo "Starting cleanup. Cutoff date: " . $cutoffDate->format('Y-m-d H:i:s') . "\n";
    
    // Count records to be deleted
    $count = LogTable::getCount([
        '<' => 'TIMESTAMP' => $cutoffDate,
        '=STATUS' => 'granted',
    ]);
    
    echo "Found {$count} records to delete\n";
    
    if ($count > 0) {
        // Delete in chunks to avoid memory issues
        $chunkSize = 1000;
        $deletedCount = 0;
        
        while ($deletedCount < $count) {
            // Get IDs to delete
            $rs = LogTable::getList([
                'select' => ['ID'],
                'filter' => [
                    '<' => 'TIMESTAMP' => $cutoffDate,
                    '=STATUS' => 'granted',
                ],
                'limit' => $chunkSize,
                'order' => ['TIMESTAMP' => 'ASC'],
            ]);
            
            $ids = [];
            while ($row = $rs->fetch()) {
                $ids[] = $row['ID'];
            }
            
            if (empty($ids)) {
                break;
            }
            
            // Delete records
            foreach ($ids as $id) {
                LogTable::delete($id);
                $deletedCount++;
            }
            
            echo "Deleted {$deletedCount} of {$count}\n";
        }
        
        // Log cleanup event
        EventManager::getInstance()->sendEvent(
            'main',
            'OnEventLogEntryAdd',
            [
                'MODULE_ID' => 'untitlet.journal',
                'EVENT_ID' => 'UNTITLETE_JOURNAL_CLEANUP',
                'MESSAGE' => "Automatic cleanup completed. Deleted {$deletedCount} records older than {$retentionYears} years.",
                'DETAILS' => json_encode([
                    'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
                    'deleted_count' => $deletedCount,
                    'retention_years' => $retentionYears,
                ]),
            ]
        );
        
        // Send notification email if configured
        $notificationEmail = Option::get('untitlet.journal', 'notification_email', '');
        if (!empty($notificationEmail)) {
            MailEvent::send([
                'EVENT_NAME' => 'UNTITLETE_JOURNAL_CLEANUP',
                'LID' => 's1',
                'C_FIELDS' => [
                    'DELETED_COUNT' => $deletedCount,
                    'RETENTION_YEARS' => $retentionYears,
                    'CLEANUP_DATE' => date('Y-m-d H:i:s'),
                ],
                'FIELDS' => [],
                'TO' => $notificationEmail,
            ]);
        }
        
        echo "Cleanup completed successfully. Deleted {$deletedCount} records.\n";
    } else {
        echo "No records to delete.\n";
    }
    
} catch (\Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    
    // Log error
    EventManager::getInstance()->sendEvent(
        'main',
        'OnEventLogEntryAdd',
        [
            'MODULE_ID' => 'untitlet.journal',
            'EVENT_ID' => 'UNTITLETE_JOURNAL_ERROR',
            'MESSAGE' => 'Cleanup failed: ' . $e->getMessage(),
            'DETAILS' => $e->getTraceAsString(),
        ]
    );
    
    exit(1);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
