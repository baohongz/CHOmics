<?php
include_once(dirname(__FILE__) . "/config/config.php");


$col_list = $BXAF_CONFIG['TBL_BXGENOMICS_FIELDS']['Comparison'];

$col_list_core = $BXAF_CONFIG['TBL_BXGENOMICS_FIELD_VALUES']['Comparison'];



/**********************************************************************************************
 ** Save Project **
 **********************************************************************************************/
if (isset($_GET['action']) && $_GET['action'] == 'save_comparison') {
    // print_r($_POST); exit();
    $PAGE_TYPE     = trim($_POST['page_type']);


    //---------------------------------------------------------------------------
    // Edit Existing Comparison
    //---------------------------------------------------------------------------
    if ($PAGE_TYPE == 'edit') {

        $COMP_ID       = intval($_POST['comp_id']);
        $info          = array(
          'Description'  => trim($_POST['Description']),
          '_Analysis_ID' => 0,
          '_Owner_ID'    => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
          'Time_Created' => date("Y-m-d H:i:s"),
        );
        foreach ($col_list as $col) {
          $info[$col] = trim($_POST[$col]);
        }
        $BXAF_MODULE_CONN -> update($BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], $info, "`ID`={$COMP_ID}");

        exit();
    }

  //---------------------------------------------------------------------------
  // Create New Comparison
  //---------------------------------------------------------------------------
  else {

    $name          = trim($_POST['Name']);
    $analysis      = trim($_POST['analysis']);
    $info          = array(
        'Name'         => $name,
        'Species'      => $_POST['Species'],
        'Description'  => trim($_POST['Description']),
        '_Analysis_ID' => substr($analysis, 0, strpos($analysis, '_')),
        '_Projects_ID' => intval($_POST['_Projects_ID']),
        '_Owner_ID'    => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
        'Time_Created' => date("Y-m-d H:i:s"),
    );
    foreach ($col_list as $col) {
        $info[$col] = trim($_POST[$col]);
    }
    $COMP_ID = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS'], $info);

    //-----------------------------
    // Save Comparison Data
    // $comp_data_dir =
    $deg_file = "{$BXAF_CONFIG['ANALYSIS_DIR']}{$analysis}/alignment/DEG/{$name}/DEG_Analysis/{$name}_DEG.csv";

    if (!file_exists($deg_file)) {
        echo 'Error: DEG data file not exists: ' . $deg_file;
        exit();
    }

    $file = fopen($deg_file,"r");
    $header = array();
    while(! feof($file)) {
        $row = fgetcsv($file);
        if (!is_array($row) || count($row) <= 1) continue;
        // Header
        if (trim($row[0]) == '') {
            $header = array_flip($row);
        }

        $ensembl = trim($row[0]);
        $sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_GENES']}` WHERE `Ensembl` LIKE '%{$ensembl}%'";

        $gene_id = $BXAF_MODULE_CONN -> get_one($sql);

        if (!isset($gene_id) || intval($gene_id) <= 0) continue;

        $info = array(
            'Species' => $_POST['Species'],
            '_Comparisons_ID' => $COMP_ID,
            '_Genes_ID' => $gene_id,
            'Log2FoldChange' => $row[$header['logFC']],
            'PValue' => $row[$header['P.Value']],
            'AdjustedPValue' => $row[$header['adj.P.Val']],
            'Name' => $row[$header['Associated.Gene.Name']],
            '_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
            'Time_Created' => date("Y-m-d H:i:s")
        );
        $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONDATA'], $info);
    }
    fclose($file);

    //-----------------------------
    // Copy Files
    $file_list = array(
        'biological_process.txt',
        'cellular_component.txt',
        'molecular_function.txt',
        'kegg.txt',
        'interpro.txt',
        'wikipathways.txt',
        'reactome.txt',
        'geneOntology.html'
    );

    $dir_from = "{$BXAF_CONFIG['ANALYSIS_DIR']}{$analysis}/alignment/DEG/{$name}/Downstream";
    $dir_to   = $BXAF_CONFIG['GO_OUTPUT'][strtoupper($_POST['Species'])] . "comp_{$COMP_ID}";

    if (!is_dir($dir_to)) {
        mkdir($dir_to, 0775, true);
        mkdir("{$dir_to}/comp_{$COMP_ID}_GO_Analysis_Up", 0775, true);
        mkdir("{$dir_to}/comp_{$COMP_ID}_GO_Analysis_Down", 0775, true);
    }

    if (file_exists("{$dir_from}/{$name}_up_list.txt")) copy("{$dir_from}/{$name}_up_list.txt", "{$dir_to}/comp_{$COMP_ID}_up_genes.txt");
    if (file_exists("{$dir_from}/{$name}_down_list.txt")) copy("{$dir_from}/{$name}_down_list.txt", "{$dir_to}/comp_{$COMP_ID}_down_genes.txt");

    foreach ($file_list as $file) {
        if (file_exists("{$dir_from}/GO_Analysis_Up/{$file}")) {
            copy("{$dir_from}/GO_Analysis_Up/{$file}", "{$dir_to}/comp_{$COMP_ID}_GO_Analysis_Up/{$file}");
        }
        if (file_exists("{$dir_from}/GO_Analysis_Down/{$file}")) {
            copy("{$dir_from}/GO_Analysis_Down/{$file}", "{$dir_to}/comp_{$COMP_ID}_GO_Analysis_Down/{$file}");
        }
    }
  }


  exit();
}











