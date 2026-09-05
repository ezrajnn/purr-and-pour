<?php 
 
$host = "localhost"; 
$db   = "purr_and_pour"; 
$user = "root"; 
$pass = ""; 
 
$conn = new mysqli($host, $user, $pass, $db, 3304); 
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
} else { 
    echo "Connected successfully"; 
} 
 
$conn->set_charset("utf8mb4");     

?>  