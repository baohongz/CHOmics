<?php
include_once('config/config.php');



// Delete Sample or Comparison
if (isset($_GET['action']) && $_GET['action'] == 'delete_record') {

   $TYPE = trim($_POST['type']); // sample, comparison
   $ROWID = intval($_POST['rowid']);

   // 1. Delete Original Record
   if ($TYPE == 'sample') {
     $table      = $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'];
     $table_data = $BXAF_CONFIG['TBL_BXGENOMICS_GENEFPKM'];
     $data_col   = '_Samples_ID';
   } else {
     $table      = $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'];
     $table_data = $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONDATA'];
     $data_col   = '_Comparisons_ID';
   }
   $sql = "DELETE FROM `{$table}` WHERE `ID`={$ROWID}";
   $BXAF_MODULE_CONN -> Execute($sql);

   // 2. Delete Related Data
   $sql = "DELETE FROM `{$table_data}` WHERE `{$data_col}`={$ROWID}";
   $BXAF_MODULE_CONN -> Execute($sql);

   exit();
 }



// Delete Project and related Samples, Comparisons, and Expression data
if (isset($_GET['action']) && $_GET['action'] == 'delete_project') {

    $project_id = intval($_POST['rowid']);

    $sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID`={$project_id} ";
    $project_id = intval( $BXAF_MODULE_CONN -> get_one($sql) );

    if ($project_id <= 0) {
    	echo "<h3 class='text-danger'>Error</h3><div class='my-3'>You do not have permission to delete this project.</div>";
    	exit();
    }

    $action_type = $_POST['action_type'];

    if($action_type == 'delete_project'){

        $sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID`={$project_id} ";
        $Samples_IDs = $BXAF_MODULE_CONN -> get_col($sql);

        $sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID`={$project_id} ";
        $Comparisons_IDs = $BXAF_MODULE_CONN -> get_col($sql);

        $records_deleted = array();
        $records_deleted['TBL_BXGENOMICS_PROJECTS'] = 0;
        $records_deleted['TBL_BXGENOMICS_SAMPLES'] = 0;
        $records_deleted['TBL_BXGENOMICS_COMPARISONS'] = 0;

        if(is_array($Comparisons_IDs) && count($Comparisons_IDs) > 0){

            // Delete TBL_BXGENOMICS_COMPARISONS
            $sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID` = ?i";
            $count = $BXAF_MODULE_CONN -> get_one($sql, $project_id);
            if($count > 0){
                $sql = "DELETE FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID` = ?i";
                $BXAF_MODULE_CONN -> execute($sql, $project_id);
                $records_deleted['TBL_BXGENOMICS_COMPARISONS'] = $count;
            }


            foreach($Comparisons_IDs as $id){
                //Delete GO Analysis Results
                $dir = $BXAF_CONFIG['GO_OUTPUT']['HUMAN'] . "comp_$id";
                if(file_exists($dir)) shell_exec("rm -rf {$dir}");
                $dir = $BXAF_CONFIG['GO_OUTPUT']['MOUSE'] . "comp_$id";
                if(file_exists($dir)) shell_exec("rm -rf {$dir}");

                //Delete PAGE Results
                $file = $BXAF_CONFIG['PAGE_OUTPUT']['HUMAN'] . "comparison_{$id}_GSEA.PAGE.csv";
                if(file_exists($file)) unlink($file);
                $file = $BXAF_CONFIG['PAGE_OUTPUT']['MOUSE'] . "comparison_{$id}_GSEA.PAGE.csv";
                if(file_exists($file)) unlink($file);
            }

        }

        if(is_array($Samples_IDs) && count($Samples_IDs) > 0){

            // Delete TBL_BXGENOMICS_SAMPLES
            $sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID` = ?i";
            $count = $BXAF_MODULE_CONN -> get_one($sql, $project_id);
            if($count > 0){
                $sql = "DELETE FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID` = ?i";
                $BXAF_MODULE_CONN -> execute($sql, $project_id);
                $records_deleted['TBL_BXGENOMICS_SAMPLES'] = $count;
            }

        }

        // Delete custom uploaded file in tabix
        $DIR_Tabix = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/";
        shell_exec("rm -rf {$DIR_Tabix}comparison_data.*");

        // Delete custom uploaded file in tabix
        $DIR_Tabix = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/";
        shell_exec("rm -rf {$DIR_Tabix}ngs_expression_data.*");
        shell_exec("rm -rf {$DIR_Tabix}array_expression_data.*");


        // Delete TBL_BXGENOMICS_PROJECTS
        $sql = "DELETE FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = ?i";
        $BXAF_MODULE_CONN -> execute($sql, $project_id);
        $records_deleted['TBL_BXGENOMICS_PROJECTS'] = 1;

        echo "<h3 class='text-danger'>Database Records Deleted</h3>";

        echo "<ul class='my-3'>";

        echo "<li>Projects deleted: " . $records_deleted['TBL_BXGENOMICS_PROJECTS'] . "</li>";
        echo "<li>Samples deleted: " . $records_deleted['TBL_BXGENOMICS_SAMPLES'] . "</li>";
        echo "<li>Comparisons deleted: " . $records_deleted['TBL_BXGENOMICS_COMPARISONS'] . "</li>";
        echo "<li>Comparison data records deleted: " . $records_deleted['TBL_BXGENOMICS_COMPARISONDATA'] . "</li>";
        echo "<li>RNA-Seq data records deleted: " . $records_deleted['TBL_BXGENOMICS_GENEFPKM'] . "</li>";
        echo "<li>Gene level expression data records deleted: " . $records_deleted['TBL_BXGENOMICS_GENELEVELEXPRESSION'] . "</li>";

        echo "</ul>";

    }
    else if($action_type == 'delete_sample_data'){

        $DIR_Tabix = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/";

        // Delete custom uploaded file in tabix
        $DIR_Tabix = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/";
        shell_exec("rm -rf {$DIR_Tabix}ngs_expression_data.*");
        shell_exec("rm -rf {$DIR_Tabix}array_expression_data.*");

        echo "<h3><i class='fas fa-exclamation-triangle'></i> Notice</h3><div class='my-3 text-success'>Sample data in current project has been deleted.</div>";

    }
    else if($action_type == 'delete_comparison_data'){

        $sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `_Projects_ID`={$project_id} ";
        $Comparisons_IDs = $BXAF_MODULE_CONN -> get_col($sql);

        foreach($Comparisons_IDs as $id){
            //Delete GO Analysis Results
            $dir = $BXAF_CONFIG['GO_OUTPUT']['HUMAN'] . "comp_$id";
            if(file_exists($dir)) shell_exec("rm -rf {$dir}");
            $dir = $BXAF_CONFIG['GO_OUTPUT']['MOUSE'] . "comp_$id";
            if(file_exists($dir)) shell_exec("rm -rf {$dir}");

            //Delete PAGE Results
            $file = $BXAF_CONFIG['PAGE_OUTPUT']['HUMAN'] . "comparison_{$id}_GSEA.PAGE.csv";
            if(file_exists($file)) unlink($file);
            $file = $BXAF_CONFIG['PAGE_OUTPUT']['MOUSE'] . "comparison_{$id}_GSEA.PAGE.csv";
            if(file_exists($file)) unlink($file);
        }

        // Delete custom uploaded file in tabix
        $DIR_Tabix = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/";
        shell_exec("rm -rf {$DIR_Tabix}comparison_data.*");

        echo "<h3><i class='fas fa-exclamation-triangle'></i> Notice</h3><div class='my-3 text-success'>Comparison data in current project has been deleted.</div>";
        echo "<div class='my-3'>Notice: related GO analysis and PAGE analysis results are also deleted.</div>";

    }

    exit();
}


?>