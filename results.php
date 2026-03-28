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

$fasta_file = $row['fasta_file'] ?? '';
$fasta_path = __DIR__ . '/userdata/' . $fasta_file;

echo "<html><head><title>Results</title></head><body>";
include 'menuf1.php';

echo "<h2>Analysis Results</h2>";
echo "<p><strong>Analysis ID:</strong> " . htmlspecialchars((string)$analysis_id) . "</p>";
echo "<p><strong>Protein Family:</strong> " . htmlspecialchars($row['protein_family']) . "</p>";
echo "<p><strong>Taxonomic Group:</strong> " . htmlspecialchars($row['taxon_group']) . "</p>";

if (isset($row['hit_count'])) {
    echo "<p><strong>Hit Count:</strong> " . htmlspecialchars((string)$row['hit_count']) . "</p>";
}

if (!empty($row['alignment_file'])) {
    $alignment_path = __DIR__ . '/userdata/' . $row['alignment_file'];
    if (file_exists($alignment_path)) {
        $alignment = file_get_contents($alignment_path);
        echo "<h2>Multiple Sequence Alignment</h2>";
        echo "<pre>" . htmlspecialchars(substr($alignment, 0, 8000)) . "</pre>";
    }
}

if (!empty($row['conservation_plot'])) {
    $plot_path = __DIR__ . '/userdata/' . $row['conservation_plot'];
    if (file_exists($plot_path)) {
        echo "<h2>Conservation Plot</h2>";
        echo "<img src='userdata/" . htmlspecialchars($row['conservation_plot']) . "' alt='Conservation plot' width='900'>";
        echo "<p>
        Highly conserved regions appear as peaks close to 1, while lower values indicate more variable regions across the aligned sequences.
        </p>";
    }
}

if ($fasta_file && file_exists($fasta_path)) {
    $fasta = file_get_contents($fasta_path);
    $fasta_lines = explode("\n", $fasta);

    // Extract the first two sequences and the rest
    $first_two_sequences = '';
    $remaining_sequences = '';
    $sequence_count = 0;
    $is_remaining = false;

    foreach ($fasta_lines as $line) {
        if (strpos($line, '>') === 0) { // Header line
            $sequence_count++;
            if ($sequence_count > 2) {
                $is_remaining = true;
            }
        }

        if ($is_remaining) {
            $remaining_sequences .= htmlspecialchars($line) . "\n";
        } else {
            $first_two_sequences .= htmlspecialchars($line) . "\n";
        }
    }

    echo "<h3>FASTA Preview</h3>";
    echo "<pre>" . $first_two_sequences . "</pre>";
    echo "<button id='toggleFasta' onclick='toggleFasta()'>Show More</button>";
    echo "<pre id='remainingFasta' style='display:none;'>" . $remaining_sequences . "</pre>";
} else {
    echo "<p>No FASTA file found.</p>";
}

// Add JavaScript for toggling
echo "<script>
function toggleFasta() {
    var remainingFasta = document.getElementById('remainingFasta');
    var toggleButton = document.getElementById('toggleFasta');

    if (remainingFasta.style.display === 'none') {
        remainingFasta.style.display = 'block';
        toggleButton.textContent = 'Show Less';
    } else {
        remainingFasta.style.display = 'none';
        toggleButton.textContent = 'Show More';
    }
}
</script>";
if (!empty($row['length_plot'])) {
    $length_plot_path = __DIR__ . '/userdata/' . $row['length_plot'];

    if (file_exists($length_plot_path)) {
        echo "<h2>Sequence Length Distribution</h2>";
        echo "<img src='userdata/" . htmlspecialchars($row['length_plot']) . "' alt='Sequence length plot' width='800'>";
        echo "<p>
        This plot shows the distribution of protein sequence lengths in the retrieved dataset.
        Similar lengths across sequences suggest structural conservation, while strong outliers may indicate
        partial sequences, annotation differences, or distinct isoforms.
        </p>";
    } else {
        echo "<p>Length plot file recorded in the database but not found on disk.</p>";
    }
}
if (!empty($row['heatmap_file'])) {
    echo "<h2>Sequence Similarity Heatmap</h2>";
    echo "<img src='userdata/" . htmlspecialchars($row['heatmap_file']) . "' width='900'>";
}
echo "</body></html>";
?>