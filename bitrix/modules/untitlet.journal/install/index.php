<?php
/**
 * @global CMain $APPLICATION
 */

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

IncludeModuleLangFile(__FILE__);

class untitlet_journal extends CModule
{
    var $MODULE_ID = 'untitlet.journal';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_GROUP_RIGHTS = 'Y';
    var $PARTNER_NAME;
    var $PARTNER_URI;

    function __construct()
    {
        $arModuleVersion = [];

        $path = str_replace('\\', '/', __FILE__);
        $path = substr($path, 0, strlen($path) - strlen('/index.php'));

        include($path . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = GetMessage('UNTITLET_JOURNAL_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('UNTITLET_JOURNAL_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = GetMessage('UNTITLET_JOURNAL_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('UNTITLET_JOURNAL_PARTNER_URI');
    }

    function DoInstall()
    {
        global $APPLICATION;

        if (!Loader::includeModule('main')) {
            return false;
        }

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();

        ModuleManager::registerModule($this->MODULE_ID);

        $APPLICATION->SetTitle(GetMessage('UNTITLET_JOURNAL_INSTALL_TITLE'));
        return true;
    }

    function DoUninstall()
    {
        global $APPLICATION;

        if (!Loader::includeModule('main')) {
            return false;
        }

        $this->UnInstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->SetTitle(GetMessage('UNTITLET_JOURNAL_UNINSTALL_TITLE'));
        return true;
    }

    function InstallDB()
    {
        global $DB;

        $this->InstallTables();
        
        // Установка настроек по умолчанию
        \Bitrix\Main\Config\Option::set($this->MODULE_ID, 'retention_years', '3');
        \Bitrix\Main\Config\Option::set($this->MODULE_ID, 'ip_mask_after_days', '30');
        \Bitrix\Main\Config\Option::set($this->MODULE_ID, 'enable_logging', 'Y');
        \Bitrix\Main\Config\Option::set($this->MODULE_ID, 'notify_email', '');
        
        return true;
    }

    function UnInstallDB()
    {
        global $DB;

        $this->UnInstallTables();
        
        // Удаление настроек
        \Bitrix\Main\Config\Option::delete($this->MODULE_ID);
        
        return true;
    }

    function InstallTables()
    {
        global $DB;

        $charset = $DB->GetCharset();
        $collation = $DB->GetCollation();

        // Таблица логов
        $sql = "
            CREATE TABLE IF NOT EXISTS b_untitlet_journal_log (
                ID INT NOT NULL AUTO_INCREMENT,
                USER_ID INT NULL,
                SESSION_ID VARCHAR(64) NULL,
                IP VARCHAR(45) NULL,
                TIMESTAMP DATETIME NOT NULL,
                CONSENT_VERSION_ID INT NOT NULL,
                FORM_ID VARCHAR(128) NULL,
                PAGE_URL VARCHAR(512) NULL,
                USER_AGENT TEXT NULL,
                CONSENT_TYPE VARCHAR(32) NOT NULL DEFAULT 'form_checkbox',
                STATUS VARCHAR(16) NOT NULL DEFAULT 'granted',
                META TEXT NULL,
                PRIMARY KEY (ID),
                INDEX idx_timestamp (TIMESTAMP),
                INDEX idx_user_id (USER_ID),
                INDEX idx_form_id (FORM_ID),
                INDEX idx_version_id (CONSENT_VERSION_ID),
                INDEX idx_status (STATUS)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation};
        ";
        $DB->Query($sql, false);

        // Таблица версий текстов
        $sql = "
            CREATE TABLE IF NOT EXISTS b_untitlet_journal_text_version (
                ID INT NOT NULL AUTO_INCREMENT,
                CODE VARCHAR(64) NOT NULL,
                VERSION INT NOT NULL DEFAULT 1,
                TEXT_HTML TEXT NULL,
                TEXT_PLAIN TEXT NULL,
                IS_ACTIVE CHAR(1) NOT NULL DEFAULT 'N',
                CREATED_AT DATETIME NOT NULL,
                PRIMARY KEY (ID),
                UNIQUE INDEX idx_code_version (CODE, VERSION),
                INDEX idx_code_active (CODE, IS_ACTIVE)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation};
        ";
        $DB->Query($sql, false);

        // Таблица экспорта
        $sql = "
            CREATE TABLE IF NOT EXISTS b_untitlet_journal_export (
                ID INT NOT NULL AUTO_INCREMENT,
                FILE_PATH VARCHAR(512) NOT NULL,
                FILE_HASH VARCHAR(64) NOT NULL,
                FILE_TYPE VARCHAR(16) NOT NULL,
                RECORDS_COUNT INT NOT NULL DEFAULT 0,
                DATE_FROM DATETIME NULL,
                DATE_TO DATETIME NULL,
                CREATED_AT DATETIME NOT NULL,
                CREATED_BY INT NULL,
                PRIMARY KEY (ID),
                INDEX idx_created_at (CREATED_AT)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation};
        ";
        $DB->Query($sql, false);

        return true;
    }

    function UnInstallTables()
    {
        global $DB;

        $DB->DropTable('b_untitlet_journal_export');
        $DB->DropTable('b_untitlet_journal_text_version');
        $DB->DropTable('b_untitlet_journal_log');

        return true;
    }

    function InstallEvents()
    {
        // Регистрация обработчиков событий
        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'OnBeforeFormSubmit',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onBeforeFormSubmit'
        );

        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'OnAfterUserRegister',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onAfterUserRegister'
        );

        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'main',
            'OnUserAuthorize',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onUserAuthorize'
        );

        // Добавление агента для очистки
        \Bitrix\Main\Agent::addAgent(
            "\\Untitlet\\Journal\\Logger::runCleanupAgent();",
            $this->MODULE_ID,
            'N',
            86400, // Раз в сутки
            '',
            'Y'
        );

        return true;
    }

    function UnInstallEvents()
    {
        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnBeforeFormSubmit',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onBeforeFormSubmit'
        );

        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnAfterUserRegister',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onAfterUserRegister'
        );

        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnUserAuthorize',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onUserAuthorize'
        );

        // Удаление агента
        \Bitrix\Main\Agent::deleteByModule($this->MODULE_ID);

        return true;
    }

    function InstallFiles()
    {
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/admin', 
                     $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true, true);
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/admin', 
                       $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
        return true;
    }

    function GetModuleRightList()
    {
        $arr = [
            'reference_id' => ['D', 'K', 'W'],
            'reference' => [
                GetMessage('UNTITLET_JOURNAL_RIGHT_D'),
                GetMessage('UNTITLET_JOURNAL_RIGHT_K'),
                GetMessage('UNTITLET_JOURNAL_RIGHT_W')
            ]
        ];
        return $arr;
    }
}
