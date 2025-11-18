<?php
include("db.php");


if(isset($_GET['id'])){
    $idToUpdate = $_GET['id'];

}
else{
    exit('');
}


$query = "DELETE FROM posts WHERE id = " . $idToUpdate;

$result = mysqli_query($conn, $query);

if(!$result){
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    exit;
}
http_response_code(204);
echo 'post deleted';
?>
