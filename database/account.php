<?php
error_reporting(E_ERROR | E_PARSE);
require_once "../auth/jwt.php";
class account
{
    public function login($conn, $request)
    {
        if (!isset($request->password) || !isset($request->username)) {
            return false;
        }
        $password = $request->password;
        $username = $request->username;
        $sql = "SELECT * FROM user WHERE username=:usr OR email=:usr";
        $statement = $conn->prepare($sql);
        $statement->execute([
            ":usr" => $username
        ]);
        $result = $statement->fetch();
        if ($result) {   //preveri ce username obstaja 
            if (!password_verify($password, $result["password"])) { // preveri ce je geslo valid
                return false;
            }
            $payload = array( // doda username in password k payloadu
                'password' => $password,
                'username' => $result["username"],
                'iss' => "jwt.php"
            );
            $generate = new JWT();
            $token = $generate->generate($payload); // generira token z payloadom
            return $token;
        } else // ce sql ne vrne vnosa, vrne false
        {
            return false;
        }
    }
    public function signup($conn, $request) // gpt optimised code
{
    if (!isset($request->password) || !isset($request->username) || !isset($request->email)) {
        return false;
    }

    $password = $request->password;
    $username = $request->username;
    $email = $request->email;

    // Check the length of the username and password
    if (strlen($password) > 60 || strlen($username) > 30) {
        return false;
    }
    // Check for disallowed characters in the username and password
    if (preg_match('/[\'"()}{<>|]/', $password) || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return false;
    }
    
    // Check the length of the email address
    if (strlen($email) > 255) {
        return false;
    }
  
    // Check the validity of the email address
    if (!preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/', $email)) {
        return false;
    }
    
    // Check if the username already exists
    $sql = "SELECT * FROM user WHERE username=:usr";
    $statement = $conn->prepare($sql);
    $statement->execute([
        ":usr" => $username
    ]);
    $result = $statement->fetchAll();
    
    if ($result) {
        return false;
    }
    
    // Check if the email address is already in use
    $sql = "SELECT * FROM user WHERE email=:email";
    $statement = $conn->prepare($sql);
    $statement->execute([
        ":email" => $email
    ]);
    $result = $statement->fetchAll();
    if ($result) {
        return false;
    }
    
    // Insert the new user into the database
    $sql = "INSERT INTO user (username,password,email) VALUES(:username, :password, :email);";
    $statement = $conn->prepare($sql);
    if ($statement->execute([
        ':password' => password_hash($password, PASSWORD_BCRYPT),
        ':username' => $username,
        ':email' => $email
    ])) {
        return true;
    }
}
    public function checkUsername ($request,$conn){

    }
    public function getData($request, $conn, $auth = null)
    {
        $jwt =  new JWT;
        if (!isset($auth)) // preveri ce je token sploh poslan
        {
            echo "token ne obstaja";
            return false;
        }
        $token = $auth;
        if (!$jwt->is_valid($token)) // preveri ce je token valid
        {
            echo "token ni valid";
            return false;
        }
        $token = $jwt->decode($token); // razsifrira token
        $token = json_decode($token, 1);
        $username = $token["username"];
        $sql = "SELECT * FROM user WHERE username=:usr"; // nesmes uporabit <':usr'> ker nebo deloval (https://www.php.net/manual/en/pdo.prepare.php drugi komentar)
        $statement = $conn->prepare($sql);
        $statement->execute([
            ":usr" => $username
        ]);
        $info = $statement->fetch();
        $bruh =  array(
            "username" => $info["username"],
            "id" => $info["id"],
            "email" => $info["email"],
            "phoneNumber" => $info["phoneNumber"],
            "timeCreated" => $info["timeCreated"]
        );
        echo json_encode($bruh);
        return true;
    }
    public function setImage($request, $conn, $auth = null)
    {
        $jwt =  new JWT;
        if (!isset($auth)) // preveri ce je token sploh poslan
        {
            echo "token ne obstaja"; // debug
            return false;
        }
        $token = $auth;
        if (!$jwt->is_valid($token)) // preveri ce je token valid
        {
            return false;
        }
        $token = $jwt->decode($token); // razsifrira token
        $token = json_decode($token, 1);
        $password = $token["password"];
        $username = $token["username"];
        $sql = "SELECT * FROM user WHERE password =:pswrd AND username=:usr"; // nesmes uporabit <':usr'> ker nebo deloval (https://www.php.net/manual/en/pdo.prepare.php drugi komentar)
        $statement = $conn->prepare($sql);
        $statement->execute([
            ":pswrd" => $password,
            ":usr" => $username
        ]);
        $info = $statement->fetch();
        $id = $info["id"];
        unset($request->token);
        if ($this->saveImage($id, false)) {
            return true;
        }
        return false;
    }
    private function saveImage(int $profileID, bool $default = true) // shrani poljubno slika ali doda "default image" profilu
    {
        if ($default) // nastavi default image profilu
        {
            $file = "./profile/images/1.png";
            $des = "./profile/images/$profileID.png";
            if (!copy($file, $des)) {
                echo "failed to copy $file to $des";
                return false;
            }
            return true;
        }
        $handle = fopen("php://input", "rb"); // prebere POST podatke
        $destination = fopen("$../profile/images/$profileID.png", "wb"); // lokacija kjer se shrani file
        $size = 0;
        while (!feof($handle)) {
            $chunk = fread($handle, 1024 * 1024); // prebere po chunkih
            $size += strlen($chunk);
            fwrite($destination, $chunk); // chunk napise na destination
        }
        fclose($handle);
        fclose($destination);
        return true;
    }
}
