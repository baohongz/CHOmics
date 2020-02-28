<?php
include_once('config.php');

$dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];

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
}


// Read Colnames
$file = fopen($dir . '/PCA_var.coord.csv', "r") or die('No file.');
$header = array_slice(fgetcsv($file), 1);
fclose($file);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

    <script src="../library/plotly.min.js"></script>

    <link   href='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.css.php' rel='stylesheet' type='text/css'>
	<script src='/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/datatables/datatables.min.js.php'></script>

</head>
<body>

  <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_MENU'])) include_once($BXAF_CONFIG['BXAF_PAGE_MENU']); ?>
  <div id="bxaf_page_wrapper" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_WRAPPER']; ?>">
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_LEFT'])) include_once($BXAF_CONFIG['BXAF_PAGE_LEFT']); ?>
    <div id="bxaf_page_right" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT']; ?>">
      <div id="bxaf_page_right_content" class="<?php echo $BXAF_CONFIG['BXAF_PAGE_CSS_RIGHT_CONTENT']; ?>">


          <div class="container-fluid">

      			<h1 class="">
      				FactoMineR PCA Analysis
      			</h1>
            <hr />
            <?php include_once('component_header.php'); ?>

            <?php include_once('component_r_header_2.php'); ?>

            <br />
            <strong>Select Dimensions: </strong>
            <select class="custom-select mb-3" style="max-width:200px;" id="select_dim">
              <?php
                $index = 1;
                foreach ($header as $colname) {
                  echo '<option value="' . $colname . '">';
                  echo 'PC' . $index;
                  echo '</option>';
                  $index++;
                }
              ?>
            </select>


            <div id="div_variables_datatable" class="my-5">

              <h4>Active Variables</h4>
              <div id="container_variables_datatable"></div>

              <?php if (file_exists($dir . '/' . 'PCA_quanti.sup.coord.csv')) { ?>
              <h4>Supplementary Variables</h4>

              <table class="table table-sm table-striped table-bordered w-100" id="supplementary_variables_datatable">
                <thead>
                  <tr class="table-info">
                    <th>Variable</th>
                    <th>Coord</th>
                    <th>Cor</th>
                    <th>Cos2</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
              <?php } if (file_exists($dir . '/' . 'PCA_quali.sup.coord.csv')) { ?>

              <h4>Qualitative Supplementary Variables</h4>
              <table class="table table-sm table-striped table-bordered w-100" id="qualitative_variables_datatable">
                <thead>
                  <tr class="table-info">
                    <th>Variable</th>
                    <th>Coord</th>
                    <th>Cos2</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
              <?php } ?>


            </div>



            <div id="debug"></div>

          </div>


      </div>
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
    </div>
  </div>











<script>


$(document).ready(function() {

  $('#btn_index_r_variables_table').addClass('active');
  onChangeDimHandler();

  //----------------------------------------------------------------------------------
  // Get Variables Table Data
  //----------------------------------------------------------------------------------
  $(document).on('change', '#select_dim', () => onChangeDimHandler());



});


function onChangeDimHandler() {
  var select_dim = $('#select_dim').val();
  $.ajax({
    type: 'POST',
    url: 'exe_r.php?action=get_variables_data_table',
    data: {
      dim: select_dim,
      time_stamp: '<?php echo $TIME_STAMP; ?>'
    },
    success: function(response) {
      //console.log(response);


      var table_content = '';
      table_content += '<table class="table table-bordered table-striped table-hover w-100" id="variables_datatable">';
      table_content += '  <thead>';
      table_content += '    <tr class="table-info">';
      table_content += '      <th>Variable</th>';
      table_content += '      <th>Contrib</th>';
      table_content += '      <th>Coord</th>';
      table_content += '      <th>Cor</th>';
      table_content += '      <th>Cos2</th>';
      table_content += '    </tr>';
      table_content += '  </thead>';
      table_content += '  <tbody>';
      table_content += response
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
      table_content += '  </tbody>';
      table_content += ' </table>';
      $('#container_variables_datatable').html(table_content);

      $('#variables_datatable').DataTable();

      if (response.data_supplementary != null) {
        var table_content_supplementary = response
          .data_supplementary
          .map(data => {
            var row_content = '';
            row_content += '<tr>';
            row_content += '  <td>' + data[0] + '</td>';
            row_content += '  <td>' + data[1] + '</td>';
            row_content += '  <td>' + data[2] + '</td>';
            row_content += '  <td>' + data[3] + '</td>';
            row_content += '</tr>';
            return row_content;
          })
          .join('');
        $('#supplementary_variables_datatable')
          .find('tbody')
            .html(table_content_supplementary)
          .parent()
            .DataTable();
      }
      if (response.data_quantitative != null) {
        var table_content_quantitative = response
          .data_quantitative
          .map(data => {
            var row_content = '';
            row_content += '<tr>';
            row_content += '  <td>' + data[0] + '</td>';
            row_content += '  <td>' + data[1] + '</td>';
            row_content += '  <td>' + data[2] + '</td>';
            row_content += '</tr>';
            return row_content;
          })
          .join('');
        $('#qualitative_variables_datatable')
          .find('tbody')
            .html(table_content_quantitative)
          .parent()
            .DataTable();
      }



    }
  });

}

</script>

</body>
</html>