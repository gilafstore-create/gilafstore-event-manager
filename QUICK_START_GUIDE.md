# Event Manager - Quick Start Guide

**Date:** June 14, 2026  
**Environment:** Windows (XAMPP) / Linux (Hostinger)

---

## 🚀 Immediate Setup (5 Steps)

### Step 1: Set Up Queue Worker Cron Job

#### **For Windows (XAMPP - Local Development)**

**Option A: Windows Task Scheduler (Recommended)**

1. Open Task Scheduler (`taskschd.msc`)
2. Click "Create Basic Task"
3. Name: `Event Manager Queue Worker`
4. Trigger: Daily
5. Start time: Today, 12:00 AM
6. Action: Start a program
7. Program: `C:\xampp\php\php.exe`
8. Arguments: `C:\xampp\htdocs\gilafstore.com\public_html\event-manager\workers\queue_worker.php`
9. Click "Finish"
10. Right-click the task → Properties
11. Triggers tab → Edit → Advanced settings
12. Check "Repeat task every: 5 minutes"
13. Duration: Indefinitely
14. Click OK

**Option B: Manual Testing (For Now)**
```powershell
# Run manually every 5 minutes during development
cd C:\xampp\htdocs\gilafstore.com\public_html
php event-manager\workers\queue_worker.php
```

#### **For Linux (Hostinger - Production)**

```bash
# SSH into your server
ssh your-username@your-server.com

# Edit crontab
crontab -e

# Add this line (adjust path to your installation)
*/5 * * * * php /home/your-username/public_html/event-manager/workers/queue_worker.php >> /var/log/em_queue.log 2>&1

# Save and exit (Ctrl+X, then Y, then Enter)

# Verify cron job is added
crontab -l
```

**Create log directory:**
```bash
mkdir -p /var/log
touch /var/log/em_queue.log
chmod 666 /var/log/em_queue.log
```

---

### Step 2: Create Your First CRM Connection

#### **Access the CRM Hub:**
1. Navigate to: `http://localhost/event-manager/pages/crm-hub/manage-connections.php`
   - Or on production: `https://gilafstore.com/event-manager/pages/crm-hub/manage-connections.php`

#### **Create a Test Connection:**

**For Testing (Webhook.site):**
1. Go to https://webhook.site
2. Copy your unique URL (e.g., `https://webhook.site/abc123`)
3. In Event Manager, click **"New Connection"**
4. Fill in:
   - **Name:** `Test Webhook`
   - **CRM Type:** `Custom Webhook`
   - **API Endpoint:** `https://webhook.site/abc123` (your URL)
   - **API Key:** `test-key-123` (any value for testing)
   - **Status:** `Active`
   - **Auto-sync:** ✓ Checked
5. Click **"Save Connection"**
6. Click **"Test"** button
7. Check webhook.site to see the test request

**For Production (Real CRM):**

**Salesforce Example:**
```
Name: Production Salesforce
CRM Type: Salesforce
API Endpoint: https://your-instance.salesforce.com/services/data/v57.0
API Key: [Your Salesforce OAuth Token]
Status: Active
Auto-sync: ✓
```

**HubSpot Example:**
```
Name: Production HubSpot
CRM Type: HubSpot
API Endpoint: https://api.hubapi.com
API Key: [Your HubSpot API Key]
Status: Active
Auto-sync: ✓
```

---

### Step 3: Test Event Dispatching

#### **Method 1: Use Existing Event Producers**

**Test PRODUCT_CREATED Event:**
1. Go to your admin panel
2. Create a new product
3. Check Event Manager → Event Operations → Event Logs
4. You should see a `PRODUCT_CREATED` event

**Test ORDER_CANCELLED Event:**
1. Go to admin → Orders
2. Cancel an order
3. Check Event Logs for `ORDER_CANCELLED` event

#### **Method 2: Manual Test Script**

Create a test file: `event-manager/test_dispatch.php`

```php
<?php
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/em_db.php';
require_once __DIR__ . '/includes/em_dispatcher.php';

// Test event dispatch
$testPayload = [
    'test_id' => 12345,
    'test_name' => 'Test Event',
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    em_dispatch('TEST_EVENT', $testPayload);
    echo "✓ Event dispatched successfully!\n";
    echo "Check Event Manager → Event Logs to see the event.\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
```

