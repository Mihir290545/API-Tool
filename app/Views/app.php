<?php
// Compute base path dynamically so it works seamlessly on any domain, subfolder, or port
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$baseDir = rtrim(dirname($scriptName), '/');
$baseUrl = ($baseDir === '' || $baseDir === '/') ? '' : $baseDir;
$apiBaseUrl = $baseUrl . '/api';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="api-base" content="<?= htmlspecialchars($apiBaseUrl, ENT_QUOTES, 'UTF-8') ?>">
  <title>DevDock — API Development & Testing Platform</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%233b82f6'><rect width='24' height='24' rx='6'/><path d='M7 8l5 4-5 4M13 16h4' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>">
</head>
<body>

  <!-- App Header -->
  <header class="app-header">
    <div class="brand-section">
      <a href="#" class="brand-logo">
        <div class="brand-icon">D</div>
        <span>DevDock</span>
      </a>
      <span class="brand-badge">API STUDIO</span>
    </div>

    <div class="header-actions">
      <!-- Active Environment Selector -->
      <div class="env-selector">
        <span style="font-size: 11px; color: var(--text-muted);">ENV:</span>
        <select id="env-dropdown">
          <option value="">No Environment</option>
        </select>
      </div>

      <!-- User Menu -->
      <div id="user-menu-wrapper" style="display: none;">
        <div class="env-selector" style="cursor: pointer;" id="user-menu-trigger">
          <span style="font-size: 11px; color: var(--text-muted);">👤</span>
          <span id="user-menu-name" style="font-size: 12px; font-weight: 500; color: var(--text-primary); max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
          <span style="font-size: 10px; color: var(--text-muted);">▼</span>
        </div>
        <div id="user-dropdown" style="display: none; position: absolute; right: 60px; top: 48px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 4px; min-width: 160px; z-index: 9999; box-shadow: 0 8px 24px rgba(0,0,0,0.3);">
          <div style="padding: 8px 12px; border-bottom: 1px solid var(--border-color); margin-bottom: 4px;">
            <div style="font-size: 11px; color: var(--text-muted);">Signed in as</div>
            <div id="user-dropdown-email" style="font-size: 12px; font-weight: 600; color: var(--text-primary);"></div>
          </div>
          <button class="btn btn-sm" id="btn-logout" style="width: 100%; text-align: left; padding: 6px 12px; background: transparent; color: var(--text-secondary);">Sign Out</button>
        </div>
      </div>

      <!-- Manage Environments Button -->
      <button id="btn-manage-envs" class="btn-icon" title="Manage Environments" style="font-size: 14px;">⚙</button>

      <!-- Code Generator Button -->
      <button id="btn-curl-gen" class="btn btn-sm" title="Generate cURL Code" style="font-size: 11px; display: none;">Code</button>

      <!-- Theme Switcher -->
      <button id="btn-toggle-theme" class="btn-icon" title="Toggle Dark/Light Mode">☀️</button>
    </div>
  </header>

  <!-- App Main Layout -->
  <div class="app-container">

    <!-- Left Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-tabs">
        <div class="sidebar-tab active" data-tab="collections">Collections</div>
        <div class="sidebar-tab" data-tab="history">History</div>
      </div>

      <!-- View: Collections -->
      <div id="view-collections" class="sidebar-content" style="display: block;">
        <div class="sidebar-toolbar" style="padding: 0 0 8px 0;">
          <input type="text" class="search-input" placeholder="Search requests..." id="search-collections">
          <button class="btn btn-sm btn-primary" id="btn-new-collection" style="margin-left: 6px; white-space: nowrap;">+ New</button>
          <button class="btn btn-sm" id="btn-export-collection" title="Export collection as JSON" style="margin-left: 4px;">↑ Export</button>
          <label class="btn btn-sm" title="Import collection from JSON" style="margin-left: 4px; cursor: pointer;">↓ Import<input type="file" id="import-collection-file" accept=".json" style="display: none;"></label>
        </div>
        <div id="collections-list">
          <!-- Dynamic Collections Tree -->
        </div>
      </div>

      <!-- View: History -->
      <div id="view-history" class="sidebar-content" style="display: none;">
        <div class="sidebar-toolbar" style="padding: 0 0 8px 0;">
          <span style="font-size: 11px; color: var(--text-muted); font-weight: 600;">RECENT EXECUTIONS</span>
          <button class="btn btn-sm" id="btn-clear-history" style="font-size: 10px;">Clear</button>
        </div>
        <div id="history-list">
          <!-- Dynamic History Items -->
        </div>
      </div>
    </aside>

    <!-- Center Workspace -->
    <main class="workspace">
      
      <!-- Top Request Bar -->
      <div class="request-bar">
        <select id="req-method" class="method-select">
          <option value="GET">GET</option>
          <option value="POST">POST</option>
          <option value="PUT">PUT</option>
          <option value="PATCH">PATCH</option>
          <option value="DELETE">DELETE</option>
          <option value="HEAD">HEAD</option>
          <option value="OPTIONS">OPTIONS</option>
        </select>

        <div class="url-input-container">
          <input type="text" id="req-url" class="url-input" placeholder="https://api.example.com/v1/resource or {{base_url}}/users" value="https://jsonplaceholder.typicode.com/todos/1">
        </div>

        <button id="btn-send" class="btn btn-primary btn-send">Send</button>
        <button id="btn-save-request" class="btn">Save</button>
      </div>

      <!-- Split Request / Response -->
      <div class="workspace-body">

        <!-- Top Panel: Request Configuration -->
        <section class="panel-container">
          <div class="panel-tabs-bar">
            <div class="panel-tabs" id="req-tabs">
              <div class="tab-btn active" data-tab="params">Params</div>
              <div class="tab-btn" data-tab="headers">Headers</div>
              <div class="tab-btn" data-tab="body">Body</div>
              <div class="tab-btn" data-tab="auth">Auth</div>
              <div class="tab-btn" data-tab="tests">Tests</div>
            </div>
          </div>

          <div class="panel-content">
            <!-- Params Tab -->
            <div id="tab-content-params" class="req-tab-content" style="display: block;">
              <table class="kv-table">
                <thead>
                  <tr>
                    <th style="width: 30px;"></th>
                    <th>Query Parameter Key</th>
                    <th>Value</th>
                    <th style="width: 30px;"></th>
                  </tr>
                </thead>
                <tbody id="params-table-body"></tbody>
              </table>
              <button class="btn btn-sm" id="btn-add-param" style="margin-top: 8px;">+ Add Parameter</button>
            </div>

            <!-- Headers Tab -->
            <div id="tab-content-headers" class="req-tab-content" style="display: none;">
              <table class="kv-table">
                <thead>
                  <tr>
                    <th style="width: 30px;"></th>
                    <th>Header Name</th>
                    <th>Value</th>
                    <th style="width: 30px;"></th>
                  </tr>
                </thead>
                <tbody id="headers-table-body"></tbody>
              </table>
              <button class="btn btn-sm" id="btn-add-header" style="margin-top: 8px;">+ Add Header</button>
            </div>

            <!-- Body Tab -->
            <div id="tab-content-body" class="req-tab-content" style="display: none; height: 100%;">
              <div style="display: flex; gap: 16px; margin-bottom: 8px; align-items: center;">
                <label style="font-size: 12px; cursor: pointer;"><input type="radio" name="body-type" value="none"> none</label>
                <label style="font-size: 12px; cursor: pointer;"><input type="radio" name="body-type" value="json" checked> JSON</label>
                <label style="font-size: 12px; cursor: pointer;"><input type="radio" name="body-type" value="raw"> raw text</label>
                <button class="btn btn-sm" id="btn-beautify-json" style="margin-left: auto;">Beautify JSON</button>
              </div>
              <div id="body-editor-container" style="height: calc(100% - 36px);">
                <textarea id="req-body-editor" class="code-editor" placeholder='{\n  "title": "foo",\n  "body": "bar",\n  "userId": 1\n}'></textarea>
              </div>
            </div>

            <!-- Auth Tab -->
            <div id="tab-content-auth" class="req-tab-content" style="display: none;">
              <div class="form-group" style="max-width: 300px; margin-bottom: 16px;">
                <label class="form-label">Authentication Type</label>
                <select id="auth-type-select" class="form-control">
                  <option value="none">No Auth</option>
                  <option value="bearer">Bearer Token</option>
                  <option value="basic">Basic Auth</option>
                  <option value="apikey">API Key</option>
                </select>
              </div>

              <!-- Bearer Token Fields -->
              <div id="auth-bearer-fields" style="display: none; max-width: 500px;">
                <div class="form-group">
                  <label class="form-label">Token</label>
                  <input type="password" id="auth-bearer-token" class="form-control" placeholder="Bearer eyJhbGciOi...">
                </div>
              </div>

              <!-- Basic Auth Fields -->
              <div id="auth-basic-fields" style="display: none; max-width: 500px;">
                <div class="form-group" style="margin-bottom: 8px;">
                  <label class="form-label">Username</label>
                  <input type="text" id="auth-basic-user" class="form-control" placeholder="Username">
                </div>
                <div class="form-group">
                  <label class="form-label">Password</label>
                  <input type="password" id="auth-basic-pass" class="form-control" placeholder="Password">
                </div>
              </div>

              <!-- API Key Fields -->
              <div id="auth-apikey-fields" style="display: none; max-width: 500px;">
                <div class="form-group" style="margin-bottom: 8px;">
                  <label class="form-label">Key Name</label>
                  <input type="text" id="auth-apikey-key" class="form-control" placeholder="X-API-KEY">
                </div>
                <div class="form-group" style="margin-bottom: 8px;">
                  <label class="form-label">Value</label>
                  <input type="password" id="auth-apikey-val" class="form-control" placeholder="Secret Key Value">
                </div>
                <div class="form-group">
                  <label class="form-label">Add To</label>
                  <select id="auth-apikey-addto" class="form-control">
                    <option value="header">Header</option>
                    <option value="query">Query Parameter</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Tests Tab -->
            <div id="tab-content-tests" class="req-tab-content" style="display: none;">
              <table class="kv-table">
                <thead>
                  <tr>
                    <th style="width: 30px;"></th>
                    <th>Assertion Rule</th>
                    <th>Property Path (optional)</th>
                    <th>Expected Value</th>
                    <th style="width: 30px;"></th>
                  </tr>
                </thead>
                <tbody id="tests-table-body"></tbody>
              </table>
              <button class="btn btn-sm" id="btn-add-test" style="margin-top: 8px;">+ Add Assertion</button>
            </div>
          </div>
        </section>

        <!-- Bottom Panel: Response Viewer -->
        <section class="response-container">
          <div class="panel-tabs-bar">
            <div class="panel-tabs" id="res-tabs">
              <div class="tab-btn active" data-tab="body">Response Body</div>
              <div class="tab-btn" data-tab="headers">Headers</div>
              <div class="tab-btn" data-tab="tests">
                Test Results <span id="test-results-count" class="brand-badge" style="margin-left: 4px;">0</span>
              </div>
            </div>

            <!-- Response Metrics -->
            <div class="response-status-bar">
              <div class="stat-item">
                <span>Status:</span>
                <span id="res-status" class="stat-value">-</span>
              </div>
              <div class="stat-item">
                <span>Time:</span>
                <span id="res-time" class="stat-value">-</span>
              </div>
              <div class="stat-item">
                <span>Size:</span>
                <span id="res-size" class="stat-value">-</span>
              </div>
              <button id="btn-copy-response" class="btn btn-sm">Copy</button>
            </div>
          </div>

          <div class="panel-content" style="padding: 0; position: relative;">
            <!-- Response Body Tab -->
            <div id="res-content-body" class="res-tab-content" style="height: 100%;">
              <pre id="response-body-viewer" class="response-viewer">(Click "Send" to execute request and inspect response)</pre>
            </div>

            <!-- Response Headers Tab -->
            <div id="res-content-headers" class="res-tab-content" style="display: none; padding: 16px;">
              <table class="kv-table">
                <thead>
                  <tr>
                    <th>Header</th>
                    <th>Value</th>
                  </tr>
                </thead>
                <tbody id="response-headers-table"></tbody>
              </table>
            </div>

            <!-- Response Tests Tab -->
            <div id="res-content-tests" class="res-tab-content" style="display: none; padding: 16px;">
              <div id="test-results-list"></div>
            </div>
          </div>
        </section>

      </div>
    </main>

  </div>

  <!-- Modal: Authentication -->
  <div class="modal-overlay" id="modal-auth" style="z-index: 10000;">
    <div class="modal-card" style="width: 400px; max-width: 95vw;">
      <div class="modal-header">
        <div>
          <h3 style="font-size: 16px; font-weight: 700; color: var(--text-primary);">Welcome to DevDock</h3>
          <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">API Development &amp; Testing Platform</p>
        </div>
      </div>

      <!-- Auth Tabs -->
      <div style="display: flex; border-bottom: 1px solid var(--border-color); margin: 0 0 20px 0;">
        <button class="auth-tab-btn active" data-auth-tab="login" style="flex: 1; padding: 10px; background: none; border: none; border-bottom: 2px solid var(--accent-color); color: var(--text-primary); font-size: 13px; font-weight: 600; cursor: pointer;">Sign In</button>
        <button class="auth-tab-btn" data-auth-tab="register" style="flex: 1; padding: 10px; background: none; border: none; border-bottom: 2px solid transparent; color: var(--text-muted); font-size: 13px; cursor: pointer;">Create Account</button>
      </div>

      <div class="modal-body" style="padding-top: 0;">
        <!-- Login Form -->
        <div id="auth-login-form">
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" id="login-email" class="form-control" placeholder="developer@example.com" autocomplete="email">
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" id="login-password" class="form-control" placeholder="Your password" autocomplete="current-password">
          </div>
          <div id="auth-login-error" style="display: none; color: #ef4444; font-size: 12px; margin-bottom: 12px; padding: 8px; background: rgba(239,68,68,0.1); border-radius: 6px;"></div>
          <button class="btn btn-primary" id="btn-login" style="width: 100%;">Sign In</button>
        </div>

        <!-- Register Form -->
        <div id="auth-register-form" style="display: none;">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" id="reg-name" class="form-control" placeholder="John Doe" autocomplete="name">
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" id="reg-email" class="form-control" placeholder="developer@example.com" autocomplete="email">
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" id="reg-password" class="form-control" placeholder="Min. 6 characters" autocomplete="new-password">
          </div>
          <div id="auth-register-error" style="display: none; color: #ef4444; font-size: 12px; margin-bottom: 12px; padding: 8px; background: rgba(239,68,68,0.1); border-radius: 6px;"></div>
          <button class="btn btn-primary" id="btn-register" style="width: 100%;">Create Account</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Environment Manager -->
  <div class="modal-overlay" id="modal-env-manager">
    <div class="modal-card" style="width: 700px; max-width: 95vw; height: 500px; display: flex; flex-direction: column;">
      <div class="modal-header">
        <h3 style="font-size: 14px; font-weight: 600;">Environment Manager</h3>
        <button class="btn-icon btn-sm" onclick="App.closeModal('modal-env-manager')">×</button>
      </div>
      <div style="display: flex; flex: 1; overflow: hidden; gap: 0;">
        <!-- Left: environment list -->
        <div style="width: 200px; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 12px;">
          <div id="env-manager-list" style="flex: 1; overflow-y: auto;"></div>
          <button class="btn btn-sm btn-primary" id="btn-env-new" style="margin-top: 8px; width: 100%;">+ New Environment</button>
        </div>
        <!-- Right: variable editor -->
        <div style="flex: 1; display: flex; flex-direction: column; padding: 12px; overflow: hidden;">
          <div id="env-editor-placeholder" style="flex: 1; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 13px;">
            Select or create an environment
          </div>
          <div id="env-editor-panel" style="display: none; flex-direction: column; flex: 1; overflow: hidden;">
            <div style="display: flex; gap: 8px; margin-bottom: 12px; align-items: center;">
              <input type="text" id="env-editor-name" class="form-control" style="flex: 1;" placeholder="Environment name">
              <button class="btn btn-sm" id="btn-env-delete-current" style="color: #ef4444; border-color: #ef4444;">Delete</button>
            </div>
            <div style="font-size: 11px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; letter-spacing: 0.5px;">VARIABLES</div>
            <div style="overflow-y: auto; flex: 1;">
              <table class="kv-table" style="width: 100%;">
                <thead>
                  <tr>
                    <th style="width: 30px;"></th>
                    <th>Variable Name</th>
                    <th>Value</th>
                    <th style="width: 60px;">Secret</th>
                    <th style="width: 30px;"></th>
                  </tr>
                </thead>
                <tbody id="env-vars-table-body"></tbody>
              </table>
            </div>
            <div style="display: flex; gap: 8px; margin-top: 12px;">
              <button class="btn btn-sm" id="btn-env-add-var">+ Add Variable</button>
              <button class="btn btn-sm btn-primary" id="btn-env-save" style="margin-left: auto;">Save Environment</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: cURL Code Generator -->
  <div class="modal-overlay" id="modal-curl">
    <div class="modal-card" style="width: 600px; max-width: 95vw;">
      <div class="modal-header">
        <h3 style="font-size: 14px; font-weight: 600;">cURL Code Generator</h3>
        <button class="btn-icon btn-sm" onclick="App.closeModal('modal-curl')">×</button>
      </div>
      <div class="modal-body">
        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">Copy this command and run it in your terminal to replicate this request:</p>
        <pre id="curl-output" class="response-viewer" style="min-height: 120px; font-size: 12px; white-space: pre-wrap; word-break: break-all;"></pre>
      </div>
      <div class="modal-footer">
        <button class="btn" onclick="App.closeModal('modal-curl')">Close</button>
        <button class="btn btn-primary" id="btn-copy-curl">Copy</button>
      </div>
    </div>
  </div>

  <!-- Modal: Create Collection -->
  <div class="modal-overlay" id="modal-collection">
    <div class="modal-card">
      <div class="modal-header">
        <h3 style="font-size: 14px; font-weight: 600;">Create Collection</h3>
        <button class="btn-icon btn-sm" onclick="App.closeModal('modal-collection')">×</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Collection Name</label>
          <input type="text" id="new-col-name" class="form-control" placeholder="e.g. Users Service APIs">
        </div>
        <div class="form-group">
          <label class="form-label">Description (optional)</label>
          <textarea id="new-col-desc" class="form-control" rows="3" placeholder="Description of collection"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn" onclick="App.closeModal('modal-collection')">Cancel</button>
        <button class="btn btn-primary" id="btn-save-collection-submit">Create</button>
      </div>
    </div>
  </div>

  <!-- Modal: Save Request -->
  <div class="modal-overlay" id="modal-save-request">
    <div class="modal-card">
      <div class="modal-header">
        <h3 style="font-size: 14px; font-weight: 600;">Save Request to Collection</h3>
        <button class="btn-icon btn-sm" onclick="App.closeModal('modal-save-request')">×</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Request Name</label>
          <input type="text" id="save-req-name" class="form-control" placeholder="e.g. Get User Profile">
        </div>
        <div class="form-group">
          <label class="form-label">Target Collection</label>
          <select id="save-req-collection" class="form-control"></select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn" onclick="App.closeModal('modal-save-request')">Cancel</button>
        <button class="btn btn-primary" id="btn-confirm-save-request">Save</button>
      </div>
    </div>
  </div>

  <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/app.js"></script>
</body>
</html>

