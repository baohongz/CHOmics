<?php
include_once('config.php');


if (isset($_GET['action']) && $_GET['action'] == 'table_save_col') {

  $FORM_TYPE = $_POST['FORM_TYPE'];

  switch ($FORM_TYPE) {
    case 'Gene':
      $table           = $BXAF_CONFIG['TBL_BXGENOMICS_GENES'];
      $col             = 'GeneName';
      $category        = 'table_column_gene';
      break;
    case 'Sample':
      $table           = $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'];
      $col             = 'Name';
      $category        = 'table_column_sample';
      break;
    case 'Project':
      $table           = $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'];
      $col             = 'Name';
      $category        = 'table_column_project';
      break;
    default:
      $table           = $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'];
      $col             = 'Name';
      $category        = 'table_column_comparison';
  }


    $columns_all = $BXAF_MODULE_CONN -> get_column_names($table);
    $columns_all = array_diff($columns_all, array('bxafStatus', '_Owner_ID', 'Permission', 'Time_Created'));

    $columns_selected = array();
    foreach ($columns_all as $key) {
        if(array_key_exists($key, $_POST)) $columns_selected[] = $key;
    }

    $info = array( 'Detail' => serialize($columns_selected) );
    $BXAF_MODULE_CONN -> update(
        $BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'],
        $info,
        "`_Owner_ID`={$BXAF_CONFIG['BXAF_USER_CONTACT_ID']} AND `Category`='{$category}'"
    );

  exit();
}

?>