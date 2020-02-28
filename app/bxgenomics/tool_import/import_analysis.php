<?php
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;
include_once("config.php");



$analysis_id = 0;
$analysis_id_encrypted = '';
if(isset($_GET['id']) && intval($_GET['id']) > 0){
    $analysis_id = intval($_GET['id']);
    $analysis_id_encrypted = $analysis_id . '_' . bxaf_encrypt($analysis_id, $BXAF_CONFIG['BXAF_KEY']);
}
else if (isset($_GET['analysis']) && trim($_GET['analysis']) != '') {
  $analysis_id_encrypted = trim($_GET['analysis']);
  $analysis_id = intval(array_shift(explode('_', $analysis_id_encrypted)));
}
if($analysis_id <= 0) die("No analysis id is provided.");

$sql = "SELECT `ID` FROM ?n WHERE `bxafStatus` < 5 AND `_Owner_ID`={$BXAF_CONFIG['BXAF_USER_CONTACT_ID']} AND `_Analysis_ID`= ?i";
$found_id = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $analysis_id);
if ($found_id != '') {
    die("This analysis has already been imported as <a href='../project.php?id=$found_id'>this project</a>. Please click the link to review details and delete this project if needed.");
}


$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']}` WHERE `ID` = ?i ";
$analysis_info = $BXAF_MODULE_CONN -> get_row($sql, $analysis_id);

$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']}` WHERE `ID` = ?i ";
$experiment_info = $BXAF_MODULE_CONN -> get_row($sql, $analysis_info['Experiment_ID']);




// Add Project record
$time = date("Y-m-d H:i:s");
$species = array_shift(explode(' ', $analysis_info['Species']));
$platform_list = array('Human' => 1, 'Mouse' => 2 );
$platform_id = $platform_list[$species];
$sql = "SELECT * FROM ?n WHERE `bxafStatus` < 5 AND ?n = ?i";
$platform_info = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], 'ID', $platform_id);

$projects_id = 0;
$projects_name = $experiment_info['Name'] . ' - ' . $analysis_info['Name'];
$projects_info = array(
    'Species'       => $species,
    '_Platforms_ID' => $platform_id,
    'Platform'      => $platform_info['GEO_Accession'],
    'PlatformName'  => $platform_info['Name'],

    'Name'  => $projects_name,
    'Description'  => $experiment_info['Description'],
    'Disease'  => 'Unknown',
    '_Analysis_ID'  => $analysis_id, // Analysis ID
    'ExperimentType'  => 'BxGenomics Analysis',
    'WebLink'  => $BXAF_CONFIG['BXGENOMICS_URL'] . '/analysis.php?id=' . $analysis_id,
    'Comment'  => 'Created from BxGenomics Analysis',

    'ContactName'   => $_SESSION['User_Info']['Name'],
    'ContactEmail'  => $_SESSION['User_Info']['Email'],

    '_Owner_ID'     => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
    'Time_Created'  => $time
);
// echo "<pre>" . print_r($projects_info, true) . "</pre>";

// Check exists
$sql = "SELECT `ID` FROM ?n WHERE `bxafStatus` < 5 AND `_Owner_ID`={$BXAF_CONFIG['BXAF_USER_CONTACT_ID']} AND `Name`= ?s";
$found_id = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $projects_name);
if ($found_id != '') {
    $projects_id = intval($found_id);
    $BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $projects_info, "`ID`=" . $projects_id);
}
else {
    $projects_id = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $projects_info);
}
echo "Project is created or updated<BR>";



$DIR = $BXAF_CONFIG['USER_FILES']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $projects_id . '/';
if(file_exists($DIR)) shell_exec("rm -rf {$DIR}");
mkdir($DIR, 0755, true);
$URL = $BXAF_CONFIG['USER_FILES_URL']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $projects_id . '/';
echo "Import Data DIR: $DIR<BR>";




