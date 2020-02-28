<?php

include_once(dirname(dirname(__DIR__)) . '/config.php');

if(! is_array($BXAF_CONFIG) || ! array_key_exists('BXAF_KEY', $BXAF_CONFIG) || $BXAF_CONFIG['BXAF_KEY'] == ''){
	$BXAF_CONFIG['BXAF_KEY'] = "bxgenomics";
}

$BXAF_CONFIG['BXAF_USER_CONTACT_ID'] = intval( $_SESSION[$BXAF_CONFIG['BXAF_LOGIN_KEY']] );

ini_set('auto_detect_line_endings',TRUE);



$BXAF_CONFIG['BXAF_PAGE_FOOTER'] = dirname(__DIR__) . '/page_footer.php';




$BXAF_CONFIG['APP_DB_DRIVER'] 		= $BXAF_CONFIG['BXAF_DB_DRIVER'];
$BXAF_CONFIG['APP_DB_SERVER'] 		= $BXAF_CONFIG['BXAF_DB_SERVER'];
$BXAF_CONFIG['APP_DB_NAME'] 	    = $BXAF_CONFIG['BXAF_DB_NAME'];
$BXAF_CONFIG['APP_DB_USER'] 	    = $BXAF_CONFIG['BXAF_DB_USER'];
$BXAF_CONFIG['APP_DB_PASSWORD'] 	= $BXAF_CONFIG['BXAF_DB_PASSWORD'];

$BXAF_MODULE_CONN = bxaf_get_app_db_connection();


$BXAF_CONFIG['QUERY_OWNER_FILTER']   = " (`bxafStatus` < 5 AND `_Owner_ID`=" . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . ") ";
$BXAF_CONFIG['QUERY_DEFAULT_FILTER'] = " (`bxafStatus` < 5 AND (`_Owner_ID` IS NULL OR `_Owner_ID`=0 OR `_Owner_ID`='' OR `_Owner_ID`=" . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . ") ) ";
$BXAF_CONFIG['QUERY_ACTIVE_FILTER']  = " (`bxafStatus` < 5) ";

$BXAF_CONFIG['BXAF_MODULE_ADMIN_LOGIN_NAMES'] = array(
	'ron+1@bioinforx.com'
);



$BXAF_CONFIG['PLATFORM_ID_DEFAULT'] = 1; // Default platform: (Human: NGS) NGS_Human: Generic Human NGS Platform, ID = 1
$BXAF_CONFIG['BXGENOMICS_URL'] 	= $BXAF_CONFIG['BXAF_APP_URL'] . 'bxgenomics';

include_once(__DIR__ . '/config_functions.php');
include_once(__DIR__ . '/config_functions_shell.php');
include_once(__DIR__ . '/config_functions_supplement.php');
include_once(__DIR__ . '/config_functions_id_name.php');
include_once(__DIR__ . '/config_user_preferences.php');

include_once(__DIR__ . '/class/ServerControl.class.php');
include_once(__DIR__ . '/class/SingleAnalysis.class.php');

include_once(__DIR__ . '/library_tabix.php');



$BXAF_CONFIG['BXGENOMICS_DB_TABLES'] = array(
	'TBL_BXGENOMICS_DATA'           => 'tbl_bxgenomics_data',
	'TBL_BXGENOMICS_SAMPLE'         => 'tbl_bxgenomics_sample',
	'TBL_BXGENOMICS_PROCESS'        => 'tbl_bxgenomics_process',
	'TBL_BXGENOMICS_ANALYSIS'       => 'tbl_bxgenomics_analysis',
	'TBL_BXGENOMICS_SETTING'        => 'tbl_bxgenomics_setting',
	'TBL_BXGENOMICS_EXPERIMENT'     => 'tbl_bxgenomics_experiment',

	'TBL_BXGENOMICS_GENES'               => 'TBL_Genes',
	'TBL_BXGENOMICS_PROJECTS'            => 'TBL_Projects',
	'TBL_BXGENOMICS_SAMPLES'             => 'TBL_Samples',
	'TBL_BXGENOMICS_COMPARISONS'         => 'TBL_Comparisons',
	'TBL_BXGENOMICS_PLATFORMS'           => 'TBL_Platforms',
	'TBL_BXGENOMICS_USERSAVEDRESULTS'    => 'TBL_UserSavedResults',
	'TBL_BXGENOMICS_USERSAVEDLISTS'      => 'TBL_UserSavedLists',
	'TBL_BXGENOMICS_USERPREFERENCE'      => 'TBL_UserPreference',
	'TBL_BXGENOMICS_GENES_INDEX'         => 'TBL_BXGENOMICS_GENES_INDEX',
);

