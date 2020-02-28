<?php
include_once('config.php');


function get_stat_scale_color2($value, $type='Log2FoldChange') {
  if ($type == 'Log2FoldChange') {
    if ($value >= 1) {
      return '#FF0000';
    } else if ($value > 0) {
      return '#FF8989';
    } else if ($value == 0) {
      return '#E5E5E5';
    } else if ($value > -1) {
      return '#7070FB';
    } else {
      return '#0000FF';
    }
  }
  else if ($type == 'AdjustedPValue') {
    if ($value > 0.05) {
      return '#9CA4B3';
    } else if ($value <= 0.01) {
      return '#015402';
    } else {
      return '#5AC72C';
    }
  }
  else if ($type == 'PValue') {
    if ($value >= 0.01) {
      return '#9CA4B3';
    } else {
      return '#5AC72C';
    }
  }
  return '';
}





$TIME = $_GET['time'];
$dir = "{$BXAF_CONFIG['USER_FILES']['TOOL_META_ANALYSIS']}{$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}/{$TIME}";
$url = str_replace($BXAF_CONFIG['BXAF_ROOT_DIR'], $BXAF_CONFIG['BXAF_ROOT_URL'], $dir);

if ($TIME == 0 || !is_dir($dir) || !file_exists("{$dir}/Meta_output.csv")) {
    header("Location: index.php");
    exit();
}



//----------------------------------------------------------------------------------------
// Parameter Settings
//----------------------------------------------------------------------------------------

$filter = array(
    'datapoints'    => array('value'=>isset($_GET['datapoints']) ? $_GET['datapoints'] : 0,         'name'=>'N.data.points >='),
    'upper'         => array('value'=>isset($_GET['upper']) ? $_GET['upper'] : 0.01,                'name'=>'Percentage Up-Regulated >='),
    'downper'       => array('value'=>isset($_GET['downper']) ? $_GET['downper'] : 0.01,            'name'=>'Percentage Down-Regulated >='),
    'maxp_cutoff'   => array('value'=>isset($_GET['maxp_cutoff']) ? $_GET['maxp_cutoff'] : 1,       'name'=>'Combined_Pval_MaxP Cutoff <='),
    'rp_pval'       => array('value'=>isset($_GET['rp_pval']) ? $_GET['rp_pval'] : 0.01,               'name'=>'RP_Pval <='),
    'fisher_cutoff' => array('value'=>isset($_GET['fisher_cutoff']) ? $_GET['fisher_cutoff'] : 0,   'name'=>'Pval Fisher Cutoff <='),
);

//----------------------------------------------------------------------------------------
// Data for Meta Analysis
//----------------------------------------------------------------------------------------

$length = 0;
$max_datapoints = 0;
$meta_data_headers = array();
$meta_data = array();

if (($handle = fopen($dir . '/Meta_output.csv', "r")) !== FALSE) {
    $meta_data_headers = fgetcsv($handle);
    if ( ! is_array($meta_data_headers) || count($meta_data_headers) <= 0) {
      echo 'Meta output file is empty.';
      exit();
    }
    $meta_data_headers_flip = array_flip($meta_data_headers);
    $length = count($meta_data_headers);

    while (($data = fgetcsv($handle)) !== FALSE) {
        if(count($data) == $length){

            if (trim($data[ $meta_data_headers_flip['N.data.points'] ]) == '') {
              $data[ $meta_data_headers_flip['N.data.points'] ] = 1;
            }
            if (trim($data[ $meta_data_headers_flip['Up.Per'] ]) == '') {
              $data[ $meta_data_headers_flip['Up.Per'] ] = 1;
            }

            if ($_GET['check_datapoints'] && intval($data[$meta_data_headers_flip['N.data.points']]) < $filter['datapoints']['value'] ||
                $_GET['check_upper'] && floatval($data[$meta_data_headers_flip['Up.Per']]) < $filter['upper']['value'] ||
                $_GET['check_downper'] && floatval($data[$meta_data_headers_flip['Down.Per']]) < $filter['downper']['value'] ||
                $_GET['check_fisher_cutoff'] && floatval($data[$meta_data_headers_flip['Combined_Pval_Fisher']]) > $filter['fisher_cutoff']['value'] ||
                $_GET['check_maxp_cutoff'] && floatval($data[$meta_data_headers_flip['Combined_Pval_maxP']]) > $filter['maxp_cutoff']['value'] ||
                $_GET['check_rp_pval'] && abs(floatval($data[$meta_data_headers_flip['RP_Pval']])) > $filter['rp_pval']['value']
            ){
                continue;
            }

            if ($data[ $meta_data_headers_flip['N.data.points'] ] > $max_datapoints) {
                $max_datapoints = $data[ $meta_data_headers_flip['N.data.points'] ];
            }

            $meta_data[] = $data;
        }
    }
    fclose($handle);
}
else {
    echo 'Can not open Meta output file.';
    exit();
}


