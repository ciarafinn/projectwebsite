<?php
session_start();
require_once 'login1.php';
require_once 'db_connect.php';
?>
<html>
<head>
  <title>Previous Analyses</title>
</head>
<body>
<?php include 'menuf1.php'; ?>
<h1> Previous Analyses</h1>
<?php
$query = 'Select * from analyses'; 
$stmt = $pdo->query($query);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(count($rows) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Analysis ID</th><th>Protein Family</th><th>Taxonomic Group</th><th>Date</th></tr>";
    foreach($rows as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['analysis_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['protein_family']) . "</td>";
        echo "<td>" . htmlspecialchars($row['taxon_group']) . "</td>";
        echo "<td>". htmlspecialchars($row["is_example"]) . "</td>";
        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
        echo "<td><a href='results.php?id=11" . urlencode($row['analysis_id']) . "'>Open</a></td>";
        echo "<td><a href='run_analysis.php?id=" . urlencode($row['analysis_id']) . "'>Run</a></td>";
        echo "<td><a href='view_alignment.php?id=" . urlencode($row['analysis_id']) . "'>Alignment</a></td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "<p>No previous analyses found.</p>";
}
?>

</body>
</html>