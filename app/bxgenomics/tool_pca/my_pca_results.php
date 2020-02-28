<?php
include_once('config.php');

$sql = "SELECT * FROM `{$BXAF_CONFIG['TBL_BXGENOMICS_USERSAVEDRESULTS']}` WHERE `bxafStatus` < 5 AND `_Owner_ID` = {$BXAF_CONFIG['BXAF_USER_CONTACT_ID']} AND (`Type`='PCA_Genes_Samples' OR `Type`='PCA_Basic') ORDER BY `ID` DESC";
$my_results = $BXAF_MODULE_CONN -> get_all($sql);
// echo "<pre>" . print_r($my_results, true) . "</pre>";

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


    <h1 class="">My PCA Saved Results </h1>

    <hr />

            <?php include_once('component_header.php'); ?>

            <div class="w-100">

<?php if(! is_array($my_results) || count($my_results) <= 0) echo "<div class='text-danger m-3'>No saved results found.</div>"; else { ?>
              <table class="table table-bordered table-striped table-hover w-100" id="table_main">
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

                      if ($result['Type'] == 'PCA_Genes_Samples') {
                        $link = 'index_r_individuals_plot.php?id=';
                      } else {
                        $link = 'view_chart.php?id=';
                      }

                      echo '
                      <tr>
                        <td>
                          <a href="' . $link . bxaf_encrypt($result['ID'], $BXAF_CONFIG['BXAF_KEY']) . '">
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

  $('#table_main').DataTable({"pageLength": 100, "lengthMenu": [[10, 100, 500, 1000], [10, 100, 500, 1000]]});

  $(document).on('click', '.btn_remove_result', function() {
    var vm = $(this);
    var rowid = vm.attr('rowid');
    bootbox.confirm({
      message: "Are you sure to remove the saved PCA result?",
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
              // location.reload(true);
              window.location = 'my_pca_results.php';
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