**Run the test:**
```bash
# Windows (XAMPP)
cd C:\xampp\htdocs\gilafstore.com\public_html
php event-manager\test_dispatch.php

# Linux (Hostinger)
php /path/to/event-manager/test_dispatch.php
```

---

### Step 4: Monitor Queue Health

#### **Access Queue Status:**
Navigate to: **Event Operations → Queue Status**

**What to Check:**

1. **Health Score:**
   - ✅ 90-100% = Healthy (green)
   - ⚠️ 70-89% = Warning (yellow)
   - ❌ <70% = Critical (red)

2. **Queue Statistics:**
   - **Pending:** Should be low (< 100)
   - **Processing:** Should be 0 when worker is idle
   - **Completed:** Will grow over time
   - **Failed:** Monitor for patterns
   - **Dead Letter:** Should be minimal

3. **Recent Messages:**
   - Check for any stuck messages
   - Verify retry counts
   - Review error messages

#### **Troubleshooting:**

**If queue is not processing:**
```bash
# Check if cron job is running
# Windows: Check Task Scheduler
# Linux: Check cron logs
tail -f /var/log/cron

# Test worker manually
php event-manager/workers/queue_worker.php

# Check worker output
tail -f /var/log/em_queue.log
```

**If health score is low:**
1. Check dead-letter queue
2. Review error messages
3. Fix underlying issues
4. Manually retry failed events

---

### Step 5: Verify Everything is Working

#### **Checklist:**

**Event System:**
- [ ] Events are being logged (check Event Logs page)
- [ ] Failed events are tracked (check Failed Events page)
- [ ] Queue is processing (check Queue Status page)

**CRM Integration:**
- [ ] CRM connection created
- [ ] Connection test passes
- [ ] Webhook deliveries logged (check Delivery Logs)

**Queue Worker:**
- [ ] Cron job is running (Windows Task Scheduler or Linux crontab)
- [ ] Worker logs are being created
- [ ] Failed events are being retried

**Monitoring:**
- [ ] Dashboard shows statistics
- [ ] Event Logs are populated
- [ ] Queue Status shows health metrics
- [ ] Audit Trail is recording actions

---

## 🧪 Testing Scenarios

### Scenario 1: Test Failed Event Retry

1. **Create a failing webhook:**
   - Create CRM connection with invalid endpoint: `https://invalid-endpoint-test.com`
   - Trigger an event (create product, cancel order, etc.)

2. **Verify failure:**
   - Go to Event Operations → Failed Events
   - You should see the failed event

3. **Test manual retry:**
   - Click "Retry" button on the failed event
   - Check Queue Status to see it queued

4. **Test automatic retry:**
   - Wait 5 minutes for cron job to run
   - Check Queue Status to see retry attempts
   - Verify exponential backoff (5, 10, 20, 40, 80 minutes)

### Scenario 2: Test CRM Webhook Delivery

1. **Set up webhook.site:**
   - Go to https://webhook.site
   - Copy your unique URL

2. **Create connection:**
   - Use webhook.site URL as endpoint
   - Test connection (should pass)

3. **Trigger event:**
   - Create a product or cancel an order
   - Check webhook.site for incoming webhook

4. **Verify in Event Manager:**
   - Check CRM Hub → Delivery Logs
   - Should show successful delivery

### Scenario 3: Test Queue Processing

1. **Generate multiple events:**
   - Create 5-10 products quickly
   - Or use test script to generate events

2. **Check queue:**
   - Go to Queue Status
   - Should see pending messages

3. **Wait for processing:**
   - Wait 5 minutes for cron job
   - Or run worker manually
   - Verify messages are processed

4. **Check results:**
   - Pending count should decrease
   - Completed count should increase
   - Health score should remain high

---

## 📊 Monitoring Dashboard

### Daily Checks:

**Morning:**
1. Check Queue Status health score
2. Review failed events from overnight
3. Check dead-letter queue
4. Review audit trail for unusual activity

**During Day:**
1. Monitor event logs for errors
2. Check CRM delivery success rate
3. Verify queue is processing