if(! isset($_SESSION['META_RESULTS'][$TIME]) || ! is_array($_SESSION['META_RESULTS'][$TIME]) || count($_SESSION['META_RESULTS'][$TIME]) <= 0){

    $_SESSION['META_RESULTS'][$TIME] = array();

    foreach ($meta_data_headers as $i=>$colname) {
        if(in_array($i, array(2,3,4,5)) ){

        }
        else if(! in_array($colname, array('RP_logFC','RP_Pval','RP_FDR')) && preg_match("/(logFC|Pval|FDR)$/", $colname)){

        }
        else{
            $_SESSION['META_RESULTS'][$TIME][$i] = 1;
        }
    }
}


$gene_info = array();
$file = fopen("{$dir}/Gene_Info.csv","r") or die('file Gene_Info.csv is not found.');
while(($row = fgetcsv($file)) !== false){
    if(is_array($row) && count($row) > 0 && array_key_exists('0', $row) && is_numeric( $row[0] ) ){
        $gene_info[ $row[0] ] = $row;
    }
}
fclose($file);

$gene_name_ids = array();
foreach($gene_info as $id=>$info){
    $gene_name_ids[ $info[1] ] = $info[0];
}

$comparison_info = array();
$file = fopen("{$dir}/Comparison_Info.csv","r") or die('file Comparison_Info.csv is not found.');
while(($row = fgetcsv($file)) !== false){
    if(is_array($row) && count($row) > 0 && array_key_exists('0', $row) && is_numeric( $row[0] ) ) $comparison_info[ $row[0] ] = $row[1];
}
fclose($file);


// Number of Rows Displayed
$nrow = 3000;
if(isset($_GET['nrow']) && intval($_GET['nrow']) > 0) $nrow = intval($_GET['nrow']);
$meta_data_total_rows = count($meta_data);
$meta_data = array_slice($meta_data, 0, $nrow);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once($BXAF_CONFIG['BXAF_PAGE_HEADER']); ?>

    <script src="/<?php echo $BXAF_CONFIG['BXAF_SYSTEM_SUBDIR']; ?>library/jquery/jquery.form.min.js"></script>

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
            Meta Analysis Result
            <a class="ml-5" href="index.php" style="font-size:1rem;"> <i class="fas fa-angle-double-right"></i> Create New Meta Analysis </a>
        </h1>
        <hr />

        <input class="hidden" value="<?php echo implode(',', array_values($gene_name_ids) ); ?>" id="input_all_gene_ids">
        <input class="hidden" value="<?php echo implode(',', array_keys($comparison_info) ); ?>" id="input_all_comparison_ids">

        <div class="w-100 my-3">

            <button class="btn btn-primary" id="btn_bubble_plot"> <i class="fas fa-chart-pie"></i> View Bubble Plot </button>
            <button class="btn btn-success btn_save_session_list" gene_ids=""> <i class="fas fa-save"></i> Save Genes </button>

            <a href="../download.php?f=<?php echo bxaf_encrypt(str_replace($BXAF_CONFIG['BXAF_DIR'], '', "{$dir}/Meta_output.csv"), $BXAF_CONFIG['BXAF_KEY']); ?>" class="btn btn-warning"> <i class="fas fa-download"></i> Download Meta Data </a>

            <button type="button" class="btn btn-secondary" id="btn_pre_save_result" data-toggle="modal" data-target="#modal_confirm_save_result"> <i class="fas fa-save"></i> Save Results </button>

            <a class="ml-5" href="javascript:void(0);" onclick="$('#modal_view_options').modal('show');">
                <i class="fas fa-angle-double-right"></i> Filter Results
            </a>

        </div>

<?php
    if($meta_data_total_rows > 3000){
        echo '<div class="w-100 my-3 text-danger">Warning: only 3000 out of ' . $meta_data_total_rows . ' rows are shown. </div>';
    }