// Add Samples
$sql = "SELECT * FROM ?n WHERE `bxafStatus` < 5 AND `ID` IN (" . $analysis_info['Samples'] . ")";
$all_sample_info = $BXAF_MODULE_CONN -> get_all($sql, $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE']);
// ID, Name, Description, Treatment_Name, Data_Type

$TBL_BXGENOMICS_SAMPLES_fields = $BXAF_MODULE_CONN -> get_field_names($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']);

$sample_name_ids = array();
foreach($all_sample_info as $sample_info){

    $samples_info = array(
        'Species'       => $species,
        '_Platforms_ID' => $platform_id,
        '_Projects_ID'  => $projects_id,
        '_Samples_ID'   => $sample_info['ID'], // Analysis Sample ID

        'Name'          => $sample_info['Name'],
        'Description'   => $sample_info['Description'],

        'SampleIndex'   => $sample_info['ID'], // Analysis Sample ID

        'Tissue'        => '',
        'Treatment'     => $sample_info['Treatment_Name'],
        'DiseaseState'  => '',
        'Collection'  => 'BxGenomics Analysis',
        'Gender'        => '',
        'SampleType'    => $sample_info['Data_Type'],

        '_Owner_ID'     => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
        'Time_Created'  => $time
    );

    $s_info = unserialize($sample_info['Custom_Field1']);
    if(is_array($s_info) && count($s_info) > 0){
        foreach($s_info as $f=>$v){
            if($f != 'ID' && in_array($f, $TBL_BXGENOMICS_SAMPLES_fields) && ! array_key_exists($f, $samples_info)) $samples_info[$f] = $v;
        }
    }

    // Check exists
    $sql = "SELECT `ID` FROM ?n WHERE `bxafStatus` < 5 AND `_Projects_ID`= ?i AND `Name`= ?s";
    $found_id = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], $projects_id, $sample_info['Name']);
    if ($found_id != '') {
        $samples_id = intval($found_id);
        $BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], $samples_info, "`ID`=" . $samples_id);
    }
    else {
        $samples_id = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], $samples_info);
    }
    $sample_name_ids[ $sample_info['Name'] ] = $samples_id;
}
echo "Samples are created or updated<BR>";



// Add Sample Expression data

$file_expression_data = $DIR . 'file_expression_data.csv';
$file_expression_data_handle = fopen($file_expression_data, "w");
fputcsv( $file_expression_data_handle, array('GeneIndex', 'SampleIndex', 'GeneName', 'SampleName', 'Value'), "\t" );

$rpkm_annot_file = "{$BXAF_CONFIG['ANALYSIS_DIR']}{$analysis_id_encrypted}/alignment/rpkm_annot.csv";

$gene_info_GeneIndex = array();
$gene_info_GeneName = array();

if(file_exists($rpkm_annot_file) && ($handle = fopen($rpkm_annot_file, "r")) !== FALSE){
    $skipped_rows = array();
    $genes_not_found = array();

    // Read the first row: header
    if (($row = fgetcsv($handle)) !== FALSE) {

        $first_row = $row;
        $first_row_flip = array_flip($first_row);

        $n = 0;
        // Read data rows
        while (($row = fgetcsv($handle)) !== FALSE) {

            $n++;
            if(count($row) != count($first_row)){ $skipped_rows[] = $n;  continue;}

            $gene_name = $row[0];

            if(! array_key_exists($gene_name, $gene_info_GeneIndex)){

                // Search for gene
                $sql = "SELECT `GeneIndex`, `GeneName` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = '$species' AND `Name`= ?s";
                $gene_info = $BXAF_MODULE_CONN -> get_row($sql, $gene_name );

                if (! is_array($gene_info) || count($gene_info) <= 0) {
                    $genes_not_found[$gene_name] = 1;
                    continue;
                }

                $gene_info_GeneIndex[ $gene_name ] = $gene_info['GeneIndex'];
                $gene_info_GeneName[ $gene_name  ] = $gene_info['GeneName'];

            }

            foreach($sample_name_ids as $sample_name=>$sample_index){
                $info = array($gene_info_GeneIndex[ $gene_name ], $sample_index, $gene_info_GeneName[ $gene_name  ], $sample_name, $row[ $first_row_flip[$sample_name] ]);
                fputcsv($file_expression_data_handle, $info, "\t");
            }
        }
    }
    else {
        die("<BR>Error: File rpkm_annot.csv is empty: $deg_file<BR>");
    }
    fclose($handle);
    fclose($file_expression_data_handle);
}
else {
    fclose($file_expression_data_handle);
    die("<BR>Error: File rpkm_annot.csv is not found: $rpkm_annot_file<BR>");
}




