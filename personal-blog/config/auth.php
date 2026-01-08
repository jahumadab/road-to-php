<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

function is_admin(): bool
{
    if (!empty($_SESSION['admin'])) {
        return true;
    }

    if (!empty($_COOKIE['admin_logged_in'])) {
        $_SESSION['admin'] = true;
        return true;
    }

    return false;
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: admin.php');
        exit;
    }
}

function login_admin(string $user, string $pass): bool
{
    $config = require __DIR__ . '/config.php';

    if ($user === $config['admin']['user'] && $pass === $config['admin']['pass']) {
        $_SESSION['admin'] = true;
        setcookie('admin_logged_in', '1', [
            'expires' => time() + 86400,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return true;
    }

    return false;
}

function logout_admin(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    setcookie('admin_logged_in', '', time() - 3600, '/');
    session_destroy();
}
?>
