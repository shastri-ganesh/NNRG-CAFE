<?php
include('../conn_db.php');

$username = mysqli_real_escape_string($mysqli, $_POST["username"]);
$pwd = mysqli_real_escape_string($mysqli, $_POST["pwd"]);

$query = "SELECT s_id, s_username, s_name FROM shop WHERE s_username = '$username' AND s_pwd = '$pwd' LIMIT 1";
$result = $mysqli->query($query);

if ($result->num_rows == 1) {
    $row = $result->fetch_array();
    session_start();
    $_SESSION["shop_id"] = $row["s_id"];
    $_SESSION["shop_name"] = $row["s_name"];
    $_SESSION["firstname"] = $row["s_name"];
    $_SESSION["utype"] = "SHOP_ADMIN";
    header("location: admin_food_list.php");
    exit();
}
else {
?>
<script>
    alert("Invalid shop username or password!");
    history.back();
</script>
<?php
}
?>