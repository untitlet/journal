<?php
/**
 * Admin page for viewing consent logs
 * Path: /bitrix/admin/untitlet_journal_list.php
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Untitlet\Journal\ORM\LogTable;
use Untitlet\Journal\ORM\TextVersionTable;

if (!Loader::includeModule('untitlet.journal')) {
    ShowError('Module untitlet.journal is not installed');
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
    exit;
}

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('UNTITLETE_JOURNAL_ADMIN_TITLE'));

// Check access rights
if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
    exit;
}

include(GetLangFileName($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/untitlet.journal/admin/', '/untitlet_journal_list.php'));

// Handle export actions
$request = \Bitrix\Main\Context::getCurrent()->getRequest();
if ($request->get('export') === 'csv' && check_bitrix_sessid()) {
    exportToCsv();
    exit;
}

if ($request->get('export') === 'pdf' && check_bitrix_sessid()) {
    exportToPdf();
    exit;
}

// Build filter
$filter = [];
$filterActive = false;

if ($request->get('filter_user_id') !== '') {
    $filter['=USER_ID'] = (int)$request->get('filter_user_id');
    $filterActive = true;
}

if ($request->get('filter_form_id') !== '') {
    $filter['=FORM_ID'] = trim($request->get('filter_form_id'));
    $filterActive = true;
}

if ($request->get('filter_status') !== '') {
    $filter['=STATUS'] = trim($request->get('filter_status'));
    $filterActive = true;
}

if ($request->get('filter_consent_type') !== '') {
    $filter['=CONSENT_TYPE'] = trim($request->get('filter_consent_type'));
    $filterActive = true;
}

if ($request->get('filter_date_from') !== '') {
    $filter['>=TIMESTAMP'] = $request->get('filter_date_from') . ' 00:00:00';
    $filterActive = true;
}

if ($request->get('filter_date_to') !== '') {
    $filter['<=TIMESTAMP'] = $request->get('filter_date_to') . ' 23:59:59';
    $filterActive = true;
}

// Pagination
$pageNum = max(1, (int)($request->get('PAGEN_1') ?? 1));
$pageSize = 50;

// Get data
$rsData = LogTable::getList([
    'select' => [
        'ID',
        'USER_ID',
        'SESSION_ID',
        'IP',
        'TIMESTAMP',
        'FORM_ID',
        'PAGE_URL',
        'CONSENT_TYPE',
        'STATUS',
        'CONSENT_VERSION_ID',
        'VERSION_CODE' => 'CONSENT_VERSION.CODE',
        'VERSION_NUM' => 'CONSENT_VERSION.VERSION',
    ],
    'filter' => $filter,
    'order' => ['TIMESTAMP' => 'DESC'],
    'limit' => $pageSize,
    'offset' => ($pageNum - 1) * $pageSize,
]);

$totalCount = LogTable::getCount(['filter' => $filter]);

// Include admin UI
CJSCore::Init(['jquery', 'date']);

?>

<form method="GET" name="find_form">
    <table class="adm-work-table">
        <tr>
            <td><?= Loc::getMessage('UNTITLETE_JOURNAL_FILTER_USER_ID') ?>:</td>
            <td><input type="text" name="filter_user_id" value="<?= htmlspecialcharsbx($request->get('filter_user_id')) ?>" /></td>
            
            <td><?= Loc::getMessage('UNTITLETE_JOURNAL_FILTER_FORM_ID') ?>:</td>
            <td><input type="text" name="filter_form_id" value="<?= htmlspecialcharsbx($request->get('filter_form_id')) ?>" /></td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('UNTITLETE_JOURNAL_FILTER_STATUS') ?>:</td>
            <td>
                <select name="filter_status">
                    <option value=""><?= Loc::getMessage('UNTITLETE_JOURNAL_ALL') ?></option>
                    <option value="granted" <?= $request->get('filter_status') === 'granted' ? 'selected' : '' ?>><?= Loc::getMessage('UNTITLETE_JOURNAL_GRANTED') ?></option>
                    <option value="withdrawn" <?= $request->get('filter_status') === 'withdrawn' ? 'selected' : '' ?>><?= Loc::getMessage('UNTITLETE_JOURNAL_WITHDRAWN') ?></option>
                </select>
            </td>
            
            <td><?= Loc::getMessage('UNTITLETE_JOURNAL_FILTER_CONSENT_TYPE') ?>:</td>
            <td>
                <select name="filter_consent_type">
                    <option value=""><?= Loc::getMessage('UNTITLETE_JOURNAL_ALL') ?></option>
                    <option value="banner" <?= $request->get('filter_consent_type') === 'banner' ? 'selected' : '' ?>>Banner</option>
                    <option value="form_checkbox" <?= $request->get('filter_consent_type') === 'form_checkbox' ? 'selected' : '' ?>>Form Checkbox</option>
                    <option value="profile_update" <?= $request->get('filter_consent_type') === 'profile_update' ? 'selected' : '' ?>>Profile Update</option>
                    <option value="api" <?= $request->get('filter_consent_type') === 'api' ? 'selected' : '' ?>>API</option>
                </select>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('UNTITLETE_JOURNAL_FILTER_DATE_FROM') ?>:</td>
            <td><input type="text" name="filter_date_from" value="<?= htmlspecialcharsbx($request->get('filter_date_from')) ?>" class="adm-calendar-input" /></td>
            
            <td><?= Loc::getMessage('UNTITLETE_JOURNAL_FILTER_DATE_TO') ?>:</td>
            <td><input type="text" name="filter_date_to" value="<?= htmlspecialcharsbx($request->get('filter_date_to')) ?>" class="adm-calendar-input" /></td>
        </tr>
        <tr>
            <td colspan="4">
                <input type="submit" value="<?= Loc::getMessage('UNTITLETE_JOURNAL_FILTER_APPLY') ?>" class="adm-btn-save" />
                <input type="button" value="<?= Loc::getMessage('UNTITLETE_JOURNAL_FILTER_RESET') ?>" onclick="document.location='?lang=<?= LANGUAGE_ID ?>'" class="adm-btn" />
                
                <? if (check_bitrix_sessid()): ?>
                    <input type="hidden" name="sessid" value="<?= bitrix_sessid() ?>" />
                    <input type="submit" name="export" value="CSV" class="adm-btn" />
                    <input type="submit" name="export" value="PDF" class="adm-btn" disabled title="<?= Loc::getMessage('UNTITLETE_JOURNAL_PDF_COMING_SOON') ?>" />
                <? endif; ?>
            </td>
        </tr>
    </table>
</form>

<br/>

<table class="adm-list-table">
    <thead>
        <tr class="adm-list-table-header">
            <td class="adm-list-table-cell"><?= Loc::getMessage('UNTITLETE_JOURNAL_ID') ?></td>
            <td class="adm-list-table-cell"><?= Loc::getMessage('UNTITLETE_JOURNAL_TIMESTAMP') ?></td>
            <td class="adm-list-table-cell"><?= Loc::getMessage('UNTITLETE_JOURNAL_USER_ID') ?></td>
            <td class="adm-list-table-cell"><?= Loc::getMessage('UNTITLETE_JOURNAL_FORM_ID') ?></td>
            <td class="adm-list-table-cell"><?= Loc::getMessage('UNTITLETE_JOURNAL_VERSION') ?></td>
            <td class="adm-list-table-cell"><?= Loc::getMessage('UNTITLETE_JOURNAL_CONSENT_TYPE') ?></td>
            <td class="adm-list-table-cell"><?= Loc::getMessage('UNTITLETE_JOURNAL_STATUS') ?></td>
            <td class="adm-list-table-cell"><?= Loc::getMessage('UNTITLETE_JOURNAL_IP') ?></td>
            <td class="adm-list-table-cell"><?= Loc::getMessage('UNTITLETE_JOURNAL_PAGE_URL') ?></td>
        </tr>
    </thead>
    <tbody>
        <? while ($arRecord = $rsData->fetch()): ?>
        <tr class="adm-list-table-row">
            <td class="adm-list-table-cell"><?= $arRecord['ID'] ?></td>
            <td class="adm-list-table-cell"><?= $arRecord['TIMESTAMP']->format('d.m.Y H:i:s') ?></td>
            <td class="adm-list-table-cell">
                <? if ($arRecord['USER_ID'] > 0): ?>
                    <a href="/bitrix/admin/user_edit.php?ID=<?= $arRecord['USER_ID'] ?>&lang=<?= LANGUAGE_ID ?>"><?= $arRecord['USER_ID'] ?></a>
                <? else: ?>
                    <?= Loc::getMessage('UNTITLETE_JOURNAL_GUEST') ?>
                <? endif; ?>
            </td>
            <td class="adm-list-table-cell"><?= htmlspecialcharsbx($arRecord['FORM_ID']) ?></td>
            <td class="adm-list-table-cell">
                <?= htmlspecialcharsbx($arRecord['VERSION_CODE']) ?> v<?= $arRecord['VERSION_NUM'] ?>
            </td>
            <td class="adm-list-table-cell"><?= htmlspecialcharsbx($arRecord['CONSENT_TYPE']) ?></td>
            <td class="adm-list-table-cell">
                <span style="color: <?= $arRecord['STATUS'] === 'granted' ? 'green' : 'red' ?>">
                    <?= htmlspecialcharsbx($arRecord['STATUS']) ?>
                </span>
            </td>
            <td class="adm-list-table-cell"><?= htmlspecialcharsbx($arRecord['IP']) ?></td>
            <td class="adm-list-table-cell">
                <a href="<?= htmlspecialcharsbx($arRecord['PAGE_URL']) ?>" target="_blank">
                    <?= htmlspecialcharsbx(mb_substr($arRecord['PAGE_URL'], 0, 50)) ?>...
                </a>
            </td>
        </tr>
        <? endwhile; ?>
    </tbody>
</table>

<br/>

<?
// Show pagination
if ($totalCount > $pageSize):
    $totalPages = ceil($totalCount / $pageSize);
    ?>
    <div class="adm-pagination">
        <? for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?PAGEN_1=<?= $i ?>&<?= http_build_query(array_filter($request->getQueryList()->toArray())) ?>" 
               class="<?= $i === $pageNum ? 'adm-pagination-active' : '' ?>">
                <?= $i ?>
            </a>
        <? endfor; ?>
    </div>
    <p><?= Loc::getMessage('UNTITLETE_JOURNAL_TOTAL_RECORDS') ?>: <?= $totalCount ?></p>
<? endif; ?>

<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');

/**
 * Export to CSV
 */
