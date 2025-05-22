<?php
$host = "localhost";
$username = "root"; 
$db_pass = ""; 
$db_name = "e_commerce"; 


function OpenCon() {
    $dbhost = "localhost";
    $dbuser = "root"; 
    $dbpass = ""; 
    $dbname = "e_commerce"; 


    $conn = @new mysqli($dbhost, $dbuser, $dbpass, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . ". Please ensure the MySQL server is running and the connection parameters are correct.");
    }

    return $conn;
}


function CloseCon($conn) { 
    $conn->close();
}
?>

