<?php

include_once( dirname(__DIR__) . '/config/config.php');


function sort_zscore_absolute_value($a,$b){
    $a_val = abs($a['z-score']);
    $b_val = abs($b['z-score']);
    if ($a_val == $b_val) return 0;
    return ($a_val < $b_val) ? 1 : -1;
}

function sort_logP_absolute_value($a,$b){
    $a_val = abs($a['logP']);
    $b_val = abs($b['logP']);
    if ($a_val == $b_val) return 0;
    return ($a_val < $b_val) ? 1 : -1;
}

// -----------------------------------------------------------------------
// Example output of get_page_data()
// -----------------------------------------------------------------------
// $OUTPUT = array(
//     array(
//       'name'        => 'V$MEF2_01',
//       'gene-number' => 124,
//       'z-score'     => 0.8734,
//       'p-value'     => 0.1783,
//       'FDR'         => 0.7444,
//     ),
//     array(
//       'name'        => 'V$CETS1P54_01',
//       'gene-number' => 234,
//       'z-score'     => 0.2431,
//       'p-value'     => 0.1055,
//       'FDR'         => 0.6092,
//     ),
//     ...
// );


function get_page_data($comparison_index, $selected_genesets=false) {

    global $BXAF_CONFIG, $BXAF_MODULE_CONN;

    $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` = ?i";
    $comparison_info = $BXAF_MODULE_CONN -> get_row($sql, intval($comparison_index) );

    if(! is_array($comparison_info) || count($comparison_info) <= 0) return false;

    $species = $comparison_info['Species'];
    $comparison_index = trim($comparison_info['ID']);

    $page_file = $BXAF_CONFIG['PAGE_OUTPUT'][strtoupper($species)] . "comparison_{$comparison_index}_GSEA.PAGE.csv";

    if (!file_exists($page_file)) {
        return false;
    }

    $OUTPUT   = array();
    $directions = array('Up', 'Down');

    // Read PAGE result data
    $handle = fopen($page_file, "r");
    while(!feof($handle)) {

        $row = fgetcsv($handle);
        if (!is_array($row) || count($row) <= 1) continue;
        if (trim($row[0]) == 'Name') continue;

        // If GeneSets are defined, filter data
        if (is_array($selected_genesets) && count($selected_genesets) > 0) {
            if (in_array(trim($row[0]), $selected_genesets)) {
                $OUTPUT[] = array(
                    'name'         => $row[0],
                    'gene-number'  => $row[1],
                    'z-score'      => $row[2],
                    'p-value'      => $row[3],
                    'FDR'          => $row[4],
                );
            }
        }

        else {
            $OUTPUT[] = array(
                'name'         => $row[0],
                'gene-number'  => $row[1],
                'z-score'      => $row[2],
                'p-value'      => $row[3],
                'FDR'          => $row[4],
            );
        }
    }
    fclose($handle);

    // If GeneSets are defined, reorder it.
    if (is_array($selected_genesets) && count($selected_genesets) >= 0) {
        $OUTPUT_NEW = array();
        foreach ($selected_genesets as $geneset) {
            foreach ($OUTPUT as $row) {
                if ($row['name'] == $geneset) {
                    $OUTPUT_NEW[] = $row;
                }
            }
        }
        $OUTPUT = $OUTPUT_NEW;
    }

    return $OUTPUT;
}






function get_homer_data($comparison_index, $filename_type, $selected_pathways=false) {

    global $BXAF_CONFIG, $BXAF_MODULE_CONN;

    $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `Species` = '" . $_SESSION['SPECIES_DEFAULT'] . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` = ?i";
    $comparison_info = $BXAF_MODULE_CONN -> get_row($sql, intval($comparison_index) );

    if(! is_array($comparison_info) || count($comparison_info) <= 0) return false;

    $species = $comparison_info['Species'];
    $comparison_index = trim($comparison_info['ID']);

    $filename_types = array(
        'Biological Process'      => 'biological_process',
        'Cellular Component'      => 'cellular_component',
        'Molecular Function'      => 'molecular_function',
        'KEGG'                    => 'kegg',
        'Molecular Signature'     => 'msigdb',
        'Interpro Protein Domain' => 'interpro',
        'Wiki Pathway'            => 'wikipathways',
        'Reactome'                => 'reactome'
    );
    if(! array_key_exists($filename_type, $filename_types)) return false;

    $filename = $filename_types[$filename_type] . '.txt';

    $OUTPUT   = array();

    $dir_base = $BXAF_CONFIG['GO_OUTPUT'][strtoupper($species)] . "comp_{$comparison_index}/comp_{$comparison_index}_GO_Analysis_";

    $directions = array('Up', 'Down');

    foreach ($directions as $direction) {
        $OUTPUT[$direction] = array();

        if (!file_exists($dir_base . $direction . "/" . $filename)) {
            continue;
        }

        // Read HOMER result data
        $handle = fopen($dir_base . $direction . "/" . $filename, "r");
        while(!feof($handle)) {

            $row = fgetcsv($handle, 0, "\t");

            if (!is_array($row) || count($row) <= 1) continue;
            if (trim($row[0]) == 'TermID') continue;

            // If Pathways are defined, filter data
            if (is_array($selected_pathways) && count($selected_pathways) > 0 && array_key_exists($direction, $selected_pathways) && is_array( $selected_pathways[$direction] )) {
                if (in_array( trim($row[1]), $selected_pathways[$direction]) ) {
                    $OUTPUT[$direction][] = array(
                        'pathway_id'      => $row[0],
                        'pathway_name'    => $row[1],
                        'logP'            => $row[3],
                        'gene_number'     => $row[5],
                        'gene_names'      => $row[10],
                    );
                }
            }

            else {
                $OUTPUT[$direction][] = array(
                    'pathway_id'      => $row[0],
                    'pathway_name'    => $row[1],
                    'logP'            => $row[3],
                    'gene_number'     => $row[5],
                    'gene_names'      => $row[10],
                );
            }
        }
        fclose($handle);
    }


    // If Pathways are defined, reorder it.
    if (is_array($selected_pathways) && count($selected_pathways) > 0) {

        $OUTPUT_NEW = array();
        foreach ($directions as $direction) {
            if (! array_key_exists($direction, $selected_pathways) || ! is_array( $selected_pathways[$direction] ) ) continue;

            foreach ($selected_pathways[$direction] as $pathway) {
                $found = false;
                foreach ($OUTPUT[$direction] as $row) {
                    if ($row['pathway_name'] == $pathway && $found == false) {
                        $OUTPUT_NEW[$direction][] = $row;
                        $found = true;
                    }
                }
                if ($found == false) {
                    $OUTPUT_NEW[$direction][] = array(
                        'pathway_id'      => '',
                        'pathway_name'    => $pathway,
                        'logP'            => 0,
                        'gene_number'     => 0,
                        'gene_names'      => '',
                    );
                }
            }
        }
        $OUTPUT = $OUTPUT_NEW;
    }

    return $OUTPUT;
}

?>