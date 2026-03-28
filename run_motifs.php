<?php
session_start();
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

if (!file_exists($input_fasta)) {
    die("Input FASTA file not found.");
}

/*
 * Extract first sequence from FASTA
 */
$lines = file($input_fasta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($lines === false || count($lines) === 0) {
    die("Could not read FASTA file.");
}

$query_lines = [];
$found_header = false;

foreach ($lines as $line) {
    if (strlen($line) > 0 && $line[0] === '>') {
        if (!$found_header) {
            $found_header = true;
            $query_lines[] = $line;
        } else {
            break;
        }
    } else {
        if ($found_header) {
            $query_lines[] = $line;
        }
    }
}

if (!$found_header || count($query_lines) < 2) {
    die("No valid FASTA sequence found.");
}

$temp_seq_file = __DIR__ . '/userdata/motif_query_' . $analysis_id . '.fasta';
file_put_contents($temp_seq_file, implode(PHP_EOL, $query_lines) . PHP_EOL);

/*
 * Run patmatmotifs
 */
$motif_filename = 'motifs_' . $analysis_id . '.txt';
$motif_output = __DIR__ . '/userdata/' . $motif_filename;

$cmd = "patmatmotifs -sequence " . escapeshellarg($temp_seq_file) .
       " -outfile " . escapeshellarg($motif_output) . " 2>&1";

$output = shell_exec($cmd);

if (!file_exists($motif_output)) {
    die("Motif scan failed.<pre>" . htmlspecialchars((string)$output) . "</pre>");
}

/*
 * Save motif filename in database
 */
$update = $pdo->prepare("
    UPDATE analyses
    SET motif_file = ?
    WHERE analysis_id = ?
");
$update->execute([$motif_filename, $analysis_id]);

header("Location: view_motifs.php?id=" . urlencode($analysis_id));
exit();
?>