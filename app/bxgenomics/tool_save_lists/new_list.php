<?php
include_once("config.php");


$current_species = $_SESSION['SPECIES_DEFAULT'];
if (isset($_GET['species']) && in_array(ucfirst(strtolower($_GET['species'])), array('Human', 'Mouse')) && $current_species != ucfirst(strtolower($_GET['species'])) ) {
	$current_species = ucfirst(strtolower($_GET['species']));
}


$type_list = array('comparison', 'gene', 'sample', 'project');
$CATEGORY = 'Comparison';
if (isset($_GET['category']) && in_array(strtolower($_GET['category']), $type_list)) {
    $CATEGORY = ucfirst(strtolower($_GET['category']));
}
else if (isset($_GET['Category']) && in_array(strtolower($_GET['Category']), $type_list)) {
    $CATEGORY = ucfirst(strtolower($_GET['Category']));
}


// $microtime = microtime();
// echo $microtime;
$default_name_list = array();
if (isset($_GET['time']) && trim($_GET['time']) != '') {
    $time = $_GET['time'];

    if(! isset($_GET['type']) || $_GET['type'] == $CATEGORY){
        $default_name_list = category_list_to_idnames($_SESSION['SAVED_LIST'][$time], 'id', $CATEGORY, $current_species);
    }
    else if (isset($_GET['type']) && $_GET['type'] != $CATEGORY ) {

        $id_names = category_list_to_idnames($_SESSION['SAVED_LIST'][$time], 'id', $_GET['type'], $current_species);
        $ids = array_keys( $id_names );

        if(is_array($ids) && count($ids) > 0){

            if($_GET['type'] == 'Project' && $CATEGORY == 'Comparison'){

                $table = $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'];

                $sql = "SELECT `Name` FROM `{$table}` WHERE " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `_Projects_ID` IN (?a)";
                $default_name_list = $BXAF_MODULE_CONN -> get_col($sql, $ids);
            }
            else if($_GET['type'] == 'Project' && $CATEGORY == 'Sample'){

                $table = $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'];

                $sql = "SELECT `Name` FROM `{$table}` WHERE " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `_Projects_ID` IN (?a)";
                $default_name_list = $BXAF_MODULE_CONN -> get_col($sql, $ids);
            }
            else if($_GET['type'] == 'Comparison' && $CATEGORY == 'Sample'){

                $table = $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'];
                $sql = "SELECT `ID`, `Case_SampleIDs`, `Control_SampleIDs` FROM `{$table}` WHERE " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` IN (?a)";
                $results = $BXAF_MODULE_CONN -> get_assoc('ID', $sql, $ids);

                if(is_array($results) && count($results) > 0){
                    foreach($results as $row){
                        $default_name_list = array_unique( array_merge($default_name_list, preg_split("/[\;\,\s]/", $row['Case_SampleIDs']), preg_split("/[\;\,\s]/", $row['Control_SampleIDs']) ) );
                    }
                }
            }
        }

    }
}

else if (isset($_GET['geneset_id']) && trim($_GET['geneset_id']) != '') {

	$sql = "SELECT `Gene_Names` FROM `tbl_go_gene_list` WHERE `ID` = ?i";
	$names = $BXAF_MODULE_CONN->get_one($sql, intval($_GET['geneset_id']));

	$default_name_list = explode(", ", trim(trim($names, ',')) );
}
else if (isset($_GET['project_id']) && trim($_GET['project_id']) != '') {

    $table = $BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'];
    $sql = "SELECT `Name` FROM `{$table}` WHERE " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `ID` = ?i";
    $project_name = $BXAF_MODULE_CONN -> get_one($sql, intval($_GET['project_id']) );

    if($CATEGORY == 'Comparison'){
        $table = $BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'];
        $sql = "SELECT `Name` FROM `{$table}` WHERE " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `_Projects_ID` = ?i";
        $default_name_list = $BXAF_MODULE_CONN -> get_col($sql, intval($_GET['project_id']) );

        $_GET['Name'] = "Comparisons of Project $project_name";
    }
    else if($CATEGORY == 'Sample'){
        $table = $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'];
        $sql = "SELECT `Name` FROM `{$table}` WHERE " . $BXAF_CONFIG['QUERY_DEFAULT_FILTER'] . " AND `_Projects_ID` = ?i";
        $default_name_list = $BXAF_MODULE_CONN -> get_col($sql, intval($_GET['project_id']) );
        $_GET['Name'] = "Samples of Project $project_name";
    }
}
else if ($CATEGORY == 'Gene' && isset($_GET['gene_ids']) && trim($_GET['gene_ids']) != '') {
	$default_name_list = category_list_to_idnames(explode(',', $_GET['gene_ids']), 'id', 'gene');
}
else if ($CATEGORY == 'Comparison' && isset($_GET['comparison_ids']) && trim($_GET['comparison_ids']) != '') {
	$default_name_list = category_list_to_idnames(explode(',', trim($_GET['comparison_ids'])), 'id', 'comparison');
}
else if ($CATEGORY == 'Sample' && isset($_GET['sample_ids']) && trim($_GET['sample_ids']) != '') {
	$default_name_list = category_list_to_idnames(explode(',', trim($_GET['sample_ids'])), 'id', 'sample');
}


$default_name_list = array_unique($default_name_list);
sort($default_name_list);

$default_names = implode("\n", $default_name_list);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>
</head>
<body>
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">



    <div class="container-fluid">

        <h2 class="w-100">Create New List</h2>
        <hr class="w-100 mb-3">

        <div class="w-100 my-3">
            <a href="my_lists.php" class="ml-3">  <i class="fas fa-angle-double-right"></i> My saved lists </a>
		</div>

        <div class="w-100">

            <form class="w-100" id="form_save_list" style="max-width: 70rem;">

                <input type="hidden" id="species" name="species" value="<?php echo $current_species; ?>" >

                <div class="row my-3">
                  <div class="col-md-2 pt-2 text-md-right">Category: </div>
                  <div class="col-md-10">
                      <select id="category" name="category" class="custom-select" style="width: 12rem;">
          				<?php
          					foreach (array('Comparison', 'Gene', 'Project', 'Sample') as $term) {
          						echo '<option value="' . $term . '"';
          						if ($CATEGORY == $term) echo ' selected';
          						echo '>' . $term . '</option>';
          					}
          				?>
          			</select>
                  </div>
                </div>
                <div class="row my-3">
                  <div class="col-md-2 pt-2 text-md-right">List Name:</div>
                  <div class="col-md-10">
                    <input class="form-control" name="list_name" name="list_name" value="<?php if (isset($_GET['Name']) && trim($_GET['Name']) != '') echo trim($_GET['Name']); ?>" required>
                  </div>
                </div>
                <div class="row my-3">
                  <div class="col-md-2 pt-2 text-md-right">
					  Names:<BR/>
					  (One per row)
				  </div>
                  <div class="col-md-10">
                    <textarea
                      class="form-control"
                      style="height:150px;"
                      name="content_name" required><?php echo $default_names; ?></textarea>
                  </div>
                </div>
                <div class="row my-3">
                  <div class="col-md-2 pt-2 text-md-right">Description:</div>
                  <div class="col-md-10">
                    <textarea
                      class="form-control"
                      name="description"></textarea>
                  </div>
                </div>
                <div class="row my-3">
                  <div class="col-md-2"></div>
                  <div class="col-md-10">
                    <button class="btn btn-primary" id="btn_submit">  <i class="fas fa-save"></i> Create New List </button>
                  </div>
                </div>

            </form>

			<div id="div_debug"></div>

        </div>

    </div>


        </div>
        <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
    </div>
</div>





</body>


<script>

$(document).ready(function() {

    // Save List
    var options = {
        url: 'exe.php?action=save_list',
        type: 'post',
        beforeSubmit: function(formData, jqForm, options) {
            $('#btn_submit').children(':first').removeClass('fa-floppy-o').addClass('fa-spin fa-spinner');
            $('#btn_submit').attr('disabled', '');
            return true;
        },
        success: function(response){
            $('#btn_submit').children(':first').removeClass('fa-spin fa-spinner').addClass('fa-floppy-o');
            $('#btn_submit').removeAttr('disabled');

			// $('#div_debug').html(response);

            if (response.type == 'Error') {
                bootbox.alert(response.detail);
            } else {
                var content = response.count + ' ' + response.category + 's have been saved.';
                bootbox.alert(content, function() {
                    window.location = 'list_page.php?id=' + response.list_id
                });
            }

            return true;
        }
    };
    $('#form_save_list').ajaxForm(options);

});

</script>

</html>