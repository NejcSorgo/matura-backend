<?php
require_once "connect.php";

$payload = new stdClass();
$payload->search = "bruh";
$payload->tags = ["winter", "coat"];
$payload->superCategory = "men";
//$patload->category = "Shirts";

if ($payload->search) {
    $search = $payload->search;
} else {
    $search = "";
}

$tags = $payload->tags;
$category = "Shirts";
$superCategory = $payload->superCategory;

$sql = "SELECT p.productName, p.productPrice, p.productCategory, p.id, p.productSuperCategory, p.description, GROUP_CONCAT(t.tagName SEPARATOR ', ') AS 'tags'
FROM product p, tags t, tagtoproduct tp
WHERE p.id = tp.productID AND t.id = tp.TagID AND p.productCategory = :category AND p.productSuperCategory = :superCategory AND (p.productName LIKE :search OR t.tagName LIKE :search) AND t.tagName IN (";

for ($j = 0; $j < sizeof($tags); $j++) {
    $sql .= ":tag$j";
    if ($j < sizeof($tags) - 1) {
        $sql .= ",";
    }
}

$sql .= ") GROUP BY productName;";

$fetchProducts = $conn->prepare($sql);

for ($j = 0; $j < sizeof($tags); $j++) {
    $fetchProducts->bindParam(":tag$j", $tags[$j], PDO::PARAM_STR);
}


$fetchProducts->execute([
    ":category" => $category,
    ":superCategory" => $superCategory,
]);

$sql = "SELECT v.color, v.size, v.stock
FROM productvariant v, product p
WHERE p.id = :productid AND p.id = v.productID;";

$fetchProductVariant = $conn->prepare($sql);
$j = 0;
$products = array();

while ($productRow = $fetchProducts->fetch()) {
    $fetchProductVariant->execute([
        ":productid" => $productRow["id"],
    ]);

    $variants = array();
    $i = 0;

    while ($variantRow = $fetchProductVariant->fetch()) {
        $variants[$i] = array(
            "Color" => $variantRow["color"],
            "Size" => $variantRow["size"],
            "Stock" => $variantRow["stock"]
        );

        $i++;
    }

    $tags = explode(",", $productRow["tags"]);

    $products[$j] = array(
        "Name" => $productRow["productName"],
        "Price" => $productRow["productPrice"],
        "Category" => $productRow["productCategory"],
        "Tags" => $tags,
        "variants" => $variants,
    "ProductID" => $productRow["id"]
);

$j++;
}

echo (json_encode($products));