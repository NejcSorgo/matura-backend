<?php

$servername = "localhost";
$username = "root";
$password = "";
$database = "shop";

$dsn = "mysql:host=$servername;dbname=$database;charset=UTF8";
// Create connection
$conn = new PDO($dsn,$username,$password); //   https://www.phptutorial.net/php-pdo/ !!! 

// Check connection
try {
	$pdo = new PDO($dsn, $username, $password);

	if ($pdo) {
	}
} catch (PDOException $e) {
	echo $e->getMessage();
}
function cors($url) // fixa cors http error 
{ 
  // Allow from any origin
  if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: $url");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
  }

  // Access-Control headers are received during OPTIONS requests
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
      // may also be using PUT, PATCH, HEAD etc
      header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
      header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
  }
}