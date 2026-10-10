<?php
/**
 * config.php - ملف الإعدادات العامة لمشروع عصملي (OSMANLI Configuration)
 */

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'osmanli_db');
if (!defined('DB_PORT')) define('DB_PORT', 3306);
if (!defined('APP_NAME')) define('APP_NAME', 'عصملي - أبو سير 1854');
if (!defined('BASE_URL')) define('BASE_URL', '/asmali/');

require_once __DIR__ . '/db.php';
