# Consent Journal (untitlet.journal)

## Module for 1C-Bitrix: Legal Compliance with 152-FZ and GDPR

**Version:** 1.0.0  
**Compatibility:** Bitrix 20.0.0+, PHP 8.0+  
**License:** Commercial / Free distribution via Bitrix Marketplace

---

## 📋 Short Description (for Marketplace Card, max 250 characters)

Legally significant logging, versioning, export and scheduled deletion of user consent records. Full compliance with 152-FZ (Russia) and GDPR (EU). Ready-to-use API and admin interface.

---

## 📖 Detailed Description

### Problem Statement

Modern websites must collect and store user consent for personal data processing in accordance with:
- **152-FZ** (Federal Law of Russia "On Personal Data")
- **GDPR** (General Data Protection Regulation of the European Union)

Regulatory authorities may request proof of consent at any time. Without proper documentation, companies face fines up to:
- **Russia:** Up to 500,000 RUB per violation
- **EU:** Up to €20 million or 4% of annual global turnover

Most existing solutions only display consent banners without creating legally valid audit trails.

### Solution

**Consent Journal (untitlet.journal)** is a professional-grade module for 1C-Bitrix that provides:

✅ **Legally Valid Logging** — Immutable records with full metadata (IP, timestamp, user agent, session ID, page URL)  
✅ **Text Versioning** — Each consent is linked to the exact version of the privacy policy text active at the time  
✅ **One-Click Export** — CSV/PDF exports with SHA-256 hash verification for regulatory submissions  
✅ **Automated Retention** — Configurable automatic deletion after 1/3/5/10 years per legal requirements  
✅ **IP Anonymization** — Automatic masking of IP addresses after N days for GDPR Article 17 compliance  
✅ **Developer-Friendly API** — Simple PHP and JavaScript integration with any forms, banners, or custom scenarios  
✅ **Built-in Admin Interface** — Filtering, pagination, detailed views, and rights management  

### Key Features

#### 1. Consent Logging
- Records every consent grant and withdrawal
- Captures: User ID, Session ID, IP address, Timestamp, Form/Page ID, User Agent, Consent Type, Status
- Supports multiple consent types: Banner, Form Checkbox, Profile Update, API
- INSERT-only architecture prevents unauthorized modifications

#### 2. Text Versioning
- Store multiple versions of privacy policies, marketing consents, cookie policies
- Only one active version per consent code at any time
- Historical consents remain linked to the version that was active when given
- HTML and plain text storage formats

#### 3. Export for Regulatory Audits
- **CSV Export:** UTF-8 with BOM, semicolon-delimited, Excel-compatible
- **PDF Export:** A4 format, company header, record list, QR code with file hash
- **SHA-256 Hash:** Generated for each export file + metadata JSON
- **Export History:** Track all exports with timestamps and record counts
- **Batch Processing:** Exports large datasets in chunks of 10,000 records to prevent memory overflow

#### 4. Automated Cleanup
- Configurable retention periods: 1, 3, 5, 10 years or never
- Cron script + Bitrix Agent dual mechanism for reliability
- Deletes only `granted` consents older than retention period
- Logs all cleanup operations to Bitrix event log
- Optional email notifications on cleanup completion

#### 5. Privacy & Security
- **IP Anonymization:** Automatically masks IPs after configurable days (e.g., 30 days)
- **Minimal PII:** Stores only internal user_id, not email/phone/name
- **Access Control:** Role-based permissions (Read, Full Access)
- **CSRF/XSS/SQLi Protection:** Built-in Bitrix D7 security
- **Audit Trail:** All administrative actions logged to event_log

#### 6. Integration Ready
- **PHP API:** Single method call `Logger::log()` from any context
- **JavaScript Events:** `BX.onCustomEvent('untitlet:consent:accepted')` for custom banners
- **Bitrix Events:** Auto-logging on form submissions, user registration, authorization
- **REST API Compatible:** Works with external integrations

