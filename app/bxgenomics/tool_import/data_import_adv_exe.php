<?php
include_once('config.php');

$all_species = array('Human', 'Mouse');

$files_type_names = array(
    'Project'          => 'Projects',
    'Sample'           => 'Samples',
    'Comparison'       => 'Comparisons',
    'Expression Data'  => 'Sample Expression Data',
    'Comparison Data'  => 'Comparison Data',
);

$files_tables = array(
    'Project'          => $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'],
    'Sample'           => $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'],
    'Comparison'       => $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'],
    'Expression Data'  => '',
    'Comparison Data'  => '',
);

$files_required_fields = array(
    'Project'          =>array('Name'),
    'Sample'           =>array('Name'),
    'Comparison'       =>array('Name'),
    'Expression Data'  =>array('GeneName', 'Value'),
    'Comparison Data'  =>array('GeneName', 'Log2FoldChange', 'PValue', 'AdjustedPValue'),
);

$all_file_fields = array(
    'Project' =>array(
        'ID', 'Species', 'Name', 'Description', '_Analysis_ID', '_Platforms_ID', 'Platform', 'Platform_Type', 'PlatformName', 'ProjectIndex',
        'Disease', 'Accession', 'PubMed_ID', 'ExperimentType', 'ContactAddress', 'ContactOrganization',
        'ContactName', 'ContactEmail', 'ContactPhone', 'ContactWebLink', 'Keywords', 'ReleaseDate', 'Design', 'StudyType',
        'TherapeuticArea', 'Comment', 'Contributors', 'WebLink', 'PubMed', 'PubMed_Authors', 'Collection'
    ),
    'Sample' =>array(
        'ID', 'Project_Name', '_Projects_ID', '_Platforms_ID', 'Platform', 'Platform_Type', 'PlatformName', 'Species', 'Name', 'Description', 'SampleIndex',
        'CellType', 'DiseaseCategory', 'DiseaseState', 'Ethnicity', 'Gender', 'Infection', 'Organism',
        'Response', 'SamplePathology', 'SampleSource', 'SampleType', 'SamplingTime', 'Symptom',
        'TissueCategory', 'Tissue', 'Transfection', 'Treatment', 'Collection', 'Age', 'RIN_Number',
        'RNASeq_Total_Read_Count', 'RNASeq_Mapping_Rate', 'RNASeq_Assignment_Rate', 'Flag_To_Remove',
        'Flag_Remark', 'Uberon_ID', 'Uberon_Term'
    ),
    'Comparison' =>array(
        'ID', '_Analysis_ID', 'Project_Name', '_Projects_ID', '_Platforms_ID', 'Platform', 'Platform_Type', 'PlatformName', 'Species', 'Name', 'Description', 'ComparisonIndex',
        'Case_SampleIDs', 'Control_SampleIDs', 'ComparisonCategory', 'ComparisonContrast',
        'Case_DiseaseState', 'Case_Tissue', 'Case_CellType', 'Case_Ethnicity', 'Case_Gender',
        'Case_SamplePathology', 'Case_SampleSource', 'Case_Treatment', 'Case_SubjectTreatment',
        'Case_AgeCategory', 'ComparisonType', 'Control_DiseaseState', 'Control_Tissue',
        'Control_CellType', 'Control_Ethnicity', 'Control_Gender', 'Control_SamplePathology',
        'Control_SampleSource', 'Control_Treatment', 'Control_SubjectTreatment', 'Control_AgeCategory'
    ),
    'Expression Data'  =>array('GeneName', 'SampleName', 'Value'),
    'Comparison Data'  =>array('GeneName', 'ComparisonName', 'Log2FoldChange', 'PValue', 'AdjustedPValue'),
);



if (isset($_GET['action']) && $_GET['action'] == 'file_upload') {

    // echo "<pre>" . print_r($_GET, true) . "</pre>"; exit();

    header('Content-Type: application/json');
    $OUTPUT = array();
    $OUTPUT['content'] = '';

    $file_time = $_GET['file_time'];

    $uploads_dir = $BXAF_CONFIG['USER_FILES']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $file_time . '/';
    if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);

    $uploads_url = $BXAF_CONFIG['USER_FILES_URL']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $file_time . '/';


    $sql = "SELECT `ID`, `Type`, `GEO_Accession`, `Name` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Species` = ?s ORDER BY `Type` DESC, `Name` ASC";
    $platform_info = $BXAF_MODULE_CONN->get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], $_SESSION['SPECIES_DEFAULT']);

    $sql = "SELECT `ID`, `Name` FROM ?n WHERE `bxafStatus` < 5 AND `_Owner_ID` = {$BXAF_CONFIG['BXAF_USER_CONTACT_ID']} AND `Species` = ?s ORDER BY `Name` ASC";
    $project_info = $BXAF_MODULE_CONN->get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $_SESSION['SPECIES_DEFAULT']);


    $uploaded_files = array();
    if (isset($_FILES["files"]["error"]) && is_array($_FILES["files"]["error"]) && count($_FILES["files"]["error"]) > 0) {

        $OUTPUT['content'] .= "<table class='table table-borderless table-sm form-inline my-0'>";
        foreach ($_FILES["files"]["error"] as $key => $error) {
            if ($error == UPLOAD_ERR_OK) {

                $tmp_name = $_FILES["files"]["tmp_name"][$key];
                $name = preg_replace("/[^\w\.]/", "_", basename($_FILES["files"]["name"][$key]));
                $name_base64_encode = base64_encode($name);

                $file_encrypted = bxaf_encrypt("{$uploads_dir}{$name}", $BXAF_CONFIG['BXAF_KEY']);
                $file_url = "../download.php?f=" . bxaf_encrypt("{$uploads_dir}{$name}", $BXAF_CONFIG['BXAF_KEY']);

                if(move_uploaded_file($tmp_name, "{$uploads_dir}$name")){
                    $uploaded_files[] = $name;

                    // $OUTPUT['content'] .= "<div class='my-1'>&bull; Uploaded: <a href='{$uploads_url}$name'>$name</a> <a href='Javascript: void(0);' class='btn-preview btn btn-sm btn-primary ml-2' file_name='$name'><i class='fas fa-angle-double-right'></i> Preview </a>  <a href='Javascript: void(0);' class='btn-remove btn btn-sm btn-outline-secondary text-muted ml-2' file_name='$name'><i class='fas fa-times'></i> Delete </a></div>";

                    $OUTPUT['content'] .= "<tr id='$name_base64_encode'>";

                        $OUTPUT['content'] .= "<td class='text-right pt-2 lead' style='min-width: 20rem;'><a class='font-weight-bold' href='$file_url'><i class='fas fa-file-upload'></i> $name</a></td>";

                        $select_file_types = "<select class='m-1 custom-select file_types' file_name='$name_base64_encode' style='max-width: 12rem;'>";
                            $select_file_types .= "<option value='' selected>(Select file type)</option>";
                            foreach($files_type_names as $k => $v) $select_file_types .= "<option value='$k'>$v</option>";
                        $select_file_types .= "</select>";

                        $select_file_platforms = "<select class='m-1 custom-select file_platforms hidden' file_name='$name_base64_encode' style='max-width: 20rem;'>";
                            $select_file_platforms .= "<option value='' selected>(Select Platform)</option>";
                            foreach($platform_info as $k => $v) $select_file_platforms .= "<option value='$k'>(" . $v['Type'] . ") " . $v['GEO_Accession'] . ': ' . $v['Name'] . "</option>";
                        $select_file_platforms .= "</select>";

                        $select_file_projects = "<select class='m-1 custom-select file_projects hidden' file_name='$name_base64_encode' style='max-width: 20rem;'>";
                            $select_file_projects .= "<option value='' selected>(Select Project)</option>";
                            foreach($project_info as $k => $v) $select_file_projects .= "<option value='$k'>$v</option>";
                        $select_file_projects .= "</select>";

                        $OUTPUT['content'] .= "<td class=''>{$select_file_types}{$select_file_platforms}{$select_file_projects}</td>";

                        $OUTPUT['content'] .= "<td class='pt-2'><a href='Javascript: void(0);' class='btn-preview btn btn-sm btn-primary ml-2' file_name='$name_base64_encode'> Preview </a></td>";

                        $OUTPUT['content'] .= "<td class='pt-3'><a href='Javascript: void(0);' class='btn-remove text-muted ml-2' file_name='$name_base64_encode'><i class='fas fa-times'></i> Delete </a></td>";

                    $OUTPUT['content'] .= "</tr>";
                }
            }
        }
        $OUTPUT['content'] .= "</table>";

    }

    if(count($uploaded_files) <= 0){
        $OUTPUT['type'] = 'Error';
        $OUTPUT['content'] = "<div class='text-danger my-3'>Error: No files uploaded.</div>";
    }
    else {
        $OUTPUT['type'] = 'Success';
    }

    echo json_encode($OUTPUT);

	exit();

}




