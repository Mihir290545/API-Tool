<?php
/**
 * DevDock — Root Entry Point Proxy
 * Transparently delegates execution to public/index.php for root-level deployments.
 */

declare(strict_types=1);

require_once __DIR__ . '/public/index.php';
