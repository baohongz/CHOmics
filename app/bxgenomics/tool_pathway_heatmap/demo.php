<?php
include_once("../config/config.php");

if (isset($_GET['action']) && $_GET['action'] == 'fetch_data') {
  // echo 'aaa';
  $OUTPUT = array(

    //---------------------------------------------
    // Chart Data
    //---------------------------------------------
    'data' => array(
      array(
        'colorscale' => array(
          array(0,   'rgb(220,220,220)'),
          array(0.2, 'rgb(245,195,15)'),
          array(0.4, 'rgb(245,160,105)'),
          array(1,   'rgb(178,10,28)'),
        ),
        'text' => array(),
        'type' => 'heatmap',
        'x' => array(
          "GSE15823.GPL8300.test1",
          "GSE18965.GPL96.test1",
          "GSE19903.GPL6244.test1",
          "GSE2125.GPL570.test1",
          "GSE22528.GPL96.test1",
          "GSE27011.GPL6244.test1",
          "GSE27011.GPL6244.test2",
          "GSE27876.GPL6480.test1",
          "GSE27876.GPL6480.test3",
          "GSE31773.GPL570.test1",
        ),
        'y' => array(
          "Natural killer cell mediated cytotoxicity",
          "Dilated cardiomyopathy",
          "Calcium signaling pathway",
          "Steroid hormone biosynthesis",
          "Graft-versus-host disease",
        ),
        'z' => array(
          array(
            1.2881852556522,
            3.3712905986716,
            0.42543843211804,
            1.1329379675611,
            0.41972240809593,
            31.386374293826,
            2.1787428892663,
            0.66492452597113,
            0.17149935572307,
            0.0017539336239172,
          ),
          array(
            0.0017539336239172,
            1.2881852556522,
            3.3712905986716,
            0.42543843211804,
            1.1329379675611,
            0.41972240809593,
            31.386374293826,
            2.1787428892663,
            0.66492452597113,
            0.17149935572307,
          ),
          array(
            0.17149935572307,
            0.0017539336239172,
            1.2881852556522,
            3.3712905986716,
            0.42543843211804,
            1.1329379675611,
            0.41972240809593,
            31.386374293826,
            2.1787428892663,
            0.66492452597113,
          ),
          array(
            0.66492452597113,
            0.17149935572307,
            0.0017539336239172,
            1.2881852556522,
            3.3712905986716,
            0.42543843211804,
            1.1329379675611,
            0.41972240809593,
            31.386374293826,
            2.1787428892663,
          ),
          array(
            2.1787428892663,
            0.66492452597113,
            0.17149935572307,
            0.0017539336239172,
            1.2881852556522,
            3.3712905986716,
            0.42543843211804,
            1.1329379675611,
            0.41972240809593,
            31.386374293826,
          ),
        ),
        'zmax' => 10,
        'zmin' => 0,
      ),
    ),

    'layout'     => array(
      'height'    => 500,
      'hoverinfo' => 'text',
      'margin'    => array('l' => 300, 't' => 200),
      'width'     => 800,
      'xaxis'     => array(
        'autorange' => true,
        'range'     => array(-0.5, 9.5),
        'side'      => 'top',
        'tickangle' => -90,
        'type'      => 'category'
      ),
      'yaxis'     => array(
        'autorange' => true,
        'range' => array(-0.5, 9.5),
        'type' => 'category'
      ),
    )
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
        Demo for Pathway Heatmap &nbsp;
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
      console.log(response);
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
  // Chart Data
  //---------------------------------------------
  'data' => array(
    array(
      'colorscale' => array(
        array(0,   'rgb(220,220,220)'),
        array(0.2, 'rgb(245,195,15)'),
        array(0.4, 'rgb(245,160,105)'),
        array(1,   'rgb(178,10,28)'),
      ),
      'text' => array(),
      'type' => 'heatmap',
      'x' => array(
        "GSE15823.GPL8300.test1",
        "GSE18965.GPL96.test1",
        "GSE19903.GPL6244.test1",
        "GSE2125.GPL570.test1",
        "GSE22528.GPL96.test1",
        "GSE27011.GPL6244.test1",
        "GSE27011.GPL6244.test2",
        "GSE27876.GPL6480.test1",
        "GSE27876.GPL6480.test3",
        "GSE31773.GPL570.test1",
      ),
      'y' => array(
        "Natural killer cell mediated cytotoxicity",
        "Dilated cardiomyopathy",
        "Calcium signaling pathway",
        "Steroid hormone biosynthesis",
        "Graft-versus-host disease",
      ),
      'z' => array(
        array(
          1.2881852556522,
          3.3712905986716,
          0.42543843211804,
          1.1329379675611,
          0.41972240809593,
          31.386374293826,
          2.1787428892663,
          0.66492452597113,
          0.17149935572307,
          0.0017539336239172,
        ),
        array(
          0.0017539336239172,
          1.2881852556522,
          3.3712905986716,
          0.42543843211804,
          1.1329379675611,
          0.41972240809593,
          31.386374293826,
          2.1787428892663,
          0.66492452597113,
          0.17149935572307,
        ),
        array(
          0.17149935572307,
          0.0017539336239172,
          1.2881852556522,
          3.3712905986716,
          0.42543843211804,
          1.1329379675611,
          0.41972240809593,
          31.386374293826,
          2.1787428892663,
          0.66492452597113,
        ),
        array(
          0.66492452597113,
          0.17149935572307,
          0.0017539336239172,
          1.2881852556522,
          3.3712905986716,
          0.42543843211804,
          1.1329379675611,
          0.41972240809593,
          31.386374293826,
          2.1787428892663,
        ),
        array(
          2.1787428892663,
          0.66492452597113,
          0.17149935572307,
          0.0017539336239172,
          1.2881852556522,
          3.3712905986716,
          0.42543843211804,
          1.1329379675611,
          0.41972240809593,
          31.386374293826,
        ),
      ),
      'zmax' => 10,
      'zmin' => 0,
    ),
  ),

  'layout'     => array(
    'height'    => 500,
    'hoverinfo' => 'text',
    'margin'    => array('l' => 300, 't' => 200),
    'width'     => 800,
    'xaxis'     => array(
      'autorange' => true,
      'range' => array(-0.5, 9.5),
      'side' => 'top',
      'tickangle' => -90,
      'type' => 'category'
    ),
    'yaxis'     => array(
      'autorange' => true,
      'range' => array(-0.5, 9.5),
      'type' => 'category'
    ),
  )
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
      console.log(response);
      Plotly.newPlot('chart_div', response.data, response.layout);
      // $('#chart_div').highcharts(response);
    }
  });


});
</script>


</html>