?>

        <div class="row w-100 my-5">
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12"><strong>Toggle columns: </strong></div>
            <?php
                foreach ($meta_data_headers as $i=>$colname) {
                    echo '<div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12"><input type="checkbox" ' . ( (array_key_exists($i, $_SESSION['META_RESULTS'][$TIME]) && $_SESSION['META_RESULTS'][$TIME][$i] == 1)  ? "checked" : "") . ' class="toggle-vis mx-2" value="' . ($i+1) . '">' . $colname . '</div>';
                }
            ?>
        </div>


        <div class="w-100 my-3 table-responsive">

              <table class="datatable table table-striped table-bordered w-100">
                <thead>
                  <tr>
                  <?php
                    echo "<th class='text-center'><input type='checkbox' class='bxaf_checkbox bxaf_checkbox_all' /></th>";

                    foreach ($meta_data_headers as $colname) {
                        echo '<th style="width:250px !important;">';
                        if (strpos($colname, '.') !== false) {
                            echo substr($colname, 0, 17) . '<br />' . substr($colname, 17);
                        } else {
                            echo $colname;
                        }
                        echo '</th>';
                    }
                  ?>
                  </tr>
                </thead>
                <tbody>
                  <?php

                    foreach ($meta_data as $row) {
                      echo '<tr>';

                      echo "<td class='text-center'><input type='checkbox' class='bxaf_checkbox bxaf_checkbox_one' rowid='" . $gene_name_ids[ $row[0] ] . "' /></td>";

                      foreach ($row as $key => $value) {

                        $colname = $meta_data_headers[$key];

                        echo '<td>';

                        if ($key == $meta_data_headers_flip['RankProd']) {
                          if ($value > 0.01) {
                            echo number_format($value, 4);
                          } else {
                            echo preg_replace('/(E[+-])(\d)$/', '${1}0$2', sprintf('%1.3E',$value));
                          }
                        }
                        else if ($key == $meta_data_headers_flip['GeneIndex'] || $key == $meta_data_headers_flip['N.data.points'] || $key == $meta_data_headers_flip['EntrezID']) {
                          echo intval($value) > 0 ? intval($value) : '';
                        }
                        else if ($key == $meta_data_headers_flip['Up.Per'] || $key == $meta_data_headers_flip['Down.Per']) {
                          echo sprintf("%.4f", $value);
                        }
                        else if ($key == $meta_data_headers_flip['GeneName'] || $key == $meta_data_headers_flip['Description']) {
                          echo trim($value);
                        }
                        else {
                          $color = '#000000';
                          if (preg_match("/logFC/i", $colname )) {
                            $color =  get_stat_scale_color2(floatval($value), 'Log2FoldChange');
                          }
                          else if (preg_match("/PVal/i", $colname)) {
                            $color =  get_stat_scale_color2(floatval($value), 'PValue');
                          }
                          else if (preg_match("/FDR/i", $colname)) {
                            $color =  get_stat_scale_color2(floatval($value), 'AdjustedPValue');
                          }
                          if($color != '#000000') echo "<span style='color:{$color}'>" . sprintf("%.4f", $value) . "</span>";
                          else echo $value;
                        }

                        echo '</td>';
                      }
                      echo '</tr>';
                    }
                  ?>
                </tbody>
              </table>

        </div>


    </div>




        </div>
        <?php if(file_exists($BXAF_CONFIG['BXAF_PAGE_FOOTER'])) include_once($BXAF_CONFIG['BXAF_PAGE_FOOTER']); ?>
    </div>
</div>





<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">

<div class="modal fade" id="modal_view_options">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Display Options</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">


          <div class="w-100 my-3">
            <input type="hidden" name="time" value="<?php echo $_GET['time']; ?>" />
            <?php

            foreach($filter as $key=>$setting){
                echo "<div class='w-100 my-3 form-inline'>";
                    echo "<input type='checkbox' class='form-check-input' name='check_$key' value='1' " . (isset($_GET['check_' . $key]) ? 'checked' : '') . "> ";
                    echo "<label class='form-check-label font-weight-bold mx-2'>" . $setting['name'] . "</label>";
                    echo "<input type='text' class='form-control' name='$key' value='" . $setting['value'] . "' /> ";
                echo "</div>";
            }

            ?>
            </div>

            <div class="w-100 my-3">
                <div class="form-check form-check-inline">
                    <label class="font-weight-bold">Number of records to show: </label>
                </div>

                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="nrow" id="" value="100" <?php if ($nrow == 100) echo ' checked'; ?>>
                    <label class="form-check-label" for="">100</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="nrow" id="" value="1000" <?php if ($nrow == 1000) echo ' checked'; ?>>
                    <label class="form-check-label" for="">1000</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="nrow" id="" value="0" <?php if ($nrow == 0 || $nrow >= 3000) echo ' checked'; ?>>
                    <label class="form-check-label" for="">3000 (limit)</label>
                </div>

          </div>

      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id=""> <i class="fas fa-filter"></i> Update Settings </button>
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

