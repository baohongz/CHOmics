<?php

include_once(__DIR__ . "/config.php");

ignore_user_abort(true);
set_time_limit(0);

// Settings

$gpml_dir = "/share/DiseaseLand/pvjs/wikipathways/current/gpml/";

$species = array(
    // "Anopheles gambiae" => "mosquito",
    // "Arabidopsis thaliana" => "arabidopsis",
    // "Bos taurus" => "cow",
    // "Ciona intestinalis" => "ciona",
    // "Canis lupus familiaris" => "dog",
    // "Caenorhabditis elegans" => "worm",
    // "Danio rerio" => "zebrafish",
    // "Drosophila melanogaster" => "fly",
    // "Gallus gallus" => "chicken",
    // "Homo sapiens" => "human",
    "Mus musculus" => "mouse",
    // "Rattus norvegicus" => "rat",
    // "Oryza sativa" => "rice",
    // "Solanum lycopersicum" => "tomato",
    // "Sus scrofa" => "pig",
    // "Saccharomyces cerevisiae" => "yeast",
    // "Xenopus tropicalis" => "frog",
    // "Zea mays" => "corn",
);
$all_species = array_flip($species);






$linked_dir = dirname(__DIR__) . "/tool_pathway/pathway/Human";
if(! file_exists($linked_dir)){
    exec("ln -s {$gpml_dir}Homo_sapiens $linked_dir");
}
$linked_dir = dirname(__DIR__) . "/tool_pathway/pathway/Mouse";
if(! file_exists($linked_dir)){
    exec("ln -s {$gpml_dir}Mus_musculus $linked_dir");
}
$linked_dir = dirname(__DIR__) . "/tool_pathway/pathway/Rat";
if(! file_exists($linked_dir)){
    exec("ln -s {$gpml_dir}Rattus_norvegicus $linked_dir");
}

$table_name = 'tbl_wikipathways_info';

$sql = "SHOW TABLES";
$tables = $BXAF_MODULE_CONN->get_col($sql);
if(! is_array($tables) || ! in_array($table_name, $tables)){

    $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
      `ID` int(11) NOT NULL AUTO_INCREMENT,
      `Species` varchar(255) NOT NULL DEFAULT '',
      `File` varchar(255) NOT NULL DEFAULT '',
      `Name` varchar(255) NOT NULL DEFAULT '',
      `Author` varchar(255) NOT NULL DEFAULT '',
      `Organism` varchar(255) NOT NULL DEFAULT '',
      `WP_No` varchar(255) NOT NULL DEFAULT '',
      `Revision` varchar(255) NOT NULL DEFAULT '',
      `TYPE` varchar(255) NOT NULL DEFAULT '',
      `TEXTLABEL` varchar(255) NOT NULL DEFAULT '',
      `DB_Name` varchar(255) NOT NULL DEFAULT '',
      `DB_ID` varchar(255) NOT NULL DEFAULT '',
      `Comment` text NOT NULL,
      `Gene_Name` varchar(255) NOT NULL DEFAULT '',
      `Gene_ID` varchar(255) NOT NULL DEFAULT '',
      `Gene_Index` int(11) NOT NULL DEFAULT '0',
      `bxafStatus` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`ID`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000001";

    $ret = $BXAF_MODULE_CONN->Execute($sql);
}

$sql = "TRUNCATE TABLE `tbl_wikipathways_info`";
$BXAF_MODULE_CONN->Execute( $sql );


$pathway_files = array();
foreach ($all_species as $short_name => $full_name) {
    $dir = $gpml_dir . str_replace(' ', '_', $full_name);
    if(file_exists($dir)){
        $dir_files  = new DirectoryIterator($dir);
        foreach ($dir_files as $fileinfo) {
            if (! $fileinfo->isDot()) {
                $pathway_files[$short_name][] = $dir . '/' . $fileinfo->getFilename();
            }
        }
    }
}