foreach ($BXAF_CONFIG['BXGENOMICS_DB_TABLES'] as $key => $value) {
	$BXAF_CONFIG[$key] = $value;
}







if(! isset($_SESSION['SPECIES_DEFAULT']) || $_SESSION['SPECIES_DEFAULT'] == ''){
	$_SESSION['SPECIES_DEFAULT'] = 'Mouse';
}
if(! isset($_SESSION['BXAF_ADVANCED_USER']) || ! $_SESSION['BXAF_ADVANCED_USER']){
	$_SESSION['BXAF_ADVANCED_USER'] = true;
}







//--------------------------------------------------------------------------------------------------
// User Preferences
//--------------------------------------------------------------------------------------------------
$BXAF_CONFIG['USER_PREFERENCES'] = config_load_user_preferences();


//Check if all database tables are available. If some are missing, create them
$all_tables = $BXAF_MODULE_CONN->get_col("SHOW TABLES");
foreach($BXAF_CONFIG['BXGENOMICS_DB_TABLES'] as $k=>$tbl){
	if(! in_array($tbl, $all_tables)) {
		die("Critical error: Database table '$tbl' is missing.");
	}
}



$BXAF_CONFIG['ANALYSIS_SUBDIR'] = "app_data/analysis/";
$BXAF_CONFIG['ANALYSIS_DIR'] = $BXAF_CONFIG['BXAF_DIR'] . $BXAF_CONFIG['ANALYSIS_SUBDIR'];
$BXAF_CONFIG['ANALYSIS_URL'] = $BXAF_CONFIG['BXAF_URL'] . $BXAF_CONFIG['ANALYSIS_SUBDIR'];

$BXAF_CONFIG['SAMPLE_SUBDIR'] = "app_data/files_sample/";
$BXAF_CONFIG['SAMPLE_DIR'] = $BXAF_CONFIG['BXAF_DIR'] . $BXAF_CONFIG['SAMPLE_SUBDIR'];
$BXAF_CONFIG['SAMPLE_URL'] = $BXAF_CONFIG['BXAF_URL'] . $BXAF_CONFIG['SAMPLE_SUBDIR'];

if(!file_exists($BXAF_CONFIG['ANALYSIS_DIR'])) mkdir($BXAF_CONFIG['ANALYSIS_DIR'], 0755, true);
if(!file_exists($BXAF_CONFIG['SAMPLE_DIR'])) mkdir($BXAF_CONFIG['SAMPLE_DIR'], 0755, true);



$BXAF_CONFIG['GO_OUTPUT'] = array(
  'MOUSE' => "{$BXAF_CONFIG['BXAF_DIR']}app_data/go_output/mouse/",
);
$BXAF_CONFIG['GO_OUTPUT_URL'] = array(
  'MOUSE' => "{$BXAF_CONFIG['BXAF_URL']}app_data/go_output/mouse/",
);
foreach($BXAF_CONFIG['GO_OUTPUT'] as $k=>$f){
	if(!file_exists($f)) mkdir($f, 0775, true);
}

$BXAF_CONFIG['PAGE_OUTPUT'] = array(
  'MOUSE' => "{$BXAF_CONFIG['BXAF_DIR']}app_data/page_output/mouse/"
);
$BXAF_CONFIG['PAGE_OUTPUT_URL'] = array(
  'MOUSE' => "{$BXAF_CONFIG['BXAF_URL']}app_data/page_output/mouse/"
);
foreach($BXAF_CONFIG['PAGE_OUTPUT'] as $k=>$f){
	if(!file_exists($f)) mkdir($f, 0775, true);
}

$BXAF_CONFIG['USER_FILES_URL'] = array(
  'TOOL_PROJECTS'      => "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/user_files_projects/",
  'TOOL_BUBBLE_PLOT'   => "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/user_files_bubble_plot/",
  'TOOL_META_ANALYSIS' => "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/user_files_meta_analysis/",
  'TOOL_EXPORT'        => "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/user_files_export/",
  'TOOL_PCA'           => "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/user_files_pca/",
  'TOOL_IMPORT'        => "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/user_files_import/",
  'TOOL_VOLCANO'       => "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/user_files_volcano/",
  'TOOL_FUNCTIONAL_ENRICHMENT'     => "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/tool_functional_enrichment/",
  'TOOL_CACHE'         => "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/tool_cache/",
);
$BXAF_CONFIG['USER_FILES'] = array(
  'TOOL_PROJECTS'      => "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/user_files_projects/",
  'TOOL_BUBBLE_PLOT'   => "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/user_files_bubble_plot/",
  'TOOL_META_ANALYSIS' => "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/user_files_meta_analysis/",
  'TOOL_EXPORT'        => "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/user_files_export/",
  'TOOL_PCA'           => "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/user_files_pca/",
  'TOOL_IMPORT'        => "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/user_files_import/",
  'TOOL_VOLCANO'       => "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/user_files_volcano/",
  'TOOL_FUNCTIONAL_ENRICHMENT'     => "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/tool_functional_enrichment/",
  'TOOL_CACHE'         => "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/tool_cache/",
);
foreach($BXAF_CONFIG['USER_FILES'] as $k=>$f){
	if(!file_exists($f)) mkdir($f, 0775, true);
}