---

## ⚙️ System Requirements

| Component | Minimum Version | Recommended |
|-----------|-----------------|-------------|
| 1C-Bitrix | 20.0.0 | 22.0.0+ |
| PHP | 8.0 | 8.2+ |
| Database | MySQL 5.7 / PostgreSQL 9.6 | MySQL 8.0 / PostgreSQL 13+ |
| Memory | 256 MB | 512 MB+ |
| Disk Space | 50 MB | Depends on log volume |

### Optional Dependencies
- **mPDF 8.0+** — For PDF export (auto-installed via Composer if available)
- **wkhtmltopdf** — Alternative PDF engine (manual installation)

---

## 📦 Installation Instructions

### Method 1: Via Bitrix Marketplace (Recommended)

1. Log in to your Bitrix admin panel
2. Navigate to **Marketplace → Catalog**
3. Search for **"Consent Journal"** or **"untitlet.journal"**
4. Click **Install**
5. Wait for automatic installation to complete
6. Navigate to **Settings → Consent Journal** to configure

### Method 2: Manual Installation

1. Download the module archive from Bitrix Marketplace
2. Extract files to `/bitrix/modules/untitlet.journal/`
3. Log in to Bitrix admin panel as administrator
4. Navigate to **Settings → Modules**
5. Find **"Consent Journal (152-FZ / GDPR)"** in the list
6. Click **Install**
7. Follow the installation wizard:
   - Step 1: Database tables creation
   - Step 2: Event handlers registration
   - Step 3: Agent registration
   - Step 4: Completion

### Post-Installation Configuration

1. **Set Retention Period:**
   - Go to **Settings → Consent Journal → Settings tab**
   - Select retention period (1/3/5/10 years)
   
2. **Configure IP Anonymization:**
   - Set "Anonymize IP After (Days)" to desired value (e.g., 30)
   - Leave as 0 to disable

3. **Enable Logging Types:**
   - Check which consent types to log (Banner, Form, Profile, API)

4. **Set Notification Email:**
   - Enter email for cleanup notifications
   - Enable email notifications checkbox

5. **Configure Access Rights:**
   - Go to **Settings → Consent Journal → Access Rights**
   - Grant appropriate permissions to user groups

---

## 🚀 Quick Start Guide

### For Developers: PHP Integration

#### Basic Usage

```php
use Untitlet\Journal\Logger;

// Log consent from a form submission
Logger::log([
    'user_id' => $USER->GetID(), // or null for guests
    'consent_code' => 'privacy_policy', // or 'marketing', 'cookies'
    'form_id' => 'main_feedback', // any identifier
    'consent_type' => 'form_checkbox', // banner, form_checkbox, profile_update, api
    'meta' => ['campaign' => 'spring2026'] // optional custom data
]);

// Log consent withdrawal (GDPR Art. 17)
Logger::withdraw([
    'user_id' => $USER->GetID(),
    'consent_code' => 'marketing',
    'form_id' => 'profile_settings'
]);
```

#### Advanced Usage with Custom Metadata

```php
Logger::log([
    'user_id' => 123,
    'consent_code' => 'cookies',
    'form_id' => 'cookie_banner_v3',
    'consent_type' => 'banner',
    'page_url' => 'https://example.com/',
    'meta' => [
        'ip_masked' => false,
        'device_type' => 'mobile',
        'browser' => 'Chrome 120',
        'campaign_id' => 456,
        'ab_test_group' => 'variant_b'
    ]
]);
```

#### Create New Text Version

```php
use Untitlet\Journal\ORM\TextVersionTable;

TextVersionTable::createVersion([
    'code' => 'privacy_policy',
    'version' => 2,
    'text_html' => '<h1>Privacy Policy v2</h1><p>Updated content...</p>',
    'text_plain' => 'Privacy Policy v2\nUpdated content...',
    'is_active' => true // automatically deactivates previous version
]);
```

