<?php
error_reporting(E_ERROR | E_PARSE);
require_once "conn.php";

$response = array("error" => FALSE);
if(isset($_GET["username"])){
    $username = $_GET["username"];
    $data = get($conn, $username);
    $response["user"]["username"] = $username;
    $response["data"] = $data;
}

if(((isset($_POST["username"]) && isset($_POST["uuid"])) || (isset($_POST["id"])))
    && isset($_POST["status"])){
    
    $id = $_POST["id"]; // transaksi
    $username = $_POST["username"]; // user
    $uuid = $_POST["uuid"]; // shop
    $status = $_POST["status"];
    $services_name = $_POST["services_name"];
    switch ($status){
        case 'request': {
            $data = request($conn, $uuid, $username, $services_name);
            $response["data"]["id"] = $data["id"];
            $response["msg"] = "Cucian tersampaikan ke Laundry ".$data["laundry_name"].". Silahkan tunggu dijemput ya!";
            break;
        }
        case 'confirm_pick_ok': {
            $request["pick_estimated_time"] = $_POST["time"];

            $data = confirmPickOk($conn, $id, $request);
            $response["data"] = $data;
            $response["msg"] = "Cucian terkonfirmasi siap dijemput. Jangan lupa jemput cucian jam ".date('H:i', strtotime($data["time"]))." hari ".date('l', strtotime($data["time"])).", ya!";
            break;
        }
        case 'confirm_pick_reject': {
            $data = confirmPickReject($conn, $id);
            $response["data"] = $data;
            $response["msg"] = "Cucian ditolak";
            break;
        }
        case 'picked': {
            confirmPicked($conn, $id);
            $response["msg"] = "Cucian tercatat sudah dijemput!";
            break;
        }
        case 'verify_picked': {
            $request["weight"] = $_POST["weight"];
            $request["evidence"] = $_FILES["evidence"];
            $request["deliver_estimated_time"] = $_POST["time"];

            $data = verifyPicked($conn, $id, $request);
            $response["data"] = $data;
            $response["msg"] = "Cucian terkonfirmasi dengan berat ".$data["weight"]." Kg. Silahkan memproses cucian dan mengantarkannya jam ".date('H:i', strtotime($data["time"]))." hari ".date('l', strtotime($data["time"])).", ya!";
            break;
        }
        case 'delivered': {
            confirmDelivered($conn, $id);
            $response["msg"] = "Cucian tercatat sudah diantar!";
            break;
        }
        case 'verify_delivered': { // jika tidak verify, akan dianggap otomatis menjadi verify setelah ganti hari
            $request["comment"] = $_POST["comment"];
            $data = verifyDelivered($conn, $id, $request);

            $response["data"] = $data;
            $response["msg"] = "Terima kasih, telah menggunakan jasa cuci di ".$data["shops_name"]."!";
            break;
        }
    }
}
echo json_encode($response);

function get($conn, $username){
    $query_text_user = "SELECT id FROM uls_users WHERE username = '".$username."'";
    $query_user = mysqli_query($conn, $query_text_user);
    $data_user = mysqli_fetch_assoc($query_user);

    $query_text = "SELECT id, code, deliver_estimated_time, pick_estimated_time, picked_time, price, weight, evidence, status, service_shop_id FROM uls_transactions WHERE users_id = '".$data_user["id"]."'";
    $query = mysqli_query($conn, $query_text);
    $transactions = [];
    while($data = mysqli_fetch_assoc($query)){
        $query_text_service_shop = "SELECT price, uls_services.name AS service_name, uls_shops.name AS shop_name FROM uls_service_shop JOIN uls_services ON uls_service_shop.services_id = uls_services.id JOIN uls_shops ON uls_service_shop.shops_id = uls_shops.id WHERE uls_service_shop.id = '".$data["service_shop_id"]."'";
        $query_service_shop = mysqli_query($conn, $query_text_service_shop);
        $data_service_shop = mysqli_fetch_assoc($query_service_shop);
        $data["service_shop"] = $data_service_shop;

        unset($data["service_shop_id"]);
        array_push($transactions, $data);
    }
    return $transactions;
}

function request($conn, $uuid, $username, $services_name){
    $query_text = "SELECT id FROM uls_users WHERE username = '".$username."' LIMIT 1";
    $query = mysqli_query($conn, $query_text);
    $user = mysqli_fetch_assoc($query);

    $query_text = "SELECT id, name FROM uls_shops WHERE uuid = '".$uuid."' LIMIT 1";
    $query = mysqli_query($conn, $query_text);
    $shop = mysqli_fetch_assoc($query);

    $query_text = "SELECT uls_service_shop.id FROM uls_service_shop JOIN uls_shops ON uls_shops.id = '".$shop["id"]."' JOIN uls_services ON uls_service_shop.services_id = uls_services.id WHERE uls_services.name = '".$services_name."' LIMIT 1";
    $query = mysqli_query($conn, $query_text);
    $service_shop = mysqli_fetch_assoc($query);

    $query_text = "INSERT INTO uls_transactions(status, users_id, shops_id, service_shop_id)
        VALUES ('request', '".$user["id"]."', '".$shop["id"]."', '".$service_shop["id"]."')";
    $query = mysqli_query($conn, $query_text);
  
    $data["id"] = mysqli_insert_id($conn);
    $data["laundry_name"] = $shop["name"];
    return $data;
}

