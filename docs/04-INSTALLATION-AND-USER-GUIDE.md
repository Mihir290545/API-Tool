# DevDock — Installation, Configuration & User Guide

---

## 1. System Requirements

* **Operating System**: Windows 10/11
* **PHP Runtime**: PHP 8.1+ (PHP 8.2 included with XAMPP recommended)
* **Required PHP Extensions**: `pdo`, `pdo_mysql`, `curl`, `json`, `mbstring`, `openssl`
* **Database**: MySQL Community Server 5.7+ or MariaDB 10.4+
* **Web Server**: Apache 2.4+ (with `mod_rewrite` enabled)
* **Web Browser**: Chrome, Edge, Firefox, or Safari (Modern desktop browser)

---

## 2. Installation & Setup Walkthrough

### Step 1: Start XAMPP Services
1. Open the **XAMPP Control Panel**.
2. Start both **Apache** and **MySQL**.

### Step 2: Database Initialization
Open PowerShell or Command Prompt and execute:
```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root < database\schema.sql"
```

### Step 3: Seed Default Demo Account
Populate the database with a pre-configured user account, default collection, and sample environment:
```powershell
C:\xampp\php\php.exe database\seed.php
```

* **Demo Email:** `developer@devdock.local`
* **Demo Password:** `password123`

### Step 4: Open in Web Browser
Open your browser and navigate to:
[http://localhost/api-tool/public/](http://localhost/api-tool/public/)

---

## 3. User Walkthrough

### 3.1 Composing and Sending an API Request
1. Enter the HTTP Method (e.g., `GET`, `POST`, `PUT`, `DELETE`).
2. Input the request URL (e.g., `https://jsonplaceholder.typicode.com/posts` or use environment variable `{{base_url}}/posts`).
3. (Optional) Add Query Parameters or Request Headers in their respective tabs.
4. For `POST` or `PUT`, select **Body** -> **JSON** and supply your payload. Use **Beautify JSON** to auto-format.
5. Click the blue **Send** button.

### 3.2 Analyzing the Response
* View the HTTP Status Code badge (`200 OK`, `404 Not Found`, etc.).
* Review the exact execution latency in milliseconds and download payload size in bytes.
* Switch between **Response Body** (pretty-printed JSON) and **Headers** (full server headers).
* Click **Copy** to copy the formatted response to your clipboard.

### 3.3 Adding Automated Assertions
1. Switch to the **Tests** tab in the request panel.
2. Click **+ Add Assertion**.
3. Choose an assertion type:
   * *Status code is* (e.g. `200`)
   * *Response time is less than (ms)* (e.g. `500`)
   * *JSON property exists* (e.g. `title` or `data.id`)
   * *JSON property equals* (e.g. `completed` equals `false`)
   * *Body contains string* (e.g. `"userId": 1`)
4. Click **Send** -> switch to **Test Results** in the response panel to view live pass/fail badges.

### 3.4 Managing Collections
* Click **+ New** in the sidebar to create a named collection.
* Click **Save** in the request bar to store the active request into any collection.
* Click any saved request in the sidebar to load it into the editor.

### 3.5 Managing Environment Variables
* Select your active environment from the top-right **ENV** dropdown.
* When executing requests, any `{{var_name}}` token will automatically resolve to its corresponding value.
