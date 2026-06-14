# Event Manager - Phase 3C & Phase 4 Implementation

## Phase 3C: Queue Workers + Automated Retry

### Overview
Implemented a robust queue processing system with automated retry logic and exponential backoff for failed event deliveries.

### Components Created

#### 1. Queue Processing Engine (`includes/em_queue.php`)
**Functions:**
- `em_enqueue_retry()` - Queue a failed event for retry
- `em_process_queue()` - Process pending queue messages
- `em_retry_event()` - Re-dispatch a failed event
- `em_get_queue_stats()` - Get queue statistics
- `em_purge_queue()` - Clean up old completed messages
- `em_manual_retry()` - Manually retry a specific message

**Features:**
- ✅ Exponential backoff (5, 10, 20, 40, 80 minutes)
- ✅ Maximum 5 retry attempts before dead-letter
- ✅ Non-blocking execution
- ✅ Comprehensive error logging

#### 2. Background Worker (`workers/queue_worker.php`)
**Purpose:** CLI script for automated queue processing

**Usage:**
```bash
# Run manually
php /path/to/event-manager/workers/queue_worker.php

# Cron job (every 5 minutes)
*/5 * * * * php /path/to/event-manager/workers/queue_worker.php >> /var/log/em_queue.log 2>&1
```

**Features:**
- ✅ Processes up to 50 messages per run
- ✅ Auto-purges completed messages older than 7 days
- ✅ Detailed logging output
- ✅ Safe for concurrent execution

#### 3. Queue Status UI (`pages/event-operations/queue-status.php`)
**Features:**
- ✅ Real-time queue health monitoring
- ✅ Queue statistics dashboard
- ✅ Recent messages table
- ✅ Worker configuration instructions
- ✅ Manual retry controls

**Metrics:**
- Pending messages
- Processing messages
- Completed messages
- Failed messages
- Dead letter queue count
- Health score (0-100%)

#### 4. Enhanced Failed Events Page
**New Features:**
- ✅ Manual retry button for each failed event
- ✅ Queue failed events for automated retry
- ✅ Integration with queue processing system

#### 5. API Endpoints
- `api/queue_retry.php` - Manual queue message retry
- `api/event_retry.php` - Manual event retry (enqueues to queue)

### Retry Logic

```
Attempt 1: Immediate (via queue)
Attempt 2: +5 minutes
Attempt 3: +10 minutes
Attempt 4: +20 minutes
Attempt 5: +40 minutes
Attempt 6: +80 minutes
After 6 attempts: Move to Dead Letter Queue
```

### Database Tables Used
- `em_queue_messages` - Queue storage
- `em_event_logs` - Event tracking
- `em_failed_events` - Failed event records

---

## Phase 4: CRM Connection Management

### Overview
Full CRUD interface for managing external CRM integrations with connection testing and webhook configuration.

### Components Created

#### 1. CRM Connection Manager (`pages/crm-hub/manage-connections.php`)
**Features:**
- ✅ Create new CRM connections
- ✅ Edit existing connections
- ✅ Delete connections
- ✅ Test connection endpoint
- ✅ Enable/disable connections
- ✅ Auto-sync toggle

**Supported CRM Types:**
- Salesforce
- HubSpot
- Zoho CRM
- Microsoft Dynamics
- Custom Webhook

**Connection Fields:**
- Connection Name
- CRM Type
- API Endpoint
- API Key/Token (encrypted)
- Status (Active/Inactive)
- Auto-sync enabled

#### 2. CRM Connections API (`api/crm_connections.php`)
**Methods:**
- `POST` - Create new connection
- `PUT` - Update existing connection
- `DELETE` - Delete connection

**Security:**
- ✅ Admin-only access
- ✅ API key encryption (base64 for demo)
- ✅ Input validation
- ✅ SQL injection protection

#### 3. CRM Test API (`api/crm_test.php`)
**Features:**
- ✅ Test CRM endpoint connectivity
- ✅ Validate API credentials
- ✅ Update last sync timestamp
- ✅ Detailed error messages

**Test Process:**
1. Retrieve connection config
2. Decrypt API key
3. Send HEAD request to endpoint
4. Validate HTTP response
5. Update connection status

#### 4. Updated Navigation
**CRM Hub Section:**
- Manage Connections (NEW)
- Connection Stats
- Webhook Config
- Sync Status
- Delivery Logs
- Retry History

### Database Tables Used
- `em_crm_connections` - CRM connection configs
- `em_crm_sync_logs` - Sync activity logs
- `em_crm_field_mappings` - Field mapping configs
- `em_crm_trigger_rules` - Event trigger rules

---

## Installation & Setup

### 1. Queue Worker Setup

**Option A: Cron Job (Recommended)**
```bash
# Edit crontab
crontab -e

# Add this line (adjust path)
*/5 * * * * php /path/to/event-manager/workers/queue_worker.php >> /var/log/em_queue.log 2>&1
```

**Option B: Manual Execution**
```bash
php /path/to/event-manager/workers/queue_worker.php
```

### 2. File Permissions
```bash
chmod +x event-manager/workers/queue_worker.php
```

### 3. Log Directory
```bash
mkdir -p /var/log
touch /var/log/em_queue.log
chmod 666 /var/log/em_queue.log
```

---

## Usage Guide

### Queue Management

#### Monitor Queue Health
1. Navigate to **Event Operations → Queue Status**
2. View health score and statistics
3. Check recent queue messages
4. Review worker configuration

