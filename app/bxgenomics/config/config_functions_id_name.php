<?php

// Convert List of IDs or Names to List of ID=>Name Array
if (!function_exists('category_list_to_idnames')) {
    function category_list_to_idnames($list, $value_type = 'name', $category = 'gene', $species = '') {

        global $BXAF_MODULE_CONN, $BXAF_CONFIG;

        if($species == '') $species = $_SESSION['SPECIES_DEFAULT'];


        if (! is_array($list) || count($list) <= 0) return array();

        $value_type = strtolower(trim(strval($value_type)));
        $category = strtolower(trim(strval($category)));

        $categorie_tables = array(
            'gene'      => $BXAF_CONFIG['TBL_BXGENOMICS_GENES'],
            'sample'    => $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'],
            'project'   => $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'],
            'comparison'=> $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']
        );

        if(! array_key_exists($category, $categorie_tables)) return array();


        $result = array();
        $number_list = count($list);

        if($category == 'gene' && $value_type == 'id'){

            $n=0;
            do{
                $list_partial = array_slice($list, $n, 1000);
                $n += 1000;

                $sql = "SELECT `GeneIndex`, `GeneName` FROM ?n WHERE `Species` = ?s AND `GeneIndex` IN (?a)";
                $rs = $BXAF_MODULE_CONN -> get_assoc('GeneIndex', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'], $species, $list_partial );
                if(is_array($rs)) $result = $result + $rs;
            } while($n <= $number_list);

        }
        else if($category == 'gene' && $value_type == 'name'){

            $n=0;
            do{
                $list_partial = array_slice($list, $n, 1000);
                $n += 1000;

                $sql = "SELECT `GeneIndex`, `GeneName` FROM ?n WHERE `Species` = '$species' AND `Name` IN (?a)";
                $rs = $BXAF_MODULE_CONN -> get_assoc('GeneIndex', $sql, $BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'], $list_partial );
                if(is_array($rs)) $result = $result + $rs;

            } while($n <= $number_list);

        }
        else {
            $field_name = 'Name';
            if($category == 'gene') $field_name = 'GeneName';

            $n=0;
            do{
                $list_partial = array_slice($list, $n, 1000);
                $n += 1000;

                $sql = "SELECT `ID`, ?n FROM ?n WHERE ?n IN (?a) AND `Species` = ?s AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'];
                $rs = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $field_name, $categorie_tables[$category], $value_type, $list_partial, $species);
                if(is_array($rs)) $result = $result + $rs;

            } while($n <= $number_list);

        }

        // Keep the order and case insensitive
        $result_strtolower = array();
        foreach($result as $k=>$v) if($v != '') $result_strtolower[$k] = strtolower($v);

        $new_result = array();
        if($value_type == 'id'){
            foreach($list as $id) if(array_key_exists($id, $result_strtolower)) $new_result[$id] = $result[$id];
        }
        else {
            $result_flip = array_flip($result_strtolower);
            foreach($list as $name) if(array_key_exists(strtolower($name), $result_flip) ) $new_result[ $result_flip[strtolower($name)] ] = $result[ $result_flip[strtolower($name)] ];
        }

        return $new_result;

    }
}

// Convert Text (IDs or Names, split by commas or spaces) to List of ID=>Name Array
if (!function_exists('category_text_to_idnames')) {
    function category_text_to_idnames($text, $value_type = 'name', $category = 'gene', $species = '') {

        if($species == '') $species = $_SESSION['SPECIES_DEFAULT'];

        $text = trim(strval($text));
        if($text == '') return array();

        // split the phrase by any number of commas or space characters, which include " ", \r, \t, \n and \f
        $list = preg_split("/[\s,]+/", $text, NULL, PREG_SPLIT_NO_EMPTY);

        return category_list_to_idnames($list, $value_type, $category, $species);
    }
}

?>