# Bitrix Marketplace Submission Checklist for untitlet.journal

## ✅ Module Validation Checklist

### 1. File Structure Verification
- [x] `/bitrix/modules/untitlet.journal/install/index.php` — Installation script
- [x] `/bitrix/modules/untitlet.journal/install/version.php` — Version file
- [x] `/bitrix/modules/untitlet.journal/install/options.php` — Settings form
- [x] `/bitrix/modules/untitlet.journal/include.php` — Module entry point
- [x] `/bitrix/modules/untitlet.journal/lib/` — D7 classes (ORM, Logger, EventHandlers)
- [x] `/bitrix/modules/untitlet.journal/admin/` — Admin interface pages
- [x] `/bitrix/modules/untitlet.journal/lang/ru/` — Russian localization
- [x] `/bitrix/modules/untitlet.journal/lang/en/` — English localization
- [x] `/bitrix/modules/untitlet.journal/cron/` — Cron scripts
- [x] `/bitrix/modules/untitlet.journal/README.md` — Documentation
- [x] `/bitrix/modules/untitlet.journal/MARKETPLACE_DESCRIPTION.md` — Marketplace description

### 2. Code Quality Checks
- [x] All PHP files pass `php -l` syntax validation
- [x] No deprecated Bitrix functions used (only D7 ORM, Config\Option, etc.)
- [x] Proper namespace usage (`\Untitlet\Journal\`)
- [x] PSR-4 autoloading compatible
- [x] No hardcoded paths (use `$_SERVER['DOCUMENT_ROOT']`)
- [x] SQL injection protected via D7 ORM
- [x] XSS protected via `htmlspecialchars()` in admin interface
- [x] CSRF protection via Bitrix sesskey

### 3. Bitrix Marketplace Requirements
- [x] Module code follows naming convention: `partner_name.module_name`
- [x] Version format: `MAJOR.MINOR.PATCH` (1.0.0)
- [x] `install/version.php` contains `VERSION` and `VERSION_DATE`
- [x] Localization files exist for Russian (required) and English (recommended)
- [x] `install/index.php` implements `DoInstall()` and `DoUninstall()`
- [x] Uninstall preserves data by default (compliance requirement)
- [x] Access rights defined in `install/index.php` (D/W/K permissions)
- [x] Event handlers registered during installation
- [x] Agents registered for scheduled tasks

### 4. Security Compliance
- [x] No direct file access (check `defined('B_PROLOG_INCLUDED')` where needed)
- [x] Admin pages check `$APPLICATION->GetGroupRight()` permissions
- [x] User input sanitized before database operations
- [x] Output escaped in admin interface
- [x] No sensitive data in logs
- [x] IP anonymization implemented for GDPR

### 5. Functionality Verification
- [x] Logger::log() creates records correctly
- [x] Logger::withdraw() creates withdrawal records
- [x] Text versioning works (create, activate, deactivate)
- [x] CSV export produces valid UTF-8 BOM files
- [x] SHA-256 hash generation works
- [x] Cleanup cron script deletes old records
- [x] IP anonymization agent works
- [x] Event handlers trigger on form submission, registration
- [x] JS events fire correctly (`untitlet:consent:accepted`)

### 6. Documentation Completeness
- [x] README.md with installation instructions
- [x] API usage examples (PHP and JavaScript)
- [x] Administrator guide for daily operations
- [x] FAQ section
- [x] Compliance checklist (152-FZ, GDPR)
- [x] Changelog
- [x] Contact information

### 7. Marketplace Description Elements
- [x] Short description (≤250 characters)
- [x] Detailed description with features
- [x] System requirements table
- [x] Installation instructions (step-by-step)
- [x] Quick start guide for developers
- [x] Screenshots list (to be captured)
- [x] Keywords for search
- [x] Support contact information

---

## 📋 Required Files for Marketplace Submission

### Mandatory Files
1. **Module archive** (`untitlet.journal.zip`)
   - Complete module structure
   - All PHP files with valid syntax
   - Both language packs (ru, en)

2. **Screenshots** (minimum 5, recommended 7)
   - `screenshot_1_admin_list.png` — Admin records list with filters
   - `screenshot_2_detail_view.png` — Record detail with consent text
   - `screenshot_3_versions.png` — Text versions management
   - `screenshot_4_export.png` — Export interface
   - `screenshot_5_settings.png` — Settings page
   - `screenshot_6_rights.png` — Access rights matrix
   - `screenshot_7_mobile.png` — Mobile responsive view

3. **Icon** (optional but recommended)
   - `icon.png` — 128x128px module icon

### Description Files
4. **description.ru.php** (Russian marketplace description)
   ```php
   <?php
   $arDescription = [
       'NAME' => 'Журнал согласий (152-ФЗ / GDPR)',
       'DESCRIPTION' => 'Юридически значимое логирование, версионирование, экспорт и регламентное удаление записей о согласиях пользователей',
       'FULL_DESCRIPTION' => file_get_contents(__DIR__ . '/MARKETPLACE_DESCRIPTION_RU.txt'),
       'CATEGORY' => ['SECURITY', 'COMPLIANCE'],
       'VENDOR' => [
           'NAME' => 'Untitlet',
           'URL' => 'https://untitlet.com',
       ],
   ];
   ```

5. **description.en.php** (English marketplace description)
   ```php
   <?php
   $arDescription = [
       'NAME' => 'Consent Journal (152-FZ / GDPR)',
       'DESCRIPTION' => 'Legally significant logging, versioning, export and scheduled deletion of user consent records',
       'FULL_DESCRIPTION' => file_get_contents(__DIR__ . '/MARKETPLACE_DESCRIPTION_EN.txt'),
       'CATEGORY' => ['SECURITY', 'COMPLIANCE'],
       'VENDOR' => [
           'NAME' => 'Untitlet',
           'URL' => 'https://untitlet.com',
       ],
   ];
   ```

---

## 🚀 Publication Steps

### Step 1: Prepare Module Archive
```bash
cd /workspace
zip -r untitlet.journal.zip untitlet.journal/ \
  --exclude "*.git*" \
  --exclude "images/*" \
  --exclude "*.md"
```

### Step 2: Capture Screenshots
- Install module on test Bitrix environment
- Navigate through all admin pages
- Take screenshots at 1920x1080 resolution
- Crop to show relevant UI elements
- Save as PNG with descriptive names

### Step 3: Create Marketplace Account
- Register at https://marketplace.1c-bitrix.ru/
- Verify developer account
- Complete company profile

### Step 4: Submit Application
1. Log in to Marketplace Partner Console
2. Click "Add Product" → "Module for Bitrix CMS"
3. Fill in product details:
   - Name: Журнал согласий (152-ФЗ / GDPR) / Consent Journal
   - Category: Security → Compliance
   - Price: Free / Commercial (select model)
   - Version: 1.0.0
   - Compatible versions: 20.0.0 – 24.x.x

4. Upload files:
   - Module archive (untitlet.journal.zip)
   - Screenshots (5-7 images)
   - Icon (optional)

5. Paste descriptions:
   - Short description (250 chars)
   - Full description (from MARKETPLACE_DESCRIPTION.md)

6. Specify technical requirements:
   - Bitrix version: 20.0.0+
   - PHP version: 8.0+
   - Database: MySQL/PostgreSQL

7. Set licensing model:
   - Free tier: Basic features
   - Commercial tier: Advanced features (if applicable)

### Step 5: Moderation Process
- Bitrix team reviews submission (3-10 business days)
- Address any feedback or required changes
- Re-submit if rejected
- Wait for approval email

### Step 6: Post-Publication
- Monitor user reviews and ratings
- Respond to support requests
- Release updates as needed
- Track download statistics

---

## ⚠️ Common Rejection Reasons & How to Avoid

| Reason | Prevention |
|--------|------------|
| Syntax errors in PHP files | Run `php -l` on all files before submission |
| Missing Russian localization | Include complete `lang/ru/` folder |
| Direct SQL queries without escaping | Use D7 ORM exclusively |
| No uninstall option | Implement `DoUninstall()` with data preservation option |
| Hardcoded absolute paths | Use `$_SERVER['DOCUMENT_ROOT']` |
| Missing access rights control | Check permissions on all admin pages |
| Incomplete documentation | Include README with installation and usage |
| Broken demo/test credentials | Don't include demo credentials in module |
| Copyright violations | Use only original code or properly licensed libraries |
| Misleading description | Accurately describe all features and limitations |

---

## 📊 Marketplace Optimization Tips

### Keywords Strategy
Include these keywords in description:
- Primary: 152-ФЗ, GDPR, согласие, consent, персональные данные
- Secondary: журнал, логирование, аудит, compliance, privacy
- Long-tail: доказательная база, роскомнадзор, обработка ПДн

### Pricing Strategy
- **Free tier:** Attract users, build reputation
  - Basic logging
  - CSV export
  - 1-year retention
  
- **Commercial tier:** Monetize advanced features
  - PDF export
  - Unlimited retention
  - Priority support
  - Multi-site support

### Rating Optimization
- Provide excellent documentation
- Respond to support requests within 24 hours
- Regular updates with bug fixes
- Encourage satisfied users to leave reviews

---

## 📞 Support Contacts for Marketplace Team

**Bitrix Marketplace Administration:**
- Email: marketplace@bitrix24.com
- Partner portal: https://partners.1c-bitrix.ru/
- Documentation: https://dev.1c-bitrix.ru/learning/

**Legal Compliance Questions:**
- 152-FZ: Consult with Russian privacy lawyer
- GDPR: Consult with EU data protection officer

---

*Checklist last updated: January 2026*  
*Module version: 1.0.0*  
*Prepared by: Untitlet Development Team*
