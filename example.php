<?php
session_start();
require_once 'db_connect.php';

$example_id = 11;
?>

<html>
<head>
  <title>Example Dataset</title>
</head>
<body>

<?php include 'menuf1.php'; ?>

<h1>Example Dataset</h1>

<p><strong>Protein family:</strong> glucose-6-phosphatase</p>
<p><strong>Taxonomic group:</strong> Aves</p>
<p><strong>Analysis ID:</strong> <?php echo htmlspecialchars((string)$example_id); ?></p>

<h2>Dataset Overview</h2>
<p>
This example dataset demonstrates the full workflow of the protein analysis website
using a precomputed search for glucose-6-phosphatase proteins in birds.
</p>

<h2>Example Outputs</h2>

<p><a href="results.php?id=<?php echo urlencode($example_id); ?>">View retrieved sequences</a></p>

<p><a href="run_alignment.php?id=<?php echo urlencode($example_id); ?>">Run alignment</a></p>
<p><a href="view_alignment.php?id=<?php echo urlencode($example_id); ?>">View alignment</a></p>

<p><a href="run_motifs.php?id=<?php echo urlencode($example_id); ?>">Run PROSITE motif scan</a></p>
<p><a href="view_motifs.php?id=<?php echo urlencode($example_id); ?>">View motif results</a></p>

<p><a href="run_conservation.php?id=<?php echo urlencode($example_id); ?>">Run conservation analysis</a></p>
<p><a href="view_conservation.php?id=<?php echo urlencode($example_id); ?>">View conservation results</a></p>
<p><a href="run_motifs.php?id=11">Run PROSITE motif scan</a></p>
<p><a href="view_motifs.php?id=11">View PROSITE motif results</a></p>
<p><a href="results.php?id=11">View example dataset results</a></p>
</body>
</html>