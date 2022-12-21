<?php
require_once "conn.php";

$response = array("error" => FALSE);
if(isset($_GET["uuid"])){
    $uuid = $_GET["uuid"];
    $data = show($conn, $uuid);
    $response["data"] = $data;
}else{
    $data = get($conn);
    $response["data"] = $data;
}
echo json_encode($response);

function get($conn){
    $query_text = "SELECT uls_shops.uuid, uls_shops.name, uls_shops.image_url, uls_shops.open_time, uls_shops.close_time, uls_shops.address, uls_users.name AS owner_name FROM uls_shops JOIN uls_users ON uls_shops.users_id = uls_users.id";
    $query = mysqli_query($conn, $query_text);
    $data = [];
    if(mysqli_num_rows($query) > 0){
        while($result = mysqli_fetch_assoc($query)){
            array_push($data, $result);
        }
    }
    return $data;
}

function show($conn, $uuid){
    $query_text = "SELECT uls_shops.id, uls_shops.uuid, uls_shops.name, uls_shops.image_url, uls_shops.open_time, uls_shops.close_time, uls_shops.address, uls_users.name AS owner_name, uls_shops.latitude, uls_shops.longitude, uls_shops.note FROM uls_shops JOIN uls_users ON uls_shops.users_id = uls_users.id WHERE uls_shops.uuid = '".$uuid."' LIMIT 1";
    $query = mysqli_query($conn, $query_text);
    $data = mysqli_fetch_assoc($query);
    

    $query_text = "SELECT uls_service_shop.id, uls_service_shop.price, uls_services.name, uls_shops.name AS shops_name, uls_shops.image_url FROM uls_service_shop JOIN uls_shops ON uls_service_shop.shops_id = uls_shops.id JOIN uls_services ON uls_service_shop.services_id = uls_services.id WHERE uls_service_shop.shops_id = '".$data["id"]."'";
    $query = mysqli_query($conn, $query_text);
    $data_service_shop = [];
    while($result = mysqli_fetch_assoc($query)){
        array_push($data_service_shop, $result);
    }
    
    unset($data["id"]);
    $data["service_shop"] = $data_service_shop;
    return $data;
}
?>