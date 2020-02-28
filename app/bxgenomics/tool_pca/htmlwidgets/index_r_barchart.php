<?php
include_once('config.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>
    <link href="../library/tether/dist/css/tether.min.css" rel="stylesheet">
    <link href="../library/wenk.min.css" rel="stylesheet">

    <script type="text/javascript" src="../library/tether/dist/js/tether.min.js"></script>
    <script type="text/javascript" src="../library/jquery.form.min.js"></script>
    <script type="text/javascript" src="../library/bootbox.min.js"></script>
    <script type="text/javascript" src="../library/plotly.min.js"></script>
    <script type="text/javascript" src="../library/config.js"></script>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

</style>

</head>
<body>

  <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
  <div id="bxaf_page_content" class="row no-gutters h-100">

    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>

    <!-- <div id="bxaf_page_right_container"> -->
      <div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">

        <div id="bxaf_page_right_content" class="w-100 p-2">


          <div class="container-fluid px-0 pt-3 w-100">

      			<h1 class="">
      				FactoMineR PCA Analysis
              &nbsp;
              <a href="index.php" class="font_normal">
                <i class="fas fa-angle-double-right"></i>
                Upload Files for PCA Analysis
              </a>
      			</h1>
            <hr />

            <!--------------------------------------------------------------------------------------------->
            <!-- BarChart -->
            <!--------------------------------------------------------------------------------------------->
            <div id="div_barchart"></div>


            <!--------------------------------------------------------------------------------------------->
            <!-- BarChart -->
            <!--------------------------------------------------------------------------------------------->
            <div id="div_variables_datatable">
              <table class="table table-bordered table-striped table-hover w-100" id="variables_datatable">
                <thead>
                  <tr class="table-info">
                    <th>Variable</th>
                    <th>Contrib</th>
                    <th>Coord</th>
                    <th>Cor</th>
                    <th>Cos2</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>


            <!--------------------------------------------------------------------------------------------->
            <!-- Variables Plot -->
            <!--------------------------------------------------------------------------------------------->
            <div id="div_variables_plot">




            <div id="debug"></div>

          </div>



        </div>
  		  <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
  		</div>
    <!-- </div> -->

	</div>











<script>


$(document).ready(function() {

  //----------------------------------------------------------------------------------
  // Get BarChart Info
  //----------------------------------------------------------------------------------
  $.ajax({
    type: 'POST',
    url: 'exe_r.php?action=get_barchart',
    success: function(response) {
      //console.log(response);
      var type = response.type;
      if (type == 'Error') {
        bootbox.alert(response.detail);
      }
      else if (type == 'Success') {
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
          width: 500,
          height: 400,
          xaxis: { title: 'Dimensions' },
          yaxis: { title: 'Percentage of Variance' },
        };
        Plotly.newPlot('div_barchart', data, layout);
      }
    }
  });




  //----------------------------------------------------------------------------------
  // Get Variables Table Data
  //----------------------------------------------------------------------------------
  $.ajax({
    type: 'POST',
    url: 'exe_r.php?action=get_variables_data_table',
    success: function(response) {
      // console.log(response);

      var table_content = response
        .data
        .map(data => {
          var row_content = '';
          row_content += '<tr>';
          row_content += '  <td>' + data[0] + '</td>';
          row_content += '  <td>' + data[1] + '</td>';
          row_content += '  <td>' + data[2] + '</td>';
          row_content += '  <td>' + data[3] + '</td>';
          row_content += '  <td>' + data[4] + '</td>';
          row_content += '</tr>';
          return row_content;
        })
        .join('');

      $('#variables_datatable')
        .find('tbody')
          .append(table_content)
        .parent()
          .DataTable({'pageLength': 100, 'lengthMenu': [[10, 100, 500, 1000], [10, 100, 500, 1000]]});
    }
  });



  //----------------------------------------------------------------------------------
  // Get Variables Plot Info
  //----------------------------------------------------------------------------------
  $.ajax({
    type: 'POST',
    url: 'exe_r.php?action=get_variables_plot',
    success: function(response) {
      // console.log(response);
    }
  });


});

</script>




</body>
</html>