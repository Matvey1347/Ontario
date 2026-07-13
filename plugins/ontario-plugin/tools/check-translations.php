<?php
declare(strict_types=1);

$base = dirname(__DIR__);
$dir = $base . '/translations';
$required = ['en', 'ru', 'fr', 'pl'];
$errors = [];
$loaded = [];

foreach ($required as $code) {
    $file = $dir . '/' . $code . '.php';

    if (! is_file($file)) {
        $errors[] = "Missing translation file: {$code}.php";
        continue;
    }

    $data = require $file;

    if (! is_array($data)) {
        $errors[] = "Invalid translation file structure: {$code}.php";
        continue;
    }

    foreach (['code', 'name', 'native_name', 'flag', 'strings'] as $field) {
        if (! array_key_exists($field, $data)) {
            $errors[] = "Missing field '{$field}' in {$code}.php";
        }
    }

    if (($data['code'] ?? '') !== $code) {
        $errors[] = "Translation code mismatch in {$code}.php";
    }

    if (! isset($data['strings']) || ! is_array($data['strings'])) {
        $errors[] = "Invalid strings array in {$code}.php";
        continue;
    }

    foreach ($data['strings'] as $key => $value) {
        if (! is_string($key) || $key === '' || ! preg_match('/^[a-z0-9._-]+$/', $key)) {
            $errors[] = "Invalid translation key '{$key}' in {$code}.php";
            continue;
        }

        if (! is_string($value) || trim($value) === '') {
            $errors[] = "Empty translation value for '{$key}' in {$code}.php";
        }
    }

    $loaded[$code] = $data;
}

if (isset($loaded['en']) && is_array($loaded['en']['strings'] ?? null)) {
    $reference_keys = array_keys($loaded['en']['strings']);

    foreach ($required as $code) {
        if (! isset($loaded[$code]['strings']) || ! is_array($loaded[$code]['strings'])) {
            continue;
        }

        $keys = array_keys($loaded[$code]['strings']);
        $missing = array_diff($reference_keys, $keys);
        $extra = array_diff($keys, $reference_keys);

        if ($missing !== []) {
            $errors[] = "Missing keys in {$code}.php: " . implode(', ', $missing);
        }

        if ($extra !== []) {
            $errors[] = "Unexpected keys in {$code}.php: " . implode(', ', $extra);
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo 'Translations OK: en, ru, fr, pl' . PHP_EOL;
