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
                'id' => $result["id"],
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
    public function signup($conn, $request) 
    {
        if (!isset($request->password) || !isset($request->username) || !isset($request->email)) {
            return false;
        }

        $password = $request->password;
        $username = $request->username;
        $email = $request->email;
        if (isset($request->phoneNumber))
            $phoneNumber = $request->phoneNumber;
        if (isset($request->firstName))
            $firstName = $request->firstName;
        if (isset($request->lastName))
            $lastName = $request->lastName;
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
        $sql = "INSERT INTO user (username,password,email";
        if (isset($phoneNumber)) {
            $sql .= ",phoneNumber";
        }
        if (isset($lastName)) {
            $sql .= ",lastName";
        }
        if (isset($firstName)) {
            $sql .= ",firstName";
        }
        $sql .= ") VALUES(:username, :password, :email";
        if (isset($phoneNumber)) {
            $sql .= ",:phoneNumber";
        }
        if (isset($lastName)) {
            $sql .= ",:lastName";
        }
        if (isset($firstName)) {
            $sql .= ",:firstName";
        }

        $sql .= ");";
        $statement = $conn->prepare($sql);
        if (isset($phoneNumber))
            $statement->bindParam(":phoneNumber", $phoneNumber, PDO::PARAM_STR);

        if (isset($lastName))
            $statement->bindParam(":lastName", $lastName, PDO::PARAM_STR);

        if (isset($firstName))
            $statement->bindParam(":firstName", $firstName, PDO::PARAM_STR);


        $statement->bindParam(":email", $email, PDO::PARAM_STR);
        $statement->bindParam(":password", password_hash($password, PASSWORD_BCRYPT), PDO::PARAM_STR);
        $statement->bindParam(":username", $username, PDO::PARAM_STR);

        if ($statement->execute()) {
            return true;
        }
    }
    public function changeData($request, $conn, $auth)
    {
        if (!isset($auth)) // preveri ce je token sploh poslan
        {
            echo "token ne obstaja";
            return false;
        }
        if (!isset($request->phoneNumber) && !isset($request->username) && !isset($request->email) || !isset($request->id)) {
            echo "manjkajo vsa polja";
            return false;
        }
        $jwt =  new JWT;

        if (!$jwt->is_valid($auth)) {
            echo "token ni valid";
            return false;
        }
        
        $phoneNumber = $request->phoneNumber;
        $username = $request->username;
        $email = $request->email;
        $id = $request->id;

        // Check the length of the username and password
        if (strlen($username) > 30 && isset($username)) {
            echo "username je predugi";
            return false;
        }
        // Check for disallowed characters in the username and password
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username && isset($username))) {
            echo "username vsebuje nedovoljene znake";
            return false;
        }

        // Check the length of the email address
        if (strlen($email) > 255 && isset($email)) {
            echo "email je predugi";
            return false;
        }

        // Check the validity of the email address
        if (!preg_match('/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/', $email) && isset($email)) {
            echo "email ni valid";
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
        $sql = "UPDATE user SET username = :username, phoneNumber = :phoneNumber, email = :email WHERE id = :id";
        $statement = $conn->prepare($sql);
        if ($statement->execute([
            ':phoneNumber' => $phoneNumber,
            ':username' => $username,
            ':email' => $email,
            ':id' => $id
        ])) {
            return true;
        }
    }
    public function getData($conn, $auth = null)
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
        $userid = $token["id"];
        $sql = "SELECT * FROM user WHERE id=:id"; // nesmes uporabit <':usr'> ker nebo deloval (https://www.php.net/manual/en/pdo.prepare.php drugi komentar)
        $statement = $conn->prepare($sql);
        $statement->execute([
            ":id" => $userid
        ]);
        $row = $statement->fetch();
        $bruh =  array(
            "username" => $row["username"],
            "id" => $row["id"],
            "email" => $row["email"],
            "phoneNumber" => $row["phoneNumber"],
            "timeCreated" => $row["timeCreated"],
            "firstName" =>$row["firstName"],
            "lastName"=>$row["lastName"]
        );
        echo json_encode($bruh);
        return true;
    }
    public function setImage($conn, $auth = null, $file = null)
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
            echo "token ni valid";
            return false;
        }
        $token = $jwt->decode($token); // razsifrira token
        $token = json_decode($token, 1);
        $id = $token["id"];
        echo $id;
        if ($this->saveImage($id, false, $file)) {
            echo "image fetch";
            return true;
        }
        echo "prislo do konca";
        return false;
    }
    private function saveImage(int $profileID, $isDefault = false, $file = null)
    {

        if ($isDefault) {
            echo "default";
            // code to set default image
        } else {

            $allowedTypes = array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
            $detectedType = exif_imagetype($file['tmp_name']);
            if (!in_array($detectedType, $allowedTypes)) {
                echo json_encode(array('message' => 'Invalid file type. Only PNG, JPEG, and GIF images are allowed.'));
                return false;
            }

            // Set the destination path where the file will be saved
            $destination = '../profile/images/' . $profileID . '.png';

            // Move the file to the specified path
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // File uploaded successfully
                echo json_encode(array('message' => 'File uploaded successfully'));
            } else {
                // Failed to upload file
                echo json_encode(array('message' => 'Failed to upload file'));
            }
        }
    }
}
