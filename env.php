<?php
function loadDotEnv($path)
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || substr($line, 0, 1) === '#') {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ($value === '') {
            $value = '';
        } elseif ($value[0] === '"' && substr($value, -1) === '"') {
            $value = substr($value, 1, -1);
        } elseif ($value[0] === "'" && substr($value, -1) === "'") {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function getEnvValue($name)
{
    $value = getenv($name);

    if ($value !== false && $value !== '') {
        return $value;
    }

    if (isset($_ENV[$name])) {
        return $_ENV[$name];
    }

    return null;
}

function getDefaultDotenvPath()
{
    $home = getenv('HOME');
    if ($home === false || $home === '') {
        $home = isset($_SERVER['HOME']) ? $_SERVER['HOME'] : null;
    }
    if ($home !== null) {
        return rtrim($home, '/') . '/.env';
    }

    if (function_exists('posix_getpwuid')) {
        $userInfo = posix_getpwuid(posix_geteuid());
        if (!empty($userInfo['dir'])) {
            return rtrim($userInfo['dir'], '/') . '/.env';
        }
    }

    return null;
}

function loadEnv()
{
    $dotenvPath = getenv('DOTENV_PATH');
    if ($dotenvPath === false || $dotenvPath === '') {
        $dotenvPath = getDefaultDotenvPath();
    }

    if ($dotenvPath !== null) {
        loadDotEnv($dotenvPath);
    }
}

