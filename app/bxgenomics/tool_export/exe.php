<?php
include_once("config.php");

if (isset($_GET['action']) && $_GET['action'] == 'export_genes_comparisons') {

    $species = $_SESSION['SPECIES_DEFAULT'];

    $gene_idnames = category_text_to_idnames($_POST['Gene_List'], 'name', 'gene', $species);
    $comparison_idnames = category_text_to_idnames($_POST['Comparison_List'], 'name', 'comparison', $species);


    // if (! is_array($gene_idnames) || count($gene_idnames) <= 0) {
    //     echo '<h4 class="text-danger">Error:</h4><hr /><p>No genes found.</p>';
    //     exit();
    // }
    if (! is_array($comparison_idnames) || count($comparison_idnames) <= 0) {
        echo '<h4 class="text-danger">Error</h4><p>No comparisons found.</p>';
        exit();
    }

    if (! is_array($comparison_idnames) || count($comparison_idnames) <= 0) {
        echo '<h4 class="text-danger">Error</h4><p>No comparisons found.</p>';
        exit();
    }

    if (! isset($_POST['attributes_Gene']) || ! is_array($_POST['attributes_Gene']) || count($_POST['attributes_Gene']) <= 0) {
        echo '<h4 class="text-danger">Error:</h4><hr /><p>Please select some gene attributes.</p>';
        exit();
    }
    if (! isset($_POST['attributes_Comparison']) || ! is_array($_POST['attributes_Comparison']) || count($_POST['attributes_Comparison']) <= 0) {
        echo '<h4 class="text-danger">Error:</h4><hr /><p>Please select some comparison attributes.</p>';
        exit();
    }


    // $tabix_results_public = tabix_search_records_public(array_keys($gene_idnames), array_keys($comparison_idnames), 'ComparisonData' );
    // $tabix_results_private = tabix_search_records_private(array_keys($gene_idnames), array_keys($comparison_idnames), 'ComparisonData' );
    // $tabix_results = array_merge($tabix_results_public, $tabix_results_private);

    ini_set('memory_limit','8G');
    $tabix_results = tabix_search_bxgenomics(array_keys($gene_idnames), array_keys($comparison_idnames), 'ComparisonData' );


    if (! is_array($tabix_results) || count($tabix_results) <= 0) {
        echo '<h4 class="text-danger">Error:</h4><hr /><p>No data found.</p>';
        exit();
    }

    $gene_ids = array();
    foreach ($tabix_results as $t) $gene_ids[ $t['GeneIndex'] ] = '';
    $gene_idnames = category_list_to_idnames(array_keys($gene_ids), 'id', 'gene', $species);

    $sql = "SELECT `ID`, `" . implode("`,`", $_POST['attributes_Gene']) . "` FROM ?n WHERE `ID` IN (?a) AND `Species` = '$species'";
    $genes_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_GENES'], array_keys($gene_idnames));

    $sql = "SELECT `ID`, `" . implode("`,`", $_POST['attributes_Comparison']) . "` FROM ?n WHERE `ID` IN (?a) AND `Species` = '$species'";
    $comparisons_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], array_keys($comparison_idnames));

    // Generate CSV Files
    $time = microtime(true);
    $dir = "{$BXAF_CONFIG['USER_FILES']['TOOL_EXPORT']}{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$time}";
    $url = "{$BXAF_CONFIG['USER_FILES_URL']['TOOL_EXPORT']}{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$time}";
    if(!is_dir($dir)) mkdir($dir, 0755, true);

    $data_types = array('Log2FoldChange', 'PValue', 'AdjustedPValue');

    $data = array();
    foreach ($tabix_results as $t){
        foreach ($data_types as $key){
            $data[ $key ][ $t['GeneIndex'] ][ $t['ComparisonIndex'] ] = $t[$key];
        }
    }

    $contents = array();
    foreach ($data_types as $key){
        $handle = fopen("{$dir}_{$key}.csv", "w");
        if(! $handle) continue;

        $header = array_merge($_POST['attributes_Gene'], array('Gene/Comparison Index'), array_keys($comparison_idnames));

        $row0 = array();
        foreach($_POST['attributes_Gene'] as $gene_attr){
            $row0[] = '';
        }

        // output attributes
        foreach($_POST['attributes_Comparison'] as $comparison_attr){
            $row = $row0;
            $row[] = $comparison_attr;
            foreach($comparison_idnames as $comparison_index=>$comparison_name){
                $row[] = $comparisons_info[$comparison_index][$comparison_attr];
            }
            fputcsv($handle, $row);
        }

        // output comparison names
        $row = $row0;
        $row[] = 'Comparison Name';
        foreach($comparison_idnames as $comparison_index=>$comparison_name){
            $row[] = $comparison_name;
        }
        fputcsv($handle, $row);

        // output header
        fputcsv($handle, $header);
        $num_cols = count($header);

        // output data
        foreach($gene_idnames as $gene_index=>$gene_name){
            $row = array();
            foreach($_POST['attributes_Gene'] as $gene_attr){
                $row[] = $genes_info[$gene_index][$gene_attr];
            }
            $row[] = $gene_index;

            foreach($comparison_idnames as $comparison_index=>$comparison_name){
                $row[] = $data[ $key ][ $gene_index ][ $comparison_index ];
            }
            $row = array_pad($row, $num_cols, '');
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    // Output results
    echo "<h3 class='text-success'>Download Exported CSV Files:</h3><ul>";
    foreach ($data_types as $key){
        echo "<li><a href='{$url}_{$key}.csv' target='_blank'>$key</a> (" . format_size(filesize("{$dir}_{$key}.csv")) . ")</li>";
    }
    echo "</ul>";

    exit();
}



if (isset($_GET['action']) && $_GET['action'] == 'export_genes_samples') {

    $species = $_SESSION['SPECIES_DEFAULT'];

    $gene_idnames = category_text_to_idnames($_POST['Gene_List'], 'name', 'gene', $species);


    $sample_idnames = category_text_to_idnames($_POST['Sample_List'], 'name', 'sample', $species);

    if (! is_array($sample_idnames) || count($sample_idnames) <= 0) {
        echo '<h4 class="text-danger">Error</h4><p>No samples found.</p>';
        exit();
    }

    $sample_indexes = array_keys($sample_idnames);

    $sql = "SELECT DISTINCT `_Platforms_ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'] . "` WHERE `bxafStatus` < 5 AND `ID` IN (?a)";
    $platforms_ids = $BXAF_MODULE_CONN -> get_col($sql, $sample_indexes);

    $sql = "SELECT DISTINCT `Type` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'] . "` WHERE `ID` IN (?a)";
    $platform_types = $BXAF_MODULE_CONN -> get_col($sql, $platforms_ids);

    if (! is_array($platform_types) || count($platform_types) > 1) {
        echo '<h4 class="text-danger">Error:</h4> Your samples are from different platforms. Please enter samples from either RNA-Seq or Microarray platform.';
        exit();
    }

    $platform = array_shift($platform_types);

    if($_POST['platform_type'] != '' && $_POST['platform_type'] != $platform){
        echo '<h4 class="text-danger">Error:</h4> Your selected platform type does not match the selected samples.';
        exit();
    }





    if (! isset($_POST['attributes_Gene']) || ! is_array($_POST['attributes_Gene']) || count($_POST['attributes_Gene']) <= 0) {
        echo '<h4 class="text-danger">Error:</h4><hr /><p>Please select some gene attributes.</p>';
        exit();
    }
    if (! isset($_POST['attributes_Sample']) || ! is_array($_POST['attributes_Sample']) || count($_POST['attributes_Sample']) <= 0) {
        echo '<h4 class="text-danger">Error:</h4><hr /><p>Please select some sample attributes.</p>';
        exit();
    }



    $data_table = $platform == 'NGS' ? 'GeneFPKM' : 'GeneLevelExpression';
    // $tabix_results_public = tabix_search_records_public(array_keys($gene_idnames), array_keys($sample_idnames), $data_table );
    // $tabix_results_private = tabix_search_records_private(array_keys($gene_idnames), array_keys($sample_idnames), $data_table );
    // $tabix_results = array_merge($tabix_results_public, $tabix_results_private);

    ini_set('memory_limit','8G');
    $tabix_results = tabix_search_bxgenomics(array_keys($gene_idnames), array_keys($sample_idnames), $data_table );


    if (! is_array($tabix_results) || count($tabix_results) <= 0) {
        echo '<h4 class="text-danger">Error</h4><p>No data found.</p>';
        exit();
    }

    $gene_ids = array();
    foreach ($tabix_results as $t) $gene_ids[ $t['GeneIndex'] ] = '';
    $gene_idnames = category_list_to_idnames(array_keys($gene_ids), 'id', 'gene', $species);

    $sql = "SELECT `ID`, `" . implode("`,`", $_POST['attributes_Gene']) . "` FROM ?n WHERE `ID` IN (?a) AND `Species` = '$species'";
    $genes_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_GENES'], array_keys($gene_idnames));

    $sql = "SELECT `ID`, `" . implode("`,`", $_POST['attributes_Sample']) . "` FROM ?n WHERE `ID` IN (?a) AND `Species` = '$species'";
    $samples_info = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], array_keys($sample_idnames));


    // Generate CSV Files
    $time = microtime(true);
    $dir = "{$BXAF_CONFIG['USER_FILES']['TOOL_EXPORT']}{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$time}";
    $url = "{$BXAF_CONFIG['USER_FILES_URL']['TOOL_EXPORT']}{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$time}";
    if(!is_dir($dir)) mkdir($dir, 0755, true);

    $data_types = array('Value');
    $data = array();
    foreach ($tabix_results as $t){
        foreach ($data_types as $key){
            $data[ $key ][ $t['GeneIndex'] ][ $t['SampleIndex'] ] = $t[$key];
        }
    }

    $name_suffix = ($platform == 'NGS') ? "_NGS_FPKM" : "_Array_Value";
    $names = array();
    foreach ($data_types as $n=>$key){

        $name = "Export_" . date("Y_m_d_H_i_s");
        if($key == 'Value'){
            $name .= $name_suffix . ".csv";
        }
        else {
            $name .= $name_suffix . "_{$key}.csv";
        }
        $names[$name] = $key;

        $handle = fopen("{$dir}/$name", "w");
        if(! $handle) continue;

        $header = array_merge($_POST['attributes_Gene'], array('Gene/Sample Index'), array_keys($sample_idnames));

        $row0 = array();
        foreach($_POST['attributes_Gene'] as $gene_attr){
            $row0[] = '';
        }

        // output attributes
        foreach($_POST['attributes_Sample'] as $sample_attr){
            $row = $row0;
            $row[] = $sample_attr;
            foreach($sample_idnames as $sample_index=>$sample_name){
                $row[] = $samples_info[$sample_index][$sample_attr];
            }
            fputcsv($handle, $row);
        }

        // output sample names
        $row = $row0;
        $row[] = 'Sample Name';
        foreach($sample_idnames as $sample_index=>$sample_name){
            $row[] = $sample_name;
        }
        fputcsv($handle, $row);

        // output header
        fputcsv($handle, $header);
        $num_cols = count($header);

        // output data
        foreach($gene_idnames as $gene_index=>$gene_name){
            $row = array();
            foreach($_POST['attributes_Gene'] as $gene_attr){
                $row[] = $genes_info[$gene_index][$gene_attr];
            }
            $row[] = $gene_index;
            foreach($sample_idnames as $sample_index=>$sample_name){
                $row[] = $data[ $key ][ $gene_index ][ $sample_index ];
            }
            $row = array_pad($row, $num_cols, '');
            fputcsv($handle, $row);
        }
        fclose($handle);
    }


    // Output results
    echo "<h3 class='text-success'>Download Exported CSV Files:</h3><ul>";
    foreach ($names as $name=>$key){
        echo "<li><a href='{$url}/$name' target='_blank'>Sample Data</a> ($platform " . ($platform == 'NGS' ? "FPKM Values. " : "Expression Values. ") . format_size(filesize("{$dir}/$name")) . ")</li>";
    }
    echo "</ul>";

    exit();
}

?>