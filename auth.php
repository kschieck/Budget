<?php

session_start();

function displayLoginForm() {
    include __DIR__."/login-form.php";
}

if (!isset($_SESSION["budget_auth"])) {

    // Check for password headers
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="StratfordTreasureHunt"');
        header('HTTP/1.0 401 Unauthorized');
        displayLoginForm();
        exit;
    }

    $users = [
        "kyle" => '$2y$10$plYgIk.tr5Ut.Sxh70lcYOe1Foao5u5CHjHcdtyXfC5RH3c9XQ6o6',
        "lexie" => '$2y$10$54c2K9UN79Ov2sIgykvJL.F.8AtwxoSb3bzSp4SePGM8ULpHpOjkS'
    ];

    // Check for correct username
    if (!isset($users[$_SERVER['PHP_AUTH_USER']])) {
        header('HTTP/1.0 401 Unauthorized');
        echo 'Access Denied';
        exit;
    }

    $hashed_password = $users[$_SERVER['PHP_AUTH_USER']];
    $user_supplied_password = $_SERVER['PHP_AUTH_PW'];

    // Check if password hashes match
    if ($hashed_password !== crypt($user_supplied_password, $hashed_password)) {
        header('HTTP/1.0 401 Unauthorized');
        echo "Access Denied";
        exit;
    }

    $_SESSION["budget_auth"] = $_SERVER['PHP_AUTH_USER'];
}



?>