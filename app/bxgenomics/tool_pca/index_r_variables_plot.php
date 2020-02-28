<?php
include_once('config.php');


//$dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
$dir = get_PCA_dir($TIME_STAMP);

// Specify Folder for Genes & Samples
$TIME_STAMP = 0;
if (isset($_GET['id']) && $_GET['id'] != '') {
  $TIME_STAMP = $_GET['id'];
  //$dir = "{$BXAF_CONFIG['USER_FILES']['TOOL_PCA']}{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$_GET['id']}";
  $dir = get_PCA_dir($TIME_STAMP);
  if (!file_exists($dir)) {
    echo "Error. The directory ({$dir}) does not exist.";
    exit();
  }

  // Reload Variances
  //$dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $TIME_STAMP;
  $dir = get_PCA_dir($TIME_STAMP);
  if (file_exists($dir . '/PCA_barchart.csv')) {
    $_SESSION['PCA_R_VAR'] = array();
    $index = 0;
    $file = fopen($dir . '/PCA_barchart.csv', "r") or die('No file.');
    while(($row    = fgetcsv($file)) !== false){
      if ($index == 0) {
        foreach ($row as $k => $colname) {
          if ($colname == 'percentage of variance') $var_col_index = $k;
        }
      }
      if ($index > 0) {
        $_SESSION['PCA_R_VAR'][] = number_format($row[$var_col_index], 2) . '%';
      }
      $index++;
    }
    fclose($file);
  } else {
    unset($_SESSION['PCA_R_VAR']);
  }
}

// Delete all chart files.
/*
foreach (new DirectoryIterator(dirname(__FILE__) . '/files/' . $BXAF_CONFIG['BXAF_USER_CONTACT_ID']) as $fileInfo) {
  if(!$fileInfo->isDot()) {
    unlink($fileInfo->getPathname());
  }
}
*/

if (!file_exists($dir . '/PCA_var.coord.csv')) {
  echo 'No file exists.';
  exit();
}


$file = fopen($dir . '/PCA_var.coord.csv', "r") or die('No file.');
$file_data = array();
$delimiter = ",";

//------------------------------------------------------------
// Read File
$index = 0;
while(($row = fgetcsv($file, 1000, $delimiter)) !== false){
  // header
  if ($index == 0) {
    $headers = $row;
  }
  if (trim($row[0]) != '' && $index > 0) {
    $file_data[] = $row;
  }
  $index++;
}
fclose($file);

// echo $dir . '/PCA_var.coord.csv';
// print_r($headers);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-Equiv="Cache-Control" Content="no-cache" />
    <meta http-Equiv="Pragma" Content="no-cache" />
    <meta http-Equiv="Expires" Content="0" />
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <link href="../library/wenk.min.css" rel="stylesheet">
    <link href="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php" rel="stylesheet">
    <link href="../css/main.css" rel="stylesheet">
	<script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>
    <script type="text/javascript" src="../library/plotly.min.js"></script>
    <script type="text/javascript" src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php"></script>

<style>
strong {
  color: #666;
}
</style>
</head>



