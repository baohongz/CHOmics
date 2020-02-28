<?php
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;
include_once("config.php");

ignore_user_abort(true);
set_time_limit(0);


if(file_exists("pathway/human_pathway_genes.txt") || file_exists("pathway/mouse_pathway_genes.txt")) {
    echo "Pathways have been processed before.";
    exit();
}

// All Pathways
$BXAF_CONFIG['PATHWAY_LIST_ALL'] = array();

$dir = new DirectoryIterator(__DIR__ . "/pathway/homo_sapiens");
foreach ($dir as $fileinfo) {
    if (! $fileinfo->isDot()) {
      $filename_old = $fileinfo->getFilename();
      $filename = substr($filename_old, 3);
      $position_WP = strpos($filename, '_WP');
      $filename = substr($filename, 0, $position_WP);
      $printname = str_replace('_', ' ', $filename);
      if (trim($printname) != '') {
        $BXAF_CONFIG['PATHWAY_LIST_ALL']['Human'][$printname]['Path'] = "/pathway/homo_sapiens/$filename_old";
        $BXAF_CONFIG['PATHWAY_LIST_ALL']['Human'][$printname]['File'] = $filename_old;
      }
    }
}

$dir = new DirectoryIterator(__DIR__ . "/pathway/homo_sapiens_reactome");
foreach ($dir as $fileinfo) {
    if (! $fileinfo->isDot()) {
      $filename_old = $fileinfo->getFilename();
      $filename = substr($filename_old, 3);
      $position_WP = strpos($filename, '_WP');
      $filename = substr($filename, 0, $position_WP);
      $printname = str_replace('_', ' ', $filename);
      if (trim($printname) != '') {
        $BXAF_CONFIG['PATHWAY_LIST_ALL']['Human'][$printname . ' (Reactome)']['Path'] = "/pathway/homo_sapiens_reactome/$filename_old";
        $BXAF_CONFIG['PATHWAY_LIST_ALL']['Human'][$printname . ' (Reactome)']['File'] = $filename_old;
      }
    }
}
ksort($BXAF_CONFIG['PATHWAY_LIST_ALL']['Human']);


$dir = new DirectoryIterator(__DIR__ . "/pathway/mus_musculus");
foreach ($dir as $fileinfo) {
    if (! $fileinfo->isDot()) {
        $filename_old = $fileinfo->getFilename();
        $filename = substr($filename_old, 3);
        $position_WP = strpos($filename, '_WP');
        $filename = substr($filename, 0, $position_WP);
        $printname = str_replace('_', ' ', $filename);
        if (trim($printname) != '') {
          $BXAF_CONFIG['PATHWAY_LIST_ALL']['Mouse'][$printname]['Path'] = "/pathway/mus_musculus/$filename_old";
          $BXAF_CONFIG['PATHWAY_LIST_ALL']['Mouse'][$printname]['File'] = $filename_old;
        }
    }
}
ksort($BXAF_CONFIG['PATHWAY_LIST_ALL']['Mouse']);
// echo "BXAF_CONFIG['PATHWAY_LIST_ALL']<pre>" . print_r($BXAF_CONFIG['PATHWAY_LIST_ALL'], true) . "</pre>";
// exit();

$human_found = array();
$mouse_found = array();
foreach($BXAF_CONFIG['PATHWAY_LIST_ALL'] as $species=>$pathways){
	foreach($pathways as $pathway_name=>$pathway_info){

        if($species == 'Human'){
            $human_found[$pathway_name] = array();
            $human_found[$pathway_name]['Path'] = $pathway_info['Path'];
            $human_found[$pathway_name]['File'] = $pathway_info['File'];
            $human_found[$pathway_name]['Genes'] = array();
        }
        else if($species == 'Mouse'){
            $mouse_found[$pathway_name] = array();
            $mouse_found[$pathway_name]['Path'] = $pathway_info['Path'];
            $mouse_found[$pathway_name]['File'] = $pathway_info['File'];
            $mouse_found[$pathway_name]['Genes'] = array();
        }
        $content = str_replace('&#xA;', ' ', str_replace("\n", '', file_get_contents(__DIR__ . $pathway_info['Path'])));

        $matches = array();
        $pattern = "|<DataNode TextLabel=\"\s*(.*)\s*\" GraphId=\"\s*(.*)\s*\" Type=\"\s*(.*)\s*\".*<Xref Database=\"\s*(.*)\s*\" ID=\"\s*(.*)\s*\".*</DataNode>|mU";
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);


        foreach($matches as $i=>$match){
            if(count($match) != 6 || ! in_array($match[4], array('Uniprot-TrEMBL', 'Ensembl', 'Entrez Gene')) ) continue;

            $sql = "SELECT `GeneName`, `GeneIndex`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = '" . $species . "' AND `Name` IN (?a)";
            $row = $BXAF_MODULE_CONN -> get_row($sql, array($match[1], $match[5]) );

            if(is_array($row) && count($row) > 0) {

                $gene = array();
                $gene[ 'GeneSymbol' ] = $row['GeneName'];
                $gene[ 'GeneIndex' ]  = $row['GeneIndex'];
                $gene[ 'TextLabel' ]  = trim($match[1]);
                $gene[ 'GraphId' ]    = trim($match[2]);
                $gene[ 'Type' ]       = trim($match[3]);
                $gene[ 'Database' ]   = trim($match[4]);
                $gene[ 'DatabaseID' ] = trim($match[5]);

                if($species == 'Human') $human_found[$pathway_name]['Genes'][ $gene[ 'GeneSymbol' ] ] = $gene;
                else if($species == 'Mouse') $mouse_found[$pathway_name]['Genes'][ $gene[ 'GeneSymbol' ] ] = $gene;
            }

        }

    }
}

