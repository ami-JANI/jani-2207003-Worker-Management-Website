<?php
include "db_connect.php";

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM workers WHERE id=$id");

header("Location: workers.php");
?>