// Add Comparisons

$analysis_Step_Detail = unserialize($analysis_info['Step_Detail']);
$step3_details = $analysis_Step_Detail[3];
if(array_key_exists('Samples Used', $search)){
    $analysis_samples_used = explode('<br>', ltrim($step3_details['Samples Used'], '<br>') );
}
else {
    $step3_details = array_pop($step3_details);
    $analysis_samples_used = explode('<br>', ltrim($step3_details['Samples Used'], '<br>') );
}

$treatment_array = array();
foreach($analysis_samples_used as $r){
    $r = explode('(', rtrim($r, ')' ) );
    $treatment_array[$r[0]] = explode(', ', $r[1]);
}


$file_comparison_data = $DIR . 'file_comparison_data.csv';
$file_comparison_data_handle = fopen($file_comparison_data, "w");
fputcsv($file_comparison_data_handle, array('GeneIndex', 'ComparisonIndex', 'GeneName', 'ComparisonName', 'Log2FoldChange', 'PValue', 'AdjustedPValue'), "\t" );

$comparison_file_all = array();

$analysis_comparisons = unserialize($analysis_info['Comparisons']);

$comparison_files = array();
$genes_not_found = array();

foreach($analysis_comparisons as $comparison_name){

    $analysis_comparison_treatments = explode('.vs.', $comparison_name);
    $Case_SampleIDs = implode(',', $treatment_array[$analysis_comparison_treatments[0]]);
    $Control_SampleIDs = implode(',', $treatment_array[$analysis_comparison_treatments[1]]);

    $comparisons_info = array(
        'Species'       => $species,
        '_Platforms_ID' => $platform_id,
        '_Projects_ID'  => $projects_id,
        '_Analysis_ID'   => $analysis_id,

        'Name'          => $comparison_name,
        'Description'   => '',

        'Case_SampleIDs'        => $Case_SampleIDs,
        'Control_SampleIDs'     => $Control_SampleIDs,
        'ComparisonCategory'    => '',
        'ComparisonContrast'    => '',
        'Case_DiseaseState'     => 'Unknown Disease',
        'Case_Tissue'           => 'Unknown Tissue',
        'Case_Ethnicity'        => 'Unknown Ethnicity',
        'Case_Gender'           => 'Unknown Gender',
        'Case_SampleSource'     => 'Unknown Sample Source',
        'Case_CellType'         => 'Unknown Cell Type',
        'Case_Treatment'        => 'Unknown Treatment',

        '_Owner_ID'     => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
        'Time_Created'  => $time
    );

    // Check exists
    $comparisons_id = 0;
    $sql = "SELECT `ID` FROM ?n WHERE `bxafStatus` < 5 AND `_Projects_ID`= ?i AND `Name`= ?s";
    $found_id = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], $projects_id, $comparison_name);
    if ($found_id != '') {
        $comparisons_id = intval($found_id);
        $BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], $comparisons_info, "`ID`=" . $comparisons_id);
    }
    else {
        $comparisons_id = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], $comparisons_info);
    }

    // Add comparison data
    $deg_file = "{$BXAF_CONFIG['ANALYSIS_DIR']}{$analysis_id_encrypted}/alignment/DEG/{$comparison_name}/Overview/{$comparison_name}_alldata.csv";
    // if(! file_exists($deg_file)) $deg_file = "{$BXAF_CONFIG['ANALYSIS_DIR']}{$analysis_id_encrypted}/alignment/DEG/{$comparison_name}/DEG_Analysis/{$comparison_name}_DEG_low.csv";

    if(file_exists($deg_file) && ($handle = fopen($deg_file, "r")) !== FALSE){

        // Read the first row: header
        if (($row = fgetcsv($handle)) !== FALSE) {

            $comparison_file = $DIR . 'comp_' . $comparisons_id . '.csv';
            $comparison_file_all[$comparisons_id] = $comparison_file;

            $comparison_file_handle = fopen($comparison_file, "w");
            fputcsv($comparison_file_handle, array('GeneName', 'Log2FoldChange', 'PValue', 'AdjustedPValue'));

            $first_row = $row;
            $first_row_flip = array_flip($first_row);

            $column_Ensembl = 0;
            $sample_columns = array_slice($first_row_flip, 1, $first_row_flip['AveExpr'] - 1, true);
            $column_Log2FoldChange = $first_row_flip['logFC'];
            $column_PValue = $first_row_flip['P.Value'];
            $column_AdjustedPValue = $first_row_flip['adj.P.Val'];

            $n = 0;
            // Read data rows
            while (($row = fgetcsv($handle)) !== FALSE) {
                $n++;
                if(count($row) != count($first_row)){ $skipped_rows[] = $n; continue;}

                $gene_name = $row[0];

                if(! array_key_exists($gene_name, $gene_info_GeneIndex)){

                    // Search for gene
                    $sql = "SELECT `GeneIndex`, `GeneName` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = '$species' AND `Name`= ?s";
                    $gene_info = $BXAF_MODULE_CONN -> get_row($sql, $gene_name );

                    if (! is_array($gene_info) || count($gene_info) <= 0) {
                        $genes_not_found[$gene_name] = 1;
                        continue;
                    }

                    $gene_info_GeneIndex[ $gene_name ] = $gene_info['GeneIndex'];
                    $gene_info_GeneName[ $gene_name  ] = $gene_info['GeneName'];

                }

                $info = array($gene_info_GeneName[ $gene_name  ], $row[$column_Log2FoldChange], $row[$column_PValue], $row[$column_AdjustedPValue]);
                fputcsv($comparison_file_handle, $info);

                $info = array($gene_info_GeneIndex[ $gene_name ], $comparisons_id, $gene_info_GeneName[ $gene_name  ], $comparison_name, $row[$column_Log2FoldChange], $row[$column_PValue], $row[$column_AdjustedPValue]);
                fputcsv($file_comparison_data_handle, $info, "\t");

            }
            fclose($comparison_file_handle);
        }
        else {
            // die("<BR>Error: DEG file is empty: $deg_file<BR>");
        }
        fclose($handle);

    }
    else {
        fclose($file_comparison_data_handle);
        // die("<BR>Error: DEG file is not found: $deg_file<BR>");
    }
}
fclose($file_comparison_data_handle);

