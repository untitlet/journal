<?php
/**
 * Точка входа модуля
 */

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Автозагрузка классов
Loader::registerAutoLoadClasses(
    'untitlet.journal',
    [
        // ORM классы
        'Untitlet\Journal\ORM\LogTable' => '/lib/orm/logtable.php',
        'Untitlet\Journal\ORM\TextVersionTable' => '/lib/orm/textversiontable.php',
        
        // Основной логгер
        'Untitlet\Journal\Logger' => '/lib/logger.php',
        
        // Обработчики событий
        'Untitlet\Journal\EventHandlers' => '/lib/eventhandlers/eventhandlers.php',
    ]
);

// Добавление пункта в меню администратора
AddEventHandler('main', 'OnBuildAdminMenu', 'untitlet_journal_build_admin_menu');

function untitlet_journal_build_admin_menu(&$aGlobalMenu, &$aAdditionalMenu)
{
    global $APPLICATION;
    
    if ($APPLICATION->GetGroupRight('untitlet.journal') == 'D') {
        return;
    }
    
    $aMenu = [
        'parent_menu' => 'global_menu_services',
        'section' => 'untitlet.journal',
        'sort' => 100,
        'text' => GetMessage('UNTITLET_JOURNAL_MENU_TITLE'),
        'title' => GetMessage('UNTITLET_JOURNAL_MENU_TITLE'),
        'url' => 'untitlet_journal_list.php?lang=' . LANGUAGE_ID,
        'icon' => 'untitlet_journal_menu_icon',
        'page_icon' => 'untitlet_journal_page_icon',
        'items_id' => 'menu_untitlet_journal',
        'items' => [
            [
                'text' => GetMessage('UNTITLET_JOURNAL_MENU_LOGS'),
                'url' => 'untitlet_journal_list.php?lang=' . LANGUAGE_ID,
                'icon' => 'untitlet_journal_logs_icon',
            ],
            [
                'text' => GetMessage('UNTITLET_JOURNAL_MENU_VERSIONS'),
                'url' => 'untitlet_journal_versions.php?lang=' . LANGUAGE_ID,
                'icon' => 'untitlet_journal_versions_icon',
            ],
            [
                'text' => GetMessage('UNTITLET_JOURNAL_MENU_EXPORT'),
                'url' => 'untitlet_journal_export.php?lang=' . LANGUAGE_ID,
                'icon' => 'untitlet_journal_export_icon',
            ],
            [
                'text' => GetMessage('UNTITLET_JOURNAL_MENU_SETTINGS'),
                'url' => '/bitrix/admin/settings.php?lang=' . LANGUAGE_ID . '&mid=untitlet.journal',
                'icon' => 'untitlet_journal_settings_icon',
            ],
        ]
    ];
    
    $aGlobalMenu[] = $aMenu;
}