function exportToCsv()
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="consent_logs_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Add BOM for Excel UTF-8 compatibility
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, [
        'ID',
        'User ID',
        'Session ID',
        'IP',
        'Timestamp',
        'Consent Version ID',
        'Form ID',
        'Page URL',
        'User Agent',
        'Consent Type',
        'Status',
        'Meta',
    ], ';');
    
    // Data
    $rsData = LogTable::getList([
        'select' => ['*'],
        'order' => ['TIMESTAMP' => 'DESC'],
    ]);
    
    while ($row = $rsData->fetch()) {
        fputcsv($output, [
            $row['ID'],
            $row['USER_ID'],
            $row['SESSION_ID'],
            $row['IP'],
            $row['TIMESTAMP']->format('Y-m-d H:i:s'),
            $row['CONSENT_VERSION_ID'],
            $row['FORM_ID'],
            $row['PAGE_URL'],
            $row['USER_AGENT'],
            $row['CONSENT_TYPE'],
            $row['STATUS'],
            json_encode($row['META']),
        ], ';');
    }
    
    fclose($output);
    exit;
}

/**
 * Export to PDF (placeholder - requires mPDF or similar)
 */
function exportToPdf()
{
    // TODO: Implement PDF export with mPDF
    ShowError('PDF export coming soon. Requires mPDF library.');
}
?>
