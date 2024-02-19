<?php

require_once(__DIR__."/config.php");

session_start();

function GenerateRandomToken() {
    return random_bytes(128);
}

function storeTokenForUser($userName, $token) {
    if (!isset($_SESSION["budget_auth"])) {
        error_log("An unauthorized session attempted to store a token for user $userName");
        return false;
    }
    if ($_SESSION["budget_auth"] !== $userName) {
        $authedUser = $_SESSION["budget_auth"];
        error_log("$authedUser's session attempted to store a token for user $userName");
    }
    return setUserToken($userName, $token);
}

function fetchTokenByUserName($userName) {
    return getUserToken($userName);
}

function logUserIn($userName) {
    $_SESSION["budget_auth"] = $userName;
}

function IsUserLoggedIn() {
    return isset($_SESSION["budget_auth"]);
}

//https://stackoverflow.com/questions/1354999/keep-me-logged-in-the-best-approach
function onLogin($user) {
    global $secretkey;
    $token = GenerateRandomToken(); // generate a token, should be 128 - 256 bit
    $base64Token = base64_encode($token);
    storeTokenForUser($user, $base64Token);
    $cookie = "$user:$token";
    $mac = hash_hmac('sha256', $cookie, $secretkey);
    $cookie .= ':' . $mac;
    $cookieDurationDays = 100;
    setcookie('rememberme', $cookie, time() + (86400 * $cookieDurationDays));
}

function rememberMe() {
    global $secretkey;
    $cookie = isset($_COOKIE['rememberme']) ? $_COOKIE['rememberme'] : '';
    if ($cookie) {
        list ($user, $token, $mac) = explode(':', $cookie);
        if (!hash_equals(hash_hmac('sha256', "$user:$token", $secretkey), $mac)) {
            return false;
        }
        $base64Token = fetchTokenByUserName($user);
        $usertoken = base64_decode($base64Token);
        if (hash_equals($usertoken, $token)) {
            logUserIn($user);
        }
    }
}

function displayLoginForm() {
    include __DIR__."/login-form.php";
}

if (!isset($_SESSION["budget_auth"])) {

    rememberMe();
    if (!IsUserLoggedIn()) {

        $auth_username = isset($_SERVER['PHP_AUTH_USER'])? $_SERVER['PHP_AUTH_USER'] :
            (isset($_POST["username"])? $_POST["username"] : "");
        $auth_password = isset($_SERVER['PHP_AUTH_PW'])? $_SERVER['PHP_AUTH_PW'] :
            (isset($_POST["password"])? $_POST["password"] : "");

        // Check for password headers
        if (!$auth_username) {
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
        if (!isset($users[$auth_username])) {
            header('HTTP/1.0 401 Unauthorized');
            echo 'Access Denied';
            exit;
        }

        $hashed_password = $users[$auth_username];
        $user_supplied_password = $auth_password;

        // Check if password hashes match
        if ($hashed_password !== crypt($user_supplied_password, $hashed_password)) {
            header('HTTP/1.0 401 Unauthorized');
            echo "Access Denied";
            exit;
        }

        logUserIn($auth_username);
        onLogin($auth_username);
    }
}



?>