if (isset($_GET['action']) && $_GET['action'] == 'preview_data') {
    // echo "<pre>" . print_r($_GET, true) . "</pre>";
    // echo "<pre>" . print_r($_POST, true) . "</pre>";

    $_Projects_ID = '';
    if(array_key_exists('_Projects_ID', $_POST)) $_Projects_ID  = intval($_POST['_Projects_ID']);

    $_Platforms_ID = '';
    if(array_key_exists('_Platforms_ID', $_POST)) $_Platforms_ID = intval($_POST['_Platforms_ID']);

    $file_type = '';
    if(array_key_exists('file_type', $_POST)) $file_type = strval($_POST['file_type']);

    $file_name = '';
    if(array_key_exists('file_name', $_POST)) $file_name = base64_decode($_POST['file_name']);
    $file_time = '';
    if(array_key_exists('file_time', $_POST)) $file_time = strval($_POST['file_time']);

    $uploads_dir = $BXAF_CONFIG['USER_FILES']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $file_time . '/';
    $uploads_url = $BXAF_CONFIG['USER_FILES_URL']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $file_time . '/';

    $uploaded_file = $uploads_dir . $file_name;
    $uploaded_file_url = $uploads_url . $file_name;


    if(! file_exists($uploaded_file)){
        echo "<h2 class='text-danger'><i class='fas fa-exclamation-triangle'></i> Import Failed</h2><hr /><div class='my-3'>No uploaded files found.</div>";
        exit();
    }

    $current_file_fields = $all_file_fields[$file_type];

    $current_name = basename($uploaded_file);

    $first_row = array();
    $data_rows = array();
    $data_rows_number = 0;
    $error_rows = array();


    $delimiter = ",";

    $projects_found = array();
    if (($handle = fopen($uploaded_file, "r")) !== FALSE) {

        $first_row = fgetcsv($handle, 0, $delimiter);
        if(! is_array($first_row) || count($first_row) <= 1){
            $delimiter = "\t";
            $first_row = fgetcsv($handle, 0, $delimiter);
        }

        if(! is_array($first_row) || count($first_row) <= 1){
            echo "<div class='text-danger'>Error: Can not detect the delimiter of file $current_name! The file you uploaded must be either comma-separated (CSV) or Tab-delimited (TSV).</div>";
            exit();
        }


        $n = 0;
        $genes_in_file = array();
        $comparisons_in_file = array();
        while(($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $n++;
            if (!is_array($row) || count($row) <= 0 || count($row) != count($first_row)) {
                $error_rows[$n] = count($row);
            }
            else {
                $data_rows[$n] = $row;
                // if($file_type == 'Expression Data') $genes_in_file[$n] = $row[0];
                // else if($file_type == 'Comparison Data'){
                //     $genes_in_file[$n] = $row[0];
                //     $comparisons_in_file[$n] = $row[1];
                // }
            }
        }
        fclose($handle);
    }


    $content = "<div class='border border-primary rounded mt-5 p-3' file_name='$current_name'>";

    $content .= "<input type='hidden' name='file_time' value='$file_time' />";
    $content .= "<input type='hidden' name='file_name' value='$file_name' />";
    $content .= "<input type='hidden' name='_Projects_ID' value='$_Projects_ID' />";
    $content .= "<input type='hidden' name='_Platforms_ID' value='$_Platforms_ID' />";
    $content .= "<input type='hidden' name='file_type' value='$file_type' />";

    $data_rows_number = count($data_rows);
    $content .= "<h3 class='my-2'>File: <strong>$current_name</strong> (Type: $file_type)</h3>";
    $content .= "<div class='my-2 text-muted'>File processed ... " . $data_rows_number . " row(s) read ...  (if over 6 rows found, only first and last 3 rows are shown below). <span class='text-danger'>Note: Columns in red are required.</span></div>";

    if (is_array($error_rows) && count($error_rows) > 0) {
        $content .= "<div class='text-danger'>Error: These rows have wrong column numbers and will be ignored: " . implode(", ", array_keys($error_rows)) . "</div>";
    }

    if ($data_rows_number > 0) {
        if($data_rows_number > 5){
            $data_rows = array_slice($data_rows, 0, 3, true) + array_slice($data_rows, -3, 3, true);
        }

        if($file_type == 'Expression Data') $content .= "<div class='my-3'><a class='mx-2' href='Javascript: void(0);' onClick=\"$('.column_matches').val('Value');\"><i class='fas fa-caret-right'></i> Set All Matched Fields to Value</a> <a class='mx-2' href='Javascript: void(0);' onClick=\"$('.column_matches').val('');\"><i class='fas fa-caret-right'></i> Set All Matched Fields to Empty</a></div>";

        $content .= "<div class='table-responsive'><table class='table table-hover table-bordered'><thead>";

        $content .= "<tr class='table-success'><th>Matched Fields: </th>";
        foreach($first_row as $col){

            $content .= "<th>";
                $content .= "<select class='custom-select column_matches' name='column_match_" . base64_encode($col) . "'>";
                $content .= "<option></option>";
                foreach($current_file_fields as $fld){
                    $cap = str_replace('_', ' ', $fld);

                    $content .= "<option value='$fld' " . (($fld == $col || $fld == get_name_and_type ($col, 'type')) ? "Selected" : "") . ">$cap</option>";

                }
                $content .= "</select>";
            $content .= "</th>";
        }
        $content .= "</tr>";



        if($file_type == 'Project' || $file_type == 'Sample' || $file_type == 'Comparison'){
            $content .= "<tr class='table-success'><th class='text-nowrap'>Preset Values: </th>";
            foreach($first_row as $col){
                $content .= "<th>";
                    $content .= "<input class='form-control' type='text' name='column_preset_" . base64_encode($col) . "' value='' />";
                $content .= "</th>";
            }
            $content .= "</tr>";
        }
        else if($file_type == 'Expression Data'){
            $content .= "<tr class='table-success'><th class='text-nowrap'>Sample Name: </th>";
            foreach($first_row as $col){
                $content .= "<th>";
                    $content .= "<input class='form-control' type='text' name='column_preset_" . base64_encode($col) . "' value='' />";
                $content .= "</th>";
            }
            $content .= "</tr>";
        }
        else if($file_type == 'Comparison Data'){
            $content .= "<tr class='table-success'><th class='text-nowrap'>Comparison Name: </th>";
            foreach($first_row as $col){
                $content .= "<th>";
                    $content .= "<input class='form-control' type='text' name='column_preset_" . base64_encode($col) . "' value='" . (get_name_and_type ($col, 'type') != '' ? get_name_and_type ($col, 'name') : "") . "' />";
                $content .= "</th>";
            }
            $content .= "</tr>";
        }

        $content .= "<tr class='table-info'>";
        $content .= "<th>#</th>";
        foreach($first_row as $col){
            $content .= "<th class='" . (in_array($col, $files_required_fields[$file_type]) ? "text-danger" : "") . "'>$col</th>";
        }
        $content .= "</tr>";

        $content .= "</thead>";

        $content .= "<tbody class='border border-primary'>";

        foreach($data_rows as $n=>$row){
            $content .= "<tr>";
            $contents[$n] = array();
            $content .= "<td>$n</td>";
            foreach($row as $col) $content .= "<td>$col</td>";
            $content .= "</tr>";
        }
        $content .= "</tbody></table></div>";

    }

    $content .= '<div class="w-100 my-3 form-check form-check-inline">';
    $content .= '<button type="submit" class="btn btn-primary" id="btn-submit"> <i class="fas fa-upload"></i> Import ' . $file_name . ' (Type: ' . $file_type . ') </button>';
    if($file_type == 'Expression Data' || $file_type == 'Comparison Data') $content .= '<input class="form-check-input mx-2" type="checkbox" name="chk_update" id="chk_update" value="1"><label class="form-check-label" for="chk_update">Update if sample expression data or comparison data already imported</label>';
    $content .= '</div>';

    $content .= "</div>";



    echo $content;

	exit();

}




if (isset($_GET['action']) && $_GET['action'] == 'import_data') {

    // echo "<pre>" . print_r($_GET, true) . "</pre>";
    // echo "<pre>" . print_r($_POST, true) . "</pre>"; exit();

    header('Content-Type: application/json');
    $OUTPUT = array();
    $OUTPUT['type'] = 'Error';

    $errors = array();
    $message = array();


    $file_name = '';
    if(array_key_exists('file_name', $_POST)) $file_name = strval($_POST['file_name']);
    $file_time = '';
    if(array_key_exists('file_time', $_POST)) $file_time = strval($_POST['file_time']);
    $file_type = '';
    if(array_key_exists('file_type', $_POST)) $file_type = strval($_POST['file_type']);

    // Process file
    $uploads_dir = $BXAF_CONFIG['USER_FILES']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $file_time . '/';
    $uploads_url = $BXAF_CONFIG['USER_FILES_URL']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $file_time . '/';

    $uploaded_file = $uploads_dir . $file_name;
    $uploaded_file_url = $uploads_url . $file_name;

    if(! file_exists($uploaded_file)){
        $OUTPUT['detail'] = "No uploaded files found.";
        echo json_encode($OUTPUT);
        exit();
    }

    $current_name = basename($uploaded_file);

    $handle = fopen($uploaded_file, "r");
    if ($handle === FALSE) {
        $OUTPUT['detail'] = "Can not open uploaded file. Please upload your file again.";
        echo json_encode($OUTPUT);
        exit();
    }


    $Species = '';
    $_Platforms_ID = '';
    if(array_key_exists('_Platforms_ID', $_POST) && $file_type == 'Project') $_Platforms_ID = intval($_POST['_Platforms_ID']);
    $_Projects_ID = '';
    if(array_key_exists('_Projects_ID', $_POST) && ($file_type == 'Sample' || $file_type == 'Comparison')) $_Projects_ID  = intval($_POST['_Projects_ID']);

// $OUTPUT['detail'] = "$_Platforms_ID: $_Projects_ID<pre>" . print_r($_POST, true) . "</pre>";
// echo json_encode($OUTPUT);
// exit();

    $current_file_fields = $all_file_fields[$file_type];

    $column_match = array();
    $column_preset = array();
    foreach($_POST as $key=>$val){
        if(preg_match("/^column_match_/", $key)){
            $column_match[ base64_decode( str_replace('column_match_', '', $key) ) ] = $val;
        }
        else if(preg_match("/^column_preset_/", $key)){
            $column_preset[ base64_decode( str_replace('column_preset_', '', $key) ) ] = $val;
        }
    }

    $first_row = array();
    $data_rows = array();
    $data_rows_number = 0;
    $error_rows = array();


    $delimiter = ",";
    $first_row = fgetcsv($handle, 0, $delimiter);
    if(! is_array($first_row) || count($first_row) <= 1){
        $delimiter = "\t";
        $first_row = fgetcsv($handle, 0, $delimiter);
    }

    if(! is_array($first_row) || count($first_row) <= 1){
        $OUTPUT['detail'] = "Can not detect the delimiter of file $current_name! The file you uploaded must be either comma-separated (CSV) or Tab-delimited (TSV).";
        echo json_encode($OUTPUT);
        exit();
    }


    $first_row_fields = array();
    $first_row_values = array();

    foreach($first_row as $i=>$col){
        if(array_key_exists($col, $column_match) && $column_match[$col] != '')   $first_row_fields[$i] = $column_match[$col];
        if(array_key_exists($col, $column_preset) && $column_preset[$col] != '') $first_row_values[$i] = $column_preset[$col];
    }

    $missing_fields = array();
    foreach($files_required_fields[$file_type] as $fld){
        if(! in_array($fld, $first_row_fields)) $missing_fields[] = $fld;
    }

    if (is_array($missing_fields) && count($missing_fields) > 0) {
        $OUTPUT['detail'] = "Required fields are missing in file $current_name: <strong>" . implode(", ", $missing_fields) . "</strong>.";
        echo json_encode($OUTPUT);
        exit();
    }

    if($_Projects_ID > 0){
        $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?i";
        $project_info = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], 'ID', $_Projects_ID );

        if (! is_array($project_info) || count($project_info) <= 5) {
            $OUTPUT['detail'] = "The specified project (ID=$_Projects_ID) is not found.";
            echo json_encode($OUTPUT);
            exit();
        }
    }

    if($_Platforms_ID > 0){
        $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?i";
        $platform_info = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], 'ID', $_Platforms_ID );

        if (! is_array($platform_info) || count($platform_info) <= 5) {
            $OUTPUT['detail'] = "The specified platform (ID=$_Platforms_ID) is not found.";
            echo json_encode($OUTPUT);
            exit();
        }

    }

    $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?i";
    $platform_info1 = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], 'ID', $_SESSION['SPECIES_DEFAULT'] == 'Human' ? 1 : 2 );


    $first_row_fields_flip = array_flip($first_row_fields);

    $batch_commands = '';


    if ($file_type == 'Project' || $file_type == 'Sample' || $file_type == 'Comparison') {

        $number_updated = 0;
        $number_created = 0;

        $imported_names = array();
        $errors = array();
        $all_info = array();

        while(($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {

            if (!is_array($row) || count($row) <= 0) continue;
            if(count($row) < count($first_row))  continue;

            $info = array(
                '_Owner_ID'     => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
                'Time_Created'  => date("Y-m-d H:i:s")
            );

            foreach($row as $i=>$val){
                if(array_key_exists($i, $first_row_values) && $first_row_values[$i] != '') $val = $first_row_values[$i];
                if(array_key_exists($i, $first_row_fields)){
                    $fld = $first_row_fields[$i];
                    $info[$fld] =  $val;
                }
            }

            if($file_type == 'Project'){

                if($Species != '') $info['Species'] = $Species;

                if($_Platforms_ID > 0){
                    $info['_Platforms_ID'] = $_Platforms_ID;
                    $info['Platform'] = $platform_info['GEO_Accession'];
                    $info['PlatformName'] = $platform_info['Name'];
                    $info['Platform_Type'] = $platform_info['Type'];

                }
                else {

                    if($info['_Platforms_ID'] > 0){
                        $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?i";
                        $platform_info1 = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], 'ID', $info['_Platforms_ID'] );

                        if (! is_array($platform_info1) || count($platform_info1) <= 5) {
                            $errors[] =  "The specified platform (ID=" . $info['_Platforms_ID'] . ") is not found.";
                        }
                    }
                    else if($info['Platform'] != ''){
                        $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?s";
                        $platform_info1 = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], 'GEO_Accession', $info['Platform'] );

                        if (! is_array($platform_info1) || count($platform_info1) <= 5) {
                            $errors[] =  "The specified platform (GEO_Accession=" . $info['Platform'] . ") is not found.";
                        }
                    }
                    else if($info['PlatformName'] != ''){
                        $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?s";
                        $platform_info1 = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], 'Name', $info['PlatformName'] );

                        if (! is_array($platform_info1) || count($platform_info1) <= 5) {
                            $errors[] =  "The specified platform (Name=" . $info['PlatformName'] . ") is not found.";
                        }
                    }

                    if (is_array($platform_info1) && count($platform_info1) > 0) {

                        $info['_Platforms_ID'] = $platform_info1['ID'];
                        $info['Platform']      = $platform_info1['GEO_Accession'];
                        $info['PlatformName']  = $platform_info1['Name'];
                        $info['Platform_Type'] = $platform_info1['Type'];
                        $info['Species']       = $platform_info1['Species'];
                    }

                }

                // Set to default Species and Platform
                if($info['Species'] == ''){
                    $info['Species'] = $_SESSION['SPECIES_DEFAULT'];
                }
            }


            if($file_type == 'Sample' || $file_type == 'Comparison'){

                if($_Projects_ID > 0){
                    $info['_Projects_ID'] = $_Projects_ID;
                    $info['Project_Name'] = $project_info['Name'];
                }

                //User specified Project Name, but no Project ID
                if($info['Project_Name'] != '' && ($info['_Projects_ID'] == '' || $info['_Projects_ID'] == 0) ) {
                    $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?s";
                    $temp_project_info = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], 'Name', $info['Project_Name'] );

                    $info['_Projects_ID']  = $temp_project_info['ID'];
                    $info['Project_Name']  = $temp_project_info['Name'];

                    $info['Species']       = $temp_project_info['Species'];

                    $info['_Platforms_ID'] = $temp_project_info['_Platforms_ID'];
                    $info['Platform']      = $temp_project_info['Platform'];
                    $info['PlatformName']  = $temp_project_info['PlatformName'];
                    $info['Platform_Type'] = $temp_project_info['Platform_Type'];

                }

                if($info['_Projects_ID'] > 0) {
                    $sql = "SELECT * FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND ?n = ?i";
                    $temp_project_info = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], 'ID', $info['_Projects_ID'] );

                    $info['_Projects_ID']  = $temp_project_info['ID'];
                    $info['Project_Name']  = $temp_project_info['Name'];

                    $info['Species']       = $temp_project_info['Species'];

                    $info['_Platforms_ID'] = $temp_project_info['_Platforms_ID'];
                    $info['Platform']      = $temp_project_info['Platform'];
                    $info['PlatformName']  = $temp_project_info['PlatformName'];
                    $info['Platform_Type'] = $temp_project_info['Platform_Type'];

                }

                // Skip row if no project is defined
                if($info['_Projects_ID'] == '' || $info['_Projects_ID'] == 0) {
                    continue;
                }

            }


            // Check exists
            $sql = "SELECT `ID` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name`= ?s";
            $record_id = $BXAF_MODULE_CONN -> get_one($sql, $files_tables[$file_type], $info[ 'Name' ] );

            if ($record_id != '') {
                $errors[] =  $file_type . " name '" . $info[ 'Name' ] . "' is taken.";
            }
            else {
                $all_info[] = $info;
                $imported_names[] = $info[ 'Name' ];
            }

        }

        fclose($handle);


        if(count($errors) > 0){
            $OUTPUT['detail'] = "<ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
            echo json_encode($OUTPUT);
            exit();
        }
        else {
            $BXAF_MODULE_CONN -> insert_batch($files_tables[$file_type], $all_info);

            // Check exists
            $sql = "SELECT `Name`, `ID` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name` IN (?a)";
            $found_nameids = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, $files_tables[$file_type], $imported_names );
            if(! is_array($found_nameids)) $found_nameids = array();

            $url = "../tool_search/view.php?type={$file_type}&id=";
            if($file_type == 'Project') $url = "../project.php?id=";

            $message = array();
            foreach($imported_names as $name){
                if(array_key_exists($name, $found_nameids)) $message[] = "$file_type <a target='_blank' href='$url" . $found_nameids[$name] . "'>" . $name . "</a> is created.";
                else $message[] = $file_type . " name '" . $name . "' was not created.";
            }

            $OUTPUT['type'] = 'Success';
            if(count($message) > 0){
                $OUTPUT['detail'] = "<ul><li>" . implode("</li><li>", $message) . "</li></ul>";
                echo json_encode($OUTPUT);
                exit();
            }

        }

    }


    else if ($file_type == 'Expression Data') {

        $column_GeneName = $first_row_fields_flip['GeneName'];

        $all_SampleName = array();
        $all_GeneName = array();
        $all_Value = array();

        if(in_array('SampleName', $first_row_fields) ){

            $column_SampleName = $first_row_fields_flip['SampleName'];
            $column_Value = $first_row_fields_flip['Value'];

            $min_columns = max(array_keys($first_row_fields));
            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {

                if( count($row) <= $min_columns ) continue;

                $gene_name = preg_replace("/\..*$/", "", $row[$column_GeneName] );
                $all_GeneName[ $gene_name ] = 1;

                $sample_name = trim($row[$column_SampleName]);
                if($column_SampleName != '' && $row[$column_SampleName] != '' && ! in_array($sample_name, $all_SampleName) ) $all_SampleName[ $sample_name ] = 1;

                if($gene_name != '' && $sample_name != '') $all_Value[] = array($gene_name, $sample_name, $row[ $column_Value ]);
            }

        }
        else {
            foreach($first_row_fields as $i=>$c){
                if($c == 'Value') $all_SampleName[ $i ] = $first_row[$i];
            }
            if(is_array($first_row_values) && count($first_row_values) > 0){
                foreach($first_row_values as $i=>$c){
                    if($first_row_fields[$i] == 'Value') $all_SampleName[ $i ] = $c;
                }
            }

            $all_SampleName = array_flip( $all_SampleName );

            $min_columns = max(array_keys($first_row_fields));
            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {

                if( count($row) <= $min_columns ) continue;

                $gene_name = preg_replace("/\..*$/", "", $row[$column_GeneName] );
                $all_GeneName[ $gene_name ] = 1;

                foreach($all_SampleName as $sample_name=>$i){
                    $all_Value[] = array($gene_name, $sample_name, $row[ $i ]);
                }
            }
        }
        fclose($handle);


        $all_gene_nameindex = array();
        $all_gene_indexname = array();

        $all_GeneName = array_keys($all_GeneName);
        $number_genes = count($all_GeneName);

        foreach($all_species as $Species){

            $all_gene_nameindex[$Species] = array();
            $all_gene_indexname[$Species] = array();

            $n=0;
            do{
                $genes = array_slice($all_GeneName, $n, 1000);
                $n += 1000;

                $sql = "SELECT `Name`, `GeneIndex` FROM ?n WHERE `Species` = '$Species' AND `Name` IN (?a)";
                $results = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'], $genes );
                if(is_array($results)) $all_gene_nameindex[$Species] = $all_gene_nameindex[$Species] + $results;

                $sql = "SELECT `GeneIndex`, `GeneName` FROM ?n WHERE `Species` = '$Species' AND `Name` IN (?a)";
                $results = $BXAF_MODULE_CONN -> get_assoc('GeneIndex', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'], $genes );
                if(is_array($results)) $all_gene_indexname[$Species] = $all_gene_indexname[$Species] + $results;

            } while($n < $number_genes);

            foreach($all_gene_indexname[$Species] as $i=>$v) $all_gene_indexname[$Species][$i] = $v;

            $temp = array_flip($all_gene_nameindex[$Species]);
            foreach($temp as $i=>$v) $temp[$i] = $v;
            $all_gene_nameindex[$Species] = array_flip($temp);
        }


        foreach($all_SampleName as $k=>$i){
            $sql = "SELECT `ID` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND BINARY `Name` = ?s";
            $found_id = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], $k );
            if($found_id <= 0) unset($all_SampleName[$k] );
        }
        if(count($all_SampleName) <= 0) { $errors[] = "Error: No valid sample names found."; }


        $sql = "SELECT `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND BINARY `Name` IN (?a)";
        $samples_found = $BXAF_MODULE_CONN -> get_col($sql, array_keys($all_SampleName) );

        if(! is_array($samples_found) ){
            $OUTPUT['detail'] = "In your uploaded file $current_name, no samples found in the database!";
            echo json_encode($OUTPUT);
            exit();
        }
        else if(count($samples_found) != count($all_SampleName)){
            $samples_notfound = array_diff(array_keys($all_SampleName), $samples_found );

            $OUTPUT['detail'] = "In your uploaded file $current_name, some samples are not found in the system: " . implode(", ", $samples_notfound) . "";
            echo json_encode($OUTPUT);
            exit();
        }


        $all_sample_info = array();
        $sql = "SELECT `ID`, `Name`, `Species`, `_Projects_ID`, `Project_Name`, `Platform_Type` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND BINARY `Name` IN (?a)";
        $all_sample_info = $BXAF_MODULE_CONN -> get_all($sql, $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], array_keys($all_SampleName) );


        $samples_existing = array();
        $sample_namespecies = array();
        $sample_nameprojects = array();
        $sample_nameids = array();
        $sample_project_platform = array();
        $project_idnames = array();
        foreach($all_sample_info as $sample){
            $sample_namespecies[ $sample['Name'] ] = $sample['Species'];
            $sample_nameprojects[ $sample['Name'] ] = $sample['_Projects_ID'];
            $sample_nameids[ $sample['Name'] ] = $sample['ID'];
            $sample_project_platform[ $sample['_Projects_ID'] ] = strtolower($sample['Platform_Type']);
            $project_idnames[ $sample['_Projects_ID'] ] = $sample['Project_Name'];
        }
        $sample_unique_projects = array_unique( array_values($sample_nameprojects) );


        foreach($sample_unique_projects as $project_id){
            $data_type = $sample_project_platform[ $project_id ];

            $file = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/{$data_type}_expression_data.txt";

            if(file_exists($file)){
                if (($handle_temp = fopen($file, "r")) !== FALSE) {
                    // Skip head
                    $head = fgetcsv($handle_temp, 0, "\t");
                    $head_flip = array_flip($head);

                    while( ($row = fgetcsv($handle_temp, 0, "\t")) !== FALSE ){
                        if(count($row) == count($head)) $samples_existing[$project_id][ $row[ $head_flip['SampleIndex'] ] ] = $row[ $head_flip['SampleName'] ];
                    }
                    fclose($handle_temp);
                }

                if(is_array($samples_existing[$project_id]) && count($samples_existing[$project_id]) > 0){
                    foreach($samples_existing[$project_id] as $id=>$name){
                        if(in_array($id, $sample_nameids) && (! isset($_POST['chk_update']) || $_POST['chk_update'] != 1) ) $errors[] = "Sample '" . $name . "' of project '" . $project_idnames[$project_id] . "' has expression data already imported.";
                    }
                }
            }
        }

        // $OUTPUT['detail'] = "$uploads_dir<pre>" . print_r($samples_existing, true) . "</pre>";
        // echo json_encode($OUTPUT);
        // exit();

        if (count($errors) <= 0){

            $expression_data_genes_not_found_file        = $uploads_dir . "expression_data_genes_not_found_file.csv";
            $expression_data_genes_not_found_file_url    = $uploads_url . "expression_data_genes_not_found_file.csv";
            $expression_data_genes_not_found_file_handle = fopen($expression_data_genes_not_found_file, "w");

            $expression_data_genes_found_file = array();
            $expression_data_genes_found_file_handle = array();
            foreach($sample_unique_projects as $project_id){
                $expression_data_genes_found_file[$project_id]            = $uploads_dir . "{$project_id}_expression_data_genes_found_file.csv";
                $expression_data_genes_found_file_handle[$project_id]     = fopen($expression_data_genes_found_file[$project_id], "w");
                fputcsv( $expression_data_genes_found_file_handle[$project_id], array('GeneIndex', 'SampleIndex', 'GeneName', 'SampleName', 'Value'), "\t");
            }

            $expression_data_genes_total = 0;
            $expression_data_genes_found = 0;
            $expression_data_genes_not_found = 0;

            foreach($all_Value as $row){

                $gene   = $row[0];
                $sample = $row[1];
                $value  = $row[2];
                $Species = $sample_namespecies[ $sample ];

                if(array_key_exists($gene, $all_gene_nameindex[$Species])){
                    $gene_index = $all_gene_nameindex[$Species][$gene];
                    $info = array($gene_index, $sample_nameids[$sample], $all_gene_indexname[$Species][ $gene_index ], $sample, $value);
                    fputcsv( $expression_data_genes_found_file_handle[$project_id], $info, "\t");
                    $expression_data_genes_found++;
                }
                else {
                    $info = array($gene, $sample, $value);
                    fputcsv( $expression_data_genes_not_found_file_handle, $info);
                    $expression_data_genes_not_found++;
                }
                $expression_data_genes_total++;
            }

            fclose($expression_data_genes_not_found_file_handle);

            foreach($sample_unique_projects as $project_id){
                fclose ( $expression_data_genes_found_file_handle[$project_id] );

                $data_type = $sample_project_platform[ $project_id ];

                // Add tabix commands here
                $dir = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/";
                if(!file_exists($dir)) mkdir($dir, 0775, true);

                shell_exec("rm -rf {$dir}{$data_type}_expression_data.txt.*");

                $target_file = "{$dir}{$data_type}_expression_data.txt";
                $target_file_bak = "{$dir}{$data_type}_expression_data.txt_bak" . $file_time;

                if(file_exists($target_file)){
                    // Back up file
                    rename($target_file, $target_file_bak);

                    $handle_temp1 = fopen($target_file_bak, "r");
                    $handle_temp2 = fopen($expression_data_genes_found_file[$project_id], "r");
                    $handle_temp0 = fopen($target_file, "w");

                    if ($handle_temp0 !== FALSE && $handle_temp1 !== FALSE && $handle_temp2 !== FALSE) {

                        while( ($row = fgetcsv($handle_temp1, 0, "\t")) !== FALSE ){
                            if(! in_array($row[1], $sample_nameids)) fputcsv($handle_temp0, $row, "\t");
                        }
                        fclose($handle_temp1);

                        // Skip header
                        $row = fgetcsv($handle_temp2, 0, "\t");
                        while( ($row = fgetcsv($handle_temp2, 0, "\t")) !== FALSE ){
                            fputcsv($handle_temp0, $row, "\t");
                        }
                        fclose($handle_temp2);

                        fclose($handle_temp0);
                    }
                }
                else {
                    copy($expression_data_genes_found_file[$project_id], $target_file);
                }

                $batch_commands = "#!/usr/bin/bash\n\n";
                $batch_commands .= "cd $dir\n";
                $batch_commands .= "/usr/bin/tail -n +2 {$data_type}_expression_data.txt | /usr/bin/sort -k1,1n -k2,2n | {$BXAF_CONFIG['BGZIP_BIN']} > {$data_type}_expression_data.txt.gz\n";
                $batch_commands .= "{$BXAF_CONFIG['TABIX_BIN']} -s 1 -b 2 -e 2 -0 {$data_type}_expression_data.txt.gz\n";
                $batch_commands .= "/usr/bin/tail -n +2 {$data_type}_expression_data.txt | /usr/bin/sort -k2,2n -k1,1n | {$BXAF_CONFIG['BGZIP_BIN']} > {$data_type}_expression_data.txt.sample.gz\n";
                $batch_commands .= "{$BXAF_CONFIG['TABIX_BIN']} -s 2 -b 1 -e 1 -0 {$data_type}_expression_data.txt.sample.gz\n\n";

                $command = "{$uploads_dir}{$project_id}_import_batch.bash";
                file_put_contents($command, $batch_commands);
                chmod($command, 0775);
                shell_exec($command);

            }

        }

        if (count($errors) <= 0){
            $message[] = "<span class='font-weight-bold lead'>File '" . $file_name . "' has been imported successfully.</span>";
            $message[] = "<span class='text-success'>Expression data imported: " . $expression_data_genes_found . " out of " . $expression_data_genes_total . " genes.</span>";
            $message[] = "<span class='text-success'><a target='_blank' href='$expression_data_genes_not_found_file_url'><i class='fas fa-download'></i> Expression data with genes not found  (CSV file).</a>.</span>";
            // $message[] = "<span class='text-success'>A background task has been scheduled to process expression data with tabix tool.</span>";
        }

    }


    else if ($file_type == 'Comparison Data') {

        $errors = array();

        $first_row_fields_flip = array_flip($first_row_fields);
        $column_GeneName = $first_row_fields_flip['GeneName'];
        $column_ComparisonName = '';

        $all_GeneName = array();
        $all_ComparisonName = array();
        $all_Value = array();

        $column_definitions = array();
        if(in_array('ComparisonName', $first_row_fields) ){
            $column_ComparisonName = $first_row_fields_flip['ComparisonName'];
            $column_definitions['Log2FoldChange'] = $first_row_fields_flip['Log2FoldChange'];
            $column_definitions['PValue']         = $first_row_fields_flip['PValue'];
            $column_definitions['AdjustedPValue'] = $first_row_fields_flip['AdjustedPValue'];
        }
        else if(count($first_row_values) > 0){

            foreach($first_row_values as $i=>$c){
                $all_ComparisonName[$c] = 1;
            }

            foreach($all_ComparisonName as $c=>$v){
                if($c == ''){ unset($all_ComparisonName[$c]); continue; }
                $column_definitions[$c]['Log2FoldChange'] = '';
                $column_definitions[$c]['PValue']         = '';
                $column_definitions[$c]['AdjustedPValue'] = '';
            }

            foreach($first_row_fields as $i=>$f){
                if(in_array($f, array('Log2FoldChange', 'PValue', 'AdjustedPValue'))){
                    $c = $first_row_values[$i];
                    $column_definitions[$c][$f] = $i;
                }
            }
        }

        if(! is_array($column_definitions) || count($column_definitions) <= 0){
            $OUTPUT['detail'] = "Comparison name is missing.";
            echo json_encode($OUTPUT);
            exit();
        }


        // $OUTPUT['detail'] = "$uploads_dir<pre>" . print_r($column_definitions, true) . "</pre>";
        // echo json_encode($OUTPUT);
        // exit();

        $min_columns = max(array_keys($first_row_fields));
        while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {

            if( count($row) <= $min_columns ) continue;

            $gene_name = preg_replace("/\..*$/", "", $row[$column_GeneName] );
            $all_GeneName[ $gene_name ] = 1;

            if($column_ComparisonName != ''){
                $comparison_name = trim($row[$column_ComparisonName]);
                if($comparison_name != ''){
                    $all_ComparisonName[ $comparison_name ] = 1;
                    $all_Value[ ] = array($gene_name, $comparison_name, floatval($row[ $column_definitions['Log2FoldChange'] ]), floatval($row[ $column_definitions['PValue'] ]), floatval($row[ $column_definitions['AdjustedPValue'] ]) );
                }
            }
            else {
                foreach($column_definitions as $comparison_name=>$cols){
                    $all_Value[ ] = array($gene_name, $comparison_name, floatval($row[ $cols['Log2FoldChange'] ]), floatval($row[ $cols['PValue'] ]), floatval($row[ $cols['AdjustedPValue'] ]) );
                }
            }

        }
        fclose($handle);


        $all_gene_nameindex = array();
        $all_gene_indexname = array();

        $all_GeneName = array_keys($all_GeneName);
        $number_genes = count($all_GeneName);

        foreach($all_species as $Species){

            $all_gene_nameindex[$Species] = array();
            $all_gene_indexname[$Species] = array();

            $n=0;
            do{
                $genes = array_slice($all_GeneName, $n, 1000);
                $n += 1000;

                $sql = "SELECT `Name`, `GeneIndex` FROM ?n WHERE `Species` = '$Species' AND `Name` IN (?a)";
                $results = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'], $genes );
                if(is_array($results)) $all_gene_nameindex[$Species] = $all_gene_nameindex[$Species] + $results;

                $sql = "SELECT `GeneIndex`, `GeneName` FROM ?n WHERE `Species` = '$Species' AND `Name` IN (?a)";
                $results = $BXAF_MODULE_CONN -> get_assoc('GeneIndex', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'], $genes );
                if(is_array($results)) $all_gene_indexname[$Species] = $all_gene_indexname[$Species] + $results;

            } while($n < $number_genes);

            foreach($all_gene_indexname[$Species] as $i=>$v) $all_gene_indexname[$Species][$i] = $v;

            $temp = array_flip($all_gene_nameindex[$Species]);
            foreach($temp as $i=>$v) $temp[$i] = $v;
            $all_gene_nameindex[$Species] = array_flip($temp);
        }


        foreach($all_ComparisonName as $k=>$v){
            $sql = "SELECT `ID` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name` = ?s";
            $found_id = $BXAF_MODULE_CONN -> get_one($sql, $files_tables['Comparison'], $k );
            if($found_id <= 0) unset($all_ComparisonName[$k] );
        }
        if(count($all_ComparisonName) <= 0) { $errors[] = "Error: No valid comparison names found."; }


        $all_comparison_info = array();
        $sql = "SELECT `ID`, `Name`, `Species`, `_Projects_ID`, `Project_Name`, `Platform_Type` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND BINARY `Name` IN (?a)";
        $all_comparison_info = $BXAF_MODULE_CONN -> get_all($sql, $files_tables['Comparison'], array_keys($all_ComparisonName) );

        $comparisons_existing = array();
        $comparison_namespecies = array();
        $comparison_nameprojects = array();
        $comparison_nameids = array();
        $comparison_project_platform = array();
        $project_idnames = array();
        foreach($all_comparison_info as $comparison){
            $comparison_namespecies[ $comparison['Name'] ] = $comparison['Species'];
            $comparison_nameprojects[ $comparison['Name'] ] = $comparison['_Projects_ID'];
            $comparison_nameids[ $comparison['Name'] ] = $comparison['ID'];
            $comparison_project_platform[ $comparison['_Projects_ID'] ] = strtolower($comparison['Platform_Type']);
            $project_idnames[ $comparison['_Projects_ID'] ] = $comparison['Project_Name'];
        }
        $comparison_unique_projects = array_unique( array_values($comparison_nameprojects) );

        foreach($comparison_unique_projects as $project_id){
            $data_type = $comparison_project_platform[ $project_id ];
            $file = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/comparison_data.txt";
            if(file_exists($file)){

                if ( ($handle_temp = fopen($file, "r")) !== FALSE) {
                    // Skip head
                    $head = fgetcsv($handle_temp, 0, "\t");
                    $head_flip = array_flip($head);

                    while( ($row = fgetcsv($handle_temp, 0, "\t")) !== FALSE ){
                        if(count($row) == count($head)) $comparisons_existing[$project_id][ $row[ $head_flip['ComparisonIndex'] ] ] = $row[ $head_flip['ComparisonName'] ];
                    }
                    fclose($handle_temp);
                }
                if(is_array($comparisons_existing[$project_id]) && count($comparisons_existing[$project_id]) > 0){
                    foreach($comparisons_existing[$project_id] as $id=>$name){
                        if(in_array($id, $comparison_nameids) && (! isset($_POST['chk_update']) || $_POST['chk_update'] != 1)) $errors[] = "Comparison '" . $name . "' of project '" . $project_idnames[$project_id] . "' has comparison data already imported.";
                    }
                }
            }
        }

        // $OUTPUT['detail'] = "$uploads_dir<pre>" . print_r($all_comparison_info, true) . "</pre>";
        // echo json_encode($OUTPUT);
        // exit();

        if (count($errors) <= 0){

            $comparison_data_genes_not_found_file        = $uploads_dir . "comparison_data_genes_not_found_file.csv";
            $comparison_data_genes_not_found_file_url    = $uploads_url . "comparison_data_genes_not_found_file.csv";
            $comparison_data_genes_not_found_file_handle = fopen($comparison_data_genes_not_found_file, "w");

            $comparison_data_project_file = array();
            $comparison_data_project_file_url = array();
            $comparison_data_project_file_handle = array();
            foreach($comparison_unique_projects as $project_id){
                $comparison_data_project_file[$project_id]            = $uploads_dir . "{$project_id}_comparison_data.txt";
                $comparison_data_project_file_url[$project_id]        = $uploads_url . "{$project_id}_comparison_data.txt";
                $comparison_data_project_file_handle[$project_id]     = fopen($comparison_data_project_file[$project_id], "w");
                fputcsv($comparison_data_project_file_handle[$project_id], array('GeneIndex', 'ComparisonIndex', 'GeneName', 'ComparisonName', 'Log2FoldChange', 'PValue', 'AdjustedPValue'), "\t");
            }

            $comparison_data_comparison_file = array();
            $comparison_data_comparison_file_url = array();
            $comparison_data_comparison_file_handle = array();
            foreach($comparison_nameids as $comparison=>$comparison_id){
                $comparison_data_comparison_file[$comparison_id] = $uploads_dir . 'comp_' . $comparison_id . '.csv';
                $comparison_data_comparison_file_handle[$comparison_id] = fopen($uploads_dir . 'comp_' . $comparison_id . '.csv', "w");
                fputcsv($comparison_data_comparison_file_handle[$comparison_id], array('GeneName', 'Log2FoldChange', 'PValue', 'AdjustedPValue'));
            }

            $comparison_data_genes_total = 0;
            $comparison_data_genes_found = 0;
            $comparison_data_genes_not_found = 0;

            foreach($all_Value as $row){

                $gene           = $row[0];
                $comparison     = $row[1];
                $Log2FoldChange = $row[2];
                $PValue         = $row[3];
                $AdjustedPValue = $row[4];

                $project_id = $comparison_nameprojects[$comparison];
                $Species = $comparison_namespecies[ $comparison ];

                $gene_index = $all_gene_nameindex[$Species][$gene];
                $comparison_index = $comparison_nameids[$comparison];

                if(array_key_exists($gene, $all_gene_nameindex[$Species])){
                    $gene_index = $all_gene_nameindex[$Species][$gene];

                    $info = array($gene_index, $comparison_index, $all_gene_indexname[$Species][ $gene_index ], $comparison, $Log2FoldChange, $PValue, $AdjustedPValue);
                    fputcsv( $comparison_data_project_file_handle[$project_id], $info, "\t");

                    $info = array($all_gene_indexname[$Species][ $gene_index ], $Log2FoldChange, $PValue, $AdjustedPValue);
                    fputcsv( $comparison_data_comparison_file_handle[$comparison_index], $info);

                    $comparison_data_genes_found++;
                }
                else {
                    fputcsv( $comparison_data_genes_not_found_file_handle, $row);
                    $comparison_data_genes_not_found++;
                }

                $comparison_data_genes_total++;
            }

            fclose($comparison_data_genes_not_found_file_handle);



            $batch_commands = "#!/usr/bin/bash\n\n";
            foreach($comparison_unique_projects as $project_id){
                fclose ( $comparison_data_project_file_handle[$project_id] );

                // Add tabix commands here
                $dir = $BXAF_CONFIG['TABIX_IMPORT_DIR'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . "/{$project_id}/";
                if(!file_exists($dir)) mkdir($dir, 0775, true);
                shell_exec("rm -rf {$dir}comparison_data.txt.*");

                $target_file     = "{$dir}comparison_data.txt";
                $target_file_bak = "{$dir}comparison_data.txt_bak" . $file_time;

                if(file_exists($target_file)){
                    // Back up file
                    rename($target_file, $target_file_bak);

                    $handle_temp1 = fopen($target_file_bak, "r");
                    $handle_temp2 = fopen($comparison_data_project_file[$project_id], "r");
                    $handle_temp0 = fopen($target_file, "w");

                    if ($handle_temp0 !== FALSE && $handle_temp1 !== FALSE && $handle_temp2 !== FALSE) {

                        while( ($row = fgetcsv($handle_temp1, 0, "\t")) !== FALSE ){
                            if(! in_array($row[1], $comparison_nameids)) fputcsv($handle_temp0, $row, "\t");
                        }
                        fclose($handle_temp1);

                        // Skip header
                        $row = fgetcsv($handle_temp2, 0, "\t");
                        while( ($row = fgetcsv($handle_temp2, 0, "\t")) !== FALSE ){
                            fputcsv($handle_temp0, $row, "\t");
                        }
                        fclose($handle_temp2);

                        fclose($handle_temp0);
                    }
                }
                else {
                    copy($comparison_data_project_file[$project_id], $target_file);
                }

                $batch_commands .= "cd $dir\n";
                $batch_commands .= "/usr/bin/tail -n +2 comparison_data.txt | /usr/bin/sort -t\$'\\t' -k1,1n -k2,2n | {$BXAF_CONFIG['BGZIP_BIN']} > comparison_data.txt.gz\n";
                $batch_commands .= "{$BXAF_CONFIG['TABIX_BIN']} -s 1 -b 2 -e 2 -0 comparison_data.txt.gz\n";
                $batch_commands .= "/usr/bin/tail -n +2 comparison_data.txt | /usr/bin/sort -t\$'\\t' -k2,2n -k1,1n | {$BXAF_CONFIG['BGZIP_BIN']} > comparison_data.txt.comparison.gz\n";
                $batch_commands .= "{$BXAF_CONFIG['TABIX_BIN']} -s 2 -b 1 -e 1 -0 comparison_data.txt.comparison.gz\n\n";

            }

            foreach($comparison_nameids as $comparison=>$comparison_index){

                fclose ( $comparison_data_comparison_file_handle[$comparison_index] );

                $Species = $comparison_namespecies[ $comparison ];

                // Output comparison data to a folder
                $dir_go_output = $BXAF_CONFIG['GO_OUTPUT'][strtoupper($Species)] . 'comp_' . $comparison_index;
                if(!file_exists($dir_go_output)) mkdir($dir_go_output, 0755, true);

                $target_file = "$dir_go_output/comp_{$comparison_index}.csv";
                if(file_exists($target_file)) shell_exec("rm -rf $dir_go_output/*");
                copy($comparison_data_comparison_file[$comparison_index], $target_file);

                // GO Analysis
                $batch_commands .= "cd $dir_go_output\n";

                $suffix = strtolower($Species) . " {$BXAF_CONFIG['NECESSARY_FILES'][$Species]['gmt_file']} auto";
                $batch_commands .= "Rscript " . dirname(__DIR__) . "/analysis_scripts/Process.Internal.Comparison.R comp_{$comparison_index}.csv $suffix\n";
                $batch_commands .= "mv $dir_go_output/PAGE_comp_{$comparison_index}.csv " . $BXAF_CONFIG['PAGE_OUTPUT'][strtoupper($Species)] . "comparison_{$comparison_index}_GSEA.PAGE.csv \n\n";

            }

            fclose($handle);



            $command = $uploads_dir . "import_batch.bash";
            file_put_contents($command, $batch_commands);
            chmod($command, 0775);

            $info = array(
                'Command' => $command,
                'Dir' => $uploads_dir,
                'Log_File' => $uploads_dir . "import_batch.log",
                '_Owner_ID' => intval($BXAF_CONFIG['BXAF_USER_CONTACT_ID'])
            );
            $insert_id = $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PROCESS'], $info);

            run_process_in_order();

// $OUTPUT['type'] = 'Success';
// $OUTPUT['detail'] = "file_contents<pre>" . print_r($info, true) . "</pre>comparison_nameids<pre>" . print_r($comparison_nameids, true) . "</pre>";
// echo json_encode($OUTPUT);
// exit();

        }

        if (count($errors) <= 0){
            $message[] = "<span class='font-weight-bold lead'>File '" . $file_name . "' has been imported successfully.</span>";
            $message[] = "<span class='text-success'>Comparison data imported: " . $comparison_data_genes_found . " out of " . $comparison_data_genes_total . " genes.</span>";
            $message[] = "<span class='text-success'><a target='_blank' href='$comparison_data_genes_not_found_file_url'><i class='fas fa-download'></i> Comparison data with genes not found  (CSV file).</a>.</span>";
            $message[] = "<span class='text-success'>A background task has been scheduled for GO analysis and PAGE analysis.</span>";
        }

    }



    if(count($errors) > 0){
        $OUTPUT['detail'] = "<ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
        echo json_encode($OUTPUT);
        exit();
    }
    else if(count($message) > 0){

        $OUTPUT['type'] = 'Success';
        $OUTPUT['detail'] = "<ul><li>" . implode("</li><li>", $message) . "</li></ul>";
        echo json_encode($OUTPUT);
        exit();

    }
    else {
        $OUTPUT['type'] = 'Success';
        $OUTPUT['detail'] = "<span class='font-weight-bold lead'>Import has been completed successfully.</span>";
        echo json_encode($OUTPUT);
        exit();
    }


    exit();
}




if (isset($_GET['action']) && $_GET['action'] == 'remove_file') {
    // echo "<pre>" . print_r($_GET, true) . "</pre>"; exit();

    $file_name = base64_decode($_POST['file_name']);
    $file_time = $_POST['file_time'];

    $uploaded_file = $BXAF_CONFIG['USER_FILES']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $file_time . '/' . $file_name;

    if(file_exists($uploaded_file)){
        unlink($uploaded_file);
    }

    exit();
}


?>