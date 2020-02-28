<?php
include_once('config.php');


$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDRESULTS']}`
        WHERE `_Owner_ID`={$BXAF_CONFIG['BXAF_USER_CONTACT_ID']}
        AND `Type`='Meta'
        AND `bxafStatus`<5
        ORDER BY `ID` DESC";
$my_results = $BXAF_MODULE_CONN -> get_all($sql);


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
      				Saved Meta Analysis Result
              &nbsp;
              <a href="index.php" style="font-size:16px;">
                <i class="fas fa-angle-double-right"></i>
                Create New Analysis
              </a>
      			</h1>
            <hr />

            <div class="w-100">
              <table class="table table-bordered table-striped table-hover w-100">
                <thead>
                  <tr class="table-info">
                    <th>Title</th>
                    <th>Description</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    foreach ($my_results as $result) {

                      $link = 'view_chart.php?id=';

                      echo '
                      <tr>
                        <td>
                          <a href="meta_result.php?time=' . $result['Time'] . '">
                            ' . $result['Title'] . '
                          </a>
                        </td>
                        <td>' . $result['Description'] . '</td>
                        <td>
                          <button class="btn btn-sm btn-danger btn_remove_result"
                            rowid="' . bxaf_encrypt($result['ID'], $BXAF_CONFIG['BXAF_KEY']) . '">
                            <i class="fas fa-times"></i> Delete
                          </button>
                        </td>
                      </tr>';
                    }
                  ?>
                </tbody>
              </table>
            </div>

            <div id="debug"></div>

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

  $('.datatable').DataTable({"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]], dom: 'Blfrtip', buttons: ['colvis','copy','csv'] });

  $(document).on('click', '.btn_remove_result', function() {
    var vm = $(this);
    var rowid = vm.attr('rowid');
    bootbox.confirm({
      message: "Are you sure to remove the PCA result?",
      buttons: {
        confirm: {
          label: 'Remove',
          className: 'btn-danger'
        },
        cancel: {
          label: 'Cancel',
          className: 'btn-secondary'
        }
      },
      callback: function (result) {
        if (result) {
          $.ajax({
            type: 'POST',
            url: 'exe.php?action=delete_result',
            data: { rowid: rowid },
            success: function(response) {
              location.reload(true);
              // alert(response);
            }
          });
        }
      }
    });
  });

});


</script>




</body>
</html>