<body>


	<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
	<div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
		<?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
		<div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
			<div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">



          <div class="container-fluid px-0 pt-3 w-100">

      			<h1 class="">
      				FactoMineR PCA Analysis
      			</h1>
            <hr />
            <?php include_once('component_header.php'); ?>

            <?php include_once('component_r_header_2.php'); ?>



            <div class="row">
              <div class="col-lg-4 col-md-4" style="max-width:300px;">
                <div class="card">
                  <div class="card-header bg-warning">
                    <h4 class="mb-0">Parameters</h4>
                  </div>
                  <div class="card-block">

                    <!------------------------------------------------------------------------------>
                    <!-- X Coordinate -->
                    <p class="mb-0 mt-2"><strong>X Coordinate:</strong></p>
                    <select class="form-control" id="select_x">
                      <?php
                        $index = 0;
                        foreach ($headers as $colname) {
                          if (trim($colname) != '') {
                            echo '<option value="' . $colname . '">';
                            echo 'PC'. intval($index + 1);
                            echo ' (' . $_SESSION['PCA_R_VAR'][$index] . ')';
                            echo '</option>';
                            $index++;
                          }
                        }
                      ?>
                    </select>

                    <!------------------------------------------------------------------------------>
                    <!-- Y Coordinate -->
                    <p class="mb-0 mt-2"><strong>Y Coordinate:</strong></p>
                    <select class="form-control" id="select_y">
                      <?php
                      $index = 0;
                      foreach ($headers as $colname) {
                        if (trim($colname) != '') {
                          echo '<option value="' . $colname . '"';
                          if ($index == 1) echo ' selected';
                          echo '>';
                          echo 'PC'. intval($index + 1);
                          echo ' (' . $_SESSION['PCA_R_VAR'][$index] . ')';
                          echo '</option>';
                          $index++;
                        }
                      }
                      ?>
                    </select>

                    <!------------------------------------------------------------------------------>
                    <!-- Label Color -->
                    <!-- <p class="mb-0 mt-2"><strong>Label Color:</strong></p>
                    <select class="form-control" id="select_legend_1">
                      <option value="">(None)</option>
                    </select> -->

                    <!------------------------------------------------------------------------------>
                    <!-- Label Shape -->
                    <!-- <p class="mb-0 mt-2"><strong>Label Shape:</strong></p>
                    <select class="form-control" id="select_legend_2">
                      <option value="">(None)</option>
                    </select> -->


                    <!------------------------------------------------------------------------------>
                    <!-- Display Option -->
                    <p class="mb-0 mt-2">
                      <strong>Display Option:</strong>
                      Contributions &nbsp;
                    </p>
                    <label>
                      <input type="radio" class="btn_display_option" name="display_option" id="checkbox_display_option_10" checked>
                      Top 10
                    </label>&nbsp;
                    <label>
                      <input type="radio" class="btn_display_option" name="display_option" id="checkbox_display_option_20">
                      Top 20
                    </label>&nbsp;
                    <label>
                      <input type="radio" class="btn_display_option" name="display_option" id="checkbox_display_option_50">
                      Top 50
                    </label>


                    <!------------------------------------------------------------------------------>
                    <!-- Label Size -->
                    <p class="mb-0 mt-2">
                      <strong>Label Size:</strong>
                      <span class="range_value">10</span> px &nbsp;
                      <label class="pull-right">
                        <input type="checkbox" id="checkbox_show_labels" checked> <strong>Show Labels</strong>
                      </label>
                    </p>
                    <div class="example__range pt-1">
                      <input type='range' class="input_range mt-2" min="5" max="20" step="1" value="10"
                        id="input_label_size">
                    </div>






                    <!------------------------------------------------------------------------------>
                    <!-- Graph Width -->
                    <p class="mb-0 mt-2"><strong>Graph Size:</strong>
                    <span id="graph_width">640</span> *
                    <span id="graph_height">480</span>
                    px</p>
                    <div class="example__range pt-1">
                      <input type='range' class="input_range mt-2" min="200" max="1200" step="1" value="640"
                        id="input_graph_size">
                    </div>




                  </div>
                </div>
              </div>

              <div id="div_chart">
                <i class="fas fa-spinner fa-pulse"></i> Loading chart file...
              </div>

            </div>


          </div>


	  <?php // Page Footer ?>
      </div>
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
    </div>
  </div>
  <?php // Page Footer ?>











<script>


$(document).ready(function() {

  $('#btn_index_r_variables_plot').addClass('active');

  refresh_chart();

  $('.example__range').on('mousemove', function() {
    var vm = $(this).find('.input_range');
    vm.parent()
      .prev()
      .find('.range_value')
      .html(vm.val());
  });


  $('#input_graph_size').on('mousemove', function() {
    var width = $(this).val();
    var height = parseInt(parseInt(width) / 1.33333333);
    $('#graph_width').html(width);
    $('#graph_height').html(height);
  });



  $(document).on(
    'change',
    '#select_x, #select_y, #checkbox_show_labels, #input_label_size, #input_marker_size, #input_graph_size, #checkbox_display_option_10, #checkbox_display_option_20, #checkbox_display_option_50',
    function() {
    refresh_chart();
  });


});


function refresh_chart() {
  var x            = $('#select_x').val();
  var y            = $('#select_y').val();
  var legend_1     = $('#select_legend_1').val();
  var legend_2     = $('#select_legend_2').val();
  var label_size   = $('#input_label_size').val();
  var marker_size  = $('#input_marker_size').val();
  var graph_width  = $('#input_graph_size').val();
  var graph_height = parseInt(parseInt(graph_width) / 1.33333333);
  var chart_type   = 'variables_plot';
  var show_labels  = 'false';
  if ($('#checkbox_show_labels').is(':checked')) show_labels = 'true';

  var display_option = '10';
  if ($('#checkbox_display_option_20').is(':checked')) display_option = '20';
  if ($('#checkbox_display_option_50').is(':checked')) display_option = '50';

  $.ajax({
    type: 'POST',
    url: 'exe.php?action=generate_scatter_plot',
    data: {
      x: x,
      y: y,
      legend_1: legend_1,
      legend_2: legend_2,
      label_size: label_size,
      marker_size: marker_size,
      show_labels: show_labels,
      graph_width: graph_width,
      graph_height: graph_height,
      chart_type: chart_type,
      display_option: display_option,
      time_stamp: '<?php echo $TIME_STAMP; ?>'
    },
    success: function(response) {

      if (response.type && response.type == 'Error') {
        bootbox.alert(response.detail);
      }

      else {
        var time = response.chart_time;
		var content = '';
		content += '<p><a href="files/<?php echo $BXAF_CONFIG['BXAF_USER_CONTACT_ID']; ?>/chart_'+time+'.html" target="_blank">View in Full Screen</a></p>';
		content += '<iframe';
        content += ' src="files/<?php echo $BXAF_CONFIG['BXAF_USER_CONTACT_ID']; ?>/chart_'+time+'.html"';
        content += ' height="' + parseInt(parseInt(response.graph_height) * 1.3) + '"';
        content += ' width="' + parseInt(parseInt(response.graph_width) * 1.3) + '"';
        content += ' style="border: none !important;">';
        content += '</iframe>';
        $('#div_chart').html(content);
      }

    }
  });
}


</script>




</body>
</html>