$id = 1;
$added = array();
foreach($pathway_files as $short_name=>$files){
    foreach($files as $file){

        $filename = basename($file);
        $array = preg_split("/[\.\_]/", $filename);
        array_pop($array);
        $revision = array_pop($array);
        $wp_no = array_pop($array);


        $fvs = array();

        $pathway_name = '';
        $pathway_author = '';
        $pathway_organism = '';
        $last_type = '';
        $last_label = '';
        $comment = '';

        $xmlstring = file_get_contents($file);
        $values = array();
        $xmlparser = xml_parser_create();
        xml_parse_into_struct($xmlparser, $xmlstring, $values, $index);

        foreach($values as $i=>$value){

            if($value['tag'] == 'PATHWAY' && is_array($value['attributes']) && count($value['attributes']) > 0){
                $pathway_name = $value['attributes']['NAME'];
                $pathway_author = isset($value['attributes']['AUTHOR']) ? $value['attributes']['AUTHOR'] : '';
                $pathway_organism = isset($value['attributes']['ORGANISM']) ? $value['attributes']['ORGANISM'] : '';
            }
            else if($value['tag'] == 'COMMENT' && is_array($value['attributes']) && array_key_exists('SOURCE', $value['attributes']) && $value['attributes']['SOURCE'] == 'WikiPathways-description'){
                $comment = $value['value'];
            }
            else if($value['tag'] == 'DATANODE' && isset($value['attributes']['TYPE']) && $value['attributes']['TYPE'] != '' && isset($value['attributes']['TEXTLABEL']) && $value['attributes']['TEXTLABEL'] != ''){
                $last_type  = $value['attributes']['TYPE'];
                $last_label = $value['attributes']['TEXTLABEL'];
            }
            else if($value['tag'] == 'XREF' && isset($value['attributes']['DATABASE']) && $value['attributes']['DATABASE'] != '' && isset($value['attributes']['ID']) && $value['attributes']['ID'] != ''){

                $db_name = $value['attributes']['DATABASE'];
                $db_id = $value['attributes']['ID'];

                if($last_label != '' && ! array_key_exists("$wp_no/$revision/$last_type/$last_label/$last_label/$db_name/$db_id", $added)){

                    $fv = array(
                        'ID'=>$id,
                        'Species'=>ucfirst($short_name),
                        'File'=>$filename,
                        'Name'=>$pathway_name,
                        'Author'=>$pathway_author,
                        'Organism'=>$pathway_organism,
                        'WP_No'=>$wp_no,
                        'Revision'=>$revision,
                        'TYPE'=>$last_type,
                        'TEXTLABEL'=>$last_label,
                        'DB_Name'=> $db_name,
                        'DB_ID'=> $db_id,
                        'Comment'=> '', //$comment,
                        'Gene_Name'=>'',
                        'Gene_ID'=>'',
                        'Gene_Index'=>'',
                    );

                    $fvs[] = $fv;

                    $id++;

                    $added["$wp_no/$revision/$last_type/$last_label/$last_label/$db_name/$db_id"] = 1;

                    $last_type = '';
                    $last_label = '';

                }
            }
        }

        $BXAF_MODULE_CONN->insert_batch($table_name, $fvs );

        $fvs = array();
    }
}


// $sql = "SELECT * FROM `tbl_wikipathways_info` WHERE `Species` IN ('Human', 'Mouse', 'Rat') AND `Type` IN ('Protein', 'GeneProduct', 'Rna') AND `DB_Name` IN ('Entrez Gene', 'Uniprot-TrEMBL', 'Ensembl')";
$sql = "SELECT * FROM `tbl_wikipathways_info` WHERE `Species` IN ('Mouse') AND `Type` IN ('Protein', 'GeneProduct', 'Rna') AND `DB_Name` IN ('Entrez Gene', 'Uniprot-TrEMBL', 'Ensembl')";
$results = $BXAF_MODULE_CONN->get_assoc('ID', $sql );

$n= 0;
foreach($results as $id=>$info){

    $sql = "SELECT * FROM `TBL_BXGENOMICS_GENES_INDEX` WHERE `Species` = ?s AND `Name` = ?s";
    $row = $BXAF_MODULE_CONN->get_row( $sql, $info['Species'], $info['DB_ID']);

    if(is_array($row) && count($row) > 0){
        $fv = array('Gene_Index'=>$row['GeneIndex'], 'Gene_Name'=>$row['GeneName']);
        $BXAF_MODULE_CONN->update('tbl_wikipathways_info', $fv, "`ID`=$id");
        $n++;
    }
}

echo "Done";

?>