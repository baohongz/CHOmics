<?php
include_once(dirname(__FILE__) . "/config/config.php");

$col_list = $BXAF_CONFIG['TBL_BXGENOMICS_FIELDS']['Project'];





/**********************************************************************************************
 ** Save Project **
 **********************************************************************************************/
if (isset($_GET['action']) && $_GET['action'] == 'save_project') {

    $name = trim($_POST['Name']);
    $sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}`
        WHERE `Name`= ?s
        AND `_Owner_ID`={$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}
        AND `bxafStatus`<5";

    $data_check = $BXAF_MODULE_CONN -> get_one($sql, $name);

    if ($data_check > 0) {
        echo 'Error: Project "' . $name . '" is already created.';
        exit();
    }

    $info = array(
        'Name' => $name,
        'Species' => $_POST['Species'],
        'Description' => trim($_POST['Description']),
        '_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
        'Time_Created' => date("Y-m-d H:i:s"),
    );
    foreach ($col_list as $col) {
        if(array_key_exists($col, $_POST)) $info[$col] = trim($_POST[$col]);
    }
    $project_id = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $info);

    exit();
}





/**********************************************************************************************
 ** Non-exe File Starts Here **
 **********************************************************************************************/
$SAVE = false;
$PROJECT_ID = 0;
$experiment_info = array('Name'=>'', 'Description'=>'');
if (isset($_GET['expid']) && intval($_GET['expid']) > 0) {
    $SAVE = true;
    $PROJECT_ID = intval($_GET['expid']);

    $sql = "SELECT `Name`, `Description` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']}` WHERE `ID`={$PROJECT_ID}";
    $experiment_info = $BXAF_MODULE_CONN -> get_row($sql);

}

?><!DOCTYPE html>
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




				<!-- Main contents here -->
				<div class="container-fluid">
                    <div class="row my-3">
                        <h3 class="">
                        <?php echo ($PROJECT_ID == 0) ? 'Create New Project': 'Save Experiment as Project'; ?>
                        </h3>
                    </div>


          <form id="form">

          <div class="row mb-2">
            <div class="col-md-2 pt-1 text-md-right">
              Name:
            </div>
            <div class="col-md-9">
              <input class="form-control" name="Name" value="<?php echo $experiment_info['Name']; ?>">
            </div>
          </div>

          <div class="row mb-2">
            <div class="col-md-2 pt-1 text-md-right">
              Species:
            </div>
            <div class="col-md-9">
              <select class="custom-select" name="Species">
                <option value="Human">Human</option>
                <option value="Mouse">Mouse</option>
              </select>
            </div>
          </div>

          <div class="row mb-2">
            <div class="col-md-2 pt-1 text-md-right">
              Description:
            </div>
            <div class="col-md-9">
              <textarea class="form-control" name="Description" style="height:100px;"><?php echo $experiment_info['Description']; ?></textarea>
            </div>
          </div>

          <?php
            foreach ($col_list as $col) {
              echo '
              <div class="row mb-2">
                <div class="col-md-2 pt-1 text-md-right text-nowrap">
                  ' . $col . ':
                </div>
                <div class="col-md-9">
                  <input class="form-control" name="' . $col . '">
                </div>
              </div>';

            }
          ?>

          <div class="row mb-5">
            <div class="col-md-2">&nbsp;</div>
            <div class="col-md-9">
              <button class="btn btn-primary" id="btn_submit">
                <i class="fas fa-upload"></i>
                Submit
              </button>
            </div>
          </div>
          </form>

				</div>



      </div>

		  <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>

		</div>

	</div>

<script>
$(document).ready(function() {
  var options = {
		url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=save_project',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {
            if($('#Name').val() == ''){
                bootbox.alert('<h2><i class="fas fa-exclamation-triangle text-warning"></i> Warning</h2><div class="lead p-3">Please enter a valid project name.</div>');
                return false;
            }
			return true;
		},
		success: function(response){
            if (response.substring(0, 5) == 'Error') {
                bootbox.alert(response);
            }
            else {
                bootbox.alert('The project has been saved.', function() {
                    window.location = 'index.php';
                });
            }
			return true;
		}
	};
	$('#form').ajaxForm(options);
});
</script>

</body>
</html>