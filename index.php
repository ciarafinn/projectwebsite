<?php
session_start();

require_once 'login1.php';
require_once 'db_connect.php';

include 'redir.php';
?>

<html>
<head>
</head>
<title>Protein Analysis Website</title>
</head>

<body>

<?php include 'menuf.php'; ?>

<h1> Protein Sequence Analysis Tool</h1>

<form action="search.php" method="GET">

<label>Protein family:</label>
<input type="text" name="protein">

<br><br>

<label>Taxonomic group:</label>
<input type="text" name="taxon">

<br><br>

<input type="submit" value="Run Analysis">

</form>

</body>
</html>