/*****************************
 * For Tabix
 ****************************/

$BXAF_CONFIG['TABIX_MAX_INPUT_ARRAY_SIZE'] = 1000;
$BXAF_CONFIG['TABIX_MAX_OUTPUT_ARRAY_SIZE'] = 10000000;

$BXAF_CONFIG['TABIX_DIR'] = "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/tabix/";
$BXAF_CONFIG['TABIX_URL'] = "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/tabix/";

$BXAF_CONFIG['TABIX_IMPORT_DIR'] = "{$BXAF_CONFIG['BXAF_DIR']}app_data/cache/tabix/import/";
$BXAF_CONFIG['TABIX_IMPORT_URL'] = "{$BXAF_CONFIG['BXAF_URL']}app_data/cache/tabix/import/";

if(!file_exists($BXAF_CONFIG['TABIX_IMPORT_DIR'])) mkdir($BXAF_CONFIG['TABIX_IMPORT_DIR'], 0775, true);

$BXAF_CONFIG['TABIX_INDEX'] = array(
	'Mouse'=>array(
	)
);




/*****************************
 * Set public/shared server folder
 ****************************/
$BXAF_CONFIG['BXGENOMICS_SERVER_FILES_SHARED'] = $BXAF_CONFIG['BXAF_ROOT_DIR'] . $BXAF_CONFIG['BXAF_BXFILES_SUBDIR_SHARED'];

/*****************************
 * Set private folder if login
 ****************************/
