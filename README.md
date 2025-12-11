# MemberPress Pause Subscription Plugin

## Deprecation Notice

> **WARNING: THIS PLUGIN IS NO LONGER SUPPORTED**
>
> This plugin has been deprecated and will no longer receive updates, bug fixes, or security patches. All installations and continued use are **at your own risk**. No warranty or guarantee of functionality is provided.
>
> **Disclaimer:** By using this plugin, you acknowledge that:
> - No technical support will be provided
> - No compatibility updates for future WordPress or MemberPress versions
> - Any issues, data loss, or security vulnerabilities are your sole responsibility
> - You should consider migrating to an alternative solution

---

## Table of Contents

1. [Overview](#overview)
2. [What It Does](#what-it-does)
3. [How It Works](#how-it-works)
4. [Configuration Options](#configuration-options)
5. [File Structure](#file-structure)
6. [Database Schema](#database-schema)
7. [User Meta Keys](#user-meta-keys)
8. [What It Does NOT Do (Limitations)](#what-it-does-not-do-limitations)
9. [Dependencies](#dependencies)
10. [Integration Points](#integration-points)
11. [Troubleshooting](#troubleshooting)

---

## Overview

**Plugin Name:** MemberPress Pause Subscription
**Version:** 1.3
**Requires WordPress:** 5.2+
**Requires PHP:** 7.2+
**Required Plugin:** MemberPress (must be active)

The MemberPress Pause Subscription plugin allows members to temporarily pause their recurring subscriptions and have them automatically resume after a specified date. When a subscription is paused, the remaining days are preserved and added to the new expiry date upon resumption.

---

## What It Does

### User-Facing Features

| Feature | Description |
|---------|-------------|
| **Schedule Pause** | Users can schedule subscription pauses with custom start and end dates |
| **End Pause Early** | Users can manually end an active pause before the scheduled end date |
| **Account Tab** | Adds a "Subscription Pause" tab to the MemberPress account dashboard |
| **Pause Status Display** | Shows whether a pause is currently active or available |

### Admin Features

| Feature | Description |
|---------|-------------|
| **Settings Dashboard** | Configure pause parameters (max days, once-a-month limit) |
| **Current Pauses List** | View all users with active pauses in a table format |
| **Pause History Log** | View historical record of all completed pauses |
| **Manual Pause Tool** | Admin can manually set pause for any user by email |
| **Email Configuration** | Customize subjects and content for pause notifications |

### Core Business Logic

- **Subscription Suspension** - Suspends subscription at payment gateway level
- **Transaction Management** - Expires transactions and creates new ones with adjusted expiry
- **Days Preservation** - Calculates remaining subscription days and extends expiry accordingly
- **Notification System** - Sends customizable emails on pause start and end
- **Data Persistence** - Stores pause data in user meta and historical database table
- **Automatic Execution** - Uses WordPress `init` hook to check and execute pauses on page load

---

## How It Works

### Architecture

The plugin uses a trait-based architecture with two main controllers:

```
┌─────────────────────────────────────────────────────┐
│           MF_Mepr_Suspend_Schedule (Main)           │
└──────────────────────┬──────────────────────────────┘
                       │
         ┌─────────────┴─────────────┐
         │                           │
┌────────▼────────┐        ┌─────────▼─────────┐
│ MFSS_Pause_     │        │ MFSS_Settings_    │
│ Controller      │        │ Controller        │
│ (pause/resume)  │        │ (admin/config)    │
└────────┬────────┘        └─────────┬─────────┘
         │                           │
         └──────────┬────────────────┘
                    │
    ┌───────────────┴───────────────┐
    │                               │
┌───▼───────────┐       ┌───────────▼───┐
│ MFSS_Controller│       │ MFSS_Database │
│ (trait)        │       │ (trait)       │
│ redirect/msg   │       │ table ops     │
└────────────────┘       └───────────────┘
```

### User-Initiated Pause Flow

```
┌─────────────┐     ┌──────────────────┐     ┌───────────────────┐
│ User visits │────▶│ Enters start &   │────▶│ Form submitted    │
│ Account Tab │     │ end dates        │     │ with nonce        │
└─────────────┘     └──────────────────┘     └─────────┬─────────┘
                                                       │
                                                       ▼
┌─────────────────────────────────────────────────────────────────┐
│                    add_pause_form_handler()                      │
│                                                                  │
│  1. Validate nonce                                              │
│  2. validate_form_data() - check dates, limits, etc.            │
│  3. schedule_pause() - store dates in user meta                 │
│  4. If start date = today → pause_subscription() immediately    │
│  5. Redirect with success message                               │
└─────────────────────────────────────────────────────────────────┘
```

### Automatic Pause Execution Flow

```
┌──────────────────────────────────────────────────────────────┐
│                    pause_operator()                           │
│              (runs on every page load via init hook)          │
└─────────────────────────────┬────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              │                               │
              ▼                               ▼
┌──────────────────────────┐    ┌──────────────────────────────┐
│ Check if pause start     │    │ Check if pause end date      │
│ date = today             │    │ has passed                   │
└───────────┬──────────────┘    └──────────────┬───────────────┘
            │                                   │
            ▼                                   ▼
┌──────────────────────────┐    ┌──────────────────────────────┐
│   pause_subscription()   │    │    resume_transaction()      │
│                          │    │                              │
│ • Store original expiry  │    │ • Calculate remaining days   │
│ • Store subscription ID  │    │ • Create new transaction     │
│ • Store product ID       │    │ • Reactivate subscription    │
│ • Suspend subscription   │    │ • Log to historical table    │
│ • Expire transaction     │    │ • Send resume email          │
│ • Send pause email       │    │ • Update user meta           │
└──────────────────────────┘    └──────────────────────────────┘
```

### Event-Based Resume Flow

```
┌───────────────────────────────────────┐
│  mepr-event-transaction-expired       │
│  (MemberPress hook)                   │
└───────────────────┬───────────────────┘
                    │
                    ▼
┌───────────────────────────────────────┐
│  Check if user is paused and          │
│  pause end date has passed            │
└───────────────────┬───────────────────┘
                    │
                    ▼
┌───────────────────────────────────────┐
│       resume_subscription()           │
│  (directly resumes the subscription)  │
└───────────────────────────────────────┘
```

### Days Calculation Logic

When resuming a subscription:

```
Original Expiry Date: March 31, 2025
Pause Start Date:     March 15, 2025
Pause End Date:       March 25, 2025

Remaining Days = Original Expiry - Pause Start = 16 days
New Expiry Date = Pause End Date + Remaining Days = April 10, 2025
```

---

## Configuration Options

### Admin Settings (WordPress Options)

| Option Name | Type | Description |
|-------------|------|-------------|
| `mfss-pause-limit` | Number | Maximum days allowed for a single pause |
| `mfss-once-a-month` | String | Restrict pausing to once per 30 days ('true'/'false') |
| `mfss-pause-email-subject` | String | Email subject when pause starts |
| `mfss-pause-email-content` | String | Email body when pause starts |
| `mfss-resume-email-subject` | String | Email subject when pause ends |
| `mfss-resume-email-content` | String | Email body when pause ends |

### Access Settings

Navigate to **WordPress Admin → Pause Scheduler** to access:

- **Settings** - Configure limits and email templates
- **Currently Paused** - View all active pauses
- **Past Pauses** - View historical pause records
- **Set Pause** - Manually pause users by email

---

## File Structure

```
mf-memberpress-suspend-schedule/
├── mf-memberpress-suspend-schedule.php    # Main plugin file
├── app/
│   ├── lib/
│   │   ├── mfss-controller.php            # Controller trait (redirect/messaging)
│   │   └── mfss-database.php              # Database trait (table operations)
│   ├── controllers/
│   │   ├── mfss-settings-controller.php   # Admin settings & pages
│   │   └── mfss-pause-controller.php      # Core pause/resume logic
│   └── views/
│       ├── tab-content.php                # User account pause form
│       ├── partials/
│       │   └── show-redirect-message.php  # Success/error messages
│       ├── admin/
│       │   ├── settings-form.php          # Admin settings page
│       │   ├── manual-pause-form.php      # Manual pause tool
│       │   ├── paused-list.php            # Active pauses table
│       │   └── historical-list.php        # Pause history table
│       └── emails/
│           ├── pause-started.php          # Pause notification email
│           └── pause-ended.php            # Resume notification email
```

---

## Database Schema

### Historical Pauses Table

**Table Name:** `{prefix}_mfss_historical_pauses`

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(11) | Auto-increment primary key |
| `user_id` | INT(11) | WordPress user ID |
| `pause_start` | DATE | Date when pause started |
| `pause_end` | DATE | Date when pause ended |

**Note:** Records older than 1 year are automatically cleaned on plugin activation.

---

## User Meta Keys

| Meta Key | Purpose |
|----------|---------|
| `mfss-pause-start` | Scheduled pause start date (d-m-Y format) |
| `mfss-pause-end` | Scheduled pause end date (d-m-Y format) |
| `mfss-last-paused` | Date of last pause (for once-a-month validation) |
| `mfss-expiry-date` | Original subscription expiry before pause |
| `mfss-previous-sub` | Original subscription ID |
| `mfss-previous-prod` | Original product ID |
| `mfss-last-restored` | Flag (0/1) indicating if pause has been restored |

---

## What It Does NOT Do (Limitations)

### Functional Limitations

| Limitation | Description |
|------------|-------------|
| **No Manual Date Editing** | Once a pause is scheduled, users cannot modify dates via UI |
| **No Cron Job** | Relies on page loads to trigger pause/resume. If no visitors on pause end date, resumption delays |
| **No Pause Cancellation UI** | Users cannot cancel a scheduled (but not yet started) pause |
| **Single Subscription Only** | Assumes one active transaction per user; doesn't handle multiple subscriptions |
| **Date Format Locked** | Uses hardcoded `d-m-Y` format; may cause locale issues |
| **Timezone Unaware** | All dates treated as server time |
| **No Pause Overlap Prevention** | System doesn't prevent scheduling overlapping pauses |
| **No Pagination** | Admin lists may be slow with thousands of records |

### Business Logic Limitations

| Limitation | Description |
|------------|-------------|
| **No Refund System** | Pauses preserve days but don't issue refunds |
| **No Pause Reason Tracking** | System doesn't capture why users paused |
| **Admin Bypasses Validation** | Manual admin pauses can exceed maximum day limits |
| **Admin Bypasses Once-a-Month** | Admins can pause users multiple times in 30 days |

### Technical Limitations

| Limitation | Description |
|------------|-------------|
| **No Email Headers** | Emails via `wp_mail()` without custom headers; may go to spam |
| **No Historical Data Management** | No admin UI to manually manage historical records |
| **No API/REST Endpoints** | Cannot integrate with external systems |
| **No Logging/Debug Mode** | No built-in logging for troubleshooting |

---

## Dependencies

### Required

- **WordPress 5.2+**
- **PHP 7.2+**
- **MemberPress Plugin** - Must be installed and active

### MemberPress Classes Used

- `MeprTransaction`
- `MeprSubscription`
- `MeprUser`
- `MeprAccountHelper`

### MemberPress Hooks Used

- `mepr_account_nav`
- `mepr_account_nav_content`
- `mepr-event-transaction-expired`

---

## Integration Points

### With MemberPress

| Integration | Description |
|-------------|-------------|
| Account Tab | Adds "Subscription Pause" tab to account page |
| Subscription Objects | Reads/modifies subscriptions via MeprSubscription |
| Transaction Management | Creates/expires transactions via MeprTransaction |
| User Data | Accesses user data via MeprUser |

### With WordPress

| Integration | Description |
|-------------|-------------|
| Admin Menu | Creates "Pause Scheduler" menu with 4 subpages |
| User Meta | Stores all pause data in user meta |
| Options API | Stores settings in WordPress options table |
| Hooks | Uses `init`, `admin_menu`, `admin_post_*` hooks |
| Security | Uses nonces and capability checks |

---

## Troubleshooting

### Common Issues

**Pause doesn't start/end on the correct date**
- The plugin relies on page loads to trigger pause/resume actions
- If no one visits the site on that date, the action won't execute
- Consider setting up a server cron job that visits the site daily

**User can't pause their subscription**
- Check if "once-a-month" setting is enabled and user paused within 30 days
- Check if the user has an active subscription
- Verify MemberPress is active

**Emails not being sent**
- Check WordPress mail configuration
- Verify email content is set in plugin settings
- Check spam folder

**Subscription not resuming correctly**
- Verify user meta keys contain correct data
- Check `mfss-last-restored` flag
- Review historical pauses table for records

### Data Recovery

User pause data is stored in user meta. To manually check or modify:

```php
// Get pause data for a user
$pause_start = get_user_meta($user_id, 'mfss-pause-start', true);
$pause_end = get_user_meta($user_id, 'mfss-pause-end', true);
$original_expiry = get_user_meta($user_id, 'mfss-expiry-date', true);

// Clear pause data (use with caution)
delete_user_meta($user_id, 'mfss-pause-start');
delete_user_meta($user_id, 'mfss-pause-end');
delete_user_meta($user_id, 'mfss-expiry-date');
delete_user_meta($user_id, 'mfss-previous-sub');
delete_user_meta($user_id, 'mfss-previous-prod');
delete_user_meta($user_id, 'mfss-last-restored');
```

---

## Final Notes

This documentation serves as a comprehensive reference for the MemberPress Pause Subscription plugin. As the plugin is no longer maintained, users should:

1. **Test thoroughly** in a staging environment before any production use
2. **Backup regularly** - especially user meta and the historical pauses table
3. **Monitor** for compatibility issues with WordPress and MemberPress updates
4. **Consider alternatives** for long-term subscription pause functionality

