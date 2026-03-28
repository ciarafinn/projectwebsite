<?php
require_once "login1.php";

$dsn = 
"mysql:host=$host;dbname=$db;charset=$charset";
$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
   ];
   try{
    $pdo = new PDO($dsn, $username, $password, $options);
    }
    catch (PDOException $e) {
    exit('Database connection failed: ' . $e->getMessage());
}

?>