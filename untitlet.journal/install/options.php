<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

$module_id = 'untitlet.journal';

// Get request data
$request = Application::getInstance()->getContext()->getRequest();

// Handle form submission
if ($request->isPost() && check_bitrix_sessid()) {
    // Save settings
    Option::set($module_id, 'retention_years', (int)$request->getPost('retention_years'));
    Option::set($module_id, 'ip_mask_after_days', (int)$request->getPost('ip_mask_after_days'));
    Option::set($module_id, 'enable_logging', $request->getPost('enable_logging') === 'Y' ? 'Y' : 'N');
    Option::set($module_id, 'notification_email', trim($request->getPost('notification_email')));
    
    // Redirect to avoid form resubmission
    LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANG . '&up=Y');
}

// Get current settings
$retentionYears = Option::get($module_id, 'retention_years', 3);
$ipMaskAfterDays = Option::get($module_id, 'ip_mask_after_days', 0);
$enableLogging = Option::get($module_id, 'enable_logging', 'Y');
$notificationEmail = Option::get($module_id, 'notification_email', '');

?>

<form method="POST" action="<?php echo $APPLICATION->GetCurPage() ?>?mid=<?php echo urlencode($module_id) ?>&amp;lang=<?php echo LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    
    <table class="adm-detail-content-table edit-table">
        <tr>
            <td width="40%">
                <label for="retention_years"><?php echo Loc::getMessage('UNTITLETE_JOURNAL_RETENTION_YEARS') ?></label>
            </td>
            <td width="60%">
                <select name="retention_years" id="retention_years">
                    <option value="1" <?php echo $retentionYears == 1 ? 'selected' : '' ?>>1 <?php echo Loc::getMessage('UNTITLETE_JOURNAL_YEAR') ?></option>
                    <option value="3" <?php echo $retentionYears == 3 ? 'selected' : '' ?>>3 <?php echo Loc::getMessage('UNTITLETE_JOURNAL_YEARS') ?></option>
                    <option value="5" <?php echo $retentionYears == 5 ? 'selected' : '' ?>>5 <?php echo Loc::getMessage('UNTITLETE_JOURNAL_YEARS') ?></option>
                    <option value="10" <?php echo $retentionYears == 10 ? 'selected' : '' ?>>10 <?php echo Loc::getMessage('UNTITLETE_JOURNAL_YEARS') ?></option>
                </select>
            </td>
        </tr>
        
        <tr>
            <td>
                <label for="ip_mask_after_days"><?php echo Loc::getMessage('UNTITLETE_JOURNAL_IP_MASK_DAYS') ?></label>
            </td>
            <td>
                <input type="number" name="ip_mask_after_days" id="ip_mask_after_days" 
                       value="<?php echo htmlspecialcharsbx($ipMaskAfterDays) ?>" min="0" max="365" />
                <small><?php echo Loc::getMessage('UNTITLETE_JOURNAL_IP_MASK_HINT') ?></small>
            </td>
        </tr>
        
        <tr>
            <td>
                <label for="enable_logging"><?php echo Loc::getMessage('UNTITLETE_JOURNAL_ENABLE_LOGGING') ?></label>
            </td>
            <td>
                <input type="checkbox" name="enable_logging" id="enable_logging" value="Y" 
                       <?php echo $enableLogging === 'Y' ? 'checked' : '' ?> />
            </td>
        </tr>
        
        <tr>
            <td>
                <label for="notification_email"><?php echo Loc::getMessage('UNTITLETE_JOURNAL_NOTIFICATION_EMAIL') ?></label>
            </td>
            <td>
                <input type="email" name="notification_email" id="notification_email" 
                       value="<?php echo htmlspecialcharsbx($notificationEmail) ?>" 
                       style="width: 300px;" />
                <small><?php echo Loc::getMessage('UNTITLETE_JOURNAL_NOTIFICATION_EMAIL_HINT') ?></small>
            </td>
        </tr>
    </table>
    
    <br/>
    <input type="submit" name="Update" value="<?php echo Loc::getMessage('MAIN_SAVE') ?>" 
           class="adm-btn-save"/>
</form>

<h2><?php echo Loc::getMessage('UNTITLETE_JOURNAL_MODULE_INFO') ?></h2>
<p><?php echo Loc::getMessage('UNTITLETE_JOURNAL_MODULE_DESC_FULL') ?></p>

<h3><?php echo Loc::getMessage('UNTITLETE_JOURNAL_API_USAGE') ?></h3>
<pre><code>&lt;?php
// Log consent
\Untitlet\Journal\Logger::log([
    'user_id'       => $USER-&gt;GetID(),
    'consent_code'  =&gt; 'privacy_policy',
    'form_id'       =&gt; 'main_feedback',
    'consent_type'  =&gt; 'form_checkbox',
    'meta'          =&gt; ['campaign' =&gt; 'spring2026']
]);

// Withdraw consent
\Untitlet\Journal\Logger::withdraw(
    $userId,
    'privacy_policy',
    ['form_id' =&gt; 'profile_settings']
);
?&gt;</code></pre>

<h3><?php echo Loc::getMessage('UNTITLETE_JOURNAL_JS_INTEGRATION') ?></h3>
<pre><code>// JavaScript event for custom banners
BX.onCustomEvent('untitlet:consent:accepted', [{
    consent_code: 'cookies',
    form_id: 'cookie_banner_v2',
    consent_type: 'banner'
}]);</code></pre>