function confirmPickOk($conn, $id, $request){
    $query_text = "UPDATE uls_transactions SET status = 'confirm_pick_ok', pick_estimated_time = '".$request["pick_estimated_time"]."' WHERE id = '".$id."'";
    mysqli_query($conn, $query_text);
    
    $data["id"] = $id;
    $data["time"] = $request["pick_estimated_time"];
    return $data;
}

function confirmPickReject($conn, $id){
    $query_text = "UPDATE uls_transactions SET status = 'confirm_pick_reject' WHERE id = '".$id."'";
    mysqli_query($conn, $query_text);
    
    $data["id"] = $id;
    return $data;
}

function confirmPicked($conn, $id){
    $query_text = "UPDATE uls_transactions SET status = 'picked', picked_time = '".date('Y-m-d H:i:s')."' WHERE id = '".$id."'";
    mysqli_query($conn, $query_text);
    
    $data["id"] = $id;
    return $data;
}

function verifyPicked($conn, $id, $request){
    $query_text = "SELECT uls_transactions.users_id, uls_transactions.shops_id, uls_transactions.service_shop_id, uls_service_shop.price, uls_shops.name AS shops_name
        FROM uls_transactions
        JOIN uls_service_shop ON uls_transactions.service_shop_id = uls_service_shop.id
        JOIN uls_shops ON uls_transactions.shops_id = uls_shops.id
        WHERE uls_transactions.id = '".$id."' LIMIT 1";
    $query = mysqli_query($conn, $query_text);
    if(mysqli_num_rows($query) > 0){
        $data_transactions = mysqli_fetch_assoc($query);
        $total_price = intval($data_transactions["price"]) * intval($request["weight"]);
        $nota = strtoupper(getAcronym($data_transactions["shops_name"])."/".date('Y')."/".date('m')."/".date('d')."/000".$id);
        
        $target_dir = "uploads/";
        $name = $request["evidence"]["name"];
        $ext = end((explode(".", $name)));
        $evidence_url = $target_dir.uniqid(date("Y_m_d")."_".$id."_").".".$ext;
        move_uploaded_file($request["evidence"]["tmp_name"], $evidence_url);

        $query_text = "UPDATE uls_transactions SET status = 'verify_picked', price = '".$total_price."', weight = '".$request["weight"]."', code = '".$nota."', evidence = '".$evidence_url."', deliver_estimated_time = '".$request["deliver_estimated_time"]."' WHERE id = '".$id."'";
        mysqli_query($conn, $query_text);
        
        $data["id"] = $id;
        $data["total_price"] = $total_price;
        $data["weight"] = $request["weight"];
        $data["time"] = $request["deliver_estimated_time"];
        return $data;
    }
}

function confirmDelivered($conn, $id){
    $query_text = "UPDATE uls_transactions SET status = 'delivered', delivered_time = '".date('Y-m-d H:i:s')."' WHERE id = '".$id."'";
    mysqli_query($conn, $query_text);
    
    $data["id"] = $id;
    return $data;
}

function verifyDelivered($conn, $id, $request){
    $query_text = "SELECT uls_transactions.users_id, uls_transactions.shops_id, uls_transactions.service_shop_id, uls_service_shop.price, uls_shops.name AS shops_name
        FROM uls_transactions
        JOIN uls_service_shop ON uls_transactions.service_shop_id = uls_service_shop.id
        JOIN uls_shops ON uls_transactions.shops_id = uls_shops.id
        WHERE uls_transactions.id = '".$id."' LIMIT 1";
    $query = mysqli_query($conn, $query_text);
    if(mysqli_num_rows($query) > 0){
        $data_transactions = mysqli_fetch_assoc($query);
        
        $query_text = "UPDATE uls_transactions SET status = 'verify_delivered', comment = '".$request["comment"]."', comment_time = '".date('Y-m-d H:i:s')."' WHERE id = '".$id."'";
        mysqli_query($conn, $query_text);
        
        $data["id"] = $id;
        $data["shops_name"] = $data_transactions["shops_name"];
        return $data;
    }
}

function getAcronym($str){
    $acronym = '';
    $word = '';
    $words = preg_split("/(\s|\-|\.)/", $str);
    foreach($words as $w) {
        $acronym .= substr($w,0,1);
    }
    $word = $word . $acronym ;
    return $word;
}
?>