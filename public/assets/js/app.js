/**
 * DevDock — Core Application Client (Vanilla JavaScript)
 * Handles state management, UI events, API interactions, testing, and auth.
 */

const App = {
  state: {
    activeTab: 'params',
    responseTab: 'body',
    sidebarTab: 'collections',
    theme: localStorage.getItem('devdock_theme') || 'dark',
    environments: [],
    selectedEnvId: '',
    currentEditingEnvId: null,
    collections: [],
    activeRequestId: null,
    history: [],
    csrfToken: '',
    user: null,
    isLoading: false,
    rawResponse: null,
  },

  apiBase: document.querySelector('meta[name="api-base"]')?.getAttribute('content') || '/api',

  init() {
    this.applyTheme(this.state.theme);
    this.bindEvents();
    this.initDefaultRows();
    this.initAuth();
  },

  applyTheme(theme) {
    this.state.theme = theme;
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('devdock_theme', theme);
    const themeBtn = document.getElementById('btn-toggle-theme');
    if (themeBtn) {
      themeBtn.innerHTML = theme === 'dark' ? '☀️' : '🌙';
    }
  },

  async fetchApi(endpoint, options = {}) {
    options.headers = options.headers || {};
    if (this.state.csrfToken) {
      options.headers['X-CSRF-Token'] = this.state.csrfToken;
    }
    const res = await fetch(`${this.apiBase}${endpoint}`, options);
    if (res.status === 401 && !['/auth/login', '/auth/register', '/auth/me'].includes(endpoint)) {
      this.state.user = null;
      document.getElementById('user-menu-wrapper').style.display = 'none';
      document.getElementById('btn-curl-gen').style.display = 'none';
      this.openModal('modal-auth');
      throw new Error('Authentication required. Please sign in.');
    }
    return res;
  },

  bindEvents() {
    // Theme toggle
    document.getElementById('btn-toggle-theme').addEventListener('click', () => {
      this.applyTheme(this.state.theme === 'dark' ? 'light' : 'dark');
    });

    // Sidebar tab switching
    document.querySelectorAll('.sidebar-tab').forEach(tab => {
      tab.addEventListener('click', (e) => {
        document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
        e.currentTarget.classList.add('active');
        const target = e.currentTarget.dataset.tab;
        this.state.sidebarTab = target;
        document.getElementById('view-collections').style.display = target === 'collections' ? 'block' : 'none';
        document.getElementById('view-history').style.display = target === 'history' ? 'block' : 'none';
      });
    });

    // Request tabs switching
    document.querySelectorAll('#req-tabs .tab-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        document.querySelectorAll('#req-tabs .tab-btn').forEach(b => b.classList.remove('active'));
        e.currentTarget.classList.add('active');
        const tab = e.currentTarget.dataset.tab;
        this.state.activeTab = tab;
        document.querySelectorAll('.req-tab-content').forEach(c => c.style.display = 'none');
        document.getElementById(`tab-content-${tab}`).style.display = 'block';
      });
    });

    // Response tabs switching
    document.querySelectorAll('#res-tabs .tab-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        document.querySelectorAll('#res-tabs .tab-btn').forEach(b => b.classList.remove('active'));
        e.currentTarget.classList.add('active');
        const tab = e.currentTarget.dataset.tab;
        this.state.responseTab = tab;
        document.querySelectorAll('.res-tab-content').forEach(c => c.style.display = 'none');
        document.getElementById(`res-content-${tab}`).style.display = 'block';
      });
    });

    // Auth selector change
    document.getElementById('auth-type-select').addEventListener('change', (e) => {
      const type = e.target.value;
      document.getElementById('auth-bearer-fields').style.display = type === 'bearer' ? 'block' : 'none';
      document.getElementById('auth-basic-fields').style.display = type === 'basic' ? 'block' : 'none';
      document.getElementById('auth-apikey-fields').style.display = type === 'apikey' ? 'block' : 'none';
    });

    // Body type change
    document.querySelectorAll('input[name="body-type"]').forEach(radio => {
      radio.addEventListener('change', (e) => {
        const val = e.target.value;
        document.getElementById('body-editor-container').style.display = val === 'none' ? 'none' : 'block';
      });
    });

    // Send Button
    document.getElementById('btn-send').addEventListener('click', () => this.sendRequest());

    // Beautify JSON button
    document.getElementById('btn-beautify-json').addEventListener('click', () => {
      const editor = document.getElementById('req-body-editor');
      try {
        const parsed = JSON.parse(editor.value);
        editor.value = JSON.stringify(parsed, null, 2);
      } catch (err) {
        alert('Invalid JSON: ' + err.message);
      }
    });

    // Copy response button
    document.getElementById('btn-copy-response').addEventListener('click', () => {
      if (!this.state.rawResponse) return;
      navigator.clipboard.writeText(this.state.rawResponse).then(() => {
        const btn = document.getElementById('btn-copy-response');
        btn.innerText = 'Copied!';
        setTimeout(() => btn.innerText = 'Copy', 1500);
      });
    });

    // Add Row Buttons
    document.getElementById('btn-add-param').addEventListener('click', () => this.addKvRow('params-table-body'));
    document.getElementById('btn-add-header').addEventListener('click', () => this.addKvRow('headers-table-body'));
    document.getElementById('btn-add-test').addEventListener('click', () => this.addTestRow());

    // New Collection modal
    document.getElementById('btn-new-collection').addEventListener('click', () => this.openModal('modal-collection'));
    document.getElementById('btn-save-collection-submit').addEventListener('click', () => this.submitNewCollection());

    // Save Request Modal
    document.getElementById('btn-save-request').addEventListener('click', () => this.openSaveRequestModal());
    document.getElementById('btn-confirm-save-request').addEventListener('click', () => this.submitSaveRequest());

    // Environment Select
    document.getElementById('env-dropdown').addEventListener('change', (e) => {
      this.state.selectedEnvId = e.target.value;
    });

    // Clear History
    document.getElementById('btn-clear-history').addEventListener('click', () => this.clearHistory());

    // -------------------------------------------------------------------------
    // Auth Modal & User Dropdown Events
    // -------------------------------------------------------------------------
    document.querySelectorAll('.auth-tab-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        document.querySelectorAll('.auth-tab-btn').forEach(b => {
          b.classList.remove('active');
          b.style.borderBottom = '2px solid transparent';
          b.style.color = 'var(--text-muted)';
        });
        e.currentTarget.classList.add('active');
        e.currentTarget.style.borderBottom = '2px solid var(--accent-color)';
        e.currentTarget.style.color = 'var(--text-primary)';
        const tab = e.currentTarget.dataset.authTab;
        document.getElementById('auth-login-form').style.display = tab === 'login' ? 'block' : 'none';
        document.getElementById('auth-register-form').style.display = tab === 'register' ? 'block' : 'none';
      });
    });

    document.getElementById('btn-login').addEventListener('click', () => this.handleLogin());
    document.getElementById('btn-register').addEventListener('click', () => this.handleRegister());

    const userTrigger = document.getElementById('user-menu-trigger');
    if (userTrigger) {
      userTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const drop = document.getElementById('user-dropdown');
        drop.style.display = drop.style.display === 'block' ? 'none' : 'block';
      });
    }

    document.addEventListener('click', () => {
      const drop = document.getElementById('user-dropdown');
      if (drop) drop.style.display = 'none';
    });

    const logoutBtn = document.getElementById('btn-logout');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', () => this.handleLogout());
    }

    // -------------------------------------------------------------------------
    // Environment Manager Events
    // -------------------------------------------------------------------------
    const manageEnvsBtn = document.getElementById('btn-manage-envs');
    if (manageEnvsBtn) {
      manageEnvsBtn.addEventListener('click', () => this.openEnvManagerModal());
    }
    const envNewBtn = document.getElementById('btn-env-new');
    if (envNewBtn) {
      envNewBtn.addEventListener('click', () => this.createNewEnvironment());
    }
    const envAddVarBtn = document.getElementById('btn-env-add-var');
    if (envAddVarBtn) {
      envAddVarBtn.addEventListener('click', () => this.addEnvVarRow('', '', false));
    }
    const envSaveBtn = document.getElementById('btn-env-save');
    if (envSaveBtn) {
      envSaveBtn.addEventListener('click', () => this.saveCurrentEnvironment());
    }
    const envDeleteBtn = document.getElementById('btn-env-delete-current');
    if (envDeleteBtn) {
      envDeleteBtn.addEventListener('click', () => this.deleteCurrentEnvironment());
    }

    // -------------------------------------------------------------------------
    // cURL Code Generator Events
    // -------------------------------------------------------------------------
    const curlGenBtn = document.getElementById('btn-curl-gen');
    if (curlGenBtn) {
      curlGenBtn.addEventListener('click', () => this.openCurlModal());
    }
    const copyCurlBtn = document.getElementById('btn-copy-curl');
    if (copyCurlBtn) {
      copyCurlBtn.addEventListener('click', () => {
        const text = document.getElementById('curl-output').innerText;
        navigator.clipboard.writeText(text).then(() => {
          copyCurlBtn.innerText = 'Copied!';
          setTimeout(() => copyCurlBtn.innerText = 'Copy', 1500);
        });
      });
    }

    // -------------------------------------------------------------------------
    // Collection Export & Import Events
    // -------------------------------------------------------------------------
    const exportBtn = document.getElementById('btn-export-collection');
    if (exportBtn) {
      exportBtn.addEventListener('click', () => this.exportCurrentCollection());
    }
    const importInput = document.getElementById('import-collection-file');
    if (importInput) {
      importInput.addEventListener('change', (e) => this.handleCollectionImport(e));
    }
  },

  // ---------------------------------------------------------------------------
  // Authentication
  // ---------------------------------------------------------------------------
  async initAuth() {
    try {
      const res = await fetch(`${this.apiBase}/auth/me`);
      if (res.ok) {
        const json = await res.json();
        if (json.success && json.data.user) {
          this.setAuthenticatedUser(json.data.user, json.data.csrf_token);
          await this.loadInitialData();
          return;
        }
      }
    } catch (e) {
      console.log('Session check completed:', e);
    }
    this.openModal('modal-auth');
  },

  setAuthenticatedUser(user, csrfToken) {
    this.state.user = user;
    if (csrfToken) {
      this.state.csrfToken = csrfToken;
    }
    const nameElem = document.getElementById('user-menu-name');
    const emailElem = document.getElementById('user-dropdown-email');
    if (nameElem) nameElem.innerText = user.name || user.email;
    if (emailElem) emailElem.innerText = user.email;
    document.getElementById('user-menu-wrapper').style.display = 'block';
    document.getElementById('btn-curl-gen').style.display = 'inline-block';
    this.closeModal('modal-auth');
  },

  async handleLogin() {
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    const errBox = document.getElementById('auth-login-error');
    errBox.style.display = 'none';

    if (!email || !password) {
      errBox.innerText = 'Email and password are required.';
      errBox.style.display = 'block';
      return;
    }

    try {
      const res = await fetch(`${this.apiBase}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });
      const json = await res.json();
      if (!json.success) {
        throw new Error(json.message || 'Login failed.');
      }
      this.setAuthenticatedUser(json.data.user, json.data.csrf_token);
      await this.loadInitialData();
    } catch (err) {
      errBox.innerText = err.message;
      errBox.style.display = 'block';
    }
  },

  async handleRegister() {
    const name = document.getElementById('reg-name').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;
    const errBox = document.getElementById('auth-register-error');
    errBox.style.display = 'none';

    if (!name || !email || !password) {
      errBox.innerText = 'All fields are required.';
      errBox.style.display = 'block';
      return;
    }

    try {
      const res = await fetch(`${this.apiBase}/auth/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password }),
      });
      const json = await res.json();
      if (!json.success) {
        throw new Error(json.message || 'Registration failed.');
      }
      this.setAuthenticatedUser(json.data.user, json.data.csrf_token);
      await this.loadInitialData();
    } catch (err) {
      errBox.innerText = err.message;
      errBox.style.display = 'block';
    }
  },

  async handleLogout() {
    try {
      await this.fetchApi('/auth/logout', { method: 'POST' });
    } catch (e) {
      // ignore
    }
    this.state.user = null;
    this.state.csrfToken = '';
    window.location.reload();
  },

  async loadInitialData() {
    await this.fetchEnvironments();
    await this.fetchCollections();
    await this.fetchHistory();
  },

  // ---------------------------------------------------------------------------
  // Key-Value Table Helpers
  // ---------------------------------------------------------------------------
  initDefaultRows() {
    this.addKvRow('params-table-body');
    this.addKvRow('headers-table-body', 'Content-Type', 'application/json');
    this.addTestRow('status_code_is', '200');
  },

  addKvRow(tableBodyId, defaultKey = '', defaultVal = '') {
    const tbody = document.getElementById(tableBodyId);
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="width: 30px; text-align: center;">
        <input type="checkbox" class="row-enable" checked>
      </td>
      <td><input type="text" class="kv-input row-key" placeholder="Key" value="${defaultKey}"></td>
      <td><input type="text" class="kv-input row-value" placeholder="Value" value="${defaultVal}"></td>
      <td style="width: 30px; text-align: center;">
        <button type="button" class="btn-icon btn-sm" onclick="this.closest('tr').remove()">×</button>
      </td>
    `;
    tbody.appendChild(tr);
  },

  addTestRow(defaultType = 'status_code_is', defaultExpected = '') {
    const tbody = document.getElementById('tests-table-body');
    if (!tbody) return;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="width: 30px; text-align: center;">
        <input type="checkbox" class="test-enable" checked>
      </td>
      <td>
        <select class="kv-input test-type" onchange="App.handleTestTypeChange(this)">
          <option value="status_code_is" ${defaultType === 'status_code_is' ? 'selected' : ''}>Status code is</option>
          <option value="response_time_less_than" ${defaultType === 'response_time_less_than' ? 'selected' : ''}>Response time < (ms)</option>
          <option value="json_property_exists" ${defaultType === 'json_property_exists' ? 'selected' : ''}>JSON property exists</option>
          <option value="json_property_equals" ${defaultType === 'json_property_equals' ? 'selected' : ''}>JSON property equals</option>
          <option value="body_contains" ${defaultType === 'body_contains' ? 'selected' : ''}>Body contains string</option>
        </select>
      </td>
      <td><input type="text" class="kv-input test-prop" placeholder="e.g. data.id (optional)" style="display:${defaultType.includes('property') ? 'block' : 'none'};"></td>
      <td><input type="text" class="kv-input test-expected" placeholder="Expected Value" value="${defaultExpected}"></td>
      <td style="width: 30px; text-align: center;">
        <button type="button" class="btn-icon btn-sm" onclick="this.closest('tr').remove()">×</button>
      </td>
    `;
    tbody.appendChild(tr);
  },

  handleTestTypeChange(selectElem) {
    const propInput = selectElem.closest('tr').querySelector('.test-prop');
    propInput.style.display = selectElem.value.includes('property') ? 'block' : 'none';
  },

  getKvData(tableBodyId) {
    const rows = document.querySelectorAll(`#${tableBodyId} tr`);
    const data = [];
    rows.forEach(r => {
      const enabled = r.querySelector('.row-enable').checked;
      const key = r.querySelector('.row-key').value.trim();
      const value = r.querySelector('.row-value').value.trim();
      if (key) {
        data.push({ key, value, is_enabled: enabled });
      }
    });
    return data;
  },

  getTestsData() {
    const rows = document.querySelectorAll('#tests-table-body tr');
    const tests = [];
    rows.forEach(r => {
      const enabled = r.querySelector('.test-enable').checked;
      const test_type = r.querySelector('.test-type').value;
      const property_path = r.querySelector('.test-prop').value.trim();
      const expected_value = r.querySelector('.test-expected').value.trim();
      if (test_type && expected_value) {
        tests.push({ test_type, property_path, expected_value, is_enabled: enabled });
      }
    });
    return tests;
  },

  // ---------------------------------------------------------------------------
  // Send Request & Response Handling
  // ---------------------------------------------------------------------------
  async sendRequest() {
    const method = document.getElementById('req-method').value;
    const url = document.getElementById('req-url').value.trim();

    if (!url) {
      alert('Please enter a valid request URL.');
      return;
    }

    const headers = this.getKvData('headers-table-body');
    const params = this.getKvData('params-table-body');
    const tests = this.getTestsData();

    // Body
    const bodyType = document.querySelector('input[name="body-type"]:checked').value;
    const body = bodyType === 'none' ? null : document.getElementById('req-body-editor').value;

    // Auth
    const authType = document.getElementById('auth-type-select').value;
    const auth = { type: authType };
    if (authType === 'bearer') {
      auth.token = document.getElementById('auth-bearer-token').value.trim();
    } else if (authType === 'basic') {
      auth.username = document.getElementById('auth-basic-user').value;
      auth.password = document.getElementById('auth-basic-pass').value;
    } else if (authType === 'apikey') {
      auth.key = document.getElementById('auth-apikey-key').value.trim();
      auth.value = document.getElementById('auth-apikey-val').value.trim();
      auth.addTo = document.getElementById('auth-apikey-addto').value;
    }

    const payload = {
      method,
      url,
      headers,
      params,
      auth,
      body,
      tests,
      environment_id: this.state.selectedEnvId || null,
      request_id: this.state.activeRequestId,
    };

    // UI Loading state
    const sendBtn = document.getElementById('btn-send');
    sendBtn.disabled = true;
    sendBtn.innerText = 'Sending...';

    try {
      const res = await this.fetchApi('/request/send', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      const json = await res.json();
      if (!json.success) {
        throw new Error(json.message || 'Execution error');
      }

      this.renderResponse(json.data.response, json.data.test_results);
      this.fetchHistory();
    } catch (err) {
      alert('Error executing request: ' + err.message);
    } finally {
      sendBtn.disabled = false;
      sendBtn.innerText = 'Send';
    }
  },

  renderResponse(res, testResults) {
    const statusElem = document.getElementById('res-status');
    statusElem.innerText = `${res.status_code} ${res.status_text}`;
    statusElem.className = 'stat-value';
    if (res.status_code >= 200 && res.status_code < 300) {
      statusElem.classList.add('stat-success');
    } else {
      statusElem.classList.add('stat-danger');
    }

    document.getElementById('res-time').innerText = `${res.response_time_ms} ms`;
    document.getElementById('res-size').innerText = `${res.response_size_bytes} B`;

    // Response Body
    this.state.rawResponse = res.response_body;
    const bodyViewer = document.getElementById('response-body-viewer');
    try {
      const parsed = JSON.parse(res.response_body);
      bodyViewer.innerText = JSON.stringify(parsed, null, 2);
    } catch (e) {
      bodyViewer.innerText = res.response_body || '(Empty Response Body)';
    }

    // Response Headers
    const headersTable = document.getElementById('response-headers-table');
    headersTable.innerHTML = '';
    for (const [key, val] of Object.entries(res.response_headers || {})) {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="font-weight: 600; color: var(--text-secondary); width: 35%;">${key}</td>
        <td style="font-family: var(--font-mono);">${Array.isArray(val) ? val.join('; ') : val}</td>
      `;
      headersTable.appendChild(tr);
    }

    // Test Results
    const testsContainer = document.getElementById('test-results-list');
    testsContainer.innerHTML = '';
    const badgeCount = document.getElementById('test-results-count');

    if (!testResults || testResults.length === 0) {
      testsContainer.innerHTML = '<div style="color:var(--text-muted); padding:20px; text-align:center;">No assertions configured for this request.</div>';
      badgeCount.innerText = '0';
    } else {
      const passedCount = testResults.filter(t => t.passed).length;
      badgeCount.innerText = `${passedCount}/${testResults.length}`;

      testResults.forEach(t => {
        const card = document.createElement('div');
        card.className = `test-card ${t.passed ? 'passed' : 'failed'}`;
        card.innerHTML = `
          <div>
            <div class="test-title">
              <span>${t.passed ? '✓' : '✗'}</span>
              <span>${t.test_name}</span>
            </div>
            ${t.error_message ? `<div style="font-size:11px; color:var(--status-5xx); margin-top:4px;">${t.error_message}</div>` : ''}
          </div>
          <div style="font-size:11px; font-weight:bold; color:${t.passed ? 'var(--status-2xx)' : 'var(--status-5xx)'};">
            ${t.passed ? 'PASS' : 'FAIL'}
          </div>
        `;
        testsContainer.appendChild(card);
      });
    }
  },

  // ---------------------------------------------------------------------------
  // Collections & Saved Requests
  // ---------------------------------------------------------------------------
  async fetchCollections() {
    try {
      const res = await this.fetchApi('/collections');
      const json = await res.json();
      if (json.success) {
        this.state.collections = json.data;
        this.renderCollections();
      }
    } catch (e) {
      console.error(e);
    }
  },

  renderCollections() {
    const list = document.getElementById('collections-list');
    list.innerHTML = '';

    if (this.state.collections.length === 0) {
      list.innerHTML = '<div style="color:var(--text-muted); text-align:center; padding:20px;">No collections yet. Click "+ New" to start.</div>';
      return;
    }

    this.state.collections.forEach(col => {
      const card = document.createElement('div');
      card.className = 'collection-card';
      card.innerHTML = `
        <div class="collection-header" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'flex' : 'none'">
          <div class="collection-title">
            <span>📁</span>
            <span>${col.name}</span>
            <span style="font-size:10px; color:var(--text-muted);">(${col.requests ? col.requests.length : 0})</span>
          </div>
          <button class="btn-icon btn-sm" title="Delete Collection" onclick="event.stopPropagation(); App.deleteCollection(${col.id})">×</button>
        </div>
        <div class="collection-requests">
          ${(col.requests || []).map(r => `
            <div class="request-item ${this.state.activeRequestId === r.id ? 'active' : ''}" onclick="App.loadSavedRequest(${r.id})">
              <span class="req-badge badge-${r.method}">${r.method}</span>
              <span class="req-name">${r.name}</span>
            </div>
          `).join('')}
        </div>
      `;
      list.appendChild(card);
    });
  },

  async loadSavedRequest(id) {
    try {
      const res = await this.fetchApi(`/requests/${id}`);
      const json = await res.json();
      if (!json.success) return;

      const req = json.data;
      this.state.activeRequestId = req.id;

      document.getElementById('req-method').value = req.method;
      document.getElementById('req-url').value = req.url;

      // Populate headers
      const headersTbody = document.getElementById('headers-table-body');
      headersTbody.innerHTML = '';
      if (req.headers && req.headers.length > 0) {
        req.headers.forEach(h => this.addKvRow('headers-table-body', h.key, h.value));
      } else {
        this.addKvRow('headers-table-body');
      }

      // Populate params
      const paramsTbody = document.getElementById('params-table-body');
      paramsTbody.innerHTML = '';
      if (req.params && req.params.length > 0) {
        req.params.forEach(p => this.addKvRow('params-table-body', p.key, p.value));
      } else {
        this.addKvRow('params-table-body');
      }

      // Populate tests
      const testsTbody = document.getElementById('tests-table-body');
      testsTbody.innerHTML = '';
      if (req.tests && req.tests.length > 0) {
        req.tests.forEach(t => this.addTestRow(t.test_type, t.expected_value));
      } else {
        this.addTestRow();
      }

      // Populate Body
      const bodyRadio = document.querySelector(`input[name="body-type"][value="${req.body_type}"]`);
      if (bodyRadio) bodyRadio.checked = true;
      document.getElementById('body-editor-container').style.display = req.body_type === 'none' ? 'none' : 'block';
      document.getElementById('req-body-editor').value = req.body_content || '';

      // Populate Auth
      const authTypeSelect = document.getElementById('auth-type-select');
      authTypeSelect.value = req.auth_type || 'none';
      authTypeSelect.dispatchEvent(new Event('change'));

      const authConf = req.auth_config || {};
      if (req.auth_type === 'bearer') {
        document.getElementById('auth-bearer-token').value = authConf.token || '';
      } else if (req.auth_type === 'basic') {
        document.getElementById('auth-basic-user').value = authConf.username || '';
        document.getElementById('auth-basic-pass').value = authConf.password || '';
      } else if (req.auth_type === 'apikey') {
        document.getElementById('auth-apikey-key').value = authConf.key || '';
        document.getElementById('auth-apikey-val').value = authConf.value || '';
        document.getElementById('auth-apikey-addto').value = authConf.addTo || 'header';
      }

      this.renderCollections();
    } catch (err) {
      console.error(err);
    }
  },

  async submitNewCollection() {
    const name = document.getElementById('new-col-name').value.trim();
    const desc = document.getElementById('new-col-desc').value.trim();
    if (!name) return;

    try {
      const res = await this.fetchApi('/collections', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, description: desc }),
      });
      const json = await res.json();
      if (json.success) {
        this.closeModal('modal-collection');
        document.getElementById('new-col-name').value = '';
        document.getElementById('new-col-desc').value = '';
        this.fetchCollections();
      }
    } catch (err) {
      alert('Error creating collection: ' + err.message);
    }
  },

  async deleteCollection(id) {
    if (!confirm('Are you sure you want to delete this collection and its saved requests?')) return;
    try {
      await this.fetchApi(`/collections/${id}`, { method: 'DELETE' });
      this.fetchCollections();
    } catch (err) {
      alert(err.message);
    }
  },

  openSaveRequestModal() {
    const colSelect = document.getElementById('save-req-collection');
    if (this.state.collections.length === 0) {
      alert('Please create a collection first before saving requests.');
      this.openModal('modal-collection');
      return;
    }
    colSelect.innerHTML = this.state.collections.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    this.openModal('modal-save-request');
  },

  async submitSaveRequest() {
    const name = document.getElementById('save-req-name').value.trim();
    const collection_id = document.getElementById('save-req-collection').value;
    if (!name) {
      alert('Please enter a request name.');
      return;
    }

    const authType = document.getElementById('auth-type-select').value;
    const authConfig = {};
    if (authType === 'bearer') {
      authConfig.token = document.getElementById('auth-bearer-token').value.trim();
    } else if (authType === 'basic') {
      authConfig.username = document.getElementById('auth-basic-user').value;
      authConfig.password = document.getElementById('auth-basic-pass').value;
    } else if (authType === 'apikey') {
      authConfig.key = document.getElementById('auth-apikey-key').value.trim();
      authConfig.value = document.getElementById('auth-apikey-val').value.trim();
      authConfig.addTo = document.getElementById('auth-apikey-addto').value;
    }

    const payload = {
      id: this.state.activeRequestId,
      collection_id,
      name,
      method: document.getElementById('req-method').value,
      url: document.getElementById('req-url').value.trim(),
      headers: this.getKvData('headers-table-body'),
      params: this.getKvData('params-table-body'),
      tests: this.getTestsData(),
      body_type: document.querySelector('input[name="body-type"]:checked').value,
      body_content: document.getElementById('req-body-editor').value,
      auth_type: authType,
      auth_config: authConfig,
    };

    try {
      const res = await this.fetchApi('/requests', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const json = await res.json();
      if (json.success) {
        this.state.activeRequestId = json.data.id;
        this.closeModal('modal-save-request');
        this.fetchCollections();
        alert('Request saved to collection!');
      }
    } catch (err) {
      alert(err.message);
    }
  },

  // ---------------------------------------------------------------------------
  // Environments & Variables
  // ---------------------------------------------------------------------------
  async fetchEnvironments() {
    try {
      const res = await this.fetchApi('/environments');
      const json = await res.json();
      if (json.success) {
        this.state.environments = json.data;
        const select = document.getElementById('env-dropdown');
        select.innerHTML = '<option value="">No Environment</option>' +
          json.data.map(e => `<option value="${e.id}">${e.name}</option>`).join('');
        if (json.data.length > 0 && !this.state.selectedEnvId) {
          this.state.selectedEnvId = json.data[0].id;
          select.value = json.data[0].id;
        }
      }
    } catch (e) {
      console.error(e);
    }
  },

  openEnvManagerModal() {
    this.openModal('modal-env-manager');
    this.renderEnvManagerList();
    if (this.state.environments.length > 0) {
      this.selectEnvForEditing(this.state.environments[0].id);
    } else {
      document.getElementById('env-editor-placeholder').style.display = 'flex';
      document.getElementById('env-editor-panel').style.display = 'none';
    }
  },

  renderEnvManagerList() {
    const list = document.getElementById('env-manager-list');
    list.innerHTML = '';
    this.state.environments.forEach(env => {
      const item = document.createElement('div');
      item.className = 'request-item';
      if (this.state.currentEditingEnvId === env.id) item.classList.add('active');
      item.style.padding = '8px';
      item.style.cursor = 'pointer';
      item.style.marginBottom = '4px';
      item.innerHTML = `
        <span style="font-weight: 500;">🌐 ${env.name}</span>
        <span style="font-size: 10px; color: var(--text-muted); margin-left: auto;">${env.variables ? env.variables.length : 0} vars</span>
      `;
      item.onclick = () => this.selectEnvForEditing(env.id);
      list.appendChild(item);
    });
  },

  async selectEnvForEditing(envId) {
    this.state.currentEditingEnvId = envId;
    this.renderEnvManagerList();

    try {
      const res = await this.fetchApi(`/environments/${envId}`);
      const json = await res.json();
      if (!json.success) return;

      const env = json.data;
      document.getElementById('env-editor-placeholder').style.display = 'none';
      document.getElementById('env-editor-panel').style.display = 'flex';
      document.getElementById('env-editor-name').value = env.name;

      const tbody = document.getElementById('env-vars-table-body');
      tbody.innerHTML = '';
      if (env.variables && env.variables.length > 0) {
        env.variables.forEach(v => {
          this.addEnvVarRow(v.var_key, v.var_value, (v.is_secret == 1 || v.is_secret === true));
        });
      } else {
        this.addEnvVarRow('', '', false);
      }
    } catch (e) {
      console.error(e);
    }
  },

  addEnvVarRow(key = '', val = '', isSecret = false) {
    const tbody = document.getElementById('env-vars-table-body');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="width: 30px; text-align: center;">
        <input type="checkbox" class="var-enable" checked>
      </td>
      <td><input type="text" class="kv-input var-key" placeholder="variable_name" value="${key}"></td>
      <td><input type="${isSecret ? 'password' : 'text'}" class="kv-input var-val" placeholder="value" value="${val}"></td>
      <td style="width: 60px; text-align: center;">
        <input type="checkbox" class="var-secret" ${isSecret ? 'checked' : ''} onchange="this.closest('tr').querySelector('.var-val').type = this.checked ? 'password' : 'text'">
      </td>
      <td style="width: 30px; text-align: center;">
        <button type="button" class="btn-icon btn-sm" onclick="this.closest('tr').remove()">×</button>
      </td>
    `;
    tbody.appendChild(tr);
  },

  async createNewEnvironment() {
    const name = prompt('Enter new environment name (e.g. Staging, Production):', 'Staging');
    if (!name || !name.trim()) return;

    try {
      const res = await this.fetchApi('/environments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name.trim(), variables: [] }),
      });
      const json = await res.json();
      if (json.success) {
        await this.fetchEnvironments();
        this.selectEnvForEditing(json.data.id);
      }
    } catch (e) {
      alert(e.message);
    }
  },

  async saveCurrentEnvironment() {
    if (!this.state.currentEditingEnvId) return;

    const name = document.getElementById('env-editor-name').value.trim();
    if (!name) {
      alert('Environment name cannot be empty.');
      return;
    }

    const rows = document.querySelectorAll('#env-vars-table-body tr');
    const variables = [];
    rows.forEach(r => {
      const key = r.querySelector('.var-key').value.trim();
      const val = r.querySelector('.var-val').value;
      const enabled = r.querySelector('.var-enable').checked;
      const secret = r.querySelector('.var-secret').checked;
      if (key) {
        variables.push({
          var_key: key,
          var_value: val,
          is_enabled: enabled,
          is_secret: secret,
        });
      }
    });

    try {
      const res = await this.fetchApi(`/environments/${this.state.currentEditingEnvId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, variables }),
      });
      const json = await res.json();
      if (json.success) {
        alert('Environment saved!');
        await this.fetchEnvironments();
        this.renderEnvManagerList();
      }
    } catch (e) {
      alert('Error saving environment: ' + e.message);
    }
  },

  async deleteCurrentEnvironment() {
    if (!this.state.currentEditingEnvId) return;
    if (!confirm('Are you sure you want to delete this environment?')) return;

    try {
      await this.fetchApi(`/environments/${this.state.currentEditingEnvId}`, { method: 'DELETE' });
      this.state.currentEditingEnvId = null;
      await this.fetchEnvironments();
      this.openEnvManagerModal();
    } catch (e) {
      alert(e.message);
    }
  },

  // ---------------------------------------------------------------------------
  // cURL Code Generator
  // ---------------------------------------------------------------------------
  async openCurlModal() {
    const curlOutput = document.getElementById('curl-output');
    curlOutput.innerText = 'Generating cURL command...';
    this.openModal('modal-curl');

    if (this.state.activeRequestId) {
      try {
        const res = await this.fetchApi(`/requests/${this.state.activeRequestId}/curl`);
        const json = await res.json();
        if (json.success && json.data.curl) {
          curlOutput.innerText = json.data.curl;
          return;
        }
      } catch (e) {
        console.warn('Backend curl generation fallback to client-side:', e);
      }
    }

    // Client-side fallback generator from active form
    const method = document.getElementById('req-method').value;
    const url = document.getElementById('req-url').value.trim();
    const headers = this.getKvData('headers-table-body');
    const bodyType = document.querySelector('input[name="body-type"]:checked').value;
    const body = bodyType === 'none' ? null : document.getElementById('req-body-editor').value;

    const parts = [`curl -X ${method}`];

    // Auth
    const authType = document.getElementById('auth-type-select').value;
    if (authType === 'bearer') {
      const token = document.getElementById('auth-bearer-token').value.trim();
      if (token) parts.push(`-H "Authorization: Bearer ${token}"`);
    } else if (authType === 'basic') {
      const u = document.getElementById('auth-basic-user').value;
      const p = document.getElementById('auth-basic-pass').value;
      if (u) parts.push(`-u "${u}:${p}"`);
    } else if (authType === 'apikey') {
      const k = document.getElementById('auth-apikey-key').value.trim();
      const v = document.getElementById('auth-apikey-val').value.trim();
      const addTo = document.getElementById('auth-apikey-addto').value;
      if (k && addTo === 'header') parts.push(`-H "${k}: ${v}"`);
    }

    headers.forEach(h => {
      if (h.is_enabled && h.key) {
        parts.push(`-H "${h.key}: ${h.value}"`);
      }
    });

    if (body && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
      parts.push(`-H "Content-Type: application/json"`);
      parts.push(`-d '${body.replace(/'/g, "'\\''")}'`);
    }

    parts.push(`"${url}"`);
    curlOutput.innerText = parts.join(' \\\n  ');
  },

  // ---------------------------------------------------------------------------
  // Collection Export & Import
  // ---------------------------------------------------------------------------
  exportCurrentCollection() {
    if (this.state.collections.length === 0) {
      alert('No collections available to export.');
      return;
    }

    let colId = this.state.collections[0].id;
    if (this.state.collections.length > 1) {
      const names = this.state.collections.map((c, i) => `${i + 1}. ${c.name}`).join('\n');
      const choice = prompt(`Select collection to export:\n${names}\n\nEnter number:`, '1');
      if (!choice) return;
      const index = parseInt(choice, 10) - 1;
      if (this.state.collections[index]) {
        colId = this.state.collections[index].id;
      }
    }

    window.open(`${this.apiBase}/collections/${colId}/export`, '_blank');
  },

  async handleCollectionImport(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = async (event) => {
      try {
        const json = JSON.parse(event.target.result);
        const res = await this.fetchApi('/collections/import', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(json),
        });
        const resData = await res.json();
        if (!resData.success) {
          throw new Error(resData.message || 'Import failed.');
        }
        alert(`Collection imported successfully! (${resData.data.imported_requests} requests imported)`);
        await this.fetchCollections();
      } catch (err) {
        alert('Error importing collection: ' + err.message);
      } finally {
        e.target.value = '';
      }
    };
    reader.readAsText(file);
  },

  // ---------------------------------------------------------------------------
  // History
  // ---------------------------------------------------------------------------
  async fetchHistory() {
    try {
      const res = await this.fetchApi('/history');
      const json = await res.json();
      if (json.success) {
        this.state.history = json.data;
        this.renderHistory();
      }
    } catch (e) {
      console.error(e);
    }
  },

  renderHistory() {
    const list = document.getElementById('history-list');
    list.innerHTML = '';

    if (this.state.history.length === 0) {
      list.innerHTML = '<div style="color:var(--text-muted); text-align:center; padding:20px;">No requests sent yet.</div>';
      return;
    }

    this.state.history.forEach(item => {
      const div = document.createElement('div');
      div.className = 'history-item';
      div.onclick = () => {
        document.getElementById('req-method').value = item.method;
        document.getElementById('req-url').value = item.url;
      };
      div.innerHTML = `
        <div class="history-top">
          <span class="req-badge badge-${item.method}">${item.method}</span>
          <span class="status-badge ${item.status_code >= 200 && item.status_code < 400 ? 'status-2xx' : 'status-4xx'}">
            ${item.status_code || 'ERR'}
          </span>
        </div>
        <div class="history-url">${item.url}</div>
        <div class="history-meta" style="margin-top:4px;">
          <span>⏱ ${item.response_time_ms ? Math.round(item.response_time_ms) + 'ms' : '-'}</span>
          <span>•</span>
          <span>${item.executed_at ? item.executed_at.substring(11, 19) : ''}</span>
        </div>
      `;
      list.appendChild(div);
    });
  },

  async clearHistory() {
    if (!confirm('Clear all request history?')) return;
    try {
      await this.fetchApi('/history', { method: 'DELETE' });
      this.fetchHistory();
    } catch (e) {
      alert(e.message);
    }
  },

  // ---------------------------------------------------------------------------
  // Modal Helpers
  // ---------------------------------------------------------------------------
  openModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('active');
  },

  closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('active');
  },
};

document.addEventListener('DOMContentLoaded', () => App.init());
