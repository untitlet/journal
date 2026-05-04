<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

// Include module files
IncludeModuleLangFile(__FILE__);

// Add admin menu item
$aMenu = [
    [
        'parent_menu' => 'global_menu_services',
        'section' => 'untitlet.journal',
        'sort' => 100,
        'text' => 'Журнал согласий',
        'title' => 'Журнал согласий 152-ФЗ / GDPR',
        'url' => 'untitlet_journal_list.php?lang=' . LANGUAGE_ID,
        'icon' => 'untitlet_journal_menu_icon',
        'page_icon' => 'untitlet_journal_page_icon',
        'items_id' => 'menu_untitlet_journal',
        'items' => [
            [
                'url' => 'untitlet_journal_list.php?lang=' . LANGUAGE_ID,
                'text' => 'Записи журнала',
            ],
            [
                'url' => '/bitrix/admin/settings.php?lang=' . LANGUAGE_ID . '&mid=untitlet.journal',
                'text' => 'Настройки модуля',
            ],
        ],
    ],
];

return $aMenu;
