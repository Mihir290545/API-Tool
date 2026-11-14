# DevDock — Requirements Analysis & Specification

**Project:** DevDock (API Development & Testing Platform)  
**Author:** Mihir Raval  
**Track:** Software Development / PHP Developer  
**Institution / Program:** Yuva Intern  

---

## 1. Executive Summary & Problem Statement

Modern web developers interact with multiple external and internal RESTful services daily. Testing endpoints with different HTTP verbs, authentication methods, request headers, query parameters, and JSON payloads is frequently manual, error-prone, and dispersed across arbitrary terminal commands or ad-hoc browser tabs.

**DevDock** provides a developer-centric, structured web workspace built on native **PHP 8.x**, **MySQL**, and modern **vanilla JavaScript**. It empowers software engineers to compose requests, inspect latency and response headers, organize endpoints into collections, leverage environment variables, validate payloads through automated assertions, and maintain a complete audit history—with zero paid dependencies.

---

## 2. Functional Requirements (FR)

| ID | Requirement Specification | Verification Method |
| :--- | :--- | :--- |
| **FR-01** | User authentication: secure registration, login with `password_hash()`, and session termination. | Unit & Integration Test |
| **FR-02** | Request composer supporting `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`. | HTTP Dispatcher Validation |
| **FR-03** | Dynamic Query Parameter and Request Header key-value builders. | Form Serialization Test |
| **FR-04** | JSON request payload editor with client-side syntax verification and formatting. | JSON Parser Verification |
| **FR-05** | Authentication injector supporting No Auth, Bearer Token, Basic Auth, and API Key (header/query). | Outbound Header Inspection |
| **FR-06** | Outbound execution through native PHP cURL engine capturing timing (ms), size (B), headers, and body. | Benchmark & Metric Capture |
| **FR-07** | Response visualizer featuring JSON pretty-printing, raw view, and one-click clipboard copy. | Frontend UI Inspection |
| **FR-08** | Relational Collection management: create, rename, delete collections, and save/load requests. | Database CRUD Verification |
| **FR-09** | Execution history auditing: automatic persistence of executed requests, metrics, and one-click replay. | History Service Audit |
| **FR-10** | Multi-profile environments with double-curly variable substitution (`{{base_url}}`). | Regex Replacer Test |
| **FR-11** | Automated assertion engine validating status codes, response time caps, JSON properties, and body text. | Assertion Test Runner |

---

## 3. Non-Functional Requirements (NFR)

* **NFR-01: Performance**: Outbound proxy overhead added by PHP cURL execution layer must not exceed 15ms over direct network latency.
* **NFR-02: Security (SSRF Protection)**: Outbound requests must undergo strict DNS and IP range validation. Any request targeting private RFC1918 subnets (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`), loopback (`127.0.0.0/8`), or link-local metadata (`169.254.169.254`) must be rejected.
* **NFR-03: Security (SQL Injection)**: All database queries must strictly utilize PDO prepared statements with parameter binding.
* **NFR-04: Security (XSS Defense)**: User input rendered into DOM elements must undergo strict UTF-8 escaping.
* **NFR-05: Maintainability**: Zero Composer or framework dependency; structured PSR-4 autoloading with clean separation of concerns (Core, Controllers, Services, Helpers).
* **NFR-06: Zero-Cost Policy**: 100% free and open-source technologies (PHP 8, MySQL Community, Apache XAMPP, Vanilla JS).
