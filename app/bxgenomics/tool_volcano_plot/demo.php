<?php
include_once("../config/config.php");

if (isset($_GET['action']) && $_GET['action'] == 'fetch_data') {
  // echo 'aaa';
  $OUTPUT = array(

    //---------------------------------------------
    // Chart Settings
    //---------------------------------------------
    'chart' => array('type' => 'scatter', 'zoomType' => 'xy'),
    'title' => array('text' => 'Volcano Chart'),
    'plotOptions'          => array(
      'scatter'            => array(
        'allowPointSelect' => true,
        'marker'           => array(
          'radius'         => 2,
          'states'         => array('hover'=> array('enabled'=>true, 'lineColor'=>'#333333'))
        ),
        'states'           => array('hover'=>array('marker'=>array('enabled'=>true))),
        'turboThreshold'   => 50000,
      ),
      'series'   => array(
        'cursor' => 'pointer',
        'point'  => array('events' => array()),
      ),
    ),
    'tooltip'        => array(
      'useHTML'      => true,
      'headerFormat' => '<span style="font-size:12px; color:green">{series.name}<br>',
      'pointFormat'  => "<b>name: </b>{point.alt_name}<br><b>id: </b><a href='http://useast.ensembl.org/Homo_sapiens/Gene/Summary?db=core;g={point.name}' target=_blank>{point.name}</a><br><b>fold change: </b>{point.x}<br><b>significance: </b>{point.y}<br>Click to view detail",
    ),


    //---------------------------------------------
    // Axis
    //---------------------------------------------
    'xAxis' => array(
      'title'         => array('enabled' => true, 'text' => 'log2(Fold Change)'),
      'startOnTick'   => true,
      'endOnTick'     => true,
      'showLastLabel' => true,
      'gridLineWidth' => 1,
      'min'           => -2.7277,
      'max'           => 8.871,
    ),
    'yAxis' => array(
      'title'         => array('enabled' => true, 'text' => '-log10(FDR)'),
      'startOnTick'   => true,
      'endOnTick'     => true,
      'showLastLabel' => true,
      'gridLineWidth' => 1,
      'min'           => 0,
      'max'           => 22.88173,
    ),


    //---------------------------------------------
    // Chart Data
    //---------------------------------------------
    'series'     => array(

      // Up-Regulated Data
      array(
        'name'       => 'up-regulated',
        'color'      => '#FF0000',
        'dataLabels' => array(
          'enabled' => true,
          'x'       => 35,
          'y'       => 5,
          'style'   => array('color' => 'black'),
        ),
        'data'    => array(
          array(
            'x'        => 4.2264,
            'y'        => 7.16033,
            'name'     => 'ISG15',
            'alt_name' => 'ISG15'
          ),
          array(
            'x'        => 1.6629,
            'y'        => 2.21975,
            'name'     => 'SLC25A34',
            'alt_name' => 'SLC25A34'
          ),
          array(
            'x'        => 2.5818,
            'y'        => 4.25034,
            'name'     => 'IFI44',
            'alt_name' => 'IFI44'
          ),
        )
      ),

      // Down-Regulated Data
      array(
        'name'       => 'down-regulated',
        'color'      => '#009966',
        'dataLabels' => array(
          'enabled' => true,
          'x'       => -35,
          'y'       => 5,
          // 'formatter' => 'aaa',
          'style'   => array('color' => 'black'),
        ),
        'data'    => array(
          array(
            'x'        => -1.6942,
            'y'        => 1.31884,
            'name'     => 'DVL1',
            'alt_name' => 'DVL1'
          ),
          array(
            'x'        => -2.3262,
            'y'        => 5.92848,
            'name'     => 'DFFA',
            'alt_name' => 'DFFA'
          ),
          array(
            'x'        => -1.5935,
            'y'        => -1.5935,
            'name'     => 'NPHP4',
            'alt_name' => 'NPHP4'
          ),
        )
      ),

      // Unregulated Data
      array(
        'name'       => 'unregulated',
        'color'      => '#AEB6BF',
        'data'    => array(
          array(
            'x'        => -0.0409,
            'y'        => 0,
            'name'     => 'DDX11L1',
            'alt_name' => 'DDX11L1'
          ),
          array(
            'x'        => 0.3919,
            'y'        => 0,
            'name'     => 'LOC643837',
            'alt_name' => 'LOC643837'
          ),
          array(
            'x'        => -0.18139,
            'y'        => 0.1,
            'name'     => 'LOC100288069',
            'alt_name' => 'LOC100288069'
          ),
        )
      ),

      //-------------------------------------------------------------------------------
      // Threshold
      //-------------------------------------------------------------------------------
      array(
        'name'       => 'downfold threshold',
        'color'      => '#000000',
        'type'       => 'line',
        'dashStyle'  => 'Dash',
        'marker'     => array('enabled' => false),
        'data'       => array(array(-1, 0), array(-1, 45.763)),
      ),
      array(
        'name'       => 'upfold threshold',
        'color'      => '#000000',
        'type'       => 'line',
        'dashStyle'  => 'Dash',
        'marker'     => array('enabled' => false),
        'data'       => array(array(1, 0), array(1, 45.763)),
      ),
      array(
        'name'       => 'significance threshold',
        'color'      => '#000000',
        'type'       => 'line',
        'dashStyle'  => 'DashDot',
        'marker'     => array('enabled' => false),
        'data'       => array(array(-5.455, 1.301), array(17.742, 1.301)),
      ),


    ),



  );

  header('Content-Type: application/json');
  echo json_encode($OUTPUT);

  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
  <script src="../library/highcharts.js.php"></script>
  <!-- Highlight.js -->
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.11.0/styles/default.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.11.0/styles/atelier-sulphurpool-light.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.11.0/highlight.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.11.0/languages/php.min.js"></script>
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


    <div class="container-fluid">
      <h2 class="mt-3">
        Demo for Volcano Plot &nbsp;
        <a href="demo.php" style="font-size:16px;" download>
          <i class="fas fa-angle-double-right" aria-hidden="true"></i>
          Download this page
        </a>
      </h2>

      <div id="chart_div" style="width: 100%; height: 500px;"></div>


      <div class="row w-100">

        <div class="col-md-6">
          <h4>HTML</h4>
          <pre style="width:100%; border:1px solid blue;"><code class="php">
  <?php echo htmlentities('<div id="chart_div" style="width: 100%; height: 500px;">') . '<br />';?>
          &nbsp;</code></pre>

          <h4>FrontEnd JS Code</h4>
          <pre style="height: 287px; width:100%; border:1px solid blue;"><code class="php" style="height:285px;">
$(document).ready(function() {

  $.ajax({
    type: 'POST',
    url: 'demo.php?action=fetch_data',
    success: function(response) {
      $('#chart_div').highcharts(response);
    }
  });

});
          </code></pre>
        </div>



        <div class="col-md-6">

          <h4>PHP Output From Backend</h4>

          <pre style="overflow-y: scroll; height:400px; width:100%; border:1px solid blue;"><code class="php" style="">
$OUTPUT = array(

  //---------------------------------------------
  // Chart Settings
  //---------------------------------------------
  'chart' => array('type' => 'scatter', 'zoomType' => 'xy'),
  'title' => array('text' => 'Volcano Chart'),
  'plotOptions'          => array(
    'scatter'            => array(
      'allowPointSelect' => true,
      'marker'           => array(
        'radius'         => 2,
        'states'         => array('hover'=> array('enabled'=>true, 'lineColor'=>'#333333'))
      ),
      'states'           => array('hover'=>array('marker'=>array('enabled'=>true))),
      'turboThreshold'   => 50000,
    ),
    'series'   => array(
      'cursor' => 'pointer',
      'point'  => array('events' => array()),
    ),
  ),
  'tooltip'        => array(
    'useHTML'      => true,
    'headerFormat' => '<?php echo htmlentities('<span style="font-size:12px; color:green">{series.name}<br>'); ?>',
    'pointFormat'  => "<?php echo htmlentities("<b>name: </b>{point.alt_name}<br><b>id: </b><a href='http://useast.ensembl.org/Homo_sapiens/Gene/Summary?db=core;g={point.name}' target=_blank>{point.name}</a><br><b>fold change: </b>{point.x}<br><b>significance: </b>{point.y}<br>Click to view detail");?>",
  ),


  //---------------------------------------------
  // Axis
  //---------------------------------------------
  'xAxis' => array(
    'title'         => array('enabled' => true, 'text' => 'log2(Fold Change)'),
    'startOnTick'   => true,
    'endOnTick'     => true,
    'showLastLabel' => true,
    'gridLineWidth' => 1,
    'min'           => -2.7277,
    'max'           => 8.871,
  ),
  'yAxis' => array(
    'title'         => array('enabled' => true, 'text' => '-log10(FDR)'),
    'startOnTick'   => true,
    'endOnTick'     => true,
    'showLastLabel' => true,
    'gridLineWidth' => 1,
    'min'           => 0,
    'max'           => 22.88173,
  ),


  //---------------------------------------------
  // Chart Data
  //---------------------------------------------
  'series'     => array(

    // Up-Regulated Data
    array(
      'name'       => 'up-regulated',
      'color'      => '#FF0000',
      'dataLabels' => array(
        'enabled' => true,
        'x'       => 35,
        'y'       => 5,
        'style'   => array('color' => 'black'),
      ),
      'data'    => array(
        array(
          'x'        => 4.2264,
          'y'        => 7.16033,
          'name'     => 'ISG15',
          'alt_name' => 'ISG15'
        ),
        array(
          'x'        => 1.6629,
          'y'        => 2.21975,
          'name'     => 'SLC25A34',
          'alt_name' => 'SLC25A34'
        ),
        array(
          'x'        => 2.5818,
          'y'        => 4.25034,
          'name'     => 'IFI44',
          'alt_name' => 'IFI44'
        ),
      )
    ),

    // Down-Regulated Data
    array(
      'name'       => 'down-regulated',
      'color'      => '#009966',
      'dataLabels' => array(
        'enabled' => true,
        'x'       => -35,
        'y'       => 5,
        // 'formatter' => 'aaa',
        'style'   => array('color' => 'black'),
      ),
      'data'    => array(
        array(
          'x'        => -1.6942,
          'y'        => 1.31884,
          'name'     => 'DVL1',
          'alt_name' => 'DVL1'
        ),
        array(
          'x'        => -2.3262,
          'y'        => 5.92848,
          'name'     => 'DFFA',
          'alt_name' => 'DFFA'
        ),
        array(
          'x'        => -1.5935,
          'y'        => -1.5935,
          'name'     => 'NPHP4',
          'alt_name' => 'NPHP4'
        ),
      )
    ),

    // Unregulated Data
    array(
      'name'       => 'unregulated',
      'color'      => '#AEB6BF',
      'data'    => array(
        array(
          'x'        => -0.0409,
          'y'        => 0,
          'name'     => 'DDX11L1',
          'alt_name' => 'DDX11L1'
        ),
        array(
          'x'        => 0.3919,
          'y'        => 0,
          'name'     => 'LOC643837',
          'alt_name' => 'LOC643837'
        ),
        array(
          'x'        => -0.18139,
          'y'        => 0.1,
          'name'     => 'LOC100288069',
          'alt_name' => 'LOC100288069'
        ),
      )
    ),

    //-------------------------------------------------------------------------------
    // Threshold
    //-------------------------------------------------------------------------------
    array(
      'name'       => 'downfold threshold',
      'color'      => '#000000',
      'type'       => 'line',
      'dashStyle'  => 'Dash',
      'marker'     => array('enabled' => false),
      'data'       => array(array(-1, 0), array(-1, 45.763)),
    ),
    array(
      'name'       => 'upfold threshold',
      'color'      => '#000000',
      'type'       => 'line',
      'dashStyle'  => 'Dash',
      'marker'     => array('enabled' => false),
      'data'       => array(array(1, 0), array(1, 45.763)),
    ),
    array(
      'name'       => 'significance threshold',
      'color'      => '#000000',
      'type'       => 'line',
      'dashStyle'  => 'DashDot',
      'marker'     => array('enabled' => false),
      'data'       => array(array(-5.455, 1.301), array(17.742, 1.301)),
    ),
  ),
);

header('Content-Type: application/json');
echo json_encode($OUTPUT);
            </code></pre>
          </div>

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


</body>


<script>
hljs.initHighlightingOnLoad();
$(document).ready(function() {

  $.ajax({
    type: 'POST',
    url: 'demo.php?action=fetch_data',
    success: function(response) {
      // Plotly.newPlot('chart_div', response.data, response.layout);
      $('#chart_div').highcharts(response);
    }
  });


});
</script>


</html>
