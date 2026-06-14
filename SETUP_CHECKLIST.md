# Event Manager - Setup Checklist

**Date:** June 14, 2026  
**Use this checklist to ensure everything is configured correctly**

---

## ✅ Pre-Setup Verification

### System Requirements
- [ ] PHP 7.4+ installed
- [ ] MySQL/MariaDB database running
- [ ] Web server (Apache/Nginx) running
- [ ] Database tables created (run installation script if needed)

### File Permissions
- [ ] `event-manager/workers/queue_worker.php` is executable
- [ ] Log directory exists and is writable
- [ ] All PHP files are readable by web server

---

## 🚀 Step 1: Run Setup Test

### Windows (XAMPP):
```powershell
# Option A: Double-click this file
event-manager\run_setup_test.bat

# Option B: Run from command line
cd C:\xampp\htdocs\gilafstore.com\public_html
php event-manager\test_setup.php
```

### Linux (Hostinger):
```bash
cd /path/to/public_html
php event-manager/test_setup.php
```

### Expected Results:
- [ ] ✓ Database connection successful
- [ ] ✓ All required tables exist
- [ ] ✓ Event definitions found
- [ ] ✓ Queue stats retrieved
- [ ] ✓ Test event dispatched

### If Tests Fail:
- [ ] Check database credentials in `includes/db_connect.php`
- [ ] Run installation script to create missing tables
- [ ] Verify file permissions
- [ ] Check PHP error logs

---

## 🔄 Step 2: Set Up Queue Worker

### Windows (XAMPP) - Task Scheduler:

1. **Create Task:**
   - [ ] Open Task Scheduler (`Win+R` → `taskschd.msc`)
   - [ ] Click "Create Basic Task"
   - [ ] Name: `Event Manager Queue Worker`
   - [ ] Description: `Processes event retry queue every 5 minutes`

2. **Configure Trigger:**
   - [ ] Trigger: Daily
   - [ ] Start: Today at 12:00 AM
   - [ ] Recur every: 1 day

3. **Configure Action:**
   - [ ] Action: Start a program
   - [ ] Program: `C:\xampp\php\php.exe`
   - [ ] Arguments: `C:\xampp\htdocs\gilafstore.com\public_html\event-manager\workers\queue_worker.php`
   - [ ] Start in: `C:\xampp\htdocs\gilafstore.com\public_html`

4. **Advanced Settings:**
   - [ ] Right-click task → Properties
   - [ ] Triggers tab → Edit
   - [ ] Check "Repeat task every: 5 minutes"
   - [ ] Duration: Indefinitely
   - [ ] Click OK

5. **Test Task:**
   - [ ] Right-click task → Run
   - [ ] Check if it runs without errors
   - [ ] Verify in Event Manager → Queue Status

### Linux (Hostinger) - Cron Job:

1. **Edit Crontab:**
   ```bash
   crontab -e
   ```

2. **Add Cron Job:**
   ```bash
   */5 * * * * php /home/username/public_html/event-manager/workers/queue_worker.php >> /var/log/em_queue.log 2>&1
   ```
   - [ ] Replace `/home/username/public_html` with your actual path
   - [ ] Save and exit

3. **Verify Cron Job:**
   ```bash
   crontab -l
   ```
   - [ ] Confirm the job is listed

4. **Create Log File:**
   ```bash
   mkdir -p /var/log
   touch /var/log/em_queue.log
   chmod 666 /var/log/em_queue.log
   ```

5. **Test Manually:**
   ```bash
   php /path/to/event-manager/workers/queue_worker.php
   ```
   - [ ] Should run without errors

### Verification:
- [ ] Wait 5 minutes
- [ ] Check Queue Status page
- [ ] Verify "Last Run" timestamp updates
- [ ] Check log file for output

---

## 🔌 Step 3: Create First CRM Connection

### Access CRM Hub:
- [ ] Navigate to: Event Manager → CRM Hub → Manage Connections
- [ ] Click "New Connection" button

### Test Connection (Webhook.site):

1. **Get Test URL:**
   - [ ] Go to https://webhook.site
   - [ ] Copy your unique URL

2. **Create Connection:**
   - [ ] Name: `Test Webhook`
   - [ ] CRM Type: `Custom Webhook`
   - [ ] API Endpoint: `https://webhook.site/YOUR-ID`
   - [ ] API Key: `test-key-123`
   - [ ] Status: `Active`
   - [ ] Auto-sync: ✓ Checked
   - [ ] Click "Save Connection"

3. **Test Connection:**
   - [ ] Click "Test" button
   - [ ] Should show "Connection successful"
   - [ ] Check webhook.site for incoming request

### Production Connection (Optional):

**Salesforce:**
- [ ] Name: `Production Salesforce`
- [ ] CRM Type: `Salesforce`
- [ ] API Endpoint: `https://your-instance.salesforce.com/services/data/v57.0`
- [ ] API Key: [Your OAuth Token]
- [ ] Test connection

**HubSpot:**
- [ ] Name: `Production HubSpot`
- [ ] CRM Type: `HubSpot`
- [ ] API Endpoint: `https://api.hubapi.com`
- [ ] API Key: [Your API Key]
- [ ] Test connection

### Verification:
- [ ] Connection appears in list
- [ ] Status shows "Active"
- [ ] Test passes successfully
- [ ] Last sync timestamp updates

---

## 🧪 Step 4: Test Event Dispatching

### Method 1: Use Existing Triggers

**Test PRODUCT_CREATED:**
- [ ] Go to Admin → Products
- [ ] Create a new product
- [ ] Go to Event Manager → Event Operations → Event Logs
- [ ] Verify `PRODUCT_CREATED` event appears

