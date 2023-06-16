<?php
class admin
{
    public function getDashboardData($conn,$auth){
        if (!$this->verify($auth, $conn)) {
            return false;
        }
        $stats = array();
        $stmt = $conn->query("SELECT COUNT(*) AS total_products FROM product");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_products'] = $result['total_products'];
    
        // Get total count of users
        $stmt = $conn->query("SELECT COUNT(*) AS total_users FROM user");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_users'] = $result['total_users'];
    
        // Get total count of transactions
        $stmt = $conn->query("SELECT COUNT(*) AS total_transactions FROM orders");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_transactions'] = $result['total_transactions'];
    
        // Get total count of reviews
        $stmt = $conn->query("SELECT COUNT(*) AS total_reviews FROM review");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_reviews'] = $result['total_reviews'];
        echo json_encode($stats);
        return true;
    }
    public function insertProduct($payload, $conn, $auth) // vstavi not novi produkte + tage za ta produkt.
{
    if (!$this->verify($auth, $conn)) {
        return false;
    }
    
    $productName = $payload->name;
    $productPrice = $payload->price;
    $productCategory = $payload->category;  
    $productSuperCategory = $payload->superCategory;
    $description = $payload->description;
    $tags = str_replace(' ', '', $payload->tags); // odstrani presledke
    
    $sql = "INSERT INTO product (productName, productPrice, productCategory, productSuperCategory,description) VALUES (:productName, :productPrice, :productCategory, :productSuperCategory, :description)";
    $statement = $conn->prepare($sql);
    $statement->bindParam(':productName', $productName);
    $statement->bindParam(':productPrice', $productPrice);
    $statement->bindParam(':productCategory', $productCategory);
    $statement->bindParam(':productSuperCategory', $productSuperCategory);
    $statement->bindParam(':description', $description);
    

    
    if ($statement->execute()) {
        $productID = $conn->lastInsertId(); // zadnji id od presnje transakcije
        $payload = array(
            "Tags" => $tags,
            "id" => $productID,
        );
        $payload = json_encode($payload);
        $payload = json_decode($payload);
        $this->insertTag($payload, $conn,$auth);    
        return true;
    }
    
    return false;
}
    public function insertProductVariant($payload, $conn, $auth) // vnese produkt v tabelo
    {
        if (!$this->verify($auth, $conn)) {
            return false;
        }
        $variantColor = $payload['variantColor'];
        $variantSize = $payload['variantSize'];
        $variantStock = $payload['variantStock'];
        $productID = $payload['productID'];
        $sql = "INSERT INTO product (productName,productPrice,productCategory) VALUES ('$variantColor',$variantSize,'$variantStock','$productID')";
        if ($conn->query($sql))
            return true;
    }
    public function insertTag($payload, $conn, $auth)
    {
        if (!$this->verify($auth, $conn)) {
            return false;
        }

        if (!isset($payload->Tags) || !isset($payload->id)) {
            echo "nema podatkov";
            return false;
        }
        $tags = str_replace(' ', '', $payload->Tags); // odstrani presledke
        $tags = explode(",", $tags);
        $productID = $payload->id;


        $insertTag = $conn->prepare("SELECT * FROM tags WHERE TagName = :tagName");
        foreach ($tags as $tagName) { // iterate skozi $tags array in za vsako preveri ce ze obstaja.

            $insertTag->bindParam(':tagName', $tagName);
            if ($insertTag->execute() && $row = $insertTag->fetch()) { // ce tak ze obstaj v bazi
                $TagID = $row['id'];
                $check = $conn->prepare("SELECT COUNT(*) FROM tagtoproduct WHERE ProductID = :productID AND TagID = :TagID"); // preveri ce je ze vnos v tabeli
                $check->bindParam(':productID', $productID);
                $check->bindParam(':TagID', $TagID);
                $check->execute();
                $count = $check->fetchColumn();

                if ($count == 0) {
                    $sql = "INSERT INTO tagtoproduct VALUES (:productID, :TagID)";
                    $insertTagToProduct = $conn->prepare($sql);
                    $insertTagToProduct->bindParam(':productID', $productID);
                    $insertTagToProduct->bindParam(':TagID', $TagID);
                    $insertTagToProduct->execute();
                    if ($insertTagToProduct->execute()) {
                       // echo "uspesno assignal tag v bazo (je obstajal prej v bazi) $sql";
                    } else {
                       // echo "neuspesno dal tag v bazo (je obstajal prej v bazi): $sql";
                    }
                }
            } else { // ce ne obstaja v bazi
                $insertNewTag = $conn->prepare("INSERT INTO tags (tagName) VALUES (:tagName)");
                $insertNewTag->bindParam(':tagName', $tagName);
                $insertNewTag->execute();
                $TagID = $conn->lastInsertId();
                $sql = "INSERT INTO tagtoproduct VALUES (:productID, :TagID)";
                $insertTagToProduct = $conn->prepare($sql);
                $insertTagToProduct->bindParam(':productID', $productID);
                $insertTagToProduct->bindParam(':TagID', $TagID);
                if ($insertTagToProduct->execute()) {
                  //  echo "uspesno dal tag v bazo (ni obstajal prej v bazi): $sql";
                } else {
                    //echo "neuspesno dal tag v bazo (ni obstajal prej v bazi): $sql";
                }
            }
        }

        return true;
    }



