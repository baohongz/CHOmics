<?php
include_once(dirname(__FILE__) . "/config/config.php");


$col_list = $BXAF_CONFIG['TBL_BXGENOMICS_FIELDS']['Sample'];
$col_list_core = $BXAF_CONFIG['TBL_BXGENOMICS_FIELD_VALUES']['Sample'];





/**********************************************************************************************
 ** Save Sample **
 **********************************************************************************************/
if (isset($_GET['action']) && $_GET['action'] == 'save_sample') {

    $PAGE_TYPE     = trim($_POST['page_type']);

    //---------------------------------------------------------------------------
    // Edit Existing Sample
    //---------------------------------------------------------------------------
    if ($PAGE_TYPE == 'edit') {
        $SAMPLE_ID     = intval($_POST['sample_id']);
        $name          = trim($_POST['Name']);
        $info          = array(
            'Name'         => $name,
            'Species'      => 'Human',
            'Description'  => trim($_POST['Description']),
            '_Samples_ID'  => intval($_POST['_Samples_ID']),
            '_Projects_ID' => intval($_POST['_Projects_ID']),
            '_Owner_ID'    => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
            'Time_Created' => date("Y-m-d H:i:s"),
        );
        foreach ($col_list as $col) {
            if(array_key_exists($col, $_POST)) $info[$col] = trim($_POST[$col]);
        }
        $BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], $info, "`ID`={$SAMPLE_ID}");
        exit();
    }

    //---------------------------------------------------------------------------
    // Create New Sample
    //---------------------------------------------------------------------------
    else {

        $name = trim($_POST['Name']);
        $project_id = intval($_POST['_Projects_ID']);
        $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}`
            WHERE `Name` = ?s
            AND `_Projects_ID`= ?i
            AND `_Owner_ID`={$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}
            AND `bxafStatus`<5";

        $data_check = $BXAF_MODULE_CONN -> get_row($sql, $name, intval($_POST['_Projects_ID']));

        if (is_array($data_check) && count($data_check) > 1) {
            echo 'Error: Sample name "' . $name . '" is already used in the project.';
            exit();
        }

        $info = array(
            'Name'         => $name,
            'Species'      => $_POST['Species'],
            'Description'  => trim($_POST['Description']),
            '_Samples_ID'  => intval($_POST['_Samples_ID']),
            '_Projects_ID' => intval($_POST['_Projects_ID']),
            '_Owner_ID'    => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
            'Time_Created' => date("Y-m-d H:i:s"),
        );

        foreach ($col_list as $col) {
            if(array_key_exists($col, $_POST)) $info[$col] = trim($_POST[$col]);
        }

        $SAMPLE_ID = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES'], $info);

    }

    exit();
}








/**********************************************************************************************
 ** Non-exe File Starts Here **
 **********************************************************************************************/


$project_id = 0;
// Convert Experiment ID to Project ID
if(isset($_GET['expid']) && intval($_GET['expid']) > 0) {

    $expid = intval($_GET['expid']);

    $sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT'] . "` WHERE " . $BXAF_CONFIG['QUERY_OWNER_FILTER'] . " AND `ID` = $expid";
	$exp_info = $BXAF_MODULE_CONN->get_row($sql);

    if (!is_array($exp_info) || count($exp_info) <= 0) {
        // The experiment is not found!
        die("Error: The experiment is not found.");
    }

    // Check Project Exists
    $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE " . $BXAF_CONFIG['QUERY_OWNER_FILTER'] . " AND `Name`= ?s";
    $project_info = $BXAF_MODULE_CONN -> get_row($sql, $exp_info['Name']);

    if (! is_array($project_info) || count($project_info) <= 0) {
        // Create new project
        $info = array(
    		'Name' => $exp_info['Name'],
    		'Description' => $exp_info['Description'],
    		'_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
    		'Time_Created' => date("Y-m-d H:i:s")
    	);
    	$project_id = $BXAF_MODULE_CONN->insert($BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $info);
    }
    else {
        //Retrieve
        $project_id = $project_info['ID'];
    }
}




$data_curr_sample = array();
$PAGE_TYPE = 'new';
// Edit Existing Sample
if (isset($_GET['sampleid']) && intval($_GET['sampleid']) > 0) {

    // TBL_Samples
    $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_SAMPLES']}` WHERE `ID`= ?i AND `bxafStatus`<5";
    $sample_info = $BXAF_MODULE_CONN -> get_row($sql, intval($_GET['sampleid']));

    $project_id = $sample_info['_Projects_ID'];


    if (is_array($sample_info) && count($sample_info) > 0) {
        $PAGE_TYPE = 'edit';
        $SAMPLE_ID = intval($_GET['sampleid']);
        $data_curr_sample = $sample_info;
    }
    else {

        // copy info from tbl_bxgenomics_sample
        $sql = "SELECT * FROM `" . $BXAF_CONFIG['TBL_BXGENOMICS_SAMPLE'] . "` WHERE `ID`= " . intval($_GET['sampleid']) . " AND `bxafStatus`<5";
        $data_existing_samples = $BXAF_MODULE_CONN->get_row($sql);

        if (is_array($data_existing_samples) && count($data_existing_samples) > 0) {
            $data_curr_sample = array(
                'Name'=>$data_existing_samples['Name'],
                // '_Projects_ID'=>$project_id,
                'Description'=>$data_existing_samples['Description'],
                '_Samples_ID'=>$data_existing_samples['ID'],
                'Treatment'=>$data_existing_samples['Treatment_Name'],
                '_Owner_ID'=>$BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
                'Time_Created'=>date("Y-m-d H:i:s"),
            );
        }
        else {
          // The sample id is not found!
          die("Error: The sample is not found.");
        }
    }

}