// ksort($genes_not_found);
// echo "<BR><BR>Missing genes in TBL_BXGENOMICS_GENES_INDEX from $species<pre>" . print_r(array_keys($genes_not_found), true) . "</pre>";


//
// // Add Comparisons
//
// $analysis_Step_Detail = unserialize($analysis_info['Step_Detail']);
// $step3_details = $analysis_Step_Detail[3];
// if(array_key_exists('Samples Used', $search)){
//     $analysis_samples_used = explode('<br>', ltrim($step3_details['Samples Used'], '<br>') );
// }
// else {
//     $step3_details = array_pop($step3_details);
//     $analysis_samples_used = explode('<br>', ltrim($step3_details['Samples Used'], '<br>') );
// }
//
// $treatment_array = array();
// foreach($analysis_samples_used as $r){
//     $r = explode('(', rtrim($r, ')' ) );
//     $treatment_array[$r[0]] = explode(', ', $r[1]);
// }
// // echo "treatment_array<pre>" . print_r($treatment_array, true) . "</pre>";
//
//
// $file_comparison_data = $DIR . 'file_comparison_data.csv';
// $file_comparison_data_handle = fopen($file_comparison_data, "w");
// fputcsv($file_comparison_data_handle, array('GeneIndex', 'ComparisonIndex', 'GeneName', 'ComparisonName', 'Log2FoldChange', 'PValue', 'AdjustedPValue'), "\t" );
//
// $file_expression_data = $DIR . 'file_expression_data.csv';
// $file_expression_data_handle = fopen($file_expression_data, "w");
// fputcsv( $file_expression_data_handle, array('GeneIndex', 'SampleIndex', 'GeneName', 'SampleName', 'Value'), "\t" );
//
// $comparison_file_all = array();
//
// $analysis_comparisons = unserialize($analysis_info['Comparisons']);
// foreach($analysis_comparisons as $comparison_name){
//
//     $analysis_comparison_treatments = explode('.vs.', $comparison_name);
//     $Case_SampleIDs = implode(',', $treatment_array[$analysis_comparison_treatments[0]]);
//     $Control_SampleIDs = implode(',', $treatment_array[$analysis_comparison_treatments[1]]);
//
//     $comparisons_info = array(
//         'Species'       => $species,
//         '_Platforms_ID' => $platform_id,
//         '_Projects_ID'  => $projects_id,
//         '_Analysis_ID'   => $analysis_id,
//
//         'Name'          => $comparison_name,
//         'Description'   => '',
//
//         'Case_SampleIDs'        => $Case_SampleIDs,
//         'Control_SampleIDs'     => $Control_SampleIDs,
//         'ComparisonCategory'    => '',
//         'ComparisonContrast'    => '',
//         'Case_DiseaseState'     => 'Unknown Disease',
//         'Case_Tissue'           => 'Unknown Tissue',
//         'Case_Ethnicity'        => 'Unknown Ethnicity',
//         'Case_Gender'           => 'Unknown Gender',
//         'Case_SampleSource'     => 'Unknown Sample Source',
//         'Case_CellType'         => 'Unknown Cell Type',
//         'Case_Treatment'        => 'Unknown Treatment',
//
//         '_Owner_ID'     => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
//         'Time_Created'  => $time
//     );
//     // echo "<pre>" . print_r($comparisons_info, true) . "</pre>";
//
//     // Check exists
//     $comparisons_id = 0;
//     $sql = "SELECT `ID` FROM ?n WHERE `bxafStatus` < 5 AND `_Projects_ID`= ?i AND `Name`= ?s";
//     $found_id = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], $projects_id, $comparison_name);
//     if ($found_id != '') {
//         $comparisons_id = intval($found_id);
//         $BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], $comparisons_info, "`ID`=" . $comparisons_id);
//     }
//     else {
//         $comparisons_id = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], $comparisons_info);
//     }
//
//     // Add comparison data
//     $deg_file = "{$BXAF_CONFIG['ANALYSIS_DIR']}{$analysis_id_encrypted}/alignment/DEG/{$comparison_name}/Overview/{$comparison_name}_alldata.csv";
//     // $deg_file = "{$BXAF_CONFIG['ANALYSIS_DIR']}{$analysis_id_encrypted}/alignment/DEG/{$comparison_name}/DEG_Analysis/{$comparison_name}_DEG.csv";
//     // if(! file_exists($deg_file)) $deg_file = "{$BXAF_CONFIG['ANALYSIS_DIR']}{$analysis_id_encrypted}/alignment/DEG/{$comparison_name}/DEG_Analysis/{$comparison_name}_DEG_low.csv";
//
//     if(file_exists($deg_file) && ($handle = fopen($deg_file, "r")) !== FALSE){
//
//         // Read the first row: header
//         if (($row = fgetcsv($handle)) !== FALSE) {
//
//             $comparison_file = $DIR . 'comp_' . $comparisons_id . '.csv';
//             $comparison_file_all[$comparisons_id] = $comparison_file;
//
//             $comparison_file_handle = fopen($comparison_file, "w");
//             fputcsv($comparison_file_handle, array('GeneName', 'Log2FoldChange', 'PValue', 'AdjustedPValue'));
//
//             $first_row = $row;
//             $first_row_flip = array_flip($first_row);
//
//             $column_Ensembl = 0;
//             $sample_columns = array_slice($first_row_flip, 1, $first_row_flip['AveExpr'] - 1, true);
//             $column_Log2FoldChange = $first_row_flip['logFC'];
//             $column_PValue = $first_row_flip['P.Value'];
//             $column_AdjustedPValue = $first_row_flip['adj.P.Val'];
//
//             // Read data rows
//             while (($row = fgetcsv($handle)) !== FALSE) {
//
//                 if(count($row) != count($first_row))  continue;
//
//                 $gene_name = $row[0];
//
//                 // Search for gene
//                 $sql = "SELECT `GeneIndex`, `GeneName` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = '$species' AND `Name`= ?s";
//                 $gene_info = $BXAF_MODULE_CONN -> get_row($sql, $gene_name );
//
//                 if (! is_array($gene_info) || count($gene_info) <= 0) {
//                     continue;
//                 }
//
//                 $GeneIndex = $gene_info['GeneIndex'];
//                 $GeneName = $gene_info['GeneName'];
//
//                 $info = array($gene_info['GeneName'], $row[$column_Log2FoldChange], $row[$column_PValue], $row[$column_AdjustedPValue]);
//                 fputcsv($comparison_file_handle, $info);
//
//                 $info = array($gene_info['GeneIndex'], $comparisons_id, $gene_info['GeneName'], $comparison_name, $row[$column_Log2FoldChange], $row[$column_PValue], $row[$column_AdjustedPValue]);
//                 fputcsv($file_comparison_data_handle, $info, "\t");
//
//                 foreach($sample_columns as $sample_name=>$sample_column_number){
//                     $info = array($gene_info['GeneIndex'], $sample_name_ids[$sample_name], $gene_info['GeneName'], $sample_name, $row[ $sample_column_number ]);
//                     fputcsv($file_expression_data_handle, $info, "\t");
//                 }
//             }
//             fclose($comparison_file_handle);
//         }
//         fclose($handle);
//     }
// }
// fclose($file_comparison_data_handle);
// fclose($file_expression_data_handle);

