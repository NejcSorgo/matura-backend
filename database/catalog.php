<?php
class catalog
{

    public function getCatalog($conn) // vrne json seznam productov in productVariantov *todo: vrne samo baseproduct in preveri ce je katerikoli od variantProductov in stock.*
    { // WIP
        $sql = "SELECT p.productName, p.productPrice,p.timeCreated, p.productCategory,p.id,p.description, GROUP_CONCAT(t.tagName SEPARATOR ', ') AS 'tags' FROM product p, tags t,tagtoproduct tp WHERE p.id = tp.productID AND t.id = tp.TagID GROUP BY productName; ";
        $fetchProducts = $conn->prepare($sql);
        $fetchProducts->execute();
        $sql = "SELECT v.color,v.size,v.stock FROM productvariant v,product p WHERE p.id = :productid AND p.id = v.productID;";
        $fetchProductVariant = $conn->prepare($sql);
        $j = 0;
        $products = array();
        while ($productRow = $fetchProducts->fetch()) { // hardcoded, ker drugace nena vredi dela
            $fetchProductVariant->execute([ // fetcha vse productVariante za posamezen id
                ":productid" => $productRow["id"],
            ]);
            $variants = array(); // definira variant array oz. ga zprazni.
            $i = 0;
            while ($variantRow = $fetchProductVariant->fetch()) // doda variant product noter v json
            {
                $variants[$i] = array(
                    "Color" => $variantRow["color"],
                    "Size" => $variantRow["size"],
                    "Stock" => $variantRow["stock"]
                );
                //var_dump($variants);
                $i++;
            }
            $tags = explode(",", $productRow["tags"]); // polje tags doda v samostojen array
            $products[$j] =  array( // napolni associativno polje z vsemi podatki o prodoktu
                "Name" => $productRow["productName"],
                "Price" => $productRow["productPrice"],
                "Category" => $productRow["productCategory"],
                "Desc" => $productRow["description"],
                "Tags" => $tags,
                "variants" => $variants,
                "ProductID" => $productRow["id"],
                "TimeCreated" => $productRow["timeCreated"]
            );
            $j++;
        }
        echo (json_encode($products)); // vrne json od associativnega polje
        return true;
    }
    public function getProductVariants($conn, $payload) // vrne json 1 producta in productVariante tega producta ter rating tega producta
    {
        $productID = $payload->productID;
        $sql = "SELECT v.color,v.size,v.stock FROM productvariant v,product p WHERE p.id = :productid AND p.id = v.productID;";
        $fetchVariants = $conn->prepare($sql);
        $fetchVariants->execute([ // fetcha vse productVariante za posamezen id
            ":productid" => $productID,
        ]);
        $sql = "SELECT p.productName, p.productPrice, p.productCategory,p.id,p.description, GROUP_CONCAT(t.tagName SEPARATOR ', ') AS 'tags' FROM product p, tags t,tagtoproduct tp WHERE p.id = tp.productID AND t.id = tp.TagID AND p.id = :productid GROUP BY productName; ";
        $fetchProduct = $conn->prepare($sql);
        $fetchProduct->execute([ // fetcha vse productVariante za posamezen id
            ":productid" => $productID,
        ]);
        if ($productRow = $fetchProduct->fetch()) { // hardcoded, ker drugace nena vredi dela
            $variants = array(); // definira variant array
            $i = 0;
            while ($variantRow = $fetchVariants->fetch()) // doda variant product noter v json
            {
                $variants[$i] = array(
                    "Color" => $variantRow["color"],
                    "Size" => $variantRow["size"],
                    "Stock" => $variantRow["stock"]
                );
                //var_dump($variants);
                $i++;
            }
            $tags = explode(",", $productRow["tags"]); // polje tags doda v samostojen array
            $product = array( // napolni associativno polje z vsemi podatki o prodoktu
                "Name" => $productRow["productName"],
                "Price" => $productRow["productPrice"],
                "Category" => $productRow["productCategory"],
                "Tags" => $tags,
                "Desc" => $productRow["description"],
                "Variants" => $variants,
                "ProductID" => $productRow["id"]
            );
        }
        echo (json_encode($product)); // vrne json od associativnega polje
        return true;
    }
    public function searchProductFilter($conn, $payload) // vrne produkte, ki se ujemajo z filterom.
    {
        if (isset($payload->search)) {
            $search = $payload->search;
        } else {
            $search = "";
        }
        if (!isset($payload->sort)){
            $sort = "bruh";
        }
        else {
            $sort = $payload->sort;
        }
        
        $tags = $payload->tags;
        if (isset($payload->category))
        {
            $category = $payload->category;
        }
        if (isset($payload->superCategory))
        {
            $superCategory = $payload->superCategory;
        }
        $sql = "SELECT p.productName, p.productPrice, p.productCategory,p.id,p.productSuperCategory, p.description, GROUP_CONCAT(t.tagName SEPARATOR ', ') AS 'tags'
        FROM product p, tags t, tagtoproduct tp
        WHERE p.id = tp.productID AND t.id = tp.TagID ";
        switch ($sort) { // za sorting (lowest,highest,newest,relevant)
            case "Price Lowest":
                $orderby = "ORDER BY p.productPrice";
                break;
            case "Price Highest":
                $orderby = "ORDER BY p.productPrice DESC";
                break;
            case "Newest":
                $orderby = "ORDER BY p.timeCreated";
                break;
            case "Relevant":
                $orderby = "";
                break;
            default:
                $orderby = "";
                break;
        }
        if (isset($category))
            $sql .= "AND p.productCategory=:category ";
        if (isset($superCategory))
            $sql .= "AND p.productSuperCategory=:superCategory ";
        $sql .= "AND (p.productName LIKE :search OR t.tagName LIKE :search)";
        if ($tags) {
            $sql .= "AND t.tagName IN( ";
            for ($j = 0; $j < sizeof($tags); $j++) {
                $sql .= ":tag$j";
                if ($j < sizeof($tags) - 1) { // na koncu odstrani vejico
                    $sql .= ",";
                }
            } 
            $sql .= ") GROUP BY productName ";
            $sql .= $orderby;
            $fetchProducts = $conn->prepare($sql);
            for ($j = 0; $j < sizeof($tags); $j++) { // za vsaki tag  binda param
                $fetchProducts->bindParam(":tag$j", $tags[$j], PDO::PARAM_STR);
            }
        } else {
            $sql .= "GROUP BY productName ";
            $sql .= $orderby;
            $fetchProducts = $conn->prepare($sql);
        }
        if (isset($category))
            $fetchProducts->bindParam(":category", $category, PDO::PARAM_STR);
        if (isset($superCategory))
            $fetchProducts->bindParam(":superCategory", $superCategory, PDO::PARAM_STR);
        $search = "%$search%";
        $fetchProducts->bindParam(":search", $search);
        $fetchProducts->execute();
        $sql = "SELECT v.color,v.size,v.stock
        FROM productvariant v,product p
        WHERE p.id = :productid AND p.id = v.productID;";
        $fetchProductVariant = $conn->prepare($sql);
        $j = 0;
        $products = array();
        while ($productRow = $fetchProducts->fetch()) { // hardcoded, ker drugace nena vredi dela
            $fetchProductVariant->execute([ // fetcha vse productVariante za posamezen id
                ":productid" => $productRow["id"],
            ]);
            $variants = array(); // definira variant array oz. ga zprazni.
            $i = 0;
            while ($variantRow = $fetchProductVariant->fetch()) // doda variant product noter v json
            {
                $variants[$i] = array(
                    "Color" => $variantRow["color"],
                    "Size" => $variantRow["size"],
                    "Stock" => $variantRow["stock"]
                );
                //var_dump($variants);
                $i++;
            }
            $tags = explode(",", $productRow["tags"]); // polje tags doda v samostojen array
            $products[$j] =  array( // napolni associativno polje z vsemi podatki o prodoktu
                "Name" => $productRow["productName"],
                "Price" => $productRow["productPrice"],
                "Category" => $productRow["productCategory"],
                "Tags" => $tags,
                "variants" => $variants,
                "ProductID" => $productRow["id"]
            );
            $j++;
        }
        echo (json_encode($products)); // vrne json od associativnega polje
        return true;
    }
    public function getFilter($conn) // dobi vsa polja, relevanta za filter (kategorije, barve, tage, najvecja cena, najmanjsa cena)
    {
        $sql = "SELECT DISTINCT p.ProductCategory, p.ProductSuperCategory
        FROM product p";
        $fetchCategories = $conn->prepare($sql);
        $fetchCategories->execute();
        $categories = array();
        $i = 0;
        while ($category = $fetchCategories->fetch()) {
            $categories[$i] = array(
                "category" => $category["ProductCategory"],
                "superCategory" => $category["ProductSuperCategory"]
            );
            $i++;
        }
        $sql = "SELECT DISTINCT tagName 
        FROM tags
        WHERE tagName IN ('black', 'white', 'gray', 'blue', 'green', 'yellow', 'red', 'pink', 'orange', 'purple', 'brown', 'beige', 'teal', 'turquoise', 'navy', 'olive', 'maroon', 'lavender', 'gold', 'silver', 'light blue');";
        $fetchColors = $conn->prepare($sql);
        $fetchColors->execute();
        $colors = array();
        for ($i = 0; $color = $fetchColors->fetch(); $i++) {
            $colors[$i] = $color["tagName"];
        }
        $sql = "SELECT MAX(productPrice) AS 'maxPrice', MIN(productPrice) AS 'minPrice' FROM product";
        $fetchPrices = $conn->prepare($sql);
        $fetchPrices->execute();
        $prices = $fetchPrices->fetchAll();
        $return = array(
            "categories" => $categories,
            "colors" => $colors,
            "maxPrice" => $prices[0]["maxPrice"],
            "minPrice" => $prices[0]["minPrice"]
        );
        echo json_encode($return);
        return true;
    }
    public function getReviews($conn, $payload)
    {
        if (!isset($payload->productID))
            return false;
        $productID = $payload->productID;
        $reviews = array();
        $sql  = "SELECT r.description,r.rating,u.id AS 'userid',u.username FROM review r, user u,product p WHERE r.userID = u.id AND r.productID = p.id AND p.id = :productID";
        $fetchReviews = $conn->prepare($sql);
        $fetchReviews->execute([ // fetcha vse productVariante za posamezen id
            ":productID" => $productID
        ]);
        $sql  = "SELECT AVG(rating) AS 'rating'FROM review r,product p WHERE r.productID = p.id AND r.id = :productID";
        $fetchRating = $conn->prepare($sql);
        $fetchRating->execute([ // fetcha vse productVariante za posamezen id
            ":productID" => $productID
        ]);
        $i = 0;
        $return = array();
        while ($reviewRow = $fetchReviews->fetch()) {
            $reviews[$i] = array( // napolni associativno polje z vsemi podatki o prodoktu
                "username" => $reviewRow["username"],
                "userid" => $reviewRow["userid"],
                "description" => $reviewRow["description"],
                "rate" => $reviewRow["rating"]
            );
            $i++;
        }
        $ratingRow = $fetchRating->fetch();
        $return = array(
            "reviews" => $reviews,
            "rating" => ($ratingRow["rating"])
        );
        echo json_encode($return);
        return true;
    }
}
