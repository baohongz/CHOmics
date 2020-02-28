<div class="w-100 my-3">
    <a href="index_genes_samples.php<?php if (isset($_GET['project_id']) && intval($_GET['project_id']) >= 0) echo "?project_id=" . intval($_GET['project_id']); ?>" class="mr-2">
      <i class="fas fa-caret-right"></i> Genes &amp; Samples Analysis
    </a>
    <a href="my_pca_results.php" class="mr-2">
        <i class="fas fa-caret-right"></i> Saved Results
    </a>
    <a href="index.php" class="mr-2">
        <i class="fas fa-caret-right"></i> PCA tool for uploaded data files
    </a>
</div>
