<?php
include "jwt.php";
$jwt = $_GET["token"];
$auth= new JWT();
if ($auth->is_valid($token) ) /// prever ce je JWT pravilen
    header("HTTP/1.1 401 Unauthorized");
    else
?>