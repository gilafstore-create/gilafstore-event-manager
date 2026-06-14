# LOCAL SERVER URLs — Gilaf Store & Event Manager

**Admin Portal Path:** `gs-secure-portal-92XK`  
**Local Server:** XAMPP  
**Base Path:** `c:\xampp\htdocs\gilafstore.com\public_html\`

---

## 🏪 STORE URLs (Frontend)

### Homepage
```
http://localhost/gilafstore.com/public_html/
```

### Shop & Products
```
http://localhost/gilafstore.com/public_html/shop.php
http://localhost/gilafstore.com/public_html/product.php?id=1
http://localhost/gilafstore.com/public_html/cart.php
http://localhost/gilafstore.com/public_html/checkout.php
```

### Other Pages
```
http://localhost/gilafstore.com/public_html/about-us.php
http://localhost/gilafstore.com/public_html/contact.php
```

---

## 🔐 ADMIN URLs (Secure Portal)

### Admin Login
```
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/admin_login.php
```

### Admin Dashboard
```
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/index.php
```

### Product Management
```
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/manage_products.php
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/add_product.php
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/edit_product.php?id=1
```

### Order Management
```
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/manage_orders.php
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/order_details.php?id=1
```

### Customer Management
```
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/manage_customers.php
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/customer_details.php?id=1
```

### Category Management
```
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/manage_categories.php
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/add_category.php
```

---

## 🎯 EVENT MANAGER URLs (New Module)

### Installation (Run this FIRST)
```
http://localhost/gilafstore.com/public_html/event-manager/migrations/install.php
```

### Dashboard
```
http://localhost/gilafstore.com/public_html/event-manager/pages/dashboard.php
```

### Entry Point (Redirects to Dashboard)
```
http://localhost/gilafstore.com/public_html/event-manager/
```

### Uninstall (if needed)
```
http://localhost/gilafstore.com/public_html/event-manager/migrations/uninstall.php
```

---

## 📋 QUICK START GUIDE

### Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache**
3. Start **MySQL**

### Step 2: Access Admin Portal
1. Navigate to:
   ```
   http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/admin_login.php
   ```
2. Login with your admin credentials
3. You'll see the admin dashboard

### Step 3: Install Event Manager
1. From admin sidebar, click **"Event Manager"** menu
2. Or navigate directly to:
   ```
   http://localhost/gilafstore.com/public_html/event-manager/migrations/install.php
   ```
3. Click **"Run Installation"**
4. Wait for 61 tables to be created
5. Click **"Go to Dashboard"**

### Step 4: Access Event Manager
- **From Admin Sidebar:** Click "Event Manager" → "Dashboard"
- **Direct URL:**
  ```
  http://localhost/gilafstore.com/public_html/event-manager/pages/dashboard.php
  ```

---

## 🔗 COMPLETE URL REFERENCE TABLE

| Section | URL |
|---------|-----|
| **Store Homepage** | `http://localhost/gilafstore.com/public_html/` |
| **Admin Login** | `http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/admin_login.php` |
| **Admin Dashboard** | `http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/index.php` |
| **Event Manager Install** | `http://localhost/gilafstore.com/public_html/event-manager/migrations/install.php` |
| **Event Manager Dashboard** | `http://localhost/gilafstore.com/public_html/event-manager/pages/dashboard.php` |
| **Event Manager Uninstall** | `http://localhost/gilafstore.com/public_html/event-manager/migrations/uninstall.php` |

---

## 🌐 PRODUCTION URLs (For Reference)

### Store
```
https://gilafstore.com/
```

### Admin Portal
```
https://gilafstore.com/gs-secure-portal-92XK/admin_login.php
https://gilafstore.com/gs-secure-portal-92XK/index.php
```

### Event Manager
```
https://gilafstore.com/event-manager/migrations/install.php
https://gilafstore.com/event-manager/pages/dashboard.php
```

---

## 🔧 OPTIONAL: Virtual Host Setup

For cleaner URLs like `http://gilafstore.local/`, follow these steps:

### 1. Edit Windows Hosts File
**Location:** `C:\Windows\System32\drivers\etc\hosts`

**Add this line:**
```
127.0.0.1    gilafstore.local
```

### 2. Edit Apache Virtual Hosts
**Location:** `C:\xampp\apache\conf\extra\httpd-vhosts.conf`

**Add this configuration:**
```apache
<VirtualHost *:80>
    ServerName gilafstore.local
    DocumentRoot "C:/xampp/htdocs/gilafstore.com/public_html"
    <Directory "C:/xampp/htdocs/gilafstore.com/public_html">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 3. Restart Apache
Stop and start Apache in XAMPP Control Panel

### 4. Access with Clean URLs
```
http://gilafstore.local/                                    (Store)
http://gilafstore.local/gs-secure-portal-92XK/admin_login.php   (Admin)
http://gilafstore.local/event-manager/                      (Event Manager)
```

---

## ✅ VERIFICATION CHECKLIST

After installation, verify these work:

### Store Frontend
- [ ] Homepage loads
- [ ] Products display
- [ ] Cart works
- [ ] Checkout works

### Admin Portal
- [ ] Login works
- [ ] Dashboard loads
- [ ] Product management works
- [ ] Order management works
- [ ] Event Manager menu appears

### Event Manager
- [ ] Installation completes successfully
- [ ] 61 tables created (all prefixed `em_`)
- [ ] Dashboard loads
- [ ] Stats display
- [ ] "Back to Admin" link works
- [ ] No errors in browser console

---

## 🚨 TROUBLESHOOTING

### Issue: "Page not found"
**Solution:** Check XAMPP Apache is running

### Issue: "Database connection failed"
**Solution:** Check XAMPP MySQL is running

### Issue: "Access denied"
**Solution:** Login to admin portal first at:
```
http://localhost/gilafstore.com/public_html/gs-secure-portal-92XK/admin_login.php
```

### Issue: Event Manager redirects to wrong login page
**Solution:** Already fixed! It now redirects to `gs-secure-portal-92XK/admin_login.php`

---

## 📝 NOTES

1. **Admin Portal Path:** Custom secure path `gs-secure-portal-92XK` is used instead of default `admin`
2. **Event Manager:** Fully integrated with your custom admin portal
3. **Zero Impact:** Event Manager has ZERO impact on existing store and admin functionality
4. **Isolated:** All Event Manager tables use `em_` prefix
5. **Reversible:** Can be uninstalled completely without affecting existing system

---

**All URLs updated to work with your custom admin portal path!** 🚀
