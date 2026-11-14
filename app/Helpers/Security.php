<?php

namespace App\Helpers;

/**
 * Security & Sanitization Helper
 */
class Security
{
    /**
     * Escape output against Cross-Site Scripting (XSS)
     */
    public static function escape(?string $data): string
    {
        return htmlspecialchars($data ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sanitize string input
     */
    public static function sanitizeString(?string $data): string
    {
        return trim(strip_tags($data ?? ''));
    }

    /**
     * Sanitize and validate email
     */
    public static function validateEmail(string $email): ?string
    {
        $sanitized = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return filter_var($sanitized, FILTER_VALIDATE_EMAIL) ? strtolower($sanitized) : null;
    }

    /**
     * Verify whether an outbound URL is safe (SSRF Defense)
     * Rejects local loopback, cloud metadata endpoints, and internal private IP blocks.
     */
    public static function isSafeOutboundUrl(string $url): array
    {
        $parsed = parse_url($url);

        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return ['safe' => false, 'error' => 'Malformed URL: missing scheme or host.'];
        }

        $scheme = strtolower($parsed['scheme']);
        if (!in_array($scheme, ['http', 'https'])) {
            return ['safe' => false, 'error' => 'Unsupported protocol. Only HTTP and HTTPS are permitted.'];
        }

        $host = strtolower($parsed['host']);

        // Check against known dangerous hosts & cloud metadata services
        $blockedHosts = [
            'localhost',
            '127.0.0.1',
            '::1',
            '169.254.169.254', // AWS/GCP/Azure instance metadata
            'metadata.google.internal',
        ];

        if (in_array($host, $blockedHosts)) {
            return ['safe' => false, 'error' => "Access to internal destination '{$host}' is prohibited."];
        }

        // Resolve DNS and test resolved IPs
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            // DNS resolution failure
            return ['safe' => false, 'error' => "Could not resolve hostname '{$host}'."];
        }

        // Validate IP against private and reserved ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return ['safe' => false, 'error' => "Outbound request to internal/private IP '{$ip}' is blocked for security."];
        }

        return ['safe' => true, 'ip' => $ip];
    }
}
