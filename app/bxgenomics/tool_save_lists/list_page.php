<?php
include_once("config.php");

$id = 0;
$LIST_INFO = array();
if (isset($_GET['id']) && intval($_GET['id']) > 0) {

    $id = intval($_GET['id']);

    $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDLISTS']}` WHERE " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `Species`=?s AND `ID`={$id} ";
    $LIST_INFO = $BXAF_MODULE_CONN -> get_row($sql, $_SESSION['SPECIES_DEFAULT']);

}

if (!is_array($LIST_INFO) || count($LIST_INFO) <= 1) {
    header("Location: new_list.php");
    exit();
}


switch ($LIST_INFO['Category']) {
  case 'Gene':
    $table           = $BXAF_CONFIG['TBL_BXGENOMICS_GENES'];
    $col             = 'GeneName';
    $preference_type = 'table_column_gene';
    break;
  case 'Sample':
    $table           = $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'];
    $col             = 'Name';
    $preference_type = 'table_column_sample';
    break;
  case 'Project':
    $table           = $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'];
    $col             = 'Name';
    $preference_type = 'table_column_project';
    break;
  default:
    $table           = $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'];
    $col             = 'Name';
    $preference_type = 'table_column_comparison';
}
$columns_selected = $BXAF_CONFIG['USER_PREFERENCES'][$preference_type];

$ALL_ITEMS = array();
$LIST_ITEMS_ID_LIST = unserialize($LIST_INFO['Items']);
if(is_array($LIST_ITEMS_ID_LIST) && count($LIST_ITEMS_ID_LIST) > 0){
    $sql = "SELECT * FROM `{$table}` WHERE " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `Species`=?s AND `ID` IN (?a)";
    $ALL_ITEMS = $BXAF_MODULE_CONN -> get_all($sql, $_SESSION['SPECIES_DEFAULT'], $LIST_ITEMS_ID_LIST);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

    <script>

        $(document).ready(function() {

        	$('#main_table').DataTable({"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });

        });

    </script>

</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">



                <div class="container-fluid">

                    <h3 class="mt-3">
                        <?php echo $LIST_INFO['Category']; ?> List:
                        <span style="color:#009966;">
                          <?php echo $LIST_INFO['Name']; ?>
                        </span>

                        <a href="my_lists.php" class="ml-3" style="font-size:1rem;">
                          <i class="fas fa-angle-double-right"></i> My saved lists
                        </a>

                        <a href="new_list.php?Category=<?php echo $LIST_INFO['Category']; ?>" style="font-size:1rem;" class="ml-3">
                          <i class="fas fa-plus"></i> Create new list
                        </a>
                    </h3>

                    <hr />

                    <?php
                    if (trim($LIST_INFO['Notes']) != '') {
                        echo '<div class="w-100 my-3"><hr /><h3 class="w-100 mt-3">Description</h3>';
                        echo $LIST_INFO['Notes'];
                        echo '</div>';
                    }
                    ?>


                    <div class="w-100 my-3">
                        <table class="table table-bordered table-striped table-hover w-100" id="main_table">
                            <thead class="table-success">
                                <tr>
                                    <?php
                                        echo '<th>No.</th>';
                                        foreach ($columns_selected as $col) {
                                            $caption = str_replace('_', ' ', $col);
                                            echo '<th>' . $caption . '</th>';
                                        }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $n = 0;
                                foreach ($ALL_ITEMS as $item) {
                                    $n++;
                                    echo '<tr>';
                                    echo '<td>' . $n . '</td>';
                                    foreach ($columns_selected as $col) {
                                        if($col == '_Projects_ID'){
                                            $name = $BXAF_MODULE_CONN -> get_one( "SELECT `Name` FROM ?n WHERE `ID`= ?i", $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $item[$col] );
                                            echo '<td><a href="../project.php?id=' . $item[$col] . '">' . $name . '</a></td>';
                                        }
                                        else if($col == '_Platforms_ID'){
                                            $name = $BXAF_MODULE_CONN -> get_one( "SELECT `Name` FROM ?n WHERE `ID`= ?i", $BXAF_CONFIG['TBL_BXGENOMICS_PLATFORMS'], $item[$col] );
                                            echo '<td>' . $name . '</td>';
                                        }
                                        else echo '<td>' . $item[$col] . '</td>';
                                    }
                                    echo '</tr>';
                                }
                            ?>
                            </tbody>
                        </table>
                    </div>

                </div>




            </div>
            <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
        </div>
    </div>


</body>

</html>