### For Developers: JavaScript Integration

#### Custom Cookie Banner

```javascript
// When user accepts cookies
document.getElementById('accept-cookies').addEventListener('click', function() {
    BX.onCustomEvent('untitlet:consent:accepted', [{
        consent_code: 'cookies',
        form_id: 'cookie_banner_v2',
        consent_type: 'banner',
        meta: {
            categories: ['analytics', 'marketing'],
            browser_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        }
    }]);
});

// When user withdraws consent
document.getElementById('withdraw-consent').addEventListener('click', function() {
    BX.onCustomEvent('untitlet:consent:withdrawn', [{
        consent_code: 'cookies',
        form_id: 'cookie_banner_v2'
    }]);
});
```

#### AJAX Form Integration

```javascript
BX.ready(function() {
    BX.addCustomEvent('onFormSubmit', function(event) {
        var formData = event.getFormData();
        
        if (formData.consent_checkbox === 'Y') {
            BX.onCustomEvent('untitlet:consent:accepted', [{
                consent_code: 'privacy_policy',
                form_id: formData.form_id,
                consent_type: 'form_checkbox'
            }]);
        }
    });
});
```

### For Administrators: Daily Operations

#### View Consent Records

1. Navigate to **Settings → Consent Journal → Consent Records**
2. Use filters to find specific records:
   - Date range
   - User ID
   - Form ID
   - Consent type
   - Status (Granted/Withdrawn)
3. Click on record ID for detailed view including full consent text

#### Export Data for Regulators

1. Go to **Settings → Consent Journal → Consent Records**
2. Apply desired filters (date range, user, form, etc.)
3. Click **Export to CSV** or **Export to PDF**
4. Download includes:
   - Data file (CSV/PDF)
   - SHA-256 hash for integrity verification
5. Store export securely for regulatory requests

#### Manage Text Versions

1. Navigate to **Settings → Consent Journal → Text Versions**
2. Click **Add New Version**
3. Fill in:
   - Code (e.g., `privacy_policy`)
   - Version number (auto-incremented)
   - HTML text
   - Plain text
   - Check "Make Active" to activate immediately
4. Save — previous version automatically deactivated

#### Monitor Automated Cleanup

1. Go to **Settings → Consent Journal → Settings**
2. View "Last Cleanup Run" and "Next Scheduled Cleanup"
3. Check Bitrix event log for `UNTITLET_JOURNAL_CLEANUP` entries
4. Review email notifications if enabled

---

## 🔐 Compliance Checklist

### 152-FZ (Russia) Compliance

| Requirement | Implementation | Status |
|-------------|----------------|--------|
| Art. 6: Consent must be documented | Full metadata logging with timestamp, IP, user agent | ✅ |
| Art. 9: Written form requirement | Electronic signature via session ID + IP + timestamp | ✅ |
| Art. 18: Proof of consent collection | One-click export with cryptographic hash | ✅ |
| Art. 21: Data retention limits | Configurable automated deletion (1/3/5/10 years) | ✅ |
| Art. 14: Subject access rights | Filter by user_id for subject data extraction | ✅ |

### GDPR (EU) Compliance

| Requirement | Implementation | Status |
|-------------|----------------|--------|
| Art. 7: Demonstrable consent | Immutable logs with full audit trail | ✅ |
| Art. 17: Right to erasure | Withdrawal API + automated cleanup | ✅ |
| Art. 30: Records of processing | Comprehensive export functionality | ✅ |
| Art. 25: Data minimization | Only user_id stored, no direct PII | ✅ |
| Art. 32: Security measures | D7 ORM protection, access control, audit logging | ✅ |
| Art. 5(1)(c): Storage limitation | Configurable retention periods | ✅ |

---

## ❓ FAQ (Frequently Asked Questions)

