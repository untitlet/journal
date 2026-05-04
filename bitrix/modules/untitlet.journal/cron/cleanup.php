<?php
/**
 * Cron-скрипт для автоочистки старых записей
 * Запускать ежедневно: 0 2 * * * /usr/bin/php /path/to/bitrix/modules/untitlet.journal/cron/cleanup.php
 */

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_CRON', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Untitlet\Journal\Logger;

if (!Loader::includeModule('untitlet.journal')) {
    echo "[ERROR] Module untitlet.journal is not installed\n";
    die();
}

echo "[" . date('Y-m-d H:i:s') . "] Starting consent logs cleanup...\n";

try {
    $deletedCount = Logger::cleanup();
    echo "[" . date('Y-m-d H:i:s') . "] Cleanup completed. Deleted records: {$deletedCount}\n";
    
    // Дополнительно анонимизируем старые IP
    $anonymizedCount = Logger::anonymizeOldIps();
    if ($anonymizedCount > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] Anonymized IP addresses: {$anonymizedCount}\n";
    }
} catch (\Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    
    // Логирование ошибки в файл
    error_log(
        sprintf(
            "[%s] Untitlet Journal Cleanup Error: %s\n",
            date('Y-m-d H:i:s'),
            $e->getMessage()
        ),
        3,
        $_SERVER['DOCUMENT_ROOT'] . '/upload/untitlet_journal_cron_errors.log'
    );
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');

echo "[" . date('Y-m-d H:i:s') . "] Script finished.\n";