echo "Comparisons are created or updated<BR>";





$batch_commands = "";

// Add tabix commands here
$DIR_Tabix = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$projects_id}/";
if(file_exists($DIR_Tabix)) shell_exec("rm -rf {$DIR_Tabix}");
mkdir($DIR_Tabix, 0755, true);

$target_file = $DIR_Tabix . "comparison_data.txt";
if(file_exists($target_file)){
    unlink($target_file);
}
copy($file_comparison_data, $target_file);

$batch_commands .= "cd $DIR_Tabix\n";
$batch_commands .= $BXAF_CONFIG['TAIL_BIN'] . " -n +2 comparison_data.txt | " . $BXAF_CONFIG['SORT_BIN'] . " -k1,1n -k2,2n | " . $BXAF_CONFIG['BGZIP_BIN'] . " > comparison_data.txt.gz\n";
$batch_commands .= $BXAF_CONFIG['TABIX_BIN'] . " -s 1 -b 2 -e 2 -0 comparison_data.txt.gz\n";
$batch_commands .= $BXAF_CONFIG['TAIL_BIN'] . " -n +2 comparison_data.txt | " . $BXAF_CONFIG['SORT_BIN'] . " -k2,2n -k1,1n | " . $BXAF_CONFIG['BGZIP_BIN'] . " > comparison_data.txt.comparison.gz\n";
$batch_commands .= $BXAF_CONFIG['TABIX_BIN'] . " -s 2 -b 1 -e 1 -0 comparison_data.txt.comparison.gz\n\n";

