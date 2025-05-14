<?php

require_once "../db_conn.php";
include("../function/response.php"); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   

$id = $_POST['id'];

global $conn;
$sql = "
        SELECT  name, description, image
        FROM destination WHERE id = '$id'
    "; 
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $name = $row['name'];
        $description = $row['description'];
        $image = 'http://localhost/trippartner/uploads/' . $row['image'];

        $response = [
            "locationName" => $name,
            "locationDescription" => $description,
            "locationImage" => $image
     
        ];
        api_success_response($response);
    } 


    mysqli_close($conn);
}


?>