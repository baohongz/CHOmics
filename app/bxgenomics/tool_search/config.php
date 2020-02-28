<?php

include_once('../config/config.php');




$SPECIES_ALL = array('Human', 'Mouse');

$PAGE_TYPE_ALL = array('Gene', 'Comparison', 'Project', 'Sample');

$PREFERENCE_TYPE_ALL = array(
	'Gene'=>'table_column_gene',
	'Comparison'=>'table_column_comparison',
	'Project'=>'table_column_project',
	'Sample'=>'table_column_sample'
);

$TABLE_ALL = array(
	'Gene'=>$BXAF_CONFIG['TBL_BXGENOMICS_GENES'],
	'Comparison'=>$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'],
	'Project'=>$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'],
	'Sample'=>$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']
);

$TABLE_FIELD_NAMES = array(
	'Gene' => 'GeneName',
	'Comparison' => 'Name',
	'Project' => 'Name',
	'Sample' => 'Name'
);

?>