$data_type = 'ngs';
$target_file = $DIR_Tabix . "{$data_type}_expression_data.txt";
if(file_exists($target_file)){
    unlink($target_file);
}
copy($file_expression_data, $target_file);

$batch_commands .= $BXAF_CONFIG['TAIL_BIN'] . " -n +2 {$data_type}_expression_data.txt | " . $BXAF_CONFIG['SORT_BIN'] . " -k1,1n -k2,2n | " . $BXAF_CONFIG['BGZIP_BIN'] . " > {$data_type}_expression_data.txt.gz\n";
$batch_commands .= $BXAF_CONFIG['TABIX_BIN'] . " -s 1 -b 2 -e 2 -0 {$data_type}_expression_data.txt.gz\n";
$batch_commands .= $BXAF_CONFIG['TAIL_BIN'] . " -n +2 {$data_type}_expression_data.txt | " . $BXAF_CONFIG['SORT_BIN'] . " -k2,2n -k1,1n | " . $BXAF_CONFIG['BGZIP_BIN'] . " > {$data_type}_expression_data.txt.sample.gz\n";
$batch_commands .= $BXAF_CONFIG['TABIX_BIN'] . " -s 2 -b 1 -e 1 -0 {$data_type}_expression_data.txt.sample.gz\n\n";


