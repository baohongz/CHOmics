<?php
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;
include_once("config.php");

ignore_user_abort(true);
set_time_limit(0);

exit();


    $file = '/var/www/html/bxgenomics_v3/temp/human.csv';

    $n = 0;
    if (($handle = fopen($file, "r")) !== FALSE) {

        while (($row = fgetcsv($handle, 0, "\t")) !== FALSE) {
            $info = array(
                'Species'    => 'Human',
                'Name'       => $row[0],
                'GeneIndex'  => intval($row[1])
            );

            $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'], $info);
        }
        fclose($handle);
    }
    fclose($gene_not_found_file_handle);

    $file = '/var/www/html/bxgenomics_v3/temp/mouse.csv';
    if (($handle = fopen($file, "r")) !== FALSE) {

        while (($row = fgetcsv($handle, 0, "\t")) !== FALSE) {
            $info = array(
                'Species'    => 'Mouse',
                'Name'       => $row[0],
                'GeneIndex'  => intval($row[1])
            );

            $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX'], $info);
        }
        fclose($handle);
    }
    fclose($gene_not_found_file_handle);

    $sql = "UPDATE `TBL_BXGENOMICS_GENES_INDEX` AS I, `TBL_Genes` AS G SET I.`GeneName`=G.`GeneName` WHERE I.`Species`=G.`Species` AND I.`GeneIndex`=G.`GeneIndex` AND I.`GeneName` = ''";
    $BXAF_MODULE_CONN -> execute($sql);

echo "Done";

?>