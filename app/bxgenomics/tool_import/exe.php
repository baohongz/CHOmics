<?php
include_once('config.php');

//****************************************************************************************************
// Upload Comparison Info
// Project, Sample, Comparison, Gene
//****************************************************************************************************
if (isset($_GET['action']) && $_GET['action'] == 'upload_data_info') {

    header('Content-Type: application/json');
    $OUTPUT = array();
    $OUTPUT['type'] = 'Error';


    $all_species = array('Human', 'Mouse');

    $sql = "SELECT `ID`, `Species`, `GEO_Accession`, `Name`, `Type` FROM {$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']} WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']}";
    $results = $BXAF_MODULE_CONN -> get_all($sql);
    foreach($results as $r){
        $all_platforms[ $r['Species'] ][ $r['GEO_Accession'] ] = $r;
    }

    $files_tables = array(
        'file_projects'          => $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'],
        'file_samples'           => $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'],
        'file_comparisons'       => $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'],
        'file_expression_data'   => '',
        'file_comparison_data'   => '',
    );

    $files_required_fields = array(
        'file_projects'         =>array('Name', 'Platform'),
        'file_samples'          =>array('Name', 'Project_Name'),
        'file_comparisons'      =>array('Name', 'Project_Name', 'Case_SampleIDs', 'Control_SampleIDs'),
        'file_expression_data'  =>array('GeneName'),
        'file_comparison_data'  =>array('GeneName'),
    );

    $files_all_fields = array(

        'file_projects' =>array(
            'ID', 'Species', 'Name', 'Description', '_Analysis_ID', '_Platforms_ID', 'Platform', 'Platform_Type', 'PlatformName', 'ProjectIndex',
            'Disease', 'Accession', 'PubMed_ID', 'ExperimentType', 'ContactAddress', 'ContactOrganization',
            'ContactName', 'ContactEmail', 'ContactPhone', 'ContactWebLink', 'Keywords', 'ReleaseDate', 'Design', 'StudyType',
            'TherapeuticArea', 'Comment', 'Contributors', 'WebLink', 'PubMed', 'PubMed_Authors', 'Collection'
        ),

        'file_samples' =>array(
            'ID', 'Project_Name', '_Projects_ID', '_Platforms_ID', 'Platform', 'Platform_Type', 'PlatformName', 'Species', 'Name', 'Description', 'SampleIndex', 'CellType', 'DiseaseCategory', 'DiseaseState', 'Ethnicity', 'Gender', 'Infection', 'Organism',
            'Response', 'SamplePathology', 'SampleSource', 'SampleType', 'SamplingTime', 'Symptom',
            'TissueCategory', 'Tissue', 'Transfection', 'Treatment', 'Collection', 'Age', 'RIN_Number',
            'RNASeq_Total_Read_Count', 'RNASeq_Mapping_Rate', 'RNASeq_Assignment_Rate', 'Flag_To_Remove',
            'Flag_Remark', 'Uberon_ID', 'Uberon_Term'
        ),

        'file_comparisons' =>array(
            'ID', '_Analysis_ID', 'Project_Name', '_Projects_ID', '_Platforms_ID', 'Platform', 'Platform_Type', 'PlatformName', 'Species', 'Name', 'Description', 'ComparisonIndex',
            'Case_SampleIDs', 'Control_SampleIDs', 'ComparisonCategory', 'ComparisonContrast',
            'Case_DiseaseState', 'Case_Tissue', 'Case_CellType', 'Case_Ethnicity', 'Case_Gender',
            'Case_SamplePathology', 'Case_SampleSource', 'Case_Treatment', 'Case_SubjectTreatment',
            'Case_AgeCategory', 'ComparisonType', 'Control_DiseaseState', 'Control_Tissue',
            'Control_CellType', 'Control_Ethnicity', 'Control_Gender', 'Control_SamplePathology',
            'Control_SampleSource', 'Control_Treatment', 'Control_SubjectTreatment', 'Control_AgeCategory'
        ),

        'file_expression_data'  =>array('GeneName'),

        'file_comparison_data'  =>array('GeneName')
    );

    $column_alias = array(
		'Log2FoldChange'=>array('log2foldchange','logfc','log2fc','logfoldchange', 'log fc', 'log2 foldchange', 'log foldchange', 'log fold change'),
        'AdjustedPValue'=>array('adjustedpvalue','adj.p.val','fdr','adj.p.value','adj p val', 'adj p value', 'adjusted p value', 'adjusted p val'),
		'PValue'=>array('pvalue','p.value','p.val','p value', 'pval', 'p val')
	);

    $files_type_names = array(
        'file_projects'          => 'Project',
        'file_samples'           => 'Sample',
        'file_comparisons'       => 'Comparison',
        'file_expression_data'   => 'Sample Expression Data',
        'file_comparison_data'   => 'Comparison Data',
    );




    $file_time = date('YmdHis');
    $uploads_dir = $BXAF_CONFIG['USER_FILES']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $file_time . '/';
    if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0775, true);
    $uploads_url = $BXAF_CONFIG['USER_FILES_URL']['TOOL_IMPORT'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $file_time . '/';


    $file_contents = array();
    $errors = array();

    if(! is_array($_FILES) || count($_FILES) <= 0){
        $errors[] = "Error: Please upload at least one file to continue.";
    }
    else {

        foreach($_FILES as $upload_type => $f){

            $file_name = '';
            $delimiter = ",";
            $first_row = array();
            $first_row_flip = array();
            $key_field_values = array();

            if ($_FILES[$upload_type]["error"] == UPLOAD_ERR_OK) {

                $tmp_name = $_FILES[$upload_type]["tmp_name"];
                $file_name = $_FILES[$upload_type]["name"];
                $file_type = $_FILES[$upload_type]["type"];
                $file_size = $_FILES[$upload_type]["size"];

                if($file_size <= 10){
                    $errors[] = "Error: The uploaded file '$file_name' is empty.";
                }
                else {
                    if(! move_uploaded_file($tmp_name, "{$uploads_dir}{$upload_type}")){
                        $errors[] = "Error: The file '$file_name' can not be saved.";
                    }
                    else if (($handle = fopen("{$uploads_dir}{$upload_type}", "r")) !== FALSE) {

                        $delimiter = ",";
                        $first_row = fgetcsv($handle, 0, $delimiter);
                        if(! is_array($first_row) || count($first_row) <= 1){
                            $delimiter = "\t";
                            $first_row = fgetcsv($handle, 0, $delimiter);
                        }

                        if(! is_array($first_row) || count($first_row) <= 1 ){
                            $errors[] = "Error: Can not detect the delimiter of file '$file_name'! The file you uploaded must be either comma-separated (CSV) or Tab-delimited (TSV).";
                        }
                        else {

                            $found_required_columns = true;
                            foreach($files_required_fields[$upload_type] as $k){
                                if(! in_array($k, $first_row)){
                                    $errors[] = "Error: The file '$file_name' does not have the required column '$k'.";
                                    $found_required_columns = false;
                                }
                            }


                            if($found_required_columns && in_array($upload_type, array('file_projects', 'file_samples', 'file_comparisons'))){

                                foreach($first_row as $i=>$v){
                                    if(! in_array($v, $files_all_fields[$upload_type]) ){
                                        unset($first_row[$i]);
                                    }
                                }

                                $first_row_flip = array_flip($first_row);

                                while( ($row = fgetcsv($handle, 0, $delimiter)) !== FALSE ){
                                    if(count($row) < max(array_keys($first_row)) ) continue;
                                    foreach($files_required_fields[$upload_type] as $k){
                                        if($k == 'Case_SampleIDs' || $k == 'Control_SampleIDs'){
                                            $key_field_values[$k][ $first_row_flip[$k] ] = explode(";", $row[ $first_row_flip[$k] ]);
                                        }
                                        else $key_field_values[$k][ $row[ $first_row_flip[$k] ] ] = $first_row_flip[$k];
                                    }
                                }
                            }

                            else if($found_required_columns && $upload_type == 'file_expression_data'){

                                if(in_array('SampleName', $first_row) ){

                                    if(! in_array('Value', $first_row) ){
                                        $errors[] = "Error: The file '$file_name' does not have the required column 'Value'.";
                                        $found_required_columns = false;
                                    }

                                    foreach($first_row as $i=>$v){
                                        if(! in_array($v, array('GeneName', 'SampleName', 'Value') ) ){
                                            unset($first_row[$i]);
                                        }
                                    }
                                }
                                else {
                                    foreach($first_row as $i=>$v){
                                        if($v != 'GeneName'){
                                            $key_field_values['SampleName'][ $v ] = $i;
                                        }
                                    }
                                }

                                if($found_required_columns){
                                    $first_row_flip = array_flip($first_row);
                                    while( ($row = fgetcsv($handle, 0, $delimiter)) !== FALSE ){
                                        if(count($row) < max(array_keys($first_row)) ) continue;
                                        foreach($files_required_fields[$upload_type] as $k){
                                            $key_field_values[$k][ $row[ $first_row_flip[$k] ] ] = $first_row_flip[$k];
                                        }
                                        if(in_array('SampleName', $first_row) && $row[ $first_row_flip['SampleName'] ] != '') $key_field_values['SampleName'][ $row[ $first_row_flip['SampleName'] ] ] = $first_row_flip['SampleName'];
                                    }
                                }

                            }

                            else if($found_required_columns && $upload_type == 'file_comparison_data'){

                                if(in_array('ComparisonName', $first_row) ){

                                    // Convert column alias
                                    foreach($column_alias as $k=>$vals){
                                        foreach($first_row as $i=>$v){
                                            if(in_array(strtolower($v), $vals)) $first_row[$i] = $k;
                                        }
                                    }

                                    if(! in_array('Log2FoldChange', $first_row) ){
                                        $errors[] = "Error: The file '$file_name' does not have the required column 'Log2FoldChange'.";
                                        $found_required_columns = false;
                                    }
                                    if(! in_array('PValue', $first_row) ){
                                        $errors[] = "Error: The file '$file_name' does not have the required column 'PValue'.";
                                        $found_required_columns = false;
                                    }
                                    if(! in_array('AdjustedPValue', $first_row) ){
                                        $errors[] = "Error: The file '$file_name' does not have the required column 'AdjustedPValue'.";
                                        $found_required_columns = false;
                                    }

                                    foreach($first_row as $i=>$v){
                                        if(! in_array($v, array('GeneName', 'ComparisonName', 'Log2FoldChange', 'PValue', 'AdjustedPValue') ) ){
                                            unset($first_row[$i]);
                                        }
                                    }

                                }
                                else {
                                    foreach($first_row as $i=>$v){
                                        if($v != 'GeneName'){

                                            $type = get_name_and_type ($v, 'type');
                                            $name = get_name_and_type ($v, 'name');

                                            if(in_array($type, array('Log2FoldChange', 'AdjustedPValue', 'PValue'))) {
                                                $key_field_values['ComparisonName'][ $name ][$type] = $i;
                                            }
                                            else {
                                                unset($first_row[$i]);
                                            }
                                        }
                                    }
                                }

                                if($found_required_columns){
                                    $first_row_flip = array_flip($first_row);
                                    while( ($row = fgetcsv($handle, 0, $delimiter)) !== FALSE ){
                                        if(count($row) < max(array_keys($first_row)) ) continue;
                                        foreach($files_required_fields[$upload_type] as $k){
                                            $key_field_values[$k][ $row[ $first_row_flip[$k] ] ] = $first_row_flip[$k];
                                        }
                                        if(in_array('ComparisonName', $first_row) && $row[ $first_row_flip['ComparisonName'] ] != '') $key_field_values['ComparisonName'][ $row[ $first_row_flip['ComparisonName'] ] ] = $first_row_flip['ComparisonName'];
                                    }
                                }

                                if(! array_key_exists('ComparisonName', $key_field_values) || ! is_array($key_field_values['ComparisonName']) || count($key_field_values['ComparisonName']) <= 0){
                                    $errors[] = "Error: Can not detect 'Log2FoldChange', 'AdjustedPValue' or 'PValue' columns in file '$file_name'. Please import your data with <a href='data_import_adv.php' target='_blank'>Advanced Import Tool</a>";
                                }

                            }

                        }

                        fclose($handle);
                    }

                }

            }


            if(count($errors) <= 0){
                $file_contents[$upload_type]['file_name'] = $file_name;
                $file_contents[$upload_type]['first_row'] = $first_row;
                $file_contents[$upload_type]['first_row_maxlength'] = max(array_keys($first_row));
                $file_contents[$upload_type]['delimiter'] = $delimiter;
                $file_contents[$upload_type]['key_field_values'] = $key_field_values;

            }
        }
    }



    if(count($errors) > 0){
        $OUTPUT['detail'] = "<ul><li>" . implode("</li><li>", $errors) . "</li></ul>";
        echo json_encode($OUTPUT);
        exit();
    }


    $message = array();
    $errors = array();

    $imported_names_all = array();

    $info_all = array();
    foreach($file_contents as $upload_type => $values){

        if(in_array($upload_type, array('file_projects', 'file_samples', 'file_comparisons')) ){

            foreach($file_contents[$upload_type]['key_field_values']['Name'] as $k=>$v){
                $sql = "SELECT `ID` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name` = ?s";
                $found_id = $BXAF_MODULE_CONN -> get_one($sql, $files_tables[$upload_type], $k );
                if($found_id > 0) { $errors[] = "Error: The name '$k' is taken."; }
            }

            if(in_array($upload_type, array('file_comparisons')) ){
                foreach($file_contents[$upload_type]['key_field_values']['Case_SampleIDs'] as $k=>$v){
                    $sql = "SELECT `Name` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND BINARY `Name` IN (?a)";
                    $found_names = $BXAF_MODULE_CONN -> get_col($sql, $files_tables['file_samples'], $v );
                    if(! is_array($found_names)) $found_names = array();
                    foreach($v as $nm){
                        if( ! in_array($nm, $found_names)) { $errors[] = "Error: The sample name '$nm' in 'Case_SampleIDs' column of file '{$file_contents[$upload_type]['file_name']}' is not found."; }
                    }
                }
                foreach($file_contents[$upload_type]['key_field_values']['Control_SampleIDs'] as $k=>$v){
                    $sql = "SELECT `Name` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND BINARY `Name` IN (?a)";
                    $found_names = $BXAF_MODULE_CONN -> get_col($sql, $files_tables['file_samples'], $v );
                    if(! is_array($found_names)) $found_names = array();
                    foreach($v as $nm){
                        if( ! in_array($nm, $found_names)) { $errors[] = "Error: The sample name '$nm' in 'Control_SampleIDs' column of file '{$file_contents[$upload_type]['file_name']}' is not found."; }
                    }
                }
            }

            if (count($errors) <= 0 && ($handle = fopen("{$uploads_dir}{$upload_type}", "r")) !== FALSE) {

                $delimiter = $file_contents[$upload_type]['delimiter'];
                $first_row = $file_contents[$upload_type]['first_row'];
                $first_row_flip = array_flip($first_row);

                //Skip first row
                fgetcsv($handle, 0, $delimiter);
                while( ($row = fgetcsv($handle, 0, $delimiter)) !== FALSE ){

                    if(count($row) < max(array_keys($first_row)) ) continue;

                    $info = array(
                        '_Owner_ID'     => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
                        'Time_Created'  => date("Y-m-d H:i:s")
                    );

                    if(in_array('Project_Name', $first_row) ){

                        $project_name = $row[ $first_row_flip['Project_Name'] ];

                        $sql = "SELECT `ID`, `Name` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']}";
                        $all_projects = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']);

                        if(in_array($project_name, $all_projects) ) {
                            $sql = "SELECT `ID`, `Name`, `Species`, `_Platforms_ID`, `Platform`, `PlatformName`, `Platform_Type` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name` = ?s";
                            $temp_info = $BXAF_MODULE_CONN -> get_row($sql, $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $project_name );

                            $info['Species']       = $temp_info['Species'];

                            $info['_Projects_ID']  = $temp_info['ID'];
                            $info['Project_Name']  = $temp_info['Name'];

                            $info['_Platforms_ID'] = $temp_info['_Platforms_ID'];
                            $info['Platform']      = $temp_info['Platform'];
                            $info['PlatformName']  = $temp_info['PlatformName'];
                            $info['Platform_Type'] = $temp_info['Platform_Type'];
                        }

                    }

                    if(in_array('Platform', $first_row) && ! array_key_exists('Platform', $info) ){
                        $platform = $row[ $first_row_flip['Platform'] ];
                        foreach($all_species as $Species){
                            if(array_key_exists($platform, $all_platforms[$Species]) ) {
                                $info['Species'] = $Species;

                                $info['_Platforms_ID'] = $all_platforms[$Species][$platform]['ID'];
                                $info['Platform']      = $all_platforms[$Species][$platform]['GEO_Accession'];
                                $info['PlatformName']  = $all_platforms[$Species][$platform]['Name'];
                                $info['Platform_Type'] = $all_platforms[$Species][$platform]['Type'];
                            }
                        }
                    }

                    if(in_array('Species', $first_row) && ! array_key_exists('Species', $info) ){
                        $Species = $row[ $first_row_flip['Species'] ];
                        if( in_array($Species, $all_species) ) {
                            $info['Species'] = $Species;
                        }
                    }

                    if( ! array_key_exists('Species', $info) || $info['Species'] == '' ){
                        $info['Species'] = $_SESSION['SPECIES_DEFAULT'];
                    }

                    if( ! array_key_exists('Platform', $info) || $info['Platform'] == '' ){
                        $Species = $info['Species'];
                        $info['_Platforms_ID'] = $all_platforms[ $Species ]['NGS_' . $Species]['ID'];
                        $info['Platform']      = $all_platforms[ $Species ]['NGS_' . $Species]['GEO_Accession'];
                        $info['PlatformName']  = $all_platforms[ $Species ]['NGS_' . $Species]['Name'];
                        $info['Platform_Type'] = $all_platforms[ $Species ]['NGS_' . $Species]['Type'];
                    }

                    foreach($first_row as $j=>$c){
                        if( ! in_array($c, array('Platform', 'Species', 'Project_Name'))) {
                            $info[$c] = $row[$j];
                        }
                    }

                    $info_all[$upload_type][] = $info;
                    $imported_names_all[$upload_type][] = $info['Name'];
                }
                fclose($handle);

                $BXAF_MODULE_CONN -> insert_batch($files_tables[$upload_type], $info_all[$upload_type] );

            }

            if (count($errors) <= 0){
                // Check exists
                $sql = "SELECT `Name`, `ID` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND BINARY `Name` IN (?a)";
                $found_nameids = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, $files_tables[$upload_type], $imported_names_all[$upload_type] );
                if(! is_array($found_nameids)) $found_nameids = array();

                $url = "../tool_search/view.php?type={$upload_type}&id=";
                if($upload_type == 'file_projects') $url = "../project.php?id=";

                $message[] = "<span class='font-weight-bold lead'>File '" . $file_contents[$upload_type]['file_name'] . "' has been imported successfully.</span>";
                foreach($imported_names_all[$upload_type] as $name){
                    if(array_key_exists($name, $found_nameids)) $message[] = "<span class='text-success'>" . $files_type_names[$upload_type] . " <a target='_blank' href='$url" . $found_nameids[$name] . "'>" . $name . "</a> is created.</span>";
                    else $message[] = "<span class='text-danger'>" . $files_type_names[$upload_type] . " '" . $name . "' is not created.</span>";
                }
            }

        }

        else if($upload_type == 'file_expression_data'){

            $all_gene_nameindex = array();
            $all_gene_indexname = array();
            if(is_array($file_contents[$upload_type]['key_field_values']['GeneName']) && count($file_contents[$upload_type]['key_field_values']['GeneName']) > 0){

                $all_GeneName = array_keys($file_contents[$upload_type]['key_field_values']['GeneName']);
                foreach($all_GeneName as $i=>$v) $all_GeneName[$i] = preg_replace("/\..*$/", "", $v );


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
            }

            foreach($file_contents[$upload_type]['key_field_values']['SampleName'] as $k=>$v){
                $sql = "SELECT `ID` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND BINARY `Name` = ?s";
                $found_id = $BXAF_MODULE_CONN -> get_one($sql, $files_tables['file_samples'], $k );
                if($found_id <= 0) unset($file_contents[$upload_type]['key_field_values']['SampleName'][$k] );
            }
            if(count($file_contents[$upload_type]['key_field_values']['SampleName']) <= 0) { $errors[] = "Error: No valid sample names found."; }

            if(! in_array('SampleName', $file_contents[$upload_type]['first_row']) ){
                foreach($file_contents[$upload_type]['first_row'] as $k=>$v){
                    if($v != 'GeneName' && ! array_key_exists($v, $file_contents[$upload_type]['key_field_values']['SampleName'])) unset($file_contents[$upload_type]['first_row'][$k]);
                }
            }



            $all_sample_info = array();
            $sql = "SELECT `ID`, `Name`, `Species`, `_Projects_ID`, `Project_Name`, `Platform_Type` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND BINARY `Name` IN (?a)";
            $all_sample_info = $BXAF_MODULE_CONN -> get_all($sql, $files_tables['file_samples'], array_keys($file_contents[$upload_type]['key_field_values']['SampleName']) );

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

            if (count($errors) <= 0 && ($handle = fopen("{$uploads_dir}{$upload_type}", "r")) !== FALSE) {

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

                $delimiter = $file_contents[$upload_type]['delimiter'];
                $first_row = $file_contents[$upload_type]['first_row'];
                $first_row_flip = array_flip($first_row);

                //Skip first row
                fgetcsv($handle, 0, $delimiter);

                if(in_array('SampleName', $first_row)){
                    while( ($row = fgetcsv($handle, 0, $delimiter)) !== FALSE ){

                        if(count($row) < max(array_keys($first_row)) ) continue;

                        $gene = $row[ $first_row_flip['GeneName'] ];
                        $sample = $row[ $first_row_flip['SampleName'] ];
                        $value = floatval($row[ $first_row_flip['Value'] ]);
                        $project_id = $sample_nameprojects[$sample];
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
                }
                else {
                    while( ($row = fgetcsv($handle, 0, $delimiter)) !== FALSE ){

                        if(count($row) < max(array_keys($first_row)) ) continue;

                        $gene = $row[ $first_row_flip['GeneName'] ];

                        foreach($file_contents[$upload_type]['key_field_values']['SampleName'] as $sample=>$i){

                            $project_id = $sample_nameprojects[$sample];
                            $Species = $sample_namespecies[ $sample ];
                            $gene_index = $all_gene_nameindex[$Species][$gene];
                            $value = floatval($row[ $i ]);

                            if(array_key_exists($gene, $all_gene_nameindex[$Species])){
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

                    }

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

                    // copy($expression_data_genes_found_file[$project_id], $target_file);

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

                fclose($handle);
            }


            if (count($errors) <= 0){
                $message[] = "<span class='font-weight-bold lead'>File '" . $file_contents[$upload_type]['file_name'] . "' has been imported successfully.</span>";
                $message[] = "<span class='text-success'>Expression data imported: " . $expression_data_genes_found . " / " . $expression_data_genes_total . ".</span>";
                $message[] = "<span class='text-success'><a target='_blank' href='$expression_data_genes_not_found_file_url'><i class='fas fa-download'></i> Expression data with genes not found  (CSV file).</a>.</span>";
                // $message[] = "<span class='text-success'>A background task has been scheduled to process expression data with tabix tool.</span>";
            }

        }

        else if($upload_type == 'file_comparison_data'){

            $all_gene_nameindex = array();
            $all_gene_indexname = array();
            if(is_array($file_contents[$upload_type]['key_field_values']['GeneName']) && count($file_contents[$upload_type]['key_field_values']['GeneName']) > 0){

                $all_GeneName = array_keys($file_contents[$upload_type]['key_field_values']['GeneName']);
                foreach($all_GeneName as $i=>$v) $all_GeneName[$i] = preg_replace("/\..*$/", "", $v );


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

            }


            foreach($file_contents[$upload_type]['key_field_values']['ComparisonName'] as $k=>$v){
                $sql = "SELECT `ID` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Name` = ?s";
                $found_id = $BXAF_MODULE_CONN -> get_one($sql, $files_tables['file_comparisons'], $k );
                if($found_id <= 0) unset($file_contents[$upload_type]['key_field_values']['ComparisonName'][$k] );
            }
            if(count($file_contents[$upload_type]['key_field_values']['ComparisonName']) <= 0) { $errors[] = "Error: No valid comparison names found."; }


            if( in_array('ComparisonName', $file_contents[$upload_type]['first_row']) ){
                foreach($file_contents[$upload_type]['first_row'] as $k=>$v){
                    if(! in_array($v, array('GeneName', 'ComparisonName', 'Log2FoldChange', 'PValue', 'AdjustedPValue') ) ) unset($file_contents[$upload_type]['first_row'][$k]);
                }
            }
            else if(! in_array('ComparisonName', $file_contents[$upload_type]['first_row']) ){
                $all_comparison_columns = array();
                foreach($file_contents[$upload_type]['key_field_values']['ComparisonName'] as $k=>$v){
                    $all_comparison_columns = array_merge($all_comparison_columns, array_values($v) );
                }
                foreach($file_contents[$upload_type]['first_row'] as $k=>$v){
                    if($v != 'GeneName' && ! in_array($k, $all_comparison_columns) ) unset($file_contents[$upload_type]['first_row'][$k]);
                }
            }


            $all_comparison_info = array();
            $sql = "SELECT `ID`, `Name`, `Species`, `_Projects_ID`, `Project_Name`, `Platform_Type` FROM ?n WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND BINARY `Name` IN (?a)";
            $all_comparison_info = $BXAF_MODULE_CONN -> get_all($sql, $files_tables['file_comparisons'], array_keys($file_contents[$upload_type]['key_field_values']['ComparisonName']) );

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


            if (count($errors) <= 0 && ($handle = fopen("{$uploads_dir}{$upload_type}", "r")) !== FALSE) {

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
                foreach($file_contents[$upload_type]['key_field_values']['ComparisonName'] as $comparison=>$v){
                    $comparison_id  = $comparison_nameids[ $comparison ];
                    $comparison_data_comparison_file[$comparison_id] = $uploads_dir . 'comp_' . $comparison_id . '.csv';
                    $comparison_data_comparison_file_handle[$comparison_id] = fopen($uploads_dir . 'comp_' . $comparison_id . '.csv', "w");
                    fputcsv($comparison_data_comparison_file_handle[$comparison_id], array('GeneName', 'Log2FoldChange', 'PValue', 'AdjustedPValue'));
                }

                $comparison_data_genes_total = 0;
                $comparison_data_genes_found = 0;
                $comparison_data_genes_not_found = 0;

                $delimiter = $file_contents[$upload_type]['delimiter'];
                $first_row = $file_contents[$upload_type]['first_row'];
                $first_row_flip = array_flip($first_row);

                //Skip first row
                fgetcsv($handle, 0, $delimiter);

                if(in_array('ComparisonName', $first_row)){
                    while( ($row = fgetcsv($handle, 0, $delimiter)) !== FALSE ){

                        if(count($row) < max(array_keys($first_row)) ) continue;

                        $gene = $row[ $first_row_flip['GeneName'] ];
                        $comparison = $row[ $first_row_flip['ComparisonName'] ];

                        $Log2FoldChange = floatval($row[ $first_row_flip['Log2FoldChange'] ]);
                        $PValue = floatval($row[ $first_row_flip['PValue'] ]);
                        $AdjustedPValue = floatval($row[ $first_row_flip['AdjustedPValue'] ]);

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
                            $info = array($gene, $comparison, $Log2FoldChange, $PValue, $AdjustedPValue);
                            fputcsv( $comparison_data_genes_not_found_file_handle, $info);
                            $comparison_data_genes_not_found++;
                        }
                        $comparison_data_genes_total++;
                    }
                }
                else {
                    while( ($row = fgetcsv($handle, 0, $delimiter)) !== FALSE ){

                        if(count($row) < max(array_keys($first_row)) ) continue;

                        $gene = $row[ $first_row_flip['GeneName'] ];

                        foreach($file_contents[$upload_type]['key_field_values']['ComparisonName'] as $comparison => $v){

                            $Log2FoldChange = floatval( $row[ $v['Log2FoldChange'] ] );
                            $PValue = floatval( $row[ $v['PValue'] ] );
                            $AdjustedPValue = floatval( $row[ $v['AdjustedPValue'] ] );

                            $project_id = $comparison_nameprojects[$comparison];
                            $Species = $comparison_namespecies[ $comparison ];

                            $project_id = $comparison_nameprojects[$comparison];
                            $Species = $comparison_namespecies[ $comparison ];

                            $gene_index = $all_gene_nameindex[$Species][$gene];
                            $comparison_index = $comparison_nameids[$comparison];

                            if(array_key_exists($gene, $all_gene_nameindex[$Species])){

                                $info = array($gene_index, $comparison_index, $all_gene_indexname[$Species][ $gene_index ], $comparison, $Log2FoldChange, $PValue, $AdjustedPValue);
                                fputcsv( $comparison_data_project_file_handle[$project_id], $info, "\t");

                                $info = array($all_gene_indexname[$Species][ $gene_index ], $Log2FoldChange, $PValue, $AdjustedPValue);
                                fputcsv( $comparison_data_comparison_file_handle[$comparison_index], $info);

                                $comparison_data_genes_found++;
                            }
                            else {
                                $info = array($gene, $comparison, $Log2FoldChange, $PValue, $AdjustedPValue);
                                fputcsv( $comparison_data_genes_not_found_file_handle, $info);
                                $comparison_data_genes_not_found++;
                            }

                            $comparison_data_genes_total++;
                        }
                    }
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

                foreach($file_contents[$upload_type]['key_field_values']['ComparisonName'] as $comparison=>$v){
                    $comparison_index  = $comparison_nameids[ $comparison ];
                    fclose ( $comparison_data_comparison_file_handle[$comparison_index] );


                    $Species = $comparison_namespecies[ $comparison ];

                    // Output comparison data to a folder
                    $dir_go_output = $BXAF_CONFIG['GO_OUTPUT'][strtoupper($Species)] . 'comp_' . $comparison_index;
                    if(!file_exists($dir_go_output)) mkdir($dir_go_output, 0755, true);

                    $target_file = "$dir_go_output/comp_{$comparison_index}.csv";
                    if(file_exists($target_file)) shell_exec("rm -rf $dir_go_output/*");
                    copy($comparison_data_comparison_file[$comparison_index], $target_file);

                    // GO and PAGE Analysis
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

            }


            if (count($errors) <= 0){
                $message[] = "<span class='font-weight-bold lead'>File '" . $file_contents[$upload_type]['file_name'] . "' has been imported successfully.</span>";
                $message[] = "<span class='text-success'>Comparison data imported: " . $comparison_data_genes_found . " / " . $comparison_data_genes_total . ".</span>";
                $message[] = "<span class='text-success'><a target='_blank' href='$comparison_data_genes_not_found_file_url'><i class='fas fa-download'></i> Comparison data with genes not found  (CSV file).</a>.</span>";
                $message[] = "<span class='text-success'>A background task has been scheduled for GO analysis and PAGE analysis.</span>";
            }

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


//****************************************************************************************************
// Fetch Platform Data
//****************************************************************************************************
if (isset($_GET['action']) && $_GET['action'] == 'get_platform_info') {

    $sql = "SELECT * FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']."` WHERE `bxafStatus` < 5 AND `ID` = " . intval($_GET['id']);
    $info = $BXAF_MODULE_CONN->get_row($sql);

    header('Content-type: application/json');
    echo json_encode($info);

    exit();
}


//****************************************************************************************************
// Delete Platform Data
//****************************************************************************************************
if (isset($_GET['action']) && $_GET['action'] == 'delete_platform_info') {

    $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']."` WHERE `bxafStatus` < 5 AND `ID` = " . intval($_GET['id']);
    $id = $BXAF_MODULE_CONN->get_one($sql);

    if($id > 0){
        $info = array('bxafStatus'=>9);
        $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], $info, "`ID` = $id");
    }
    else {
        echo "Error: record is not found. ";
        exit();
    }

    exit();
}


//****************************************************************************************************
// Save Platform Data
//****************************************************************************************************
if (isset($_GET['action']) && $_GET['action'] == 'save_platform_info') {

    // echo "Error: <pre>" . print_r($_POST, true) . "</pre>"; exit();

    $platform_id = intval($_POST['ID']);
    if($platform_id > 0){

        $sql = "SELECT `ID` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `ID` = " . $platform_id;
        $platform_id = $BXAF_MODULE_CONN->get_one($sql);

        if($platform_id > 0){
            $info = array(
                'Name'=>$_POST['Name'],
                'Type'=>$_POST['Type'],
                'Species'=>$_POST['Species'],
                'GEO_Accession'=>$_POST['GEO_Accession'],
                'Manufacturer'=>$_POST['Manufacturer'],
            );
            $platform_id = $BXAF_MODULE_CONN->update($BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], $info, "`ID` = $platform_id");

            if($platform_id <= 0) echo "Error: Can not update platform information!";

        }
    }

    if($platform_id <= 0){
        $info = array(
            'Name'=>$_POST['Name'],
            'Type'=>$_POST['Type'],
            'Species'=>$_POST['Species'],
            'GEO_Accession'=>$_POST['GEO_Accession'],
            'Manufacturer'=>$_POST['Manufacturer'],
            '_Owner_ID'    => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
     		'Time_Created' => date("Y-m-d H:i:s")
        );
        $platform_id = $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], $info);

        if($platform_id <= 0) echo "Error: Can not save platform information!";

    }

    exit();
}


//****************************************************************************************************
// Fetch Platform Data
//****************************************************************************************************
if (isset($_GET['action']) && $_GET['action'] == 'fetch_platform_info') {

    $platform_names_pre = preg_split("/[\s,]+/", $_POST['platform_names'], NULL, PREG_SPLIT_NO_EMPTY);

    $platform_names = array();
    foreach($platform_names_pre as $key => $value) {
        if (trim($value) != '') $platform_names[] = trim($value);
    }

    // Save in databases
    $sql = "SELECT `GEO_Accession` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']."` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `GEO_Accession` IN (?a)";
    $existing_platforms = $BXAF_MODULE_CONN->get_col($sql, $platform_names);


    //-------------------------------------------------------------------
    // Get info for each platform
    //-------------------------------------------------------------------
    $platforms_exist = array();
    $platforms_not_found = array();
    $platforms_found = array();
    $datetime = date("Y-m-d H:i:s");

    foreach ($platform_names as $platform_name) {

        // Do not overwrite existing record
        if(is_array($existing_platforms) && in_array($platform_name, $existing_platforms)) {
            $platforms_exist[] = $platform_name;
            continue;
        }

        $url = "https://www.ncbi.nlm.nih.gov/geo/query/acc.cgi?acc={$platform_name}&targ=self&form=text&view=quick";
        $content = file_get_contents($url, false, NULL, 0, 4096);

        if (!isset($content) || substr($content, 0, 9) != '^PLATFORM') {
            $platforms_not_found[] = $platform_name;
            continue;
        }

        // Generate new record
        $new_record = array(
            'GEO_Accession' => $platform_name,
            'Species' => 'Human',
            'Type' => 'Array',
     		'_Owner_ID' => 0,
     		'Time_Created' => $datetime
     	);


        $content_rows = explode("\n", $content);
        foreach ($content_rows as $key => $row) {

            // Skip rows after 20
            if ($key > 50) break;

            if (substr($row, 0, 15) == '!Platform_title') { // Name & Title
                $new_record['Title'] = substr($row, 18);
                $new_record['Name'] = substr($row, 18);
            }
            if (substr($row, 0, 16) == '!Platform_status') { // Status
                $new_record['Status'] = substr($row, 19);
            }
            if (substr($row, 0, 25) == '!Platform_submission_date') { // Submission Date
                $new_record['Submission_Date'] = substr($row, 28);
            }
            if (substr($row, 0, 26) == '!Platform_last_update_date') { // Last Updated Date
                $new_record['Last_Updated_Date'] = substr($row, 29);
            }
            if (substr($row, 0, 20) == '!Platform_technology') { // Technology
                $new_record['Technology'] = trim(substr($row, 23));

                // Check Type
                if ($new_record['Technology'] == 'high-throughput sequencing') {
                    $new_record['Type'] = 'NGS';
                }

            }
            if (substr($row, 0, 22) == '!Platform_distribution') { // Distribution
                $new_record['Distribution'] = substr($row, 25);
            }
            if (substr($row, 0, 18) == '!Platform_organism') { // Organism

                $new_record['Organism'] = trim(substr($row, 21));

                // Check Species
                if ($new_record['Organism'] == 'Homo sapiens') {
                    $new_record['Species'] = 'Human';
                }
                else if ($new_record['Organism'] == 'Mus musculus') {
                    $new_record['Species'] = 'Mouse';
                }
                else if ($new_record['Organism'] == 'Rattus norvegicus') {
                    $new_record['Species'] = 'Rat';
                }
                else {
                    $new_record['Species'] = $new_record['Organism'];
                }
            }
            if (substr($row, 0, 15) == '!Platform_taxid') { // TaxID
                $new_record['TaxID'] = substr($row, 18);
            }
            if (substr($row, 0, 22) == '!Platform_manufacturer') { // Manufacturer
                $new_record['Manufacturer'] = substr($row, 25);
            }

        }

        foreach($new_record as $k=>$v) $new_record[$k] = trim($v);

     	$platform_id = $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], $new_record);

        if($platform_id > 0) $platforms_found[] = $new_record;

    }

    header('Content-type: application/json');
    $OUTPUT = array(
        'platforms_found' => $platforms_found,
        'platforms_exist' => $platforms_exist,
        'platforms_not_found' => $platforms_not_found
    );
    echo json_encode($OUTPUT);

    exit();
}



?>