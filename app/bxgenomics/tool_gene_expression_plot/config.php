<?php

include_once('../config/config.php');

$BXAF_CONFIG['CURRENT_SYSTEM_CACHE_DIR'] = $BXAF_CONFIG['BXAF_DIR'] . "app_data/cache/tool_gene_expression_plot/{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/";
$BXAF_CONFIG['CURRENT_SYSTEM_CACHE_URL'] = $BXAF_CONFIG['BXAF_URL'] . "app_data/cache/tool_gene_expression_plot/{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/";
if (!is_dir($BXAF_CONFIG['CURRENT_SYSTEM_CACHE_DIR'])) mkdir($BXAF_CONFIG['CURRENT_SYSTEM_CACHE_DIR'], 0755, true);


$BXAF_CONFIG['TOOL_EXPORT_COLNAMES_ALL'] = $BXAF_CONFIG['TBL_BXGENOMICS_FIELDS'];


$BXAF_CONFIG['SAMPLE_FILTER_CATEGORY'] = array(
    'CellType', 'DiseaseCategory', 'Ethnicity', 'Gender', 'Tissue'
);


$sql = "SELECT `ID`, `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS']}` WHERE `bxafStatus` < 5";
$BXAF_CONFIG['PLATFORM_ID_NAME'] = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);
$BXAF_CONFIG['PLATFORM_NAME_ID'] = array_flip($BXAF_CONFIG['PLATFORM_ID_NAME']);

?>