**Test ORDER_CANCELLED:**
- [ ] Go to Admin → Orders
- [ ] Cancel an order
- [ ] Check Event Logs for `ORDER_CANCELLED` event

### Method 2: Manual Test Script

1. **Run Test:**
   ```bash
   # Windows
   cd C:\xampp\htdocs\gilafstore.com\public_html
   php event-manager\test_setup.php
   
   # Linux
   php event-manager/test_setup.php
   ```

2. **Verify:**
   - [ ] Test event appears in Event Logs
   - [ ] Event has correct payload
   - [ ] Timestamp is recent

### Verification:
- [ ] Events appear within seconds
- [ ] Event details are correct
- [ ] No errors in logs

---

## 📊 Step 5: Monitor Queue Health

### Access Queue Status:
- [ ] Navigate to: Event Manager → Event Operations → Queue Status

### Check Health Metrics:

**Health Score:**
- [ ] Score is 90-100% (green) = ✓ Healthy
- [ ] Score is 70-89% (yellow) = ⚠ Warning
- [ ] Score is <70% (red) = ❌ Critical

**Queue Statistics:**
- [ ] Pending: Low (< 100)
- [ ] Processing: 0 when idle
- [ ] Completed: Growing over time
- [ ] Failed: Minimal
- [ ] Dead Letter: Zero or very low

**Recent Messages:**
- [ ] No stuck messages
- [ ] Retry counts are reasonable
- [ ] No repeated errors

### Test Queue Processing:

1. **Create Failed Event:**
   - [ ] Create CRM connection with invalid endpoint
   - [ ] Trigger an event
   - [ ] Verify it appears in Failed Events

2. **Test Manual Retry:**
   - [ ] Go to Failed Events page
   - [ ] Click "Retry" button
   - [ ] Check Queue Status for queued message

3. **Test Automatic Retry:**
   - [ ] Wait 5 minutes for cron job
   - [ ] Check Queue Status
   - [ ] Verify retry attempt occurred

### Verification:
- [ ] Queue is processing automatically
- [ ] Failed events are retried
- [ ] Health score remains high
- [ ] No errors in worker logs

---

## 📈 Step 6: Verify All Systems

### Event System:
- [ ] Events are logged immediately
- [ ] Failed events are tracked
- [ ] Event journeys are visible
- [ ] Search works correctly

### Queue Processing:
- [ ] Cron job runs every 5 minutes
- [ ] Messages are processed
- [ ] Retries use exponential backoff
- [ ] Dead-letter queue captures abandoned events

### CRM Integration:
- [ ] Connections can be created
- [ ] Connection tests work
- [ ] Webhooks are delivered
- [ ] Delivery logs are populated

### Monitoring:
- [ ] Dashboard shows statistics
- [ ] Event Logs are real-time
- [ ] Queue Status is accurate
- [ ] Audit Trail records actions

### Export:
- [ ] Audit log export works
- [ ] CSV file downloads
- [ ] Filters are applied
- [ ] Data is complete

---

## 🎯 Final Verification

### Run Complete Test Suite:

```bash
# Windows
event-manager\run_setup_test.bat

# Linux
php event-manager/test_setup.php
```

### All Tests Should Pass:
- [ ] ✓ Database connection
- [ ] ✓ EM database functions
- [ ] ✓ Event dispatcher
- [ ] ✓ Queue processing engine
- [ ] ✓ All required tables exist
- [ ] ✓ Event definitions found
- [ ] ✓ Queue stats retrieved
- [ ] ✓ CRM connections checked
- [ ] ✓ Test event dispatched
- [ ] ✓ Worker script exists

---

## 🚨 Troubleshooting

### If Queue Not Processing:

**Windows:**
- [ ] Check Task Scheduler → Event Manager Queue Worker
- [ ] Right-click → Run to test manually
- [ ] Check "Last Run Result" for errors
- [ ] Verify PHP path is correct

**Linux:**
- [ ] Check cron logs: `tail -f /var/log/cron`
- [ ] Test manually: `php queue_worker.php`
- [ ] Check worker logs: `tail -f /var/log/em_queue.log`
- [ ] Verify file permissions

### If Events Not Appearing:
- [ ] Check database connection
- [ ] Verify event producers are installed
- [ ] Check PHP error logs
- [ ] Test dispatcher manually

### If CRM Test Fails:
- [ ] Verify API endpoint URL
- [ ] Check API credentials
- [ ] Test with curl
- [ ] Check firewall settings

### If Health Score Low:
- [ ] Review dead-letter queue
- [ ] Check error messages
- [ ] Fix underlying issues
- [ ] Manually retry failed events

---

## ✅ Setup Complete!

### You're ready when:
- [ ] All tests pass
- [ ] Queue worker is running
- [ ] CRM connection created and tested
- [ ] Events are being logged
- [ ] Queue is processing
- [ ] Health score is 90%+

### Next Steps:
1. **Configure Production:**
   - [ ] Add real CRM connections
   - [ ] Set up alert rules
   - [ ] Configure retention policies

2. **Monitor Daily:**
   - [ ] Check Queue Status health
   - [ ] Review failed events
   - [ ] Monitor CRM deliveries

3. **Optimize:**
   - [ ] Adjust retry intervals if needed
   - [ ] Add more workers if high volume
   - [ ] Configure rate limits

---

## 📚 Documentation

For detailed information, see:
- **`QUICK_START_GUIDE.md`** - Step-by-step setup
- **`PHASE_3C_4_README.md`** - Comprehensive documentation
- **`FINAL_COMPLETION_REPORT.md`** - Feature reference
- **`IMPLEMENTATION_SUMMARY.md`** - Quick reference

---

**🎉 Congratulations! Your Event Manager is fully configured and ready for production use!**