</form>






<div class="modal fade" id="modal_confirm_save_result">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">SAVE META ANALYSIS RESULT</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <strong class="text-muted">Title:</strong>
        <input class="form-control mb-2" id="save_result_title">
        <strong class="text-muted">Description:</strong>
        <textarea class="form-control" id="save_result_description"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btn_save_result">
          <i class="fas fa-save"></i> Save Result
        </button>
      </div>
    </div>
  </div>
</div>





<script>

$(document).ready(function() {

    var table = $('.datatable').DataTable({
        "pageLength": 25,
        "lengthMenu": [[25, 100, 500, 1000], [25, 100, 500, 1000]],
        "dom": 'Blfrtip',
        "buttons": ['colvis','copy','csv'],
        "order": [[ 11, 'asc' ]],
        "columnDefs": [ { "targets": 0, "orderable": false } ]
    });

    $('.toggle-vis').on( 'click', function (e) {
        var val = $(this).val();

        var checked = 0;
        if( $(this).is(':checked') ) checked = 1;

        var column = table.column( val );
        column.visible( ! column.visible() );

        $.ajax({
            type: 'get',
            url: 'exe.php?action=save_column&time=<?php echo $_GET['time']; ?>&col=' + (val - 1) + '&type=' + checked,
            success: function(response) {
                // window.location = response;
            }
        });

    } );


    <?php
    foreach ($meta_data_headers as $i=>$colname) {
        if(! array_key_exists($i, $_SESSION['META_RESULTS'][$TIME]) ){
            echo "table.column( " . ($i+1) . " ).visible( false );\n";
        }
        else {
            echo "table.column( " . ($i+1) . " ).visible( true );\n";
        }
    }
    ?>


    $(document).on('change', '.checkbox_option', function() {
        var vm = $(this);
        if (vm.is(':checked')) {
            vm.parent().parent().next().find('input').show();
        }
        else {
            vm.parent().parent().next().find('input').hide();
        }
    });


    // View Bubble Plot
    $(document).on('click', '#btn_bubble_plot', function() {
        var comps = $('#input_all_comparison_ids').val();

        var rowid = '';
        $('.bxaf_checkbox_one').each(function(index, element) {
            if ( element.checked ) {
                if(rowid == '') rowid = $(element).attr('rowid');
                else rowid += ',' + $(element).attr('rowid');
            }
        });
        if (rowid == '') {
            rowid = $('#input_all_gene_ids').val();
        }
        // window.open('../tool_bubble_plot/multiple.php?gene_ids=' + rowid + '&comparison_ids=' + comps);
        window.location = '../tool_bubble_plot/multiple.php?gene_ids=' + rowid + '&comparison_ids=' + comps;
    });



    // Save Genes
    $(document).on('click', '.btn_save_session_list', function() {

        var rowid = '';
        $('.bxaf_checkbox_one').each(function(index, element) {
            if ( element.checked ) {
                if(rowid == '') rowid = $(element).attr('rowid');
                else rowid += ',' + $(element).attr('rowid');
            }
        });
        if (rowid == '') {
            rowid = $('#input_all_gene_ids').val();
        }

        $.ajax({
            type: 'POST',
            url: '../tool_save_lists/exe.php?action=save_session_list&category=gene',
            data: {'list': rowid},
            success: function(response) {
                // window.open(response);
                window.location = response;
            }
        });

    });


  //---------------------------------------------------------------------------
  // Save Meta Result
  $(document).on('click', '#btn_save_result', function() {

    var vm          = $(this);
    var title       = $('#save_result_title').val();
    var description = $('#save_result_description').val();

    if (title == '') {
      bootbox.alert('Error: Please enter title.');
    }

    else {

      vm.attr('disabled', '')
        .children(':first')
        .removeClass('fa-floppy-o')
        .addClass('fa-spin fa-spinner');

      $.ajax({
        type: 'POST',
        url: 'exe.php?action=save_result',
        data: {
          title: title,
          description: description,
          time: '<?php echo $_GET['time']; ?>'
        },
        success: function(response) {

            vm.removeAttr('disabled')
                .children(':first')
                .addClass('fa-floppy-o')
                .removeClass('fa-spin fa-spinner');

            $('#modal_confirm_save_result').modal('hide');

            bootbox.alert('The result is saved. You can view all your saved Meta results <a href="my_results.php">here</a>.');
        }
      });
    }
  });


});

</script>


</body>
</html>