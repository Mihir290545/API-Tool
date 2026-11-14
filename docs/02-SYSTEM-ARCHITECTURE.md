# DevDock — System Architecture Design

---

## 1. High-Level Architectural Model

DevDock adopts a **Layered MVC/Service Architecture** built upon a Single Front Controller pattern:

```
[ Developer Browser / Client (Vanilla JS SPA) ]
                       │
             HTTPS/HTTP Requests
                       │
                       ▼
            [ public/index.php ]
                       │
       ┌───────────────┴───────────────┐
       ▼                               ▼
[ App\Core\Router ]           [ App\Core\Session ]
       │                               │
       ▼                               ▼
[ Controllers Layer ]        [ Security & Auth Guard ]
  ├── ApiController
  └── AuthController
       │
       ▼
[ Services Layer (Business Logic) ]
  ├── HttpClientService (cURL Engine)
  ├── CollectionService
  ├── EnvironmentService
  ├── HistoryService
  ├── TestRunnerService
  └── AuthService
       │
       ├────────────────────────────────┐
       ▼                                ▼
[ Database Layer (PDO) ]      [ External HTTP Services ]
  └── MySQL Database            └── Target Public APIs
```

---

## 2. Core Subsystems

### 2.1 The Front Controller & Routing Engine
All requests are routed through `public/index.php` using Apache `.htaccess` URL rewriting.
The `App\Core\Router` parses HTTP verbs and maps URI patterns with dynamic parameter extraction (e.g. `/api/collections/{id}`) directly to controller actions.

### 2.2 Native PSR-4 Autoloader
DevDock uses a native PHP autoloader (`App\Core\Autoloader`) adhering to PSR-4 specifications. This eliminates external package managers like Composer, keeping the application lightweight, fully portable, and zero-cost.

### 2.3 Outbound cURL Engine & SSRF Guard
Direct client-to-API requests often face browser CORS restrictions. DevDock routes outbound API requests through a secure server-side PHP cURL engine (`App\Services\HttpClientService`):
1. The URL is inspected by `App\Helpers\Security::isSafeOutboundUrl()`.
2. Hostnames are resolved to IP addresses and filtered against private/loopback CIDR blocks.
3. cURL connects with strict connection timeouts (10s) and execution timeouts (30s).
4. Response headers, payload, HTTP status code, download size, and latency in milliseconds are captured and normalized.

### 2.4 Multi-Environment Variable Engine
The `EnvironmentService` provides regex-based variable replacement:
* Syntax: `{{variable_name}}`
* Variables are evaluated at execution time across request URLs, header values, query parameters, authorization tokens, and request bodies.
* Secret variables are flagged with `is_secret = 1` for UI masking.