    public function deleteProduct($payload, $conn, $auth) // odstrani vse produkte, vhodni paramter kot array id-jev produktov.
    {
        if (!$this->verify($auth, $conn)) {
            return false;
        }

        if (!isset($payload->id) || !is_array($payload->id)) { 
            return false;
        }

        $productIDs = $payload->id;
        
        $placeholders = implode(',', array_fill(0, count($productIDs), '?')); // implode funkcija (array razdeli v string in avtomatsko napolni)
        $conn->beginTransaction(); // transakcije, ker je veliko delete stavkokv in da se naenkrat izvedejo (ce je napaka, se nebojo narobe izvedli in transakicje ne gre skozi)
    
        try {
            // Delete product variants
            $variantSql = "DELETE FROM productVariant WHERE productID IN ($placeholders)";
            $variantStatement = $conn->prepare($variantSql);
            $variantStatement->execute($productIDs);
            echo $variantSql;
            // Delete tags associated with the products
            $tagSql = "DELETE FROM tagtoproduct WHERE productID IN ($placeholders)";
            echo $tagSql;
            $tagStatement = $conn->prepare($tagSql);
            $tagStatement->execute($productIDs);

            $tagSql = "DELETE FROM review WHERE productID IN ($placeholders)";
            echo $tagSql;
            $tagStatement = $conn->prepare($tagSql);
            $tagStatement->execute($productIDs);
            
            // Delete products
            $productSql = "DELETE FROM product WHERE id IN ($placeholders)";
            $productStatement = $conn->prepare($productSql);
            $productStatement->execute($productIDs);
            
            $conn->commit(); // Commit the transaction
            return true;
        } catch (PDOException $e) { // ce je napaka cancla transakcijo in pove napako.
            echo $e;
            $conn->rollBack(); 
            return false;
        }
    }
    public function updateProduct($payload, $conn, $auth) //  dobi kot vhodni parameter array produktov, ki jih updata.
    {
        if (!$this->verify($auth, $conn)) {
            return false;
        }

        // Iterate over each object in the payload array
        foreach ($payload as $item) {
            if (!isset($item->id) || !isset($item->Name) || !isset($item->Category) || !isset($item->Price) || !isset($item->SuperCategory) || !isset($item->Desc)) {
                // Skip this item if it's missing any required properties
                continue;
            }

            $ID = $item->id;
            $Name = $item->Name;
            $Category = $item->Category;
            $description = $item->Desc;
            $superCategory = $item->SuperCategory;
            $Price = $item->Price;
            $tags = $item->Tags;

            $sql = "UPDATE product SET productName = :name, productPrice = :price, productCategory = :category, productSuperCategory = :supercategory, description = :description WHERE id = :id";
            $statement = $conn->prepare($sql);
            $statement->execute([
                ":name" => $Name,
                ":category" => $Category,
                ":price" => $Price,
                ":supercategory" => $superCategory,
                ":description" => $description,
                ":id" => $ID,
            ]);

            if ($this->insertTag($item, $conn, $auth)) {
                // Continue processing other items even if one insertion is successful
                continue;
            }

            return false;
        }

        // All items were successfully updated
        return true;
    }

    public function addProductPicture($request, $conn, $auth)
    {
        if (!$this->verify($auth, $conn)) {
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
    public function getUsers($conn, $auth)
    {
        if (!$this->verify($auth, $conn)) {
            echo "bruh";
            return false;
        }
        $sql  = "SELECT * FROM user";
        $statement = $conn->prepare($sql);
        $statement->execute();
        $users = array();
        for ($i = 0; $row = $statement->fetch(); $i++) {
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
    private function verify($auth, $conn)
    { // preveri ce je uporabnik admin
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
        if (!$row["admin"]) {
            return false;
        }

        return true;
    }
}
