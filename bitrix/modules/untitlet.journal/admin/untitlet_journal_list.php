<?php
/**
 * Административная страница: Список записей журнала согласий
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Untitlet\Journal\ORM\LogTable;
use Untitlet\Journal\ORM\TextVersionTable;

if (!Loader::includeModule('untitlet.journal')) {
    ShowError(GetMessage('UNTITLET_JOURNAL_MODULE_NOT_INSTALLED'));
    require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
    die();
}

// Проверка прав доступа
$moduleRight = $APPLICATION->GetGroupRight('untitlet.journal');
if ($moduleRight == 'D') {
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

IncludeModuleLangFile(__FILE__);

// Обработка экспорта в CSV
$request = Context::getCurrent()->getRequest();
if ($request->get('export_csv') === 'Y' && check_bitrix_sessid()) {
    exportToCsv();
}

// Фильтр
$filter = [];
$filterForm = [
    'ID' => $request->get('filter_ID'),
    'USER_ID' => $request->get('filter_USER_ID'),
    'CONSENT_TYPE' => $request->get('filter_CONSENT_TYPE'),
    'STATUS' => $request->get('filter_STATUS'),
    'FORM_ID' => $request->get('filter_FORM_ID'),
    'TIMESTAMP_FROM' => $request->get('filter_TIMESTAMP_FROM'),
    'TIMESTAMP_TO' => $request->get('filter_TIMESTAMP_TO'),
];

if ($filterForm['ID']) {
    $filter['=ID'] = (int)$filterForm['ID'];
}
if ($filterForm['USER_ID']) {
    $filter['=USER_ID'] = (int)$filterForm['USER_ID'];
}
if ($filterForm['CONSENT_TYPE']) {
    $filter['=CONSENT_TYPE'] = $filterForm['CONSENT_TYPE'];
}
if ($filterForm['STATUS']) {
    $filter['=STATUS'] = $filterForm['STATUS'];
}
if ($filterForm['FORM_ID']) {
    $filter['=FORM_ID'] = $filterForm['FORM_ID'];
}
if ($filterForm['TIMESTAMP_FROM']) {
    $filter['>=TIMESTAMP'] = $filterForm['TIMESTAMP_FROM'] . ' 00:00:00';
}
if ($filterForm['TIMESTAMP_TO']) {
    $filter['<=TIMESTAMP'] = $filterForm['TIMESTAMP_TO'] . ' 23:59:59';
}

// Пагинация
$navParams = [
    'nPageSize' => 50,
    'bShowPageNav' => true
];

// Получение данных
$rsData = LogTable::getList([
    'filter' => $filter,
    'order' => ['TIMESTAMP' => 'DESC'],
    'limit' => $navParams['nPageSize'],
    'offset' => max(0, ((int)$_GET['PAGEN_1'] - 1) * $navParams['nPageSize']),
    'select' => [
        'ID',
        'USER_ID',
        'SESSION_ID',
        'IP',
        'TIMESTAMP',
        'CONSENT_VERSION_ID',
        'FORM_ID',
        'PAGE_URL',
        'CONSENT_TYPE',
        'STATUS',
        'META',
        'VERSION_VERSION' => 'CONSENT_VERSION.VERSION',
        'VERSION_CODE' => 'CONSENT_VERSION.CODE'
    ]
]);

$rsData = new CAdminResult($rsData->fetchAll(), 'untitlet_journal_log');
$rsData->NavStart($navParams['nPageSize'], $navParams['bShowPageNav']);

// Заголовок страницы
$APPLICATION->SetTitle(GetMessage('UNTITLET_JOURNAL_PAGE_TITLE'));

// Меню действий
$aContext = [
    [
        'TEXT' => GetMessage('UNTITLET_JOURNAL_EXPORT_CSV'),
        'LINK' => 'untitlet_journal_list.php?export_csv=Y&' . bitrix_sessid_get() . '&lang=' . LANGUAGE_ID,
        'ICON' => 'btn_export',
    ],
];
$lAdmin->AddAdminContextMenu($aContext);

// Создание списка
$lAdmin = new CAdminList('untitlet_journal_log', $rsData);

// Настройка колонок
$lAdmin->AddHeaders([
    ['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'TIMESTAMP', 'content' => GetMessage('UNTITLET_JOURNAL_COL_DATE'), 'sort' => 'TIMESTAMP', 'default' => true],
    ['id' => 'USER_ID', 'content' => GetMessage('UNTITLET_JOURNAL_COL_USER'), 'sort' => 'USER_ID', 'default' => true],
    ['id' => 'CONSENT_TYPE', 'content' => GetMessage('UNTITLET_JOURNAL_COL_TYPE'), 'sort' => 'CONSENT_TYPE', 'default' => true],
    ['id' => 'STATUS', 'content' => GetMessage('UNTITLET_JOURNAL_COL_STATUS'), 'sort' => 'STATUS', 'default' => true],
    ['id' => 'FORM_ID', 'content' => GetMessage('UNTITLET_JOURNAL_COL_FORM'), 'sort' => 'FORM_ID', 'default' => true],
    ['id' => 'VERSION_VERSION', 'content' => GetMessage('UNTITLET_JOURNAL_COL_VERSION'), 'sort' => 'VERSION_VERSION', 'default' => true],
    ['id' => 'IP', 'content' => GetMessage('UNTITLET_JOURNAL_COL_IP'), 'default' => true],
    ['id' => 'PAGE_URL', 'content' => GetMessage('UNTITLET_JOURNAL_COL_URL'), 'default' => false],
]);

// Формирование строк
while ($arRes = $rsData->NavNext(true, 'f_')) {
    $row =& $lAdmin->AddRow($arRes['ID'], $arRes);
    
    // Ссылка на пользователя
    if ($arRes['USER_ID'] > 0) {
        $row->AddViewLink('USER_ID', '<a href="/bitrix/admin/user_edit.php?ID=' . $arRes['USER_ID'] . '&lang=' . LANGUAGE_ID . '">' . $arRes['USER_ID'] . '</a>');
    }
    
    // Статус с цветом
    $statusClass = $arRes['STATUS'] === 'granted' ? 'green' : 'red';
    $row->AddViewLink('STATUS', '<span style="color:' . $statusClass . ';font-weight:bold;">' . ($arRes['STATUS'] === 'granted' ? '✓' : '✗') . ' ' . $arRes['STATUS'] . '</span>');
}

// Отображение фильтра
$lAdmin->BeginFilter([
    ['id' => 'filter_ID', 'content' => 'ID', 'type' => 'text'],
    ['id' => 'filter_USER_ID', 'content' => GetMessage('UNTITLET_JOURNAL_FILTER_USER'), 'type' => 'text'],
    ['id' => 'filter_CONSENT_TYPE', 'content' => GetMessage('UNTITLET_JOURNAL_FILTER_TYPE'), 'type' => 'list', 'items' => [
        '' => '',
        'banner' => 'Banner',
        'form_checkbox' => 'Form Checkbox',
        'profile_update' => 'Profile Update',
        'api' => 'API'
    ]],
    ['id' => 'filter_STATUS', 'content' => GetMessage('UNTITLET_JOURNAL_FILTER_STATUS'), 'type' => 'list', 'items' => [
        '' => '',
        'granted' => 'Granted',
        'withdrawn' => 'Withdrawn'
    ]],
    ['id' => 'filter_FORM_ID', 'content' => GetMessage('UNTITLET_JOURNAL_FILTER_FORM'), 'type' => 'text'],
    ['id' => 'filter_TIMESTAMP_FROM', 'content' => GetMessage('UNTITLET_JOURNAL_FILTER_DATE_FROM'), 'type' => 'calendar'],
    ['id' => 'filter_TIMESTAMP_TO', 'content' => GetMessage('UNTITLET_JOURNAL_FILTER_DATE_TO'), 'type' => 'calendar'],
]);

$lAdmin->EndFilter();

// Отображение списка
$lAdmin->DisplayList();

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');

/**
 * Экспорт в CSV
 */
