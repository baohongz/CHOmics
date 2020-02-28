<?php

if (!function_exists('config_load_user_preferences')) {
    function config_load_user_preferences() {
        global $BXAF_CONFIG, $BXAF_MODULE_CONN;

        $default_values = array(
            'table_column_comparison' => array('Name', 'Case_DiseaseState', 'Case_CellType', 'Case_Tissue'),
            'table_column_gene'       => array('GeneName', 'EntrezID', 'Source', 'Alias'),
            'table_column_sample'     => array('Name', 'DiseaseState', 'CellType', 'Tissue'),
            'table_column_project'    => array('Name', 'Disease', 'Accession', 'ExperimentType'),
        );

        $RESULT = array();
        foreach ($default_values as $category=>$value) {

            $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE']}` WHERE {$BXAF_CONFIG['QUERY_DEFAULT_FILTER']} AND `Category` = ?s ORDER BY `ID` DESC";
            $data = $BXAF_MODULE_CONN -> get_row($sql, $category);

            // If not exists
            if (!is_array($data) || count($data) <= 1) {
                $info = array(
                    'Category'  => $category,
                    'Detail'    => serialize($default_values[$category]),
                    '_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID']
                );
                $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info);

                $RESULT[$category] = $default_values[$category];
            }
            else {
                $RESULT[$category] = unserialize($data['Detail']);
            }
        }

        return $RESULT;

    }
}

?>