<?php
include_once('config.php');

// Specify Folder for Genes & Samples
$TIME_STAMP = 0;
if (isset($_GET['id']) && $_GET['id'] != '') {
  $TIME_STAMP = $_GET['id'];
  //$dir = "{$BXAF_CONFIG['USER_FILES']['TOOL_PCA']}/{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$TIME_STAMP}";
  $dir = get_PCA_dir($TIME_STAMP);
  if (!file_exists($dir)) {
    echo "Error. The directory ({$dir}) does not exist.";
    exit();
  }
}


//----------------------------------------------------------------------------------------------------
// If 'id' is set
$SAVED_RESULT = false;
if (isset($_GET['id']) && trim($_GET['id']) != '' && ! is_numeric($_GET['id']) ) {
  $ROWID = bxaf_decrypt($_GET['id'], $BXAF_CONFIG['BXAF_KEY']);
  if (intval($ROWID) != 0) {
    $SAVED_RESULT = true;
    $dir          = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $_GET['id'];
    foreach ($BXAF_CONFIG['PCA_R_FILE_LIST'] as $file) {
      copy(
        $dir . '/' . $file,
        $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $file
      );
    }
  }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
  <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>
  <script src="../library/plotly.min.js"></script>
</head>
<body>

  <!-------------------------------------------------------------------------------------------------->
  <!-- Page Header -->
  <!-------------------------------------------------------------------------------------------------->
	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">
  <!-------------------------------------------------------------------------------------------------->
	<!-------------------------------------------------------------------------------------------------->


      <div class="container-fluid">
  			<h1>
  				FactoMineR PCA Analysis
  			</h1>
        <hr />

        <?php include_once('component_header.php'); ?>
        <?php include_once('component_r_header_2.php'); ?>

        <!--------------------------------------------------------------------------------------------->
        <!-- BarChart -->
        <!--------------------------------------------------------------------------------------------->
        <div id="div_barchart">
          <span id="msg_loading"><i class="fas fa-spinner fa-pulse"></i> Loading data...</span>
        </div>
      </div>


  <!-------------------------------------------------------------------------------------------------->
  <!-- Page Footer -->
  <!-------------------------------------------------------------------------------------------------->
      </div>
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
		</div>
	</div>
  <!-------------------------------------------------------------------------------------------------->
	<!-------------------------------------------------------------------------------------------------->






<script>


$(document).ready(function() {

  $('#btn_index_r_barchart').addClass('active');

  //----------------------------------------------------------------------------------
  // Get BarChart Info
  //----------------------------------------------------------------------------------
  get_bar_chart();
});


function get_bar_chart() {
  $.ajax({
    type: 'POST',
    url: 'exe_r.php?action=get_barchart',
    data: { time_stamp: '<?php echo $TIME_STAMP; ?>' },
    success: function(response) {
      //console.log(response);
      var type = response.type;
      if (type == 'Error') {
        bootbox.alert(response.detail, function(){
            window.location="index_genes_samples.php";
        });
      }
      else if (type == 'Success') {
        $('#msg_loading').hide();
        // alert(response.data.x);
        var data = [
          {
            x: response.data.x,
            y: response.data.y,
            type: 'bar'
          }
        ];
        var layout = {
          title: 'Bar Chart',
          width: 800,
          height: 600,
          xaxis: { title: 'Dimensions' },
          yaxis: { title: 'Percentage of Variance' },
        };
        Plotly.newPlot('div_barchart', data, layout);
      } else if (type == 'Pending') {
        setTimeout(function() { get_bar_chart(); }, 2000);
      }
    }
  });
}
</script>




</body>
</html>