if(isset($BXAF_CONFIG['BXAF_USER_CONTACT_ID']) && intval($BXAF_CONFIG['BXAF_USER_CONTACT_ID']) != 0){

	$BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'] = $BXAF_CONFIG['BXAF_ROOT_DIR'] . $BXAF_CONFIG['BXAF_BXFILES_SUBDIR_PRIVATE'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '_' . bxaf_encrypt($BXAF_CONFIG['BXAF_USER_CONTACT_ID'], $BXAF_CONFIG['BXAF_KEY']) . DIRECTORY_SEPARATOR;

	// Create private if not exist
	if(!is_dir($BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'])) {
		mkdir($BXAF_CONFIG['BXGENOMICS_SERVER_FILES_PRIVATE'], 0775, true);
	}
}



$BXAF_CONFIG['RNA_SEQ_WORKFLOW'] = array(
	0 => 'Raw Data QC',
	1 => 'Alignment with Subread',
	2 => 'Gene Counts and QC',
	3 => 'DEG, GSEA and GO Analysis'
);

$BXAF_CONFIG['RNA_SEQ_WORKFLOW_LOG'] = array(
	'step_0.log',
	'step_1.log',
	'step_2.log',
	'step_3.log'
);

$BXAF_CONFIG['RNA_SEQ_WORKFLOW_SCRIPT'] = array(
	'step_0.sh',
	'step_1.sh',
	'step_2.sh',
	'step_3.sh'
);

$BXAF_CONFIG['RNA_SEQ_WORKFLOW_CHECK_FINISHED'] = array(
	'finished_step_0',
	'finished_step_1',
	'finished_step_2',
	'finished_step_3'
);



$sql = "SELECT `Keyword`, `Detail` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SETTING']."` WHERE `Type`='Program Location'";
$BXAF_CONFIG['PROGRAM_DIR'] = $BXAF_MODULE_CONN->GetAssoc($sql);

$BXAF_CONFIG['Mouse_genome_index']         = $BXAF_CONFIG['BXAF_DIR'] . 'app_data/files_core/PICR/GCA_003668045.1_CriGri-PICR';
$BXAF_CONFIG['Mouse_annotation']           = $BXAF_CONFIG['BXAF_DIR'] . 'app_data/files_core/PICR/GCF_Gene_annotation_w_mouse_symbol.csv';
$BXAF_CONFIG['Mouse_gtf']                  = $BXAF_CONFIG['BXAF_DIR'] . 'app_data/files_core/PICR/GCA_Chr_GCF_003668045.1_CriGri-PICR_exon_featureCount.gff';
$BXAF_CONFIG['Mouse_comparison_source']    = $BXAF_CONFIG['BXAF_DIR'] . 'app_data/files_core/workflow-functions_GTF_XZ_TMM_2015_05.R';
$BXAF_CONFIG['Mouse_gmt_file']             = $BXAF_CONFIG['BXAF_DIR'] . 'app_data/files_core/Mouse_GO_AllPathways_with_GO_iea_December_01_2018_symbol_autoFix.gmt';

$BXAF_CONFIG['Mouse_Description']          = 'CHO PICR genome, GCA_003668045.1 with NCBI Refseq Annotation Release 103';
$BXAF_CONFIG['Mouse_GO_enrichment_genome'] = 'mouse';


$BXAF_CONFIG['NECESSARY_FILES'] = array();
$sql = "SELECT `Category`, `Keyword`, `Detail` FROM `".$BXAF_CONFIG['TBL_BXGENOMICS_SETTING']."` WHERE `Type`='Necessary File' ORDER BY `ID`";
$data = $BXAF_MODULE_CONN->get_all($sql);
foreach($data as $key => $value){
	$BXAF_CONFIG['NECESSARY_FILES'][$value['Category']][$value['Keyword']] = $value['Detail'];
	$BXAF_CONFIG[ $value['Category'] . '_' . $value['Keyword'] ] = $value['Detail'];
}




// Check System
if(!isset($_SESSION['CHECK_SYSTEM_READY']) || $_SESSION['CHECK_SYSTEM_READY'] != true){

	$system_programs_status = check_system_programs_exist();
	if($system_programs_status != 'Ready'){
		echo $system_programs_status;
		exit();
	}

	$system_writable_status = check_system_writable();
	if($system_writable_status != 'Ready'){
		echo $system_writable_status;
		exit();
	}

	$_SESSION['CHECK_SYSTEM_READY'] = true;
}


run_process_in_order();





$BXAF_CONFIG['TBL_BXGENOMICS_FIELDS'] = array(
	'Gene'       => array(
		'GeneName', 'EntrezID', 'Source', 'Description', 'Alias', 'Ensembl', 'Unigene', 'Uniprot',
	    'TranscriptNumber', 'Strand', 'Chromosome', 'Start', 'End', 'ExonLength', 'AccNum'
	),
	'Comparison' => array(
		'Case_SampleIDs', 'Control_SampleIDs', 'ComparisonCategory', 'ComparisonContrast',
	    'Case_DiseaseState', 'Case_Tissue', 'Case_CellType', 'Case_Ethnicity', 'Case_Gender',
	    'Case_SamplePathology', 'Case_SampleSource', 'Case_Treatment', 'Case_SubjectTreatment',
	    'Case_AgeCategory', 'ComparisonType', 'Control_DiseaseState', 'Control_Tissue',
	    'Control_CellType', 'Control_Ethnicity', 'Control_Gender', 'Control_SamplePathology',
	    'Control_SampleSource', 'Control_Treatment', 'Control_SubjectTreatment', 'Control_AgeCategory'
	),
    'Sample'     => array(
		'CellType', 'DiseaseCategory', 'DiseaseState', 'Ethnicity', 'Gender', 'Infection', 'Organism',
	    'Response', 'SamplePathology', 'SampleSource', 'SampleType', 'SamplingTime', 'Symptom',
	    'TissueCategory', 'Tissue', 'Transfection', 'Treatment', 'Collection', 'Age', 'RIN_Number',
	    'RNASeq_Total_Read_Count', 'RNASeq_Mapping_Rate', 'RNASeq_Assignment_Rate', 'Flag_To_Remove',
	    'Flag_Remark', 'Uberon_ID', 'Uberon_Term'
	),
    'Project'    => array(
	  'Disease', 'Accession', 'PubMed_ID', 'ExperimentType', 'ContactAddress', 'ContactOrganization',
	  'ContactName', 'ContactEmail', 'ContactPhone', 'ContactWebLink', 'Keywords', 'Design', 'StudyType',
	  'TherapeuticArea', 'Comment', 'Contributors', 'WebLink', 'PubMed', 'PubMed_Authors', 'Collection'
	)
);

$BXAF_CONFIG['TBL_BXGENOMICS_FIELD_VALUES'] = array(
	'Project'    => array(),
	'Gene'       => array(),
	'Comparison' => array(
		'ComparisonCategory' => array(
	      'Treatment vs. Control', 'Disease vs. Normal', 'Responder vs. Non-Responder',
	      'Disease1 vs. Disease2'
	    ),
	    'ComparisonContrast' => array(
	      'obesity vs normal control', 'relapsing-remitting MS (RRMS) vs normal control',
	      'response vs no response', 'rheumatoid arthritis (RA) vs normal control'
	    ),
	    'Case_CellType' => array(
	      'synovial fibroblast cell', 'pulmonary fibroblast', 'adipocyte', 'monocyte',
	      'airway epithelial cell', 'peripheral blood mononuclear cell (PBMC)'
	    ),
	    'Case_DiseaseState' => array(
	      'normal control', 'ulcerative colitis (UC)', 'psoriasis', 'rheumatoid arthritis (RA)',
	      'obesity', 'osteoarthritis (OA)'
	    ),
	    'Case_Tissue' => array(
	      'colonic mucosa', 'skin', 'peripheral blood', 'subcutaneous adipose tissue',
	      'synovial membrane', 'lung'
	    )
	),
    'Sample'     => array(
		'CellType' => array(
	      'NA', 'lymphoblast', 'peripheral blood mononuclear cell (PBMC)', 'epithelial cell'
	    ),
	    'DiseaseCategory' => array(
	      'normal control', 'inflammatory bowel disease (IBD)', 'allergy;respiratory tract disease'
	    ),
	    'DiseaseState' => array(
	      'normal control', 'asthma', 'ulcerative colitis (UC)', 'lung cancer', 'obesity'
	    ),
	    'SampleSource' => array(
	      'skin', 'lung', 'colonic mucosa', 'lymphoblast', 'ileal mucosa'
	    ),
	    'Tissue' => array(
	      'central nervous system - brain', 'skin', 'peripheral blood', 'lung', 'blood vessel'
	    )
	)
);


// Sync platform information
$sql = "UPDATE `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` AS S, `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` AS P SET S.`Platform_Type` = P.`Type` WHERE S.`_Platforms_ID` = P.`ID`";
$BXAF_MODULE_CONN -> query($sql);
$sql = "UPDATE `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` AS S, `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` AS P SET S.`Platform_Type` = P.`Type`, S.`Platform` = P.`GEO_Accession`, S.`PlatformName` = P.`Name` WHERE S.`_Platforms_ID` = P.`ID`";
$BXAF_MODULE_CONN -> query($sql);
$sql = "UPDATE `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` AS S, `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` AS P SET S.`Platform_Type` = P.`Type`, S.`Platform` = P.`GEO_Accession`, S.`PlatformName` = P.`Name` WHERE S.`_Platforms_ID` = P.`ID`";
$BXAF_MODULE_CONN -> query($sql);









if(! array_key_exists('RECORD_COUNTS', $_SESSION)){

	$filter = " (`bxafStatus` < 5 AND (`_Owner_ID` IS NULL OR `_Owner_ID`=0 OR `_Owner_ID`='' OR `_Owner_ID`=" . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . ") AND `Species` = '{$_SESSION['SPECIES_DEFAULT']}') ";

    // Record count
    $_SESSION['RECORD_COUNTS'] = array();
    $sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE  $filter ";
    $_SESSION['RECORD_COUNTS']['Comparison'] = $BXAF_MODULE_CONN -> get_one($sql);

    $sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE  $filter ";
    $_SESSION['RECORD_COUNTS']['Project'] = $BXAF_MODULE_CONN -> get_one($sql);

    $sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE  $filter ";
    $_SESSION['RECORD_COUNTS']['Sample'] = $BXAF_MODULE_CONN -> get_one($sql);

    $sql = "SELECT COUNT(*) FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE  $filter ";
    $_SESSION['RECORD_COUNTS']['Gene'] = $BXAF_MODULE_CONN -> get_one($sql);
}


$BXAF_CONFIG['PAGE_MENU_ITEMS'][] = array(
    'Name'=>'Projects (' . $_SESSION['RECORD_COUNTS']['Project'] . ')',
    'URL' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . "bxgenomics/tool_search/index.php?type=project"
);
$BXAF_CONFIG['PAGE_MENU_ITEMS'][] = array(
    'Name'=>'Comparisons (' . $_SESSION['RECORD_COUNTS']['Comparison'] . ')',
    'URL' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . "bxgenomics/tool_search/index.php?type=comparison"
);
$BXAF_CONFIG['PAGE_MENU_ITEMS'][] = array(
    'Name'=>'Samples (' . $_SESSION['RECORD_COUNTS']['Sample'] . ')',
    'URL' => '/'. $BXAF_CONFIG['BXAF_APP_SUBDIR'] . "bxgenomics/tool_search/index.php?type=sample"
);


?>