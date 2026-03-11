<?php

session_start();

$_SESSION["budget_auth"] = "demo";
echo json_encode(["success" => true]);

?>