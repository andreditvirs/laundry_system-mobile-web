<?php 
require_once "../conn.php";
$response = array("error" => FALSE);
if (isset($_POST['username'])
    && isset($_POST['password'])
    && isset($_POST['name'])
    && isset($_POST['role'])
    && isset($_POST['nik'])) {
    
    $nik = $_POST['nik'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $role = $_POST['role'];
    $address = $_POST['address'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $request = array("nik" => $nik
        , "username" => $username
        , "password" => $password
        , "name" => $name
        , "role" => $role
        , "address" => $address
        , "latitude" => $latitude
        , "longitude" => $longitude);
    if(cekNama($conn, $username) == 0 ){
      $user = registerUser($conn, $request);
      if($user){
        $response["error"] = FALSE;
        $response["user"]["username"] = $user["username"];
        echo json_encode($response);
      }else{
        $response["error"] = TRUE;
        $response["error_msg"] = "Terjadi kesalahan saat melakukan registrasi";
        echo json_encode($response);
      }
    }else{
      $response["error"] = TRUE;
      $response["error_msg"] = "User telah ada";
      echo json_encode($response);
    }
}

function registerUser($conn, $request){
    $username = escape($conn, $request["username"]);
    $password = escape($conn, $request["password"]);
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $query_text = "INSERT INTO uls_users(nik, username, password, name, role, address, latitude, longitude)
        VALUES('".$request["nik"]."'
            , '".$username."'
            , '".$password_hashed."'
            , '".$request["name"]."'
            , '".$request["role"]."'
            , '".$request["address"]."'
            , '".$request["latitude"]."'
            , '".$request["longitude"]."')";
    $user_new = mysqli_query($conn, $query_text);
    if( $user_new ) {
        $usr = "SELECT * FROM uls_users WHERE username = '".$request["username"]."'";
        $result = mysqli_query($conn, $usr);
        $user = mysqli_fetch_assoc($result);
        return $user;
    }else{
        return NULL;
    }
  }
    
  function escape($conn, $data){
    return mysqli_real_escape_string($conn, $data);
  }
    
  function cekNama($conn, $username){
      $query_text = "SELECT * FROM uls_users WHERE username = '$username'";
      if( $query = mysqli_query($conn, $query_text) ) return mysqli_num_rows($query);
  }
?>