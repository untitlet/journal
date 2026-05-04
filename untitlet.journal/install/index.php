<?php
use Bitrix\Main\Application;
use Bitrix\Main\DB\Table;
use Bitrix\Main\EventManager;
use Untitlet\Journal\ORM\LogTable;
use Untitlet\Journal\ORM\TextVersionTable;

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
        include(__DIR__ . '/version.php');
        
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = GetMessage('UNTITLETE_JOURNAL_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('UNTITLETE_JOURNAL_MODULE_DESC');
        $this->PARTNER_NAME = GetMessage('UNTITLETE_JOURNAL_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('UNTITLETE_JOURNAL_PARTNER_URI');
    }

    function DoInstall()
    {
        global $APPLICATION;
        
        if (!check_bitrix_sessid()) {
            return false;
        }
        
        RegisterModule($this->MODULE_ID);
        
        // Install database tables
        $this->installDB();
        
        // Install events
        $this->installEvents();
        
        $APPLICATION->SetFileBufferField('/bitrix/modules/' . $this->MODULE_ID . '/include.php');
        
        return true;
    }

    function DoUninstall()
    {
        global $APPLICATION;
        
        if (!check_bitrix_sessid()) {
            return false;
        }
        
        // Uninstall events
        $this->uninstallEvents();
        
        // Drop database tables
        $this->uninstallDB();
        
        UnRegisterModule($this->MODULE_ID);
        
        return true;
    }

    function installDB()
    {
        $connection = Application::getConnection();
        
        // Create log table
        if (!$connection->isTableExists(LogTable::getTableName())) {
            LogTable::createTable();
        }
        
        // Create text version table
        if (!$connection->isTableExists(TextVersion::getTableName())) {
            TextVersionTable::createTable();
        }
    }

    function uninstallDB()
    {
        $connection = Application::getConnection();
        
        if ($connection->isTableExists(LogTable::getTableName())) {
            $connection->dropTable(LogTable::getTableName());
        }
        
        if ($connection->isTableExists(TextVersionTable::getTableName())) {
            $connection->dropTable(TextVersionTable::getTableName());
        }
    }

    function installEvents()
    {
        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnBeforeFormSubmit',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onBeforeFormSubmit'
        );
        
        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnAfterUserRegister',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onAfterUserRegister'
        );
        
        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnUserAuthorize',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onUserAuthorize'
        );
    }

    function uninstallEvents()
    {
        EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnBeforeFormSubmit',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onBeforeFormSubmit'
        );
        
        EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnAfterUserRegister',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onAfterUserRegister'
        );
        
        EventManager::getInstance()->unRegisterEventHandler(
            'main',
            'OnUserAuthorize',
            $this->MODULE_ID,
            '\Untitlet\Journal\EventHandlers',
            'onUserAuthorize'
        );
    }
}
