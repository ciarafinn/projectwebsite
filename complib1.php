<?php
session_start();
require_once 'login1.php';
require_once 'db_connect.php';
?>

<html>
<head>
  <title>Protein Analysis Website</title>
</head>

<body>

<?php include 'menuf1.php'; ?>

<h1> Protein Sequence Analysis Tool</h1>

<script>
   function validate(form) {
       let fail = ""; ////check ok let fail 
       if (form.protein.value == "") fail += "Must give protein family. ";
       if (form.taxon.value == "") fail += "Must give taxonomic group. ";
       if (fail == "") return true;
       else {
           alert(fail);
           return false;
       }
   }


</script>
<p>
Please enter a protein family and taxonomic group for sequence retrieval
</p>
<form action="indexp1.php" method="post" onSubmit="return validate(this)">
  <pre>
       Protein Name <input type="text" id="protein" name="protein" value="" />
       Taxon Name <input type="text" id="taxon" name="taxon" value="" />

                   <input type="submit" value="Run Analysis" />
                   <p> Try out the example dataset to see how the analysis works! </p>
                   <button type="button" onclick="location.href='example.php'">Use Example Dataset</button>
  </pre>
</form>

</body>
</html>
