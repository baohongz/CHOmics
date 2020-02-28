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
          <strong>Select Dimensions</strong>
          <select class="form-control mb-3" style="max-width:200px;" id="select_dim">
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

          <!--------------------------------------------------------------------------------------------->
          <!-- Data Table -->
          <!--------------------------------------------------------------------------------------------->
          <div id="div_variables_datatable">
            <table class="table table-bordered table-striped table-hover w-100" id="variables_datatable">
              <thead>
                <tr class="table-info">
                  <th>Variable</th>
                  <th>Contrib</th>
                  <th>Coord</th>
                  <th>Cos2</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>

          </div>


          <hr />
          <h4>Upload Attributes File:</h4>
          <form id="form_upload_attributes_file">
            <input type="file" name="file"
              id="input_upload_attributes_file"
              onchange="$(this).parent().find('button').show()">
            <label>
              <input type="radio" name="format" value="csv" checked>
              csv
            </label> &nbsp;
            <label>
              <input type="radio" name="format" value="txt">
              txt / tsv
            </label> &nbsp;
            <input type='hidden' name='time_stamp' value='<?php echo $TIME_STAMP; ?>' />
            <button class="btn btn-sm btn-outline-primary hidden"
              id="btn_submit_upload_attributes_file">
              <i class="fas fa-upload"></i> Upload
            </button>
          </form>

          <div id="container_attributes_file" class="w-100">
            <table class="table table-bordered table-striped table-sm" id="table_attributes">
              <thead><tr></tr></thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="w-100" style="height:100px;"></div>



          <div id="debug"></div>

        </div>





      </div>
    <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
    </div>
  </div>











<script>


$(document).ready(function() {

  $('#btn_index_r_individuals_table').addClass('active');
  onChangeDimHandler();

  //----------------------------------------------------------------------------------
  // Get Variables Table Data
  //----------------------------------------------------------------------------------
  $(document).on('change', '#select_dim', () => onChangeDimHandler());


    // Upload File
    var options = {
        url: 'exe_r.php?action=upload_file',
        type: 'post',
        beforeSubmit: function(formData, jqForm, options) {
            $('#btn_submit_upload_attributes_file')
                .attr('disabled', '')
                .children(':first')
                .removeClass('fa-upload')
                .addClass('fa-spin fa-spinner');

            return true;
        },
        success: function(response){
            $('#btn_submit_upload_attributes_file')
                .removeAttr('disabled')
                .children(':first')
                .removeClass('fa-spin fa-spinner')
                .addClass('fa-upload');

            if (response.type == 'Error') {
                bootbox.alert(response.Detail);
            }
            else {
                bootbox.alert("Your atttributes file has been uploaded and the old attributes file has been replaced.", function(){
                    window.location = 'index_r_individuals_plot.php?id=<?php echo $TIME_STAMP; ?>';
                });

            }

			return true;
		}
    };
    $('#form_upload_attributes_file').ajaxForm(options);



});



function onChangeDimHandler() {
  var select_dim = $('#select_dim').val();
  $.ajax({
    type: 'POST',
    url: 'exe_r.php?action=get_individuals_data_table',
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
          row_content += '</tr>';
          return row_content;
        })
        .join('');

      table_content += '  </tbody>';
      table_content += '</table>';

      $('#div_variables_datatable').html(table_content);
      $('#variables_datatable').DataTable({'pageLength': 100, 'lengthMenu': [[10, 100, 500, 1000], [10, 100, 500, 1000]]});

    }
  });
};

</script>




</body>
</html>