/**********************************************************************************************
 ** Non-exe File Starts Here **
 **********************************************************************************************/
$PAGE_TYPE = '';

//-----------------------------------------------------------------------
// Edit Existing Comparison

$comparison_name = '';
$comparison_id = '';
$project_id = '';

if (isset($_GET['compid']) && intval($_GET['compid']) > 0) {
    $PAGE_TYPE = 'edit';
    $COMPARISON_ID = intval($_GET['compid']);
    $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_COMPARISONS']}` WHERE `ID`={$COMPARISON_ID} AND `bxafStatus`<5";
    $current_info = $BXAF_MODULE_CONN -> get_row($sql);

    if (!is_array($current_info) || count($current_info) <= 1) {
        header("Location: index.php");
        exit();
    }
    $comparison_name = $current_info['Name'];
    $comparison_id = $current_info['ID'];
    $project_id = $current_info['_Projects_ID'];
}

//-----------------------------------------------------------------------
// Create New Comparison based on an analysis and comparison
else if (isset($_GET['comp']) && trim($_GET['comp']) != '' && isset($_GET['analysis']) && trim($_GET['analysis']) != '') {
    $PAGE_TYPE = 'new';

    $comparison_name = trim($_GET['comp']);
    $analysis = trim($_GET['analysis']);

    $analysis_id = intval(substr($analysis, 0, strpos($analysis, '_')));

    $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_ANALYSIS']}` WHERE `ID`=" . $analysis_id;
    $analysis_info = $BXAF_MODULE_CONN -> get_row($sql);
    if(! is_array($analysis_info) || count($analysis_info) <= 0){
        die("No analysis is found!");
    }

    // Check Project Exists
    $sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_EXPERIMENT']}` WHERE `ID`=" . intval($analysis_info['Experiment_ID']);
    $experiment_info = $BXAF_MODULE_CONN -> get_row($sql);
    if(! is_array($experiment_info) || count($experiment_info) <= 0){
        die("No experiment is found!");
    }

    $sql = "SELECT `ID` FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS']}` WHERE `Name`= ?s AND `_Owner_ID`={$BXAF_CONFIG['BXAF_USER_CONTACT_ID']} AND `bxafStatus`<5";
    $project_id = $BXAF_MODULE_CONN -> get_one($sql, $experiment_info['Name']);

    if ($project_id <= 0) {
        // Create a new project
        $info = array(
            'Species' => array_shift(explode(' ', $analysis_info['Species'])) ,
            'Name' => $experiment_info['Name'],
            'Description' => $experiment_info['Description'],
            '_Owner_ID' => $BXAF_CONFIG['BXAF_USER_CONTACT_ID'],
            'Time_Created' => date("Y-m-d H:i:s"),
        );
        $project_id = $BXAF_MODULE_CONN -> insert($BXAF_CONFIG['TBL_BXGENOMICS_PROJECTS'], $info);

    }
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
					<div class="d-flex flex-row mt-3">
						<h3 class="align-self-baseline">Save Comparison</h3>
					</div>

          <form id="form">

            <input name="page_type" value="<?php echo $PAGE_TYPE;?>" hidden>
            <input name="comp_id" value="<?php echo ($current_info) ? $current_info['ID'] : '' ;?>" hidden>
            <input name="_Projects_ID" value="<?php echo $project_id; ?>" hidden>
            <input name="analysis" value="<?php echo $analysis;?>" hidden>

            <div class="row mb-2">
              <div class="col-md-3 pt-2 text-md-right">
                Description:
              </div>
              <div class="col-md-9">
                <textarea class="form-control"
                  name="Description"
                  style="height:100px;"
                  ><?php echo ($current_info) ? $current_info['Description'] : '' ; ?></textarea>
              </div>
            </div>


            <?php
              // foreach ($col_list as $col) {
              //   echo '
              //   <div class="row mb-2">
              //     <div class="col-md-3 pt-2 text-md-right text-nowrap">
              //       ' . $col . ':
              //     </div>
              //     <div class="col-md-9">
              //       <input class="form-control" name="' . $col . '"
              //         value="';
              //
              //         echo ($current_info) ? $current_info[$col] : '' ;
              //
              //   echo '">
              //     </div>
              //   </div>';
              //
              // }

              foreach ($col_list as $col) {
                echo '
                <div class="row mb-2">
                  <div class="col-md-3 pt-2 text-md-right text-nowrap">
                    ' . $col . ':
                  </div>
                  <div class="col-md-9">';

                  // No dropdown menu for default values
                  if (!array_key_exists($col, $col_list_core)) {
                    echo '
                    <input class="form-control" name="' . $col . '"
                      value="';
                      echo ($current_info) ? $current_info[$col] : '' ;
                    echo '">';
                  }
                  // Default values provided
                  else {

                    echo '<select class="custom-select select-default-value" style="width:250px;">';
                    foreach($col_list_core[$col] as $option) {
                      echo '<option value="' . $option . '"';
                      if ($current_info[$col] == $option) echo ' selected';
                      echo '>' . $option . '</option>';
                    }
                    echo '<option value="custom"';
                    if (isset($current_info) && !in_array($current_info[$col], $col_list_core[$col])) {
                      echo 'selected';
                    }
                    echo '>Enter your own value...</option>';
                    echo '</select> &nbsp;';
                    echo '<input class="form-control" name="' . $col . '"
                          value="';

                          if (isset($current_info) && !in_array($current_info[$col], $col_list_core[$col])) {
                            echo $current_info[$col];
                          } else {
                            echo $col_list_core[$col][0];
                          }

                    echo '"
                    style="display:inline-block; width:50%;"';

                          if (!isset($current_info) || in_array($current_info[$col], $col_list_core[$col])) {
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
        url: 'edit_comparison.php?action=save_comparison',
        type: 'post',
        beforeSubmit: function(formData, jqForm, options) {
            $('#btn_submit').attr('disabled', '')
                .children(':first')
                .removeClass('fa-upload')
                .addClass('fa-spin fa-spinner');
            return true;
        },
        success: function(response){
            $('#btn_submit').removeAttr('disabled')
                .children(':first')
                .addClass('fa-upload')
                .removeClass('fa-spin fa-spinner');
            // console.log(response);
            if (response.substring(0, 5) == 'Error') {
                bootbox.alert(response);
            } else {
                bootbox.alert('The comparison has been saved.', function() {
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
