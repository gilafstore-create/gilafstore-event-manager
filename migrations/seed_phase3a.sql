-- Phase 3A Seed Data
-- Safe: INSERT only, no ALTER/UPDATE/DELETE, idempotent via WHERE NOT EXISTS

-- ── 1. EVENT DEFINITIONS (13 canonical types) ────────────────────────────────
INSERT INTO em_event_definitions (name, description, status)
  SELECT 'ORDER_CREATED','Fired when a new order is placed (COD, Razorpay, UPI).','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'ORDER_CREATED');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'ORDER_UPDATED','Fired when an order status is updated by admin.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'ORDER_UPDATED');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'ORDER_CANCELLED','Fired when an order is cancelled or refunded.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'ORDER_CANCELLED');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'PAYMENT_SUCCESS','Fired when a payment is verified and captured successfully.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'PAYMENT_SUCCESS');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'PAYMENT_FAILED','Fired when a payment fails signature or gateway verification.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'PAYMENT_FAILED');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'CUSTOMER_CREATED','Fired when a new customer registers (direct or guest checkout).','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'CUSTOMER_CREATED');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'CUSTOMER_UPDATED','Fired when a customer profile is updated.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'CUSTOMER_UPDATED');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'PRODUCT_CREATED','Fired when a new product is created by an admin.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'PRODUCT_CREATED');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'PRODUCT_UPDATED','Fired when a product is updated by an admin.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'PRODUCT_UPDATED');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'CRM_SYNC_STARTED','Fired when a CRM synchronisation task begins.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'CRM_SYNC_STARTED');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'CRM_SYNC_COMPLETED','Fired when a CRM synchronisation task finishes.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'CRM_SYNC_COMPLETED');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'WEBHOOK_SENT','Fired when an outbound webhook is successfully delivered.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'WEBHOOK_SENT');

INSERT INTO em_event_definitions (name, description, status)
  SELECT 'WEBHOOK_FAILED','Fired when an outbound webhook delivery fails.','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_definitions WHERE name = 'WEBHOOK_FAILED');

-- ── 2. EVENT SOURCES (6 system sources) ─────────────────────────────────────
INSERT INTO em_event_sources (name, type, config, status)
  SELECT 'Gilaf Store — Orders','internal','{"description":"COD, Razorpay, UPI order placement"}','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_sources WHERE name = 'Gilaf Store — Orders');

INSERT INTO em_event_sources (name, type, config, status)
  SELECT 'Gilaf Store — Customers','internal','{"description":"Customer registration and profile updates"}','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_sources WHERE name = 'Gilaf Store — Customers');

INSERT INTO em_event_sources (name, type, config, status)
  SELECT 'Gilaf Store — Products','internal','{"description":"Admin product CRUD operations"}','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_sources WHERE name = 'Gilaf Store — Products');

INSERT INTO em_event_sources (name, type, config, status)
  SELECT 'Razorpay Gateway','external','{"description":"Razorpay payment capture and webhook receiver"}','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_sources WHERE name = 'Razorpay Gateway');

INSERT INTO em_event_sources (name, type, config, status)
  SELECT 'Gilaf Store — Emails','internal','{"description":"Transactional order email notifications"}','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_sources WHERE name = 'Gilaf Store — Emails');

INSERT INTO em_event_sources (name, type, config, status)
  SELECT 'CRM Webhook System','external','{"description":"Outbound CRM webhook delivery pipeline"}','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_sources WHERE name = 'CRM Webhook System');

-- ── 3. EVENT DESTINATIONS (4 destinations) ──────────────────────────────────
INSERT INTO em_event_destinations (name, type, config, status)
  SELECT 'Internal Event Log','internal','{"table":"em_event_logs","description":"All events written to the EM event log"}','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_destinations WHERE name = 'Internal Event Log');

INSERT INTO em_event_destinations (name, type, config, status)
  SELECT 'Email Notification','email','{"handler":"send_task_email","description":"Transactional emails via PHPMailer"}','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_destinations WHERE name = 'Email Notification');

INSERT INTO em_event_destinations (name, type, config, status)
  SELECT 'CRM Webhook','webhook','{"table":"crm_webhook_deliveries","description":"Outbound CRM webhook delivery"}','active'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_destinations WHERE name = 'CRM Webhook');

INSERT INTO em_event_destinations (name, type, config, status)
  SELECT 'Internal Queue','queue','{"table":"em_queue_messages","description":"Async retry queue (Phase 3B)"}','inactive'
  WHERE NOT EXISTS (SELECT 1 FROM em_event_destinations WHERE name = 'Internal Queue');