file_put_contents("pathway/human_pathway_genes.txt", serialize($human_found));
file_put_contents("pathway/mouse_pathway_genes.txt", serialize($mouse_found));


$gene_index = array();
foreach($human_found as $name=>$info){
    foreach($info['Genes'] as $g=>$v){
        $gene_index[$name][$g] = $v['GeneIndex'];
    }
}
file_put_contents("pathway/human_gene_index.txt", serialize($gene_index));

$gene_index = array();
foreach($mouse_found as $name=>$info){
    foreach($info['Genes'] as $g=>$v){
        $gene_index[$name][$g] = $v['GeneIndex'];
    }
}
file_put_contents("pathway/mouse_gene_index.txt", serialize($gene_index));

$gene_index = array();
foreach($human_found as $name=>$info){
    foreach($info['Genes'] as $g=>$v){
        $gene_index[$name][$v['TextLabel']] = $v['GeneIndex'];
    }
}
file_put_contents("pathway/human_textlabel_index.txt", serialize($gene_index));

$gene_index = array();
foreach($mouse_found as $name=>$info){
    foreach($info['Genes'] as $g=>$v){
        $gene_index[$name][$v['TextLabel']] = $v['GeneIndex'];
    }
}
file_put_contents("pathway/mouse_textlabel_index.txt", serialize($gene_index));




$all_gene_index = unserialize(file_get_contents("pathway/human_gene_index.txt"));
foreach($all_gene_index as $pathway_name=>$genes_list){
    $sql = "SELECT `Name`, `GeneIndex`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = 'Human' AND `GeneIndex` IN (?a)";
    $found = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, array_values($genes_list));
    file_put_contents("pathway/human_names/" . md5($pathway_name) . ".txt", serialize($found));
}

$all_gene_index = unserialize(file_get_contents("pathway/mouse_gene_index.txt"));
foreach($all_gene_index as $pathway_name=>$genes_list){
    $sql = "SELECT `Name`, `GeneIndex`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = 'Mouse' AND `GeneIndex` IN (?a)";
    $found = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, array_values($genes_list));
    file_put_contents("pathway/mouse_names/" . md5($pathway_name) . ".txt", serialize($found));
}


// $all_name_index = array();
// $all_gene_index = unserialize(file_get_contents("pathway/human_gene_index.txt"));
// foreach($all_gene_index as $pathway_name=>$genes_list){
//     $sql = "SELECT `Name`, `GeneIndex`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = 'Human' AND `GeneIndex` IN (?a)";
//     $genes_nameindex = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, array_values($genes_list));
//     $all_name_index[$pathway_name] = $genes_nameindex;
// }
// file_put_contents("pathway/human_name_index.txt", serialize($all_name_index));
//
//
// $all_name_index = array();
// $all_gene_index = unserialize(file_get_contents("pathway/mouse_gene_index.txt"));
// foreach($all_gene_index as $pathway_name=>$genes_list){
//     $sql = "SELECT `Name`, `GeneIndex`  FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES_INDEX']}` WHERE `Species` = 'Mouse' AND `GeneIndex` IN (?a)";
//     $genes_nameindex = $BXAF_MODULE_CONN -> get_assoc('Name', $sql, array_values($genes_list));
//     $all_name_index[$pathway_name] = $genes_nameindex;
// }
// file_put_contents("pathway/mouse_name_index.txt", serialize($all_name_index));


echo "done";

?>