<?php
/**
 * Jobpilot i18n helper
 * Sets $LANG (current language code) and $LANG_DIR (ltr/rtl)
 * Loads translation strings and exposes t($key) function
 */

// Detect language: query param → cookie → default 'en'
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
    $detected_lang = $_GET['lang'];
    setcookie('jp_lang', $detected_lang, time() + 31536000, '/'); // 1 year
    $_COOKIE['jp_lang'] = $detected_lang;
} else {
    $detected_lang = $_COOKIE['jp_lang'] ?? 'en';
    if (!in_array($detected_lang, ['en', 'ar'])) $detected_lang = 'en';
}

$LANG     = $detected_lang;
$LANG_DIR = ($LANG === 'ar') ? 'rtl' : 'ltr';

// Load translation strings
$_TRANSLATIONS = require __DIR__ . '/../lang/' . $LANG . '.php';

/**
 * Translate a key. Falls back to English if missing in current language.
 */
function t(string $key, string $default = ''): string {
    global $_TRANSLATIONS;
    return $_TRANSLATIONS[$key] ?? $default ?: $key;
}

/**
 * Return current language code
 */
function lang(): string { global $LANG; return $LANG; }

/**
 * Return language direction
 */
function lang_dir(): string { global $LANG_DIR; return $LANG_DIR; }

/**
 * Build lang-switch URL preserving current query string
 */
function lang_url(string $lang): string {
    $params = $_GET;
    $params['lang'] = $lang;
    $base = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    return $base . '?' . http_build_query($params);
}
