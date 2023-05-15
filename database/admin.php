<?php
class admin
{
    public function insertProduct($payload, $conn,$auth) // vnese produkt v tabelo
    {
        if(!$this->verify($auth,$conn)){
            return false;
        }
        $ProductName = $payload['productName'];
        $ProductPrice = $payload['productPrice'];
        $ProductCategory = $payload['productCategory'];
        $ProductCategory = $payload['productSuperCategory'];
        $sql = "INSERT INTO product (productName,productPrice,productCategory) VALUES ('$ProductName',$ProductPrice,'$ProductCategory')";
        if ($conn->query($sql))
            return true;
    }
    public function insertProductVariant($payload, $conn,$auth) // vnese produkt v tabelo
    {
        if (!$this->verify($auth,$conn))
        {
            return false;
        }
        $variantColor= $payload['variantColor'];
        $variantSize = $payload['variantSize'];
        $variantStock = $payload['variantStock'];
        $productID = $payload['productID'];
        $sql = "INSERT INTO product (productName,productPrice,productCategory) VALUES ('$variantColor',$variantSize,'$variantStock','$productID')";
        if ($conn->query($sql))
            return true;
    }
    public function insertTag($payload, $conn,$auth) // funkicija za dodajanje tagov ($payload): array , ki vsebuje polja 'tagName'
    {   
        if (!$this->verify($auth,$conn))
        {
            return false;
        }
        if (!isset($payload['tagName']) || !isset($payload['id'])) { // preveri ce je payload array valid
            return  false;
        }
        $tagName = $payload['tagName'];
        $productID = $payload['id'];
        $sql = "SELECT * FROM tags WHERE TagName ='$tagName'"; // preveri ce obstaja tag
        $result = $conn->query($sql);
        if ($result->num_rows > 0) { // ce ze obstaja tag, doda tag na naveden product.
            mysqli_fetch_assoc($result);
            $TagID = $result['id'];
            $sql = "INSERT INTO tagtoproduct VALUES ($productID, $TagID ')";
            $conn->query($sql);
        } else { // ce ne , naredi tag in ga doda na product
            $sql = "INSERT INTO tags (tagName) VALUES ('$tagName')"; // inserta novi tag
            $result = $conn->query($sql);
            mysqli_fetch_assoc($result);
            $TagID = $result['id'];
            $sql = "INSERT INTO tagtoproduct VALUES ($productID, $TagID ')"; // doda tag na naveden product
            $conn->query($sql);
        }
        return true;
    }


    public function deleteProduct($payload, $conn,$auth) // odstrani product
    {
        if(!$this->verify($auth,$conn)){
            return false;
        }
        if (!isset($payload['ProductID'])) {
            return false;
        }
        $ProductID = $payload['ProductID'];
        $sql = "DELETE from product WHERE id=$ProductID";
        if ($conn->query($sql)) { // zazene sql
            return true; // ce je slo skozi vrne true
        }
        return false; // drugace vrne false
    }
    public function updateProduct($payload, $conn,$auth) // posodobi product
    {
        if(!$this->verify($auth,$conn)){
            return false;
        }
        if (!isset($payload['ProductID']) || !isset($payload['ProductPrice']) || !isset($payload['ProductName']) || !isset($payload['ProductCategory']) || !isset($payload['ProductSuperCategory'])) { // preveri ce je  $payload valid
            return false;
        }
        $ProductID = $payload['ProductID'];
        $ProductName = $payload['ProductName'];
        $ProductCategory = $payload['ProductCategory'];
        $ProductPrice = $payload['ProductPrice'];
        $sql = "UPDATE product SET productName = '$ProductName', productPrice = '$ProductPrice', productCategory = '$ProductCategory' WHERE id = '$ProductID'";
        if ($conn->query($sql)) { // zazene sql
            return true; // ce je slo skozi vrne true
        }
        return false; // drugace vrne false
    }
    public function addProductPicture($request,$conn,$auth)
    {
        if(!$this->verify($auth,$conn)){
            return false;
        }
        if ($this->saveImage($request->id, false)) {
            return true;
        }
        return false;
    }
    private function saveImage(int $productID, bool $default = true) // shrani poljubno slika ali doda "default image" profilu
    {
        if ($default) // nastavi default image profilu
        {
            $file = "./product/images/1.png";
            $des = "./product/images/$productID.png";
            if (!copy($file, $des)) {
                echo "failed to copy $file to $des";
                return false;
            }
            return true;
        }
        $handle = fopen("php://input", "rb"); // prebere POST podatke
        $destination = fopen("$../products/images/$productID.png", "wb"); // lokacija kjer se shrani file
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
    public function getUsers($conn,$auth){
        if(!$this->verify($auth, $conn))
        {
            echo "bruh";
            return false;
        }
        $sql  = "SELECT * FROM user";
        $statement = $conn->prepare($sql);
        $statement->execute();
        $users = array();
        for ($i = 0;$row = $statement->fetch();$i++)
        {
            $users[$i] = array(
                "id" => $row["id"],
                "phoneNumber" => $row["phoneNumber"],
                "email" => $row["email"],
                "username" => $row["username"],
                "timeCreated" => $row["timeCreated"],
                "admin" => $row["admin"],
                "password" => $row["password"]
            );
        }
        echo json_encode($users);
        return true;
    }
 private function verify($auth,$conn){ // preveri ce je uporabnik admin
         $jwt = new JWT;
        if (!isset($auth)) // preveri ce je token sploh poslan
        {
            echo "token ne obstaja"; // debug
            return false;
        }
        if (!$jwt->is_valid($auth)) // preveri ce je token valid
        {
            echo "token ni valid";
            return false;
        }
        $token = $jwt->decode($auth); // razsifrira token
        $token = json_decode($token, 1);
        $username = $token["username"];
        $sql = "SELECT * FROM user WHERE username=:usr"; // nesmes uporabit <':usr'> ker nebo deloval (https://www.php.net/manual/en/pdo.prepare.php drugi komentar)
        $statement = $conn->prepare($sql);
        $statement->execute([
            ":usr" => $username
        ]);
        $row = $statement->fetch();
        if (!$row["admin"])
        {
            return false;
        }
        
        return true;
 }
}
