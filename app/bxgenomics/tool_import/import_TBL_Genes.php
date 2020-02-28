<?php
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;
include_once("config.php");

ignore_user_abort(true);
set_time_limit(0);

exit();


    // $file = '/home/html/temp1/Genes_H_M_Updated_Nov13_2018.csv';
    // $file = '/storage/share/DiseaseLand/Import_template/Genes/Genes_H_M_Updated_Nov13_2018.csv';
    $file = '/share/data/CHO_Biogen/PICR/more_genes/TBL_Genes_CHO_more_genes.csv';

    if (($handle = fopen($file, "r")) !== FALSE) {
        $head = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== FALSE) {

            $info = array();
            foreach($head as $i=>$v){
                $info[$v] = $row[$i];
            }
            $BXAF_MODULE_CONN -> insert('TBL_Genes', $info);
        }
        fclose($handle);
    }

    // $sql = "UPDATE `TBL_Genes` SET `ID` = `GeneIndex`";
    // $BXAF_MODULE_CONN -> execute($sql);

echo "Done";

?>