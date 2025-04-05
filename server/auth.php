<?php

require_once(__DIR__."/config.php");
require_once(__DIR__."/dao.php");

session_start();

function GenerateRandomToken() {
    return random_bytes(128);
}

function storeTokenForUser($userName, $token, $days) {
    if (!isset($_SESSION["budget_auth"])) {
        error_log("An unauthorized session attempted to store a token for user $userName");
        return false;
    }
    if ($_SESSION["budget_auth"] !== $userName) {
        $authedUser = $_SESSION["budget_auth"];
        error_log("$authedUser's session attempted to store a token for user $userName");
    }
    if (setUserToken($userName, $token, $days)) {
        $_SESSION["budget_auth_token"] = $token;
        return true;
    }
    return false;
}

function getUserTokenFromSession() {
    return isset($_SESSION["budget_auth_token"])? $_SESSION["budget_auth_token"] : null;
}

function fetchTokensByUserName($userName) {
    return getUserTokens($userName);
}

function logUserIn($userName) {
    $_SESSION["budget_auth"] = $userName;
}

function IsUserLoggedIn() {
    return isset($_SESSION["budget_auth"]);
}

function onLogin($user) {
    global $secretkey;
    $cookieDurationDays = 100;
    $token = GenerateRandomToken(); // generate a token, should be 128 - 256 bit
    $base64Token = base64_encode($token);
    storeTokenForUser($user, $base64Token, $cookieDurationDays);
    $cookie = "$user:$token";
    $mac = hash_hmac('sha256', $cookie, $secretkey);
    $cookie .= ':' . $mac;
    setcookie('rememberme', $cookie, time() + (86400 * $cookieDurationDays));
    return [
        "cookie" => base64_encode($cookie),
        "days" => $cookieDurationDays
    ];
}

function rememberMe() {
    global $secretkey;
    $cookie = isset($_COOKIE['rememberme']) ? $_COOKIE['rememberme'] : '';

    // If there's no cookie, check for post data
    if (!$cookie && $_SERVER['REQUEST_METHOD'] === "POST") {
        $postDataString = file_get_contents('php://input');
        $postData = json_decode($postDataString, true);
        if (isset($postData["rememberme"])) {
            $cookie = base64_decode($postData["rememberme"]); // base64 decode post param
        }
    }

    if ($cookie) {
        list ($user, $token, $mac) = explode(':', $cookie);
        if ($mac === null) { // Check for invalid cookie format
            return false;
        }
        if (!hash_equals(hash_hmac('sha256', "$user:$token", $secretkey), $mac)) {
            return false;
        }
        $base64Tokens = fetchTokensByUserName($user);
        foreach ($base64Tokens as $base64Token) {
            $usertoken = base64_decode($base64Token);
            if (hash_equals($usertoken, $token)) {
                logUserIn($user);
                break;
            }
        }
    }
}

if (!isset($_SESSION["budget_auth"])) {

    rememberMe();
    if (!IsUserLoggedIn()) {

        $_POST = json_decode(file_get_contents("php://input"), true);
        $auth_username = isset($_SERVER['PHP_AUTH_USER'])? $_SERVER['PHP_AUTH_USER'] :
            (isset($_POST["username"])? $_POST["username"] : "");
        $auth_password = isset($_SERVER['PHP_AUTH_PW'])? $_SERVER['PHP_AUTH_PW'] :
            (isset($_POST["password"])? $_POST["password"] : "");

        // Check for password headers
        if (!$auth_username) {
            header('HTTP/1.0 401 Unauthorized');
            error_log("3");
            echo json_encode([
                "success" => false
            ]);
            exit;
        }

        // Load users (from config for now)
        global $authusers;
        $users = $authusers;

        // Check for correct username
        if (!isset($users[$auth_username])) {
            header('HTTP/1.0 401 Unauthorized');
            error_log("2");
            echo json_encode([
                "success" => false
            ]);
            exit;
        }

        $hashed_password = $users[$auth_username];
        $user_supplied_password = $auth_password;

        // Check if password hashes match
        if ($hashed_password !== crypt($user_supplied_password, $hashed_password)) {
            header('HTTP/1.0 401 Unauthorized');
            error_log("1");
            echo json_encode([
                "success" => false
            ]);
            exit;
        }

        logUserIn($auth_username);
        $cookieData = onLogin($auth_username);
        echo json_encode([
            "success" => true,
            "cookie" => $cookieData["cookie"],
            "expire" => date('Y-m-d H:i:s', strtotime("+".$cookieData["days"]." days"))
        ]);
    }
} else {
    echo json_encode([
        "success" => true
    ]);
}



?>