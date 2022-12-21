<?php
require_once "../conn.php";
$response = array("error" => FALSE);
if (isset($_POST['username']) && isset($_POST['password'])){
    $username = $_POST["username"];
    $password = $_POST["password"];

    $query_text = "SELECT id, password FROM uls_users WHERE username = '".$username."' LIMIT 1";
    $query = mysqli_query($conn, $query_text);
    $user = mysqli_fetch_assoc($query);
    $is_password_valid = password_verify($password, $user["password"]);
    if($user && $is_password_valid){
        $query_text = "UPDATE uls_users SET last_login_at = '".date('Y-m-d H:i:s')."' WHERE id = '".$user["id"]."'";
        $query = mysqli_query($conn, $query_text);
        
        $query_text = "SELECT name, nik, username, role, address, latitude, longitude FROM uls_users WHERE username = '".$username."' LIMIT 1";
        $query = mysqli_query($conn, $query_text);
        $user = mysqli_fetch_assoc($query);
        $response["user"] = $user;
    }else{
        $response["error"] = TRUE;
        $response["error_msg"] = "Username/password tidak benar, silahkan mencoba kembali";
    }
}
echo json_encode($response);
?>