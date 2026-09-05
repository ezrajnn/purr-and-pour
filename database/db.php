<?php 
 
$host = "localhost"; 
$db   = "purr-and-pour"; 
$user = "root"; 
$pass = ""; 
 
$conn = new mysqli($host, $user, $pass, $db, 3306); 
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}
 
$conn->set_charset("utf8mb4");     

?>  