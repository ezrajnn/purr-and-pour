<?php 

$host = "localhost"; 
$db   = "purr_and_pour"; 
$user = "root"; 
$pass = ""; 
 
//db url, db_username, db_pass, database name
$conn=mysqli_connect("localhost", "root", "", "purr_and_pour");
if (!$conn){
    echo "No connection to the database";
    die;
}

?>