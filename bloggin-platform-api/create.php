<?php
include("db.php");

echo file_get_contents("php://input");
$data = json_decode(file_get_contents("php://input"), true);


if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No JSON received"]);
    exit;
}

$title = $data["title"];
$content = $data["content"];
$category = $data["category"];
$tags = json_encode($data["tags"]); // convertir array a JSON
$createAt = date("Y-m-d H:i:s");
$updateAt = date("Y-m-d H:i:s");

$query = "INSERT INTO posts (title, content, category, tags, createdAt, updatedAt)
          VALUES ('$title', '$content', '$category', '$tags', '$createAt', '$updateAt')";

$result = mysqli_query($conn, $query);

if(!$result){
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    exit;
}


$id = mysqli_insert_id($conn);
http_response_code(201);
echo json_encode([
    "id"        => $id,
    "title"     => $data["title"],
    "content"   => $data["content"],
    "category"  => $data["category"],
    "tags"      => $data["tags"],   // como array, no el JSON string
    "createdAt" => $createAt,
    "updatedAt" => $updateAt,
]);

?>