**Evening:**
1. Review daily statistics
2. Export audit logs if needed
3. Check for any alerts

---

## 🔧 Common Issues & Solutions

### Issue 1: Queue Not Processing

**Symptoms:**
- Pending count keeps growing
- Processing count always 0
- Health score dropping

**Solutions:**
```bash
# Check cron job
# Windows: Task Scheduler → Event Manager Queue Worker → Run
# Linux: crontab -l

# Test worker manually
php event-manager/workers/queue_worker.php

# Check logs
tail -f /var/log/em_queue.log

# Verify file permissions
chmod +x event-manager/workers/queue_worker.php
```

### Issue 2: CRM Connection Test Fails

**Symptoms:**
- Test button shows error
- "Connection failed" message

**Solutions:**
1. Verify API endpoint URL is correct
2. Check API key/token is valid
3. Test endpoint with curl:
   ```bash
   curl -H "Authorization: Bearer YOUR_API_KEY" https://api.example.com
   ```
4. Check firewall allows outbound HTTPS
5. Review error message for specific issue

### Issue 3: Events Not Appearing

**Symptoms:**
- Event Logs page is empty
- No events after creating products

**Solutions:**
1. Check if event producers are installed:
   - Review `includes/functions.php` for hooks
   - Check `admin_actions.php` for ORDER_CANCELLED
2. Verify database connection
3. Check error logs:
   ```bash
   tail -f /var/log/php_errors.log
   ```
4. Test dispatcher manually (see Step 3)

### Issue 4: High Dead-Letter Count

**Symptoms:**
- Dead-letter queue growing
- Many abandoned events

**Solutions:**
1. Review dead-letter messages in Queue Status
2. Check error messages for patterns
3. Fix underlying issue (bad endpoint, invalid credentials, etc.)
4. Update CRM connection if needed
5. Manually retry messages after fixing issue

---

## 📈 Performance Optimization

### For High Volume:

**Increase Worker Frequency:**
```bash
# Change from every 5 minutes to every 1 minute
*/1 * * * * php /path/to/queue_worker.php
```

**Process More Messages Per Run:**
Edit `em_queue.php`:
```php
// Change from 50 to 100
$results = em_process_queue(100);
```

**Add Multiple Workers:**
```bash
# Run 3 workers in parallel (safe for concurrent execution)
*/5 * * * * php /path/to/queue_worker.php >> /var/log/em_queue_1.log 2>&1
*/5 * * * * php /path/to/queue_worker.php >> /var/log/em_queue_2.log 2>&1
*/5 * * * * php /path/to/queue_worker.php >> /var/log/em_queue_3.log 2>&1
```

---

## 🎯 Success Criteria

### You'll know everything is working when:

✅ **Event System:**
- Events appear in Event Logs within seconds
- Failed events are tracked automatically
- Event journeys are visible

✅ **Queue Processing:**
- Health score is 90%+
- Pending count stays low
- Failed events are retried automatically
- Dead-letter count is minimal

✅ **CRM Integration:**
- Connection test passes
- Webhooks are delivered successfully
- Delivery logs show success
- Last sync timestamp updates

✅ **Monitoring:**
- Dashboard shows real-time stats
- Audit trail records all actions
- Export works correctly
- No errors in logs

---

## 📞 Next Steps After Setup

### Once Everything is Running:

1. **Configure Production CRM Connections:**
   - Replace test webhooks with real CRM endpoints
   - Add production API credentials
   - Test each connection

2. **Set Up Monitoring:**
   - Create alert rules for critical events
   - Configure email notifications (optional)
   - Set up daily reports (optional)

3. **Optimize Settings:**
   - Adjust retry intervals if needed
   - Configure rate limits
   - Set retention policies

4. **Train Your Team:**
   - Show them Event Logs page
   - Explain Failed Events retry
   - Demonstrate CRM connection management

---

## 🎉 You're Ready!

Once you complete these 5 steps, your Event Manager will be fully operational and ready for production use.

**Need Help?**
- Check `PHASE_3C_4_README.md` for detailed documentation
- Review `FINAL_COMPLETION_REPORT.md` for feature reference
- Check error logs for troubleshooting

**Happy Event Managing! 🚀**
