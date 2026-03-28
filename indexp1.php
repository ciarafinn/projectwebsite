<?php
session_start();
require_once 'db_connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
 * Only continue if form data exists
 */
if (!isset($_POST['protein']) || !isset($_POST['taxon'])) {
    header('Location: complib1.php');
    exit();
}

/*
 * Clean input
 */
$protein = trim($_POST['protein']);
$taxon   = trim($_POST['taxon']);

if ($protein === '' || $taxon === '') {
    die("Protein family and taxonomic group are required.");
}

/*
 * Save to session
 */
$_SESSION['protein'] = $protein;
$_SESSION['taxon']   = $taxon;

/*
 * 1. Insert initial analysis request into database
 */
$stmt = $pdo->prepare("
    INSERT INTO analyses (protein_family, taxon_group, is_example)
    VALUES (?, ?, 0)
");
$stmt->execute([$protein, $taxon]);

$analysis_id = $pdo->lastInsertId();
$_SESSION['analysis_id'] = $analysis_id;

/*
 * 2. Build EDirect paths
 * Adjust these if your edirect folder is elsewhere
 */
$esearch = __DIR__ . '/edirect/esearch';
$efetch  = __DIR__ . '/edirect/efetch';

/*
 * 3. Build a safer fielded query
 * [PROT] = protein name
 * [ORGN] = organism / taxonomic group
 */
$query = $protein . '[PROT] AND ' . $taxon . '[ORGN]';
$query_escaped = escapeshellarg($query);

/*
 * 4. Run esearch first to get hit count
 */
$count_cmd = $esearch . ' -db protein -query ' . $query_escaped . ' | grep -oPm1 "(?<=<Count>)[^<]+"';
$count_output = shell_exec($count_cmd);

if ($count_output === null) {
    die("Search failed. Could not retrieve hit count from NCBI.");
}

$hit_count = (int) trim($count_output);
$_SESSION['hit_count'] = $hit_count;

/*
 * 5. Handle no results
 */
if ($hit_count === 0) {
    echo "<html><head><title>No Results</title></head><body>";
    include 'menuf1.php';
    echo "<h2>No matching proteins found</h2>";
    echo "<p>No records were found for:</p>";
    echo "<p><strong>Protein Family:</strong> " . htmlspecialchars($protein) . "</p>";
    echo "<p><strong>Taxonomic Group:</strong> " . htmlspecialchars($taxon) . "</p>";
    echo "<p>Please try a more specific or alternative search term.</p>";
    echo "</body></html>";
    exit();
}

/*
 * 6. Stop very large result sets before efetch
 * You can change this threshold
 */
$max_allowed = 500;

if ($hit_count > $max_allowed) {
    echo "<html><head><title>Too Many Results</title></head><body>";
    include 'menuf1.php';
    echo "<h2>Too many results</h2>";
    echo "<p>Your query returned <strong>" . htmlspecialchars((string)$hit_count) . "</strong> records.</p>";
    echo "<p>This is too many to fetch and analyse safely in one run.</p>";
    echo "<p>Please refine your search by using a more specific protein family or a narrower taxonomic group.</p>";
    echo "<p><strong>Protein Family:</strong> " . htmlspecialchars($protein) . "</p>";
    echo "<p><strong>Taxonomic Group:</strong> " . htmlspecialchars($taxon) . "</p>";
    echo "</body></html>";
    exit();
}

/*
 * 7. Fetch FASTA
 */
$fasta_cmd = $esearch . ' -db protein -query ' . $query_escaped . ' | ' . $efetch . ' -format fasta';
$fasta = shell_exec($fasta_cmd);

if ($fasta === null || trim($fasta) === '') {
    die("FASTA retrieval failed.");
}

/*
 * 8. Use a folder to store result files
 */
$data_dir = __DIR__ . '/userdata';

if (!is_dir($data_dir)) {
    die("userdata directory does not exist: " . htmlspecialchars($data_dir));
}

if (!is_writable($data_dir)) {
    die("userdata directory is not writable: " . htmlspecialchars($data_dir));
}

/*
 * 9. Save FASTA to file
 */
$fasta_filename = 'analysis_' . $analysis_id . '.fasta';
$fasta_path = $data_dir . '/' . $fasta_filename;

if (file_put_contents($fasta_path, $fasta) === false) {
    die("Failed to save FASTA file at: " . htmlspecialchars($fasta_path));
}
/*
 * 10. Update database with results
 * This assumes your analyses table has columns like hit_count and fasta_file
 * If not, comment this block out for now
 */
try {
    $update = $pdo->prepare("
        UPDATE analyses
        SET hit_count = ?, fasta_file = ?
        WHERE analysis_id = ?
    ");
    $update->execute([$hit_count, $fasta_filename, $analysis_id]);
} catch (PDOException $e) {
    /*
     * If those columns don't exist yet, don't crash the whole page.
     * You can remove this try/catch once your schema is ready.
     */
}

/*
 * 11. Redirect to results page
 */
header("Location: results.php?id=" . urlencode($analysis_id));
exit();
?>