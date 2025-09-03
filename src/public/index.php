<?php
declare(strict_types=1);

$app = require_once __DIR__ . '/../bootstrap.php';

$app->handle($_SERVER['REQUEST_URI'] ?? '/');