### Q1: Does this module replace cookie banners?
**A:** No. This module provides backend logging infrastructure. You still need a frontend banner/component to collect consent. The module integrates with any banner via JavaScript events or PHP API.

### Q2: Can records be modified or deleted?
**A:** By design, records are INSERT-only. Direct database updates are blocked at ORM level. Deletion is only possible through:
- Automated cleanup after retention period expires
- Manual deletion by administrators with full access rights (logged to event_log)
- GDPR withdrawal requests (creates new "withdrawn" record, doesn't delete original)

### Q3: How is IP address handled for GDPR?
**A:** Two options:
1. **Anonymization:** After N days (configurable), IP is masked to `XXX.XXX.XXX.Y` format
2. **Hashing:** Option to store SHA-256 hash instead of raw IP (planned in v1.1)

### Q4: What happens if I update privacy policy text?
**A:** Create a new version via admin panel. Old consents remain linked to old version. New consents link to new active version. This provides legal clarity on which text user agreed to.

### Q5: Can I export data for a specific user only?
**A:** Yes. Use filter by User ID on Consent Records page, then export. Perfect for responding to data subject access requests (GDPR Art. 15).

### Q6: Does this work with Bitrix24 Cloud?
**A:** No. Bitrix24 Cloud does not support custom module installation. This module requires self-hosted Bitrix (Standard, Business, Enterprise licenses).

### Q7: How much database space will this consume?
**A:** Approximately 500 bytes per consent record. Example calculations:
- 10,000 consents/year × 5 years = ~25 MB
- 100,000 consents/year × 5 years = ~250 MB
- Indexes add ~20% overhead

---

## 🛠 Technical Support & Updates

### Support Channels
- **Documentation:** Built-in README.md and inline help
- **Email:** support@untitlet.com (for paid support contracts)
- **GitHub Issues:** For bug reports and feature requests (if open-source)

### Update Policy
- **Minor updates (1.0.x):** Bug fixes, security patches — free
- **Major updates (1.x.0):** New features — may require license renewal

### Changelog

#### Version 1.0.0 (Initial Release)
- ✅ Core logging functionality
- ✅ Text versioning system
- ✅ CSV export with SHA-256
- ✅ Automated cleanup agent
- ✅ IP anonymization
- ✅ Bitrix event integration
- ✅ Admin interface with filters
- ✅ Role-based access control
- ✅ English and Russian localization

---

## 📄 License Agreement

This module is distributed under the terms specified on Bitrix Marketplace. Typical licensing models:

- **Free Tier:** Basic logging, CSV export, 1-year retention
- **Commercial Tier:** PDF export, unlimited retention, priority support
- **Enterprise Tier:** Multi-site support, custom integrations, SLA

See Bitrix Marketplace product page for current pricing.

---

## 🏷 Keywords (for Marketplace Search)

152-FZ, GDPR, consent, privacy, personal data, compliance, logging, audit, export, retention, cookie banner, privacy policy, data protection, Russian law, European regulation, Bitrix module, legal compliance

---

## 📸 Screenshots (Placeholders for Marketplace)

The following screenshots should be added to the Marketplace listing:

1. **Admin List Page** — Filtered view of consent records with pagination
2. **Detail View** — Full record details including consent text version
3. **Text Versions** — CRUD interface for managing policy versions
4. **Export Interface** — CSV/PDF export with hash verification
5. **Settings Page** — Retention, anonymization, notification configuration
6. **Access Rights** — Permission matrix for user groups
7. **Mobile Responsive** — Admin interface on tablet/mobile devices

*Note: Actual screenshots should be captured from a live installation and uploaded to Bitrix Marketplace during submission.*

---

## 📬 Contact Information

**Developer:** Untitlet  
**Website:** https://untitlet.com  
**Email:** info@untitlet.com  
**Support:** support@untitlet.com  

---

*Last updated: January 2026*  
*Module version: 1.0.0*  
*Compatible with Bitrix 20.0.0 – 24.x.x*
