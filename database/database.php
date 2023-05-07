<?php
/* 
Vsebuje vse funkcije, za delo s podatkovno bazo. 
*/
require_once "../auth/jwt.php";
require_once "connect.php";
require_once "admin.php";
require_once "account.php";
require_once "catalog.php";
$request_body = file_get_contents('php://input'); //shrani podatke od POST 
$headers = apache_request_headers(); // dobi headerje (npr token od cookie)
foreach ($headers as $header => $value) { // za vsak header doda vrednost
}
if (isset($_GET["login"])) // WIP
{
  $payload = json_decode($request_body);
  cors('http://localhost:3000'); // dovoli povezavo samo s tega URL, drugace ne stima
  $login = new account;
  $token = $login->login($conn, $payload); // ustvari token
  if ($token) {
    echo ("{\"Authorization\" : \"$token\"}"); // vrne token
    http_response_code(200); // status OK
  } else {
    http_response_code(403); // status forbidden
  }
}
if (isset($_GET["signup"])) {
  $payload = json_decode($request_body);
  cors('http://localhost:3000'); // dovoli povezavo samo s tega URL, drugace ne stima
  $signup = new account;
  if ($signup->signup($conn, $payload))
    http_response_code(201); // status Created
  else
    http_response_code(400); // 400 bad request (manjka geslo, username ali vsebuje nedovoljene znake)
}

if (isset($_GET["getProductCatalog"])) {
  cors('http://localhost:3000'); // dovoli povezavo samo s tega URL, drugace ne stima
  $productCatalog = new catalog;
  if ($productCatalog->getCatalog($conn)) {
    // da dela pa ne mece napak
    http_response_code(200);  // status OK
  } else {
    http_response_code(404); // vrne not found ce nekaj ne stima
  }
}
if (isset($_GET["getAccountData"])) {
  cors('http://localhost:3000');
  $payload = json_decode($request_body);
  $account = new account;
  if ($account->getData($payload, $conn))
    http_response_code(200); // status OK
  else
    http_response_code(403); // 403 forbidden (token ni veljavem)
}
if (isset($_GET["changeProfileImage"])) {
  cors('http://localhost:3000');
  $account = new account;
  $payload = json_decode($request_body);
  if ($account->setImage($payload, $conn)) {
    http_response_code(200);
  } else {
    http_response_code(403);
  }
}
// admin funckije  - - - - -----------------------------------------------------------------

//if (isset($_GET["insertProduct"])) {
//  cors('http://localhost:3001');
 /// $admin = new admin;
  //$payload = json_decode($request_body);
 // if ($admin->setImage($payload, $conn)) {
  //  http_response_code(200);
 // } else {
 //   http_response_code(403);
 // }
//}