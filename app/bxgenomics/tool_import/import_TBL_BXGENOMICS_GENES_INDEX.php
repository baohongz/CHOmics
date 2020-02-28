<?php
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;
include_once("config.php");

ignore_user_abort(true);
set_time_limit(0);

exit();

    $file = '/share/data/CHO_Biogen/PICR/more_genes/TBL_BXGENOMICS_GENES_INDEX_CHO_more_genes.csv';

    if (($handle = fopen($file, "r")) !== FALSE) {
        $head = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== FALSE) {

            $info = array();
            foreach($head as $i=>$v){
                $info[$v] = $row[$i];
            }
            $BXAF_MODULE_CONN -> insert('TBL_BXGENOMICS_GENES_INDEX', $info);
        }
        fclose($handle);
    }

echo "Done";
exit();




    // $file = '/home/html/temp1/human_ID_lookup_large_Nov12_2018.csv';
    // $file = '/share/DiseaseLand/Import_template/CHO_ID_lookup_2_mouse_BxGenomics_Dec9_2018.csv';

    if (($handle = fopen($file, "r")) !== FALSE) {

        $head = fgetcsv($handle);

        $fvs = array();
        while (($row = fgetcsv($handle)) !== FALSE) {

            if(count($fvs) > 1000){
                $BXAF_MODULE_CONN->insert_batch('TBL_BXGENOMICS_GENES_INDEX', $fvs);
                $fvs = array();
            }

            $fv = array(
                'Species'    => 'Mouse',
                'Name'       => trim($row[0]),
                'GeneIndex'  => intval($row[1])
            );

            $fvs[] = $fv;

        }

        if(count($fvs) > 0){
            $BXAF_MODULE_CONN->insert_batch('TBL_BXGENOMICS_GENES_INDEX', $fvs);
        }

        fclose($handle);
    }

    $sql = "UPDATE `TBL_BXGENOMICS_GENES_INDEX` AS I, `TBL_Genes` AS G SET I.`GeneName`=G.`GeneName` WHERE I.`Species`=G.`Species` AND I.`GeneIndex`=G.`GeneIndex` AND I.`GeneName` = ''";
    $BXAF_MODULE_CONN -> execute($sql);

echo "Done";

?>