// echo 'data_curr_sample<pre>'; print_r($data_curr_sample); echo '</pre>';

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
					<div class="d-flex flex-row mt-3">
						<h3 class="align-self-baseline">Edit Sample <span class="text-success"><?php echo $sample_info['Name']; ?></span></h3>
                        <div class="align-self-baseline">
                        </div>
					</div>

          <form id="form">

            <input name="page_type" value="<?php echo $PAGE_TYPE; ?>" hidden>
            <input name="sample_id" value="<?php echo $SAMPLE_ID; ?>" hidden>
            <input name="_Projects_ID" value="<?php echo $project_id;?>" hidden>
            <input name="_Samples_ID" value="<?php echo $data_curr_sample['_Samples_ID'];?>" hidden>

            <div class="row mb-2">
              <div class="col-md-3 pt-1 text-md-right">
                Name:
              </div>
              <div class="col-md-9 pt-1">
                <input class="form-control" name="Name" value="<?php echo $data_curr_sample['Name']; ?>">
              </div>
            </div>

            <div class="row mb-2">
              <div class="col-md-3 pt-1 text-md-right">
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
              <div class="col-md-3 pt-1 text-md-right">
                Description:
              </div>
              <div class="col-md-9">
                <textarea class="form-control"
                  name="Description"
                  style="height:100px;"
                  ><?php echo $data_curr_sample['Description']; ?></textarea>
              </div>
            </div>

            <?php
              foreach ($col_list as $col) {
                echo '
                <div class="row mb-2">
                  <div class="col-md-3 pt-1 text-md-right">
                    ' . $col . ':
                  </div>
                  <div class="col-md-9">';

                  // No dropdown menu for default values
                  if (!array_key_exists($col, $col_list_core)) {
                    echo '
                    <input class="form-control" name="' . $col . '"
                      value="';
                      echo ($data_curr_sample) ? $data_curr_sample[$col] : '' ;
                    echo '">';
                  }
                  // Default values provided
                  else {

                    echo '<select class="custom-select select-default-value" style="width:250px;">';
                    foreach($col_list_core[$col] as $option) {
                      echo '<option value="' . $option . '"';
                      if ($data_curr_sample[$col] == $option) echo ' selected';
                      echo '>' . $option . '</option>';
                    }
                    echo '<option value="custom"';
                    if (isset($data_curr_sample) && !in_array($data_curr_sample[$col], $col_list_core[$col])) {
                      echo 'selected';
                    }
                    echo '>Enter your own value...</option>';
                    echo '</select> &nbsp;';
                    echo '<input class="form-control" name="' . $col . '"
                          value="';

                          if (isset($data_curr_sample) && !in_array($data_curr_sample[$col], $col_list_core[$col])) {
                            echo $data_curr_sample[$col];
                          } else {
                            echo $col_list_core[$col][0];
                          }

                    echo '"
                    style="display:inline-block; width:50%;"';

                          if (!isset($data_curr_sample) || in_array($data_curr_sample[$col], $col_list_core[$col])) {
                            echo ' hidden';
                          }

                    echo '>';
                  }

                echo '
                  </div>
                </div>';

              }
            ?>

            <div class="row mb-5">
              <div class="col-md-3">&nbsp;</div>
              <div class="col-md-9">
                <button class="btn btn-primary" id="btn_submit">
                  <i class="fas fa-upload"></i>
                  Save Sample Information
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

  $(document).on('change', '.select-default-value', function() {
    var curr = $(this);
    var value = curr.val();
    curr.next().val(value);
    if (value == 'custom') {
      curr.next().val('');
      curr.next().removeAttr('hidden');
    } else {
      curr.next().attr('hidden', '');
    }
  });


  var options = {
		url: 'edit_sample.php?action=save_sample',
		type: 'post',
		beforeSubmit: function(formData, jqForm, options) {
			return true;
		},
		success: function(response){
            if (response.substring(0, 5) == 'Error') {
                bootbox.alert(response);
            } else {
                // bootbox.alert(response);
                bootbox.alert('The sample has been saved.', function() {
                    window.location = 'project.php?id=<?php echo $project_id; ?>';
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