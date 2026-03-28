<?php
session_start();
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    die("No analysis ID provided.");
}

$analysis_id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM analyses WHERE analysis_id = ?");
$stmt->execute([$analysis_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['alignment_file'])) {
    die("No alignment file found.");
}

$alignment = __DIR__ . '/userdata/' . $row['alignment_file'];
$output = __DIR__ . '/userdata/heatmap_' . $analysis_id . '.png';

$cmd = "python3 heatmap.py " .
       escapeshellarg($alignment) . " " .
       escapeshellarg($output) . " 2>&1";

exec($cmd, $out, $status);

if (!file_exists($output)) {
    die("Heatmap failed.<pre>" . htmlspecialchars(implode("\n", $out)) . "</pre>");
}

/*
 * Save to database
 */
$update = $pdo->prepare("
    UPDATE analyses SET heatmap_file = ? WHERE analysis_id = ?
");
$update->execute(['heatmap_' . $analysis_id . '.png', $analysis_id]);

header("Location: results.php?id=" . $analysis_id);
exit();
?>