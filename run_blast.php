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
 * 1. Get the analysis record
 */
$stmt = $pdo->prepare("SELECT * FROM analyses WHERE analysis_id = ?");
$stmt->execute([$analysis_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die("Analysis not found.");
}

if (empty($row['fasta_file'])) {
    die("No FASTA file is recorded for this analysis.");
}

/*
 * 2. Locate the saved FASTA file
 */
$input_fasta = __DIR__ . '/userdata/' . $row['fasta_file'];

if (!file_exists($input_fasta)) {
    die("Input FASTA file not found: " . htmlspecialchars($input_fasta));
}

/*
 * 3. Extract the first sequence only for BLAST query
 * BLASTing the whole multi-sequence FASTA against nr is too heavy.
 */
$lines = file($input_fasta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($lines === false || count($lines) === 0) {
    die("Could not read FASTA file.");
}

$query_lines = [];
$found_header = false;
$found_second_header = false;

foreach ($lines as $line) {
    if (strlen($line) > 0 && $line[0] === '>') {
        if (!$found_header) {
            $found_header = true;
            $query_lines[] = $line;
        } else {
            $found_second_header = true;
            break;
        }
    } else {
        if ($found_header) {
            $query_lines[] = $line;
        }
    }
}

if (!$found_header || count($query_lines) < 2) {
    die("No valid first FASTA sequence found.");
}

/*
 * 4. Save first sequence as a temporary BLAST query file
 */
$query_filename = 'blast_query_' . $analysis_id . '.fasta';
$query_path = __DIR__ . '/userdata/' . $query_filename;

if (file_put_contents($query_path, implode(PHP_EOL, $query_lines) . PHP_EOL) === false) {
    die("Failed to save BLAST query file.");
}

/*
 * 5. Set BLAST paths
 */
$blastp = '/localdisk/home/ubuntu-software/blast217/ncbi-blast-2.17.0+-src/c++/ReleaseMT/bin/blastp';
$blast_db = '/localdisk/home/ubuntu-software/blast217/ncbi-blast-2.17.0+-src/c++/ReleaseMT/ncbidb/nr';

/*
 * 6. Set output file
 */
$blast_filename = 'blast_' . $analysis_id . '.txt';
$blast_output = __DIR__ . '/userdata/' . $blast_filename;

/*
 * 7. Run BLASTP
 * qcovs = query coverage per subject
 * max_target_seqs = keep output manageable
 * outfmt 6 = tabular format
 */
$blast_cmd = $blastp .
    ' -query ' . escapeshellarg($query_path) .
    ' -db ' . escapeshellarg($blast_db) .
    ' -out ' . escapeshellarg($blast_output) .
    ' -max_target_seqs 20' .
    ' -evalue 1e-5' .
    ' -outfmt "6 sseqid stitle pident length mismatch gapopen qstart qend sstart send evalue bitscore qcovs"' .
    ' 2>&1';

$cmd_output = shell_exec($blast_cmd);

/*
 * 8. Check output file exists and is not empty
 */
if (!file_exists($blast_output) || filesize($blast_output) === 0) {
    die("BLAST failed or returned no output.<br><pre>" . htmlspecialchars((string)$cmd_output) . "</pre>");
}

/*
 * 9. Save BLAST filename in database
 */
$update = $pdo->prepare("
    UPDATE analyses
    SET blast_file = ?
    WHERE analysis_id = ?
");
$update->execute([$blast_filename, $analysis_id]);

/*
 * 10. Redirect to BLAST results page
 */
header("Location: view_blast.php?id=" . urlencode($analysis_id));
exit();
?>