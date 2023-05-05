<?php
class admin
{
    public function insertProduct($payload, $conn) // vnese produkt v tabelo
    {
        $ProductName = $payload['productName'];
        $ProductPrice = $payload['productPrice'];
        $ProductCategory = $payload['productCategory'];
        $sql = "INSERT INTO product (productName,productPrice,productCategory) VALUES ('$ProductName',$ProductPrice,'$ProductCategory')";
        if ($conn->query($sql))
            return true;
    }
    public function insertProductVariant($payload, $conn) // vnese produkt v tabelo
    {
        $variantColor= $payload['variantColor'];
        $variantSize = $payload['variantSize'];
        $variantStock = $payload['variantStock'];
        $productID = $payload['productID'];
        $sql = "INSERT INTO product (productName,productPrice,productCategory) VALUES ('$variantColor',$variantSize,'$variantStock','$productID')";
        if ($conn->query($sql))
            return true;
    }
    public function insertTag($payload, $conn) // funkicija za dodajanje tagov ($payload): array , ki vsebuje polja 'tagName'
    {
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


    public function deleteProduct($payload, $conn) // odstrani product
    {
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
    public function updateProduct($payload, $conn) // posodobi product
    {
        if (!isset($payload['ProductID']) || !isset($payload['ProductPrice']) || !isset($payload['ProductName']) || !isset($payload['ProductCategory'])) { // preveri ce je  $payload valid
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
    public function addProductPicture($request,$conn)
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
        if (!$info->admin) { // preveri ce je uporabnik admin
            return false;
        }
        unset($request->token);
        if ($this->saveImage($id, false)) {
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
 
}
