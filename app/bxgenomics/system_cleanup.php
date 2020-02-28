<?php
$BXAF_CONFIG_CUSTOM['PAGE_LOGIN_REQUIRED']	= false;
include_once(__DIR__ . '/config/config.php');

// Find all Projects
$sql = "SELECT `ID`, `Species`, `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE `bxafStatus` < 5";
$all_projects = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);
// echo $BXAF_MODULE_CONN -> last_query() . '<pre>' . print_r($all_projects, true) . '</pre>';

$target_dir = $BXAF_CONFIG['USER_FILES']['TOOL_PROJECTS'];
$d = dir($target_dir);
while (false !== ($entry = $d->read())) {
   if($entry != '.' && $entry != '..' && ! array_key_exists($entry, $all_projects)){
       $command = "rm -rf {$target_dir}{$entry}";
       system($command);
       echo "---> Project folder $entry is deleted.<BR>";
   }
}
$d->close();
echo "User project folders are cleaned.<BR><BR>";


// Find all comparisons
$sql = "SELECT `ID`, `Species`, `Name` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `bxafStatus` < 5";
$all_comparisons = $BXAF_MODULE_CONN -> get_assoc('ID', $sql);
// echo $BXAF_MODULE_CONN -> last_query() . '<pre>' . print_r($all_comparisons, true) . '</pre>';

$list_of_go_folders = array();
$list_of_page_files = array();
foreach($all_comparisons as $id=>$c){
    $list_of_go_folders["comp_$id"] = $id;
    $list_of_page_files["comparison_{$id}_GSEA.PAGE.csv"] = $id;
}
// echo $BXAF_MODULE_CONN -> last_query() . '<pre>' . print_r($list_of_page_files, true) . '</pre>';

$target_dir = $BXAF_CONFIG['GO_OUTPUT']['HUMAN'];
$d = dir($target_dir);
while (false !== ($entry = $d->read())) {
   if($entry != '.' && $entry != '..' && ! array_key_exists($entry, $list_of_go_folders)){
       $command = "rm -rf {$target_dir}{$entry}";
       // echo "$command<BR>";
       system($command);
      echo "---> Human GO folder $entry is deleted.<BR>";
   }
}
$d->close();

$target_dir = $BXAF_CONFIG['GO_OUTPUT']['MOUSE'];
$d = dir($target_dir);
while (false !== ($entry = $d->read())) {
   if($entry != '.' && $entry != '..' && ! array_key_exists($entry, $list_of_go_folders)){
       $command = "rm -rf {$target_dir}{$entry}";
       // echo "$command<BR>";
       system($command);
      echo "---> Mouse GO folder $entry is deleted.<BR>";
   }
}
$d->close();
echo "User GO folders are cleaned.<BR><BR>";


$target_dir = $BXAF_CONFIG['PAGE_OUTPUT']['HUMAN'];
if ($dh = opendir($target_dir)) {
    while (($entry = readdir($dh)) !== false) {
        if($entry != '.' && $entry != '..' && is_file($target_dir . $entry) && ! array_key_exists($entry, $list_of_page_files)){
            // echo "{$target_dir}{$entry}<BR>";
            unlink($target_dir . $entry);
            echo "---> Human PAGE file $entry is deleted.<BR>";
        }
    }
    closedir($dh);
}

$target_dir = $BXAF_CONFIG['PAGE_OUTPUT']['MOUSE'];
if ($dh = opendir($target_dir)) {
    while (($entry = readdir($dh)) !== false) {
        if($entry != '.' && $entry != '..' && filetype($target_dir . $entry) == 'file' && ! array_key_exists($entry, $list_of_page_files)){
            // echo "{$target_dir}{$entry}<BR>";
            unlink($target_dir . $entry);
            echo "---> Mouse PAGE file $entry is deleted.<BR>";
        }
    }
    closedir($dh);
}
echo "User PAGE files are cleaned.<BR><BR>";


?>