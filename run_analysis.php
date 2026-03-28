<?php
session_start();
require_once 'login1.php';
require_once 'db_connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['id'])) {
    die("No analysis ID provided.");
}

$analysis_id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM analyses WHERE analysis_id = ?");
$stmt->execute([$analysis_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Analysis not found.");
}

if (empty($row['fasta_file'])) {
    die("No FASTA file recorded for this analysis.");
}

$input_fasta = __DIR__ . '/userdata/' . $row['fasta_file'];
$aligned_filename = 'alignment_' . $analysis_id . '.fasta';
$aligned_file = __DIR__ . '/userdata/' . $aligned_filename;

if (!file_exists($input_fasta)) {
    die("Input FASTA file does not exist: " . htmlspecialchars($input_fasta));
}

$clustalo_cmd = "clustalo -i " . escapeshellarg($input_fasta) .
                " -o " . escapeshellarg($aligned_file) .
                " --force --outfmt=fasta 2>&1";

$output = shell_exec($clustalo_cmd);

if (!file_exists($aligned_file)) {
    die("Clustal Omega failed.<br><pre>" . htmlspecialchars($output) . "</pre>");
}

try {
    $update = $pdo->prepare("
        UPDATE analyses
        SET alignment_file = ?
        WHERE analysis_id = ?
    ");
    $update->execute([$aligned_filename, $analysis_id]);
} catch (PDOException $e) {
    die("Database update failed: " . htmlspecialchars($e->getMessage()));
}

header("Location: results.php?id=" . urlencode($analysis_id));
exit();
?>