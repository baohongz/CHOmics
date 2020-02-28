<?php
include_once("../config/config.php");


if (isset($_GET['action']) && $_GET['action'] == 'fetch_data') {
  // echo 'aaa';
  $OUTPUT = array(

    //---------------------------------------------
    // Data for Plot
    //---------------------------------------------
    'data' => array(


      // Data for Frist Color Label
      array(
        'x' => array(-0.1117, 0.1705, -0.1083, 0.0419, 0.0519, -0.3256, 0.4120, -0.2883, -0.1770, 0.1367),
        'y' => array(
          'comparisonID_01',
          'comparisonID_01',
          'comparisonID_01',
          'comparisonID_02',
          'comparisonID_02',
          'comparisonID_03',
          'comparisonID_03',
          'comparisonID_03',
          'comparisonID_04',
          'comparisonID_04'),
        'name' => 'peripheral blood mononuclear cell (PBMC)',
        'hoverinfo' => 'text',
        'text' => array('text1','text2','text3','text4','text5','text6','text7','text8','text9','text10','text2'),
        'mode' => 'markers',
        'marker' => array(
          'size' => array(1400, 1500, 700, 800, 900, 1100, 1000, 2200, 5000, 5000),
          'sizeref' => 7,
          'sizemode' => 'area'
        )
      ),

      // Data for Second Color Label
      array(
        'x' => array(0.0940, -0.2144, -0.2071, 0.2458, 0.0682, -0.3945, -0.1296, 0.0187, 0.1759, -0.2972),
        'y' => array(
          'comparisonID_01',
          'comparisonID_02',
          'comparisonID_02',
          'comparisonID_02',
          'comparisonID_03',
          'comparisonID_03',
          'comparisonID_03',
          'comparisonID_03',
          'comparisonID_03',
          'comparisonID_04'),
        'name' => 'monocyte',
        'hoverinfo' => 'text',
        'text' => array('text1','text2','text3','text4','text5','text6','text7','text8','text9','text10'),
        'mode' => 'markers',
        'marker' => array(
          'size' => array(3400, 2500, 1400, 900, 1900, 1200, 1600, 1200, 5100, 5500),
          'sizeref' => 7,
          'sizemode' => 'area'
        )
      ),
    ),

    //---------------------------------------------
    // Chart Layout
    //---------------------------------------------
    'layout' => array(
      'margin'     => array('l' => 300),
      'title'      => 'Bubble Chart for WASH7P<br>Colored by Case_SampleSource',
      'showlegend' => true,
      'height'     => 500,
      'hovermode'  => 'closest',
      'xaxis'      => array('title' => 'Log 2 Fold Change'),
      'yaxis'      => array(
        'categoryorder' => 'category ascending',
        'range'         => array(-1, 4)
      )
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
  <script src="../library/plotly.min.js"></script>
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
        Demo for Bubble Plot &nbsp;
        <a href="demo.php" style="font-size:16px;" download>
          <i class="fas fa-angle-double-right" aria-hidden="true"></i>
          Download this page
        </a>
      </h2>
      <hr />
      <div id="chart_div" style="width: 100%; height: 500px;">


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
      Plotly.newPlot('chart_div', response.data, response.layout);
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
  // Data for Plot
  //---------------------------------------------
  'data' => array(

    // Data for Frist Color Label
    array(
      'x' => array(-0.1117, 0.1705, -0.1083, 0.0419, 0.0519, -0.3256, 0.4120, -0.2883, -0.1770, 0.1367),
      'y' => array(
        'comparisonID_01',
        'comparisonID_01',
        'comparisonID_01',
        'comparisonID_02',
        'comparisonID_02',
        'comparisonID_03',
        'comparisonID_03',
        'comparisonID_03',
        'comparisonID_04',
        'comparisonID_04'),
      'name'      => 'peripheral blood mononuclear cell (PBMC)',
      'hoverinfo' => 'text',
      'text'      => array('text1','text2','text3','text4','text5','text6','text7','text8','text9','text10','text2'),
      'mode'      => 'markers',
      'marker'    => array(
        'size'      => array(1400, 1500, 700, 800, 900, 1100, 1000, 2200, 5000, 5000),
        'sizeref'   => 7,
        'sizemode'  => 'area'
      )
    ),

    // Data for Second Color Label
    array(
      'x' => array(0.0940, -0.2144, -0.2071, 0.2458, 0.0682, -0.3945, -0.1296, 0.0187, 0.1759, -0.2972),
      'y' => array(
        'comparisonID_01',
        'comparisonID_02',
        'comparisonID_02',
        'comparisonID_02',
        'comparisonID_03',
        'comparisonID_03',
        'comparisonID_03',
        'comparisonID_03',
        'comparisonID_03',
        'comparisonID_04'),
      'name'      => 'monocyte',
      'hoverinfo' => 'text',
      'text'      => array('text1','text2','text3','text4','text5','text6','text7','text8','text9','text10'),
      'mode'      => 'markers',
      'marker'    => array(
        'size'      => array(3400, 2500, 1400, 900, 1900, 1200, 1600, 1200, 5100, 5500),
        'sizeref'   => 7,
        'sizemode'  => 'area'
      )
    ),
  ),

  //---------------------------------------------
  // Chart Layout
  //---------------------------------------------
  'layout' => array(
    'margin'     => array('l' => 300),
    'title'      => 'Bubble Chart for WASH7P Colored by Case_SampleSource',
    'showlegend' => true,
    'height'     => 500,
    'hovermode'  => 'closest',
    'xaxis'      => array('title' => 'Log 2 Fold Change'),
    'yaxis'      => array(
      'categoryorder' => 'category ascending',
      'range'         => array(-1, 4)
    )
  ),

);

// Return JSON from Backend
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
      Plotly.newPlot('chart_div', response.data, response.layout);
    }
  });

});
</script>


</html>
