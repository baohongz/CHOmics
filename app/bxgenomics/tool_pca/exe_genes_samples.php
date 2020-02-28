<?php
include_once('config.php');


if (isset($_GET['action']) && trim($_GET['action']) == 'get_gene_sample_data') {

    header('Content-Type: application/json');
    $OUTPUT['type'] = 'Error';
    $TIME           = time();

    $gene_idnames   = category_text_to_idnames($_POST['Gene_List'], 'name', 'gene', $_SESSION['SPECIES_DEFAULT']);
    $sample_indexnames = category_text_to_idnames($_POST['Sample_List'], 'name', 'sample', $_SESSION['SPECIES_DEFAULT']);

    // if (! is_array($gene_idnames) || count($gene_idnames) <= 0) {
    //     $OUTPUT['detail'] = 'No genes found. Please enter at least one gene name to continue.';
    //     echo json_encode($OUTPUT);
    //     exit();
    // }

    if (! is_array($sample_indexnames) || count($sample_indexnames) <= 0) {
        $OUTPUT['detail'] = 'No samples found. Please enter at least one sample name to continue.' ;
        echo json_encode($OUTPUT);
        exit();
    }

    $GENE_INDEXES = array_keys($gene_idnames);
    $SAMPLE_INDEXES = array_keys($sample_indexnames);

    $sql = "SELECT DISTINCT `_Platforms_ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'] . "` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` IN (?a)";
    $platforms_ids = $BXAF_MODULE_CONN -> get_col($sql, $SAMPLE_INDEXES);

    $sql = "SELECT DISTINCT `Type` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'] . "` WHERE `ID` IN (?a)";
    $platform_types = $BXAF_MODULE_CONN -> get_col($sql, $platforms_ids);

    if (! is_array($platform_types) || count($platform_types) > 1) {
        $OUTPUT['detail'] = 'Samples from different platforms are not allowed.';
        echo json_encode($OUTPUT);
        exit();
    }

    $SAMPLE_PLATFORM = array_shift($platform_types);

    $tabix_table = ($SAMPLE_PLATFORM == 'NGS') ? 'GeneFPKM' : 'GeneLevelExpression';


	ini_set('memory_limit','8G');
	$tabix_results = tabix_search_bxgenomics($GENE_INDEXES, $SAMPLE_INDEXES, $tabix_table);

// $OUTPUT['detail'] = count($tabix_results) . "<pre>" . print_r(array_slice($tabix_results, 0, 5, true), true) . "</pre>";
// echo json_encode($OUTPUT);
// exit();

    $gene_ids = array();
    foreach ($tabix_results as $t) $gene_ids[ $t['GeneIndex'] ] = '';
    $gene_idnames = category_list_to_idnames(array_keys($gene_ids), 'id', 'gene', $_SESSION['SPECIES_DEFAULT']);
    $GENE_INDEXES = array_keys($gene_idnames);

    $DATA_MATRIX = array();
    foreach($GENE_INDEXES as $geneindex) {
        $DATA_MATRIX[ $geneindex ] = array_merge(array($geneindex), array_fill(0, count($SAMPLE_INDEXES), 'NA'));
    }

    $sample_index_flip = array_flip($SAMPLE_INDEXES);
    foreach ($tabix_results as $tabix_row) {
        $sample_index_key = $sample_index_flip[$tabix_row['SampleIndex']] + 1;
        $DATA_MATRIX[ $tabix_row['GeneIndex'] ][$sample_index_key] = $tabix_row['Value'];
    }




    // Generate R Input CSV
    $dir = "{$BXAF_CONFIG['USER_FILES']['TOOL_PCA']}{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$TIME}";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $file = fopen("{$dir}/genes_samples.csv","w");
    $first_row = array_merge(array("GeneName"), array_values($sample_indexnames));
    fputcsv($file, $first_row);
    foreach ($DATA_MATRIX as $row) {
        $row[0] = $gene_idnames[ $row[0] ]; // Convert GeneIndex to GeneName
        fputcsv($file, $row);
    }
    fclose($file);

    chmod("{$dir}/genes_samples.csv", 0777);
    mkdir(dirname(__FILE__) . "/files/{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}", 0755, true);
    copy("{$dir}/genes_samples.csv", dirname(__FILE__) . "/files/{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/genes_samples.csv");


    // Generate Attributes CSV
    foreach ($BXAF_CONFIG['PCA_R_FILE_LIST'] as $file) {
        unlink("{$dir}/{$file}");
    }

    $ATTR = $_POST['attr'];
    if (is_array($ATTR) && count($ATTR) > 0) {
        $file = fopen("{$dir}/PCA_attributes.csv","w");

        // Header
        $first_row = array_merge(array("SampleName"), $ATTR);
        fputcsv($file, $first_row);

        // Main Data
        foreach ($SAMPLE_INDEXES as $key => $sample_index) {
            $row = array();
            $sql = "SELECT `Name`, `" . implode('`,`', $ATTR) . "`
                  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}`
                  WHERE `ID`={$sample_index}";

            $sample_info = $BXAF_MODULE_CONN -> get_row($sql);
            $row[] = $sample_info['Name'];
            foreach ($ATTR as $attr) {
                $row[] = $sample_info[$attr];
            }
            fputcsv($file, $row);
        }
        fclose($file);
    }


  // Generate R Script

    $RCODE  = "";
    $RCODE .= "library(FactoMineR);\n";
    $RCODE .= "library(explor);\n";
    $RCODE .= "library(missMDA);\n";
    $RCODE .= "library(limma);\n";
    $RCODE .= "setwd('{$BXAF_CONFIG['USER_FILES']['TOOL_PCA']}{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$TIME}');\n";
    $RCODE .= "data=read.csv('genes_samples.csv');\n";
    $RCODE .= "data1=data.matrix(data[, 2:ncol(data)]);\n";
    $RCODE .= "rownames(data1)=data[, 1]; \n";
    // $RCODE .= "data1=avereps(data1)\n";
    $RCODE .= "sel=rowSums(is.na(data1)) < (ncol(data1)/2); \n";
    $RCODE .= "data1c=data1[sel, ]; \n";

    $RCODE .= "sample_sel=( colSums(!is.na(data1c))>2); \n";
    $RCODE .= "cat(\"From\", ncol(data1c), \"Samples, after removing those with fewer than 2 genes,\", sum(sample_sel), \"samples left.\\n\"); \n";
    $RCODE .= "data1c=data1c[, sample_sel]; \n";
    
    $RCODE .= "Nfix=sum( rowSums(is.na(data1c))>0 )\n";
    $RCODE .= "cat(sum(sel), 'out of', nrow(data1), 'genes remain', Nfix, 'need to impute missing data.')\n";
    $RCODE .= "if (Nfix>0) {\n";
        $RCODE .= 'data1c <- imputePCA(data1c,ncp=2)$completeObs' . ";\n";
        // $RCODE .= "data1c[data1c<min(data)]=min(data);\n";
        // $RCODE .= "data1c[data1c>max(data)]=max(data);\n";

        $RCODE .= "data1c[data1c<min(data1)]=min(data1);\n";
        $RCODE .= "data1c[data1c>max(data1)]=max(data1);\n";

    $RCODE .= "}\n";

        $RCODE .= "pca <- PCA(t(data1c), graph = FALSE);\n";
        // $RCODE .= "outdir='{$BXAF_CONFIG['USER_FILES']['TOOL_PCA']}{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$TIME}';\n";
        // $RCODE .= "setwd(outdir);\n";
        $RCODE .= 'write.csv(pca$eig, "PCA_barchart.csv");' . "\n";
        $RCODE .= 'write.csv(pca$var$coord, "PCA_var.coord.csv");' . "\n";
        $RCODE .= 'write.csv(pca$var$cor, "PCA_var.cor.csv");' . "\n";
        $RCODE .= 'write.csv(pca$var$cos2, "PCA_var.cos2.csv");' . "\n";
        $RCODE .= 'write.csv(pca$var$contrib, "PCA_var.contrib.csv");' . "\n";
        $RCODE .= 'write.csv(pca$ind$coord, "PCA_ind.coord.csv");' . "\n";
        $RCODE .= 'write.csv(pca$ind$cos2, "PCA_ind.cos2.csv");' . "\n";
        $RCODE .= 'write.csv(pca$ind$contrib, "PCA_ind.contrib.csv");' . "\n";
        $RCODE .= 'write.csv(pca$call$X, "PCA_input.data.csv");' . "\n";
        $RCODE .= 'write(deparse(pca$call$call), file="PCA_command.txt");' . "\n";





    file_put_contents("{$dir}/genes_samples.R", $RCODE);
    chmod("{$dir}/genes_samples.R", 0777);


    chdir($dir);
    bxaf_execute_in_background("R CMD BATCH genes_samples.R");

    foreach ($BXAF_CONFIG['PCA_R_FILE_LIST'] as $file) {
        chmod("{$dir}/{$file}", 0755);
    }


    // Output JSON Object
    $OUTPUT['type'] = 'Success';
    $OUTPUT['time'] = $TIME;
    // $OUTPUT['data'] = $tabix_results;
    echo json_encode($OUTPUT);
    exit();
}


?>