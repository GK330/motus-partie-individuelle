<?php

require_once __DIR__ . '/../src/Core/DotEnv.php';
require_once __DIR__ . '/../helpers/global_helper.php';

const ENV_VARS = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME', 'JWT_SECRET', 'JWT_TTL', 'JWT_REFRESH_TTL'];

try {
    DotEnv::loadFromFile(__DIR__ . '/../.env');
} catch (InvalidArgumentException) {
    foreach (ENV_VARS as $name) {
        $value = getenv($name);
        if ($value !== false) {
            $_ENV[$name] = $value;
        }
    }
} catch (RuntimeException $e) {
    logWithDate('Env configuration failed', $e->getMessage());
    exit(1);
}

foreach (ENV_VARS as $name) {
    if (!isset($_ENV[$name])) {
        logWithDate('Env configuration failed', sprintf('%s is not set', $name));
        exit(1);
    }
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($requestUri !== "/client.php") {
    require_once __DIR__ . '/../src/Core/Database.php';
    require_once __DIR__ . '/front_controller.php';
} else {
    require_once __DIR__ . '/client.php';
}