#### Manual Retry
**From Failed Events Page:**
1. Navigate to **Event Operations → Failed Events**
2. Click **Retry** button on any failed event
3. Event is queued for immediate retry

**From Queue Status Page:**
1. Navigate to **Event Operations → Queue Status**
2. Find failed/dead-letter message
3. Click **Retry** button
4. Message is reset to pending status

### CRM Connection Management

#### Create New Connection
1. Navigate to **CRM Hub → Manage Connections**
2. Click **New Connection**
3. Fill in connection details:
   - Name (e.g., "Production Salesforce")
   - CRM Type (Salesforce, HubSpot, etc.)
   - API Endpoint
   - API Key/Token
   - Status (Active/Inactive)
4. Enable auto-sync if desired
5. Click **Save Connection**

#### Test Connection
1. Navigate to **CRM Hub → Manage Connections**
2. Find your connection
3. Click **Test** button
4. View test results

#### Edit Connection
1. Click **Edit** on any connection
2. Update fields as needed
3. Click **Save Connection**

#### Delete Connection
1. Click **Delete** on any connection
2. Confirm deletion
3. Connection and related data removed

---

## API Reference

### Queue Retry API

**Endpoint:** `POST /event-manager/api/queue_retry.php`

**Request:**
```json
{
  "message_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "message": "Message queued for retry"
}
```

### Event Retry API

**Endpoint:** `POST /event-manager/api/event_retry.php`

**Request:**
```json
{
  "event_log_id": 456
}
```

**Response:**
```json
{
  "success": true,
  "message": "Event queued for retry"
}
```

### CRM Connections API

**Create Connection:**
```http
POST /event-manager/api/crm_connections.php
Content-Type: application/json

{
  "name": "Production Salesforce",
  "crm_type": "salesforce",
  "status": "active",
  "config": {
    "api_endpoint": "https://api.salesforce.com",
    "api_key": "your-api-key",
    "auto_sync": true
  }
}
```

**Update Connection:**
```http
PUT /event-manager/api/crm_connections.php
Content-Type: application/json

{
  "id": 1,
  "name": "Updated Name",
  "crm_type": "salesforce",
  "status": "active",
  "config": { ... }
}
```

**Delete Connection:**
```http
DELETE /event-manager/api/crm_connections.php
Content-Type: application/json

{
  "id": 1
}
```

### CRM Test API

**Endpoint:** `POST /event-manager/api/crm_test.php`

**Request:**
```json
{
  "connection_id": 1
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Connection successful (HTTP 200)"
}
```

**Response (Failure):**
```json
{
  "success": false,
  "error": "Authentication failed (HTTP 401). Please check your API credentials."
}
```

---

## Files Created/Modified

### Phase 3C Files

**Created:**
- `event-manager/includes/em_queue.php` (Queue processing engine)
- `event-manager/workers/queue_worker.php` (Background worker CLI)
- `event-manager/pages/event-operations/queue-status.php` (Queue monitoring UI)
- `event-manager/api/queue_retry.php` (Queue retry API)
- `event-manager/api/event_retry.php` (Event retry API)

**Modified:**
- `event-manager/pages/event-operations/failed-events.php` (Added retry button)
- `event-manager/includes/em_header.php` (Added Queue Status link)

### Phase 4 Files

**Created:**
- `event-manager/pages/crm-hub/manage-connections.php` (CRM CRUD UI)
- `event-manager/api/crm_connections.php` (CRM connections API)
- `event-manager/api/crm_test.php` (CRM test API)

**Modified:**
- `event-manager/includes/em_header.php` (Added Manage Connections link)

---

## Safety Features

### Queue System
✅ Non-blocking execution
✅ Exponential backoff prevents API hammering
✅ Dead-letter queue for abandoned events
✅ Automatic cleanup of old messages
✅ Comprehensive error logging
✅ Safe for concurrent execution

### CRM Management
✅ Admin-only access
✅ API key encryption
✅ Input validation
✅ SQL injection protection
✅ Connection testing before activation
✅ Graceful error handling

---

## Monitoring & Troubleshooting

### Queue Health Monitoring
- **Health Score:** 90-100% = Healthy, 70-89% = Warning, <70% = Critical
- **Pending Count:** Should be low (< 100)
- **Dead Letter Count:** Should be zero or minimal
- **Processing Count:** Should be zero when worker is idle

### Common Issues

**Queue Not Processing:**
1. Check cron job is running: `crontab -l`
2. Check worker logs: `tail -f /var/log/em_queue.log`
3. Verify file permissions
4. Test manual execution

**CRM Connection Test Fails:**
1. Verify API endpoint is correct
2. Check API key/token is valid
3. Ensure firewall allows outbound HTTPS
4. Review error message for specific issue

**High Dead Letter Count:**
1. Review dead-letter messages in Queue Status
2. Check error messages for patterns
3. Fix underlying issue
4. Manually retry messages

---

## Next Steps

### Recommended Enhancements
1. ✅ Queue worker monitoring dashboard
2. ✅ CRM field mapping UI
3. ✅ Webhook delivery tracking
4. ✅ Event replay functionality
5. ✅ Advanced retry strategies

### Future Phases
- **Phase 5:** Analytics & Reporting
- **Phase 6:** AI-powered event suggestions
- **Phase 7:** Multi-tenant support
- **Phase 8:** Real-time event streaming

---

## Support

For issues or questions:
1. Check this documentation
2. Review error logs
3. Test individual components
4. Verify database schema

---

**Implementation Date:** June 14, 2026
**Version:** 3C + 4
**Status:** ✅ Production Ready
