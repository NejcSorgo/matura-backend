<?php
class catalog
{

    public function getCatalog($conn) // vrne json seznam productov in productVariantov *todo: vrne samo baseproduct in preveri ce je katerikoli od variantProductov in stock.*
    { // WIP
        $sql = "SELECT p.productName, p.productPrice, p.productCategory,p.id,p.description, GROUP_CONCAT(t.tagName SEPARATOR ', ') AS 'tags' FROM product p, tags t,tagtoproduct tp WHERE p.id = tp.productID AND t.id = tp.TagID GROUP BY productName; ";
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
                
                "Tags" => $tags,
                "variants" => $variants,
                "ProductID" => $productRow["id"]
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
    public function searchProductFilter($conn, $payload) // fixed by gpt :)
    {
        $search = $payload->search;
        $tags = $payload->filter;
        $category = $payload->category;
        $superCategory = $payload->superCategory;
        $sql = "SELECT p.productName, p.productPrice, p.productCategory,p.id,p.productSuperCategory, p.description, GROUP_CONCAT(t.tagName SEPARATOR ', ') AS 'tags'
        FROM product p, tags t, tagtoproduct tp
        WHERE p.id = tp.productID AND t.id = tp.TagID AND p.productCategory=:category AND p.productSuperCategory=:superCategory AND p.productName LIKE \"$search\" AND t.tagName IN(";

        for ($j = 0; $j < sizeof($tags); $j++) {
            $sql .= ":tag$j";
            if ($j < sizeof($tags) - 1) { // na koncu odstrani vejico
                $sql .= ",";
            }
        }

        $sql .= ") GROUP BY productName;";
        $fetchProducts = $conn->prepare($sql);
        for ($j = 0; $j < sizeof($tags); $j++) {
            $fetchProducts->bindParam(":tag$j", $tags[$j], PDO::PARAM_STR);
        }
        $fetchProducts->execute([
            ":category"  => $category,
            ":superCategory" => $superCategory
        ]);
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
    public function getCategories($conn){
        $sql = "SELECT p.category
        FROM product p";
         $fetchCategories = $conn->prepare($sql);
         $fetchCategories->execute();
         $categories = array();
         $i = 0;
         while ($category = $fetchCategories->fetch()){
            $categories[$i] = $category["productCategory"];
            $i++;
         }  
         echo json_encode($categories);
    }
}
