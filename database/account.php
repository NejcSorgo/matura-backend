<?php
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
        $sql = "SELECT * FROM user WHERE username=:usr";
        $statement = $conn->prepare($sql);
        $statement->execute([
            ":usr" => $username
        ]);
        $result = $statement->fetch();
        if ($result) {   //preveri ce username obstaja 
            if (!password_verify($password,$result["password"])) { // preveri ce je geslo valid
                return false;
            } 
            $payload = array( // doda username in password k payloadu
                'password' => $password,
                'username' => $username,
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
    public function signup($conn, $request)
    {
        if (!isset($request->password) || !isset($request->username)) {
            return false;
        }
        $password = $request->password;
        $username = $request->username;
        $sql = "SELECT * FROM user WHERE username=:usr"; //preveri ce ze obstaja uporabnik
        $statement = $conn->prepare($sql);
        $statement->execute([
            ":usr" => $username
        ]);
        $result = $statement->fetchAll();
        if (!$result) {
            return false;
        }
        if (preg_match('/[\'^£$%&*()}{#~?<>,|=_+¬-]/', $password) || preg_match('/[\'^£$%&*()}{#~?<>,|=_+¬-]/', $username)) // preveri za nedovoljene znake
            return false;
        $sql = "INSERT INTO user (username,password) VALUES(:username, :password);";
        $statement = $conn->prepare($sql);
        if ($statement->execute([
            ':password' => $password,
            ':username' => $username
        ])) {
            return true;
        }
    }

    public function getData($request, $conn)
    {
        $jwt =  new JWT;
        if (!isset($request->token)) // preveri ce je token sploh poslan
        {
            echo "token ne obstaja";
            return false;
        }
        $token = $request->token;
        if (!$jwt->is_valid($token)) // preveri ce je token valid
        {
            echo "token ni valid";
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
        $info =  array(
            "Name" => $info["username"],
            "Password" => $info["password"],
            "id" => $info["id"]
        );
        echo json_encode($info);
        return true;
    }
    public function setImage($request, $conn)
    {
        $jwt =  new JWT;
        if (!isset($request->token)) // preveri ce je token sploh poslan
        {
            echo "token ne obstaja"; // debug
            return false;
        }
        $token = $request->token;
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
