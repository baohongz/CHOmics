<?php
include_once("config.php");


$type_list = array('comparison', 'gene', 'sample', 'project');

$CATEGORY = '';
if (isset($_GET['category']) && in_array(strtolower($_GET['category']), $type_list)) {
    $CATEGORY = ucfirst(strtolower($_GET['category']));
}
else if (isset($_GET['Category']) && in_array(strtolower($_GET['Category']), $type_list)) {
    $CATEGORY = ucfirst(strtolower($_GET['Category']));
}


$current_species = $_SESSION['SPECIES_DEFAULT'];
if (isset($_GET['species']) && in_array(ucfirst(strtolower($_GET['species'])), array('Human', 'Mouse')) && $current_species != ucfirst(strtolower($_GET['species'])) ) {

	$current_species = ucfirst(strtolower($_GET['species']));

	// Check exists
	$sql = "SELECT `ID` FROM ?n WHERE `bxafStatus` < 5 AND `Category`= 'Default Species' AND `_Owner_ID` = ?i";
	$record_id = $BXAF_MODULE_CONN -> get_one($sql, $BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] );

	// Update record
	if ($record_id != '') {
		$info = array(
			'_Owner_ID'   => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
			'Category'    => 'Default Species',
			'Detail'      => $current_species
		);
		$BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info, "`ID`=" . intval($record_id) );
	}
	// Create new record
	else {
		$info = array(
			'_Owner_ID'   => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
			'Category'    => 'Default Species',
			'Detail'      => $current_species,
			'bxafStatus'      => 0
		);
		$BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_USERPREFERENCE'], $info);
	}
	$_SESSION['SPECIES_DEFAULT'] = $current_species;
}


$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDLISTS']}` WHERE `Species` = '" . $current_species . "' AND " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . "";
$data_lists = $BXAF_MODULE_CONN -> get_all($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

</head>
<body>

<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
<div class="container-fluid">

    <?php $help_key = 'My Saved Lists'; include_once( dirname(__DIR__) . "/help_content.php"); ?>


    <div class="d-flex align-items-center my-3">

        Category:
		<select id="select-page-category" class="custom-select mx-2 select-page-category" style="width: 12rem;">
            <option value=''>(All)</option>
			<?php
				foreach (array('Comparison', 'Gene', 'Project', 'Sample') as $term) {
					echo '<option value="' . $term . '"';
					if ($_GET['category'] == $term) echo ' selected';
					echo '>' . $term . '</option>';
				}
			?>
		</select>

        <a href="my_lists.php" style="font-size:1rem;" class="ml-3">
          <i class="fas fa-angle-double-right"></i> All saved lists
        </a>

        <a href="new_list.php" style="font-size:1rem;" class="ml-3">
          <i class="fas fa-plus"></i> Create new list
        </a>

	</div>

    <div class="w-100 my-3">

        <?php
          if (count($data_lists) <= 0) echo '<div class="alert alert-warning w-100 mt-3">No list available.</div>';
          else {
        ?>

            <table id="myDataTable" class="table table-bordered table-striped my-3 w-100">
              <thead>
                <tr class="table-info">
                  <th>Name</th>
                  <th>Category</th>
                  <th>Count</th>
                  <th>Time</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  foreach ($data_lists as $list) {
                    if ($CATEGORY != '' && $CATEGORY != $list['Category']) continue;
                    if ($current_species != $list['Species']) continue;

                      echo '
                      <tr>
                        <td>
                            <a href="list_page.php?id=' . $list['ID'] . '">
                                ' . $list['Name'] . '
                            </a> &nbsp;
                            <button title="Delete this list" class="btn btn-danger btn-sm btn-pre-delete" rowid="' . $list['ID'] . '">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                        <td>' . $list['Category'] . '</td>
                        <td>' . $list['Count'] . '</td>
                        <td>' . substr($list['Time_Created'], 0, 10) . '</td>
                      </tr>';

                  }
                ?>
              </tbody>
            </table>

        <?php } ?>

    </div>


</div>
</div>
<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
</div>
</div>




<script>

$(document).ready(function() {

    $('#myDataTable').DataTable({"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });

    $(document).on('change', '#select-page-category', function() {
        window.location = 'my_lists.php?category=' + $('#select-page-category').val();
    });

    $(document).on('click', '.btn-pre-delete', function() {
        var rowid = $(this).attr('rowid');
        bootbox.confirm({
            message: "Are you sure to delete the record?",
            buttons: {
                confirm: {
                    label: 'Delete',
                    className: 'btn-danger'
                },
                cancel: {
                    label: 'cancel',
                    className: 'btn-secondary'
                }
            },
            callback: function (result) {
                if (result) {
                    $.ajax({
                        type: 'post',
                        url: 'exe.php?action=delete_list',
                        data: { rowid: rowid },
                        success: function(res) {
                            location.reload(true);
                        }
                    });
                }
            }
        });
    });
});


</script>

</body>
</html>