foreach($comparison_file_all as $comp_id=>$comp_file){

    // Output comparison data to a folder
    $dir_go_output = $BXAF_CONFIG['GO_OUTPUT'][strtoupper($species)] . 'comp_' . $comp_id;
    if(!file_exists($dir_go_output)) mkdir($dir_go_output, 0755, true);

    $target_file = "$dir_go_output/comp_{$comp_id}.csv";
    if(file_exists($target_file)){
        // Remove old comp_id files
        shell_exec("rm -rf $dir_go_output/*");
    }
    copy($comp_file, $target_file);

    // GO and PAGE Analysis
    $batch_commands .= "cd $dir_go_output\n";

    $suffix = strtolower($species);
    if($suffix == 'mouse') $suffix .= " " . $BXAF_CONFIG['Mouse_gmt_file'] . " auto";
    else $suffix .= " " . $BXAF_CONFIG['Human_gmt_file'] . " auto";

    $batch_commands .= "Rscript " . dirname(__DIR__) . "/analysis_scripts/Process.Internal.Comparison.R comp_{$comp_id}.csv $suffix\n";
    $batch_commands .= "mv $dir_go_output/PAGE_comp_{$comp_id}.csv " . $BXAF_CONFIG['PAGE_OUTPUT'][strtoupper($species)] . "comparison_{$comp_id}_GSEA.PAGE.csv \n\n";

}

if($batch_commands != ''){

    $batch_commands = "#!/usr/bin/bash\n\n" . $batch_commands;

    $command = $DIR . "import_batch.bash";
    file_put_contents($command, $batch_commands);
    chmod($command, 0775);

    $info = array(
        'Command' => $command,
        'Dir' => $DIR,
        'Log_File' => $DIR . "import_batch.log",
        '_Owner_ID' => intval($BXAF_CONFIG['BXAF_USER_CONTACT_ID'])
    );
    $insert_id = $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info);
    run_process_in_order();
}

echo "Batch processing of comparison and expression data is scheduled and will run in the background.<BR>";


echo "<BR><a href='" . $BXAF_CONFIG['BXGENOMICS_URL'] . "'>Go back to Home Page</a>";


?>