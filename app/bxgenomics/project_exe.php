<?php
include_once('config/config.php');

//----------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------
// Update Project Information
//----------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'update_project') {
    // print_r($_POST);
    $ROWID = intval($_POST['rowid']);

    $sql = "SELECT `ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'] . "` WHERE {$BXAF_CONFIG['QUERY_OWNER_FILTER']} AND `ID` = ?i";
	$found = $BXAF_MODULE_CONN->get_one($sql, $ROWID);
    if($found <= 0){
        echo 'Error: Project id is not correct.';
        exit();
    }

    $sql = "SELECT `ID` FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'] . "` WHERE {$BXAF_CONFIG['QUERY_OWNER_FILTER']} AND `Name` = ?s AND `ID` != ?i";
	$found = $BXAF_MODULE_CONN->get_one($sql, $_POST['project_Name'], $ROWID);
    if($found > 0){
		echo "Error: A project with name '" . $_POST['project_Name'] . "' is already created!";
		exit();
	}

    $colnames = $BXAF_MODULE_CONN -> get_column_names($BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']);
    $info = array();
    foreach ($colnames as $col) {
        if (isset($_POST['project_' . $col])) {
            $info[$col] = $_POST['project_' . $col];
        }
    }
    $BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $info, "`ID`=" . $ROWID);

    bxaf_sync_tables();

    exit();
}


//----------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------
// Batch Update Information for Samples or Comparisons
//----------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'batch_update_info') {

    $TYPE = $_GET['type']; // 'Sample' or 'Comparison'
    $PROJECT_ID = intval($_POST['rowid']);
    $PROJECT_URL = $BXAF_CONFIG['BXAF_URL'] . 'app_data/cache/user_files_projects/' . $PROJECT_ID;
    $PROJECT_DIR = $BXAF_CONFIG['USER_FILES']['TOOL_PROJECTS'] . $PROJECT_ID;

    if ($TYPE == 'Sample') {
        $file_name = 'samples_updated.csv';
        $table = $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'];
    } else if ($TYPE == 'Comparison') {
        $file_name = 'comparisons_updated.csv';
        $table = $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'];
    }

    //-------------------------------------------------------------------
    // Upload File
    //-------------------------------------------------------------------
    if ($_FILES['file']["error"] == UPLOAD_ERR_OK) {
        $tmp_name  = $_FILES['file']["tmp_name"];
        $name      = $_FILES['file']["name"];
        $file_type = $_FILES['file']["type"];
        $file_size = $_FILES['file']["size"];
        $file      = $PROJECT_DIR . "/" . $file_name;
        move_uploaded_file($tmp_name, $file);
    }

    //-------------------------------------------------------------------
    // Read File
    //-------------------------------------------------------------------
    $file = fopen($file,"r") or die("Error: File not exist.");
    $header = array();
    // $header_flip = array();
    while(! feof($file)) {
        $row = fgetcsv($file);
        if (!is_array($row) || count($row) <= 1) continue;
        if ($row[0] == 'ID') {
            $header = $row;
            // $header_flip = array_filp($header);
            continue;
        }
        $info = array();
        foreach ($header as $key => $value) {
            $info[$value] = $row[$key];
        }
        $sql = "SELECT * FROM `{$table}` WHERE `ID`=" . intval($row[0]);
        $data_check = $BXAF_MODULE_CONN -> get_row($sql);
        if (intval($row[0]) == 0 || !is_array($data_check) || count($data_check) <= 1) {
            echo 'Error: ' . $TYPE . ' "' . $row[2] . '" does not exist.';
            exit();
        }
        $BXAF_MODULE_CONN -> update($table, $info, "`ID`=" . intval($row[0]));
    }
    fclose($file);

    exit();
}




?>
