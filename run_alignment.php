<?php
session_start();
require_once 'db_connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['id'])) {
    die("No analysis ID provided.");
}

$analysis_id = (int) $_GET['id'];

/*
 * 1. Get analysis record
 */
$stmt = $pdo->prepare("SELECT * FROM analyses WHERE analysis_id = ?");
$stmt->execute([$analysis_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Analysis not found.");
}

if (empty($row['fasta_file'])) {
    die("No FASTA file recorded for this analysis.");
}

/*
 * 2. Define input/output files
 */
$input_fasta = __DIR__ . '/userdata/' . $row['fasta_file'];
$alignment_filename = 'alignment_' . $analysis_id . '.fasta';
$alignment_file = __DIR__ . '/userdata/' . $alignment_filename;

$conservation_filename = 'conservation_' . $analysis_id . '.png';
$conservation_file = __DIR__ . '/userdata/' . $conservation_filename;

if (!file_exists($input_fasta)) {
    die("Input FASTA file not found: " . htmlspecialchars($input_fasta));
}

/*
 * 3. Run Clustal Omega alignment
 */
$clustalo_cmd = "clustalo -i " . escapeshellarg($input_fasta) .
                " -o " . escapeshellarg($alignment_file) .
                " --force --outfmt=fasta 2>&1";

$clustalo_output = shell_exec($clustalo_cmd);

if (!file_exists($alignment_file)) {
    die("Clustal Omega failed.<br><pre>" . htmlspecialchars((string)$clustalo_output) . "</pre>");
}

/*
 * 4. Run conservation plot script
 */
$python = 'python3';
$script = __DIR__ . '/conservation_plot.py';

$conservation_cmd = $python . ' ' .
                    escapeshellarg($script) . ' ' .
                    escapeshellarg($alignment_file) . ' ' .
                    escapeshellarg($conservation_file) . ' 2>&1';

$conservation_output = shell_exec($conservation_cmd);

if (!file_exists($conservation_file)) {
    die("Conservation plot generation failed.<br><pre>" . htmlspecialchars((string)$conservation_output) . "</pre>");
}

/*
 * 5. Update database
 */
try {
    $update = $pdo->prepare("
        UPDATE analyses
        SET alignment_file = ?, conservation_plot = ?
        WHERE analysis_id = ?
    ");
    $update->execute([$alignment_filename, $conservation_filename, $analysis_id]);
} catch (PDOException $e) {
    die("Database update failed: " . htmlspecialchars($e->getMessage()));
}

/*
 * 6. Redirect to results page
 */
header("Location: results.php?id=" . urlencode($analysis_id));
exit();
?>