function exportToCsv()
{
    global $APPLICATION;
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="consent_logs_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // BOM для корректного отображения кириллицы в Excel
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Заголовки
    fputcsv($output, [
        'ID',
        'Date',
        'User ID',
        'Session ID',
        'IP',
        'Consent Type',
        'Status',
        'Form ID',
        'Page URL',
        'Version',
        'User Agent',
        'Meta'
    ], ';');
    
    // Данные
    $rsData = \Untitlet\Journal\ORM\LogTable::getList([
        'order' => ['TIMESTAMP' => 'DESC'],
        'select' => [
            'ID', 'USER_ID', 'SESSION_ID', 'IP', 'TIMESTAMP',
            'CONSENT_TYPE', 'STATUS', 'FORM_ID', 'PAGE_URL', 'USER_AGENT', 'META',
            'VERSION' => 'CONSENT_VERSION.VERSION'
        ]
    ]);
    
    while ($arRes = $rsData->fetch()) {
        fputcsv($output, [
            $arRes['ID'],
            $arRes['TIMESTAMP']->format('Y-m-d H:i:s'),
            $arRes['USER_ID'] ?? '',
            $arRes['SESSION_ID'] ?? '',
            $arRes['IP'] ?? '',
            $arRes['CONSENT_TYPE'],
            $arRes['STATUS'],
            $arRes['FORM_ID'] ?? '',
            $arRes['PAGE_URL'] ?? '',
            $arRes['VERSION'] ?? '',
            substr($arRes['USER_AGENT'] ?? '', 0, 255),
            is_array($arRes['META']) ? json_encode($arRes['META']) : ''
        ], ';');
    }
    
    fclose($output);
    die();
}
