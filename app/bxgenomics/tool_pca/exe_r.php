<?php

include_once('config.php');



//********************************************************************************************
// Load Example
//********************************************************************************************

if (isset($_GET['action']) && trim($_GET['action']) == 'load_example') {

    $dir_from = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . 'demo';
    $dir_to = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
    foreach ($BXAF_CONFIG['PCA_R_FILE_LIST'] as $file) {
        copy($dir_from . '/' . $file, $dir_to . '/' . $file);
    }
    exit();
}



//********************************************************************************************
// Upload Zip Files
//********************************************************************************************

if (isset($_GET['action']) && trim($_GET['action']) == 'upload_zip') {

  if ($_FILES["file"]["error"] === 0) {
    $tmp_name       = $_FILES["file"]["tmp_name"];
    $name           = $_FILES["file"]["name"];
    $size           = $_FILES["file"]["size"];
    $type           = $_FILES["file"]["type"];
    $dir            = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];

    // if (trim($type) != 'application/zip') {
    //   echo 'Error: Please upload a zip file.';
    //   exit();
    // }

    if (!is_dir($dir . '/')) {
      mkdir($dir, 0755, true);
      chmod($dir, 0755);
    }


    foreach ($BXAF_CONFIG['PCA_R_FILE_LIST'] as $file) {
      unlink($dir . '/' . $file);
    }

    move_uploaded_file($tmp_name, $dir . '/r.zip');

    if (file_exists($dir . '/r.zip')) {
      $zip = new ZipArchive;
      $zip->open($dir . '/r.zip');
      $zip->extractTo($dir);
      $zip->close();
    }

    foreach ($BXAF_CONFIG['PCA_R_FILE_LIST_REQUIRED'] as $file) {
      if (!file_exists($dir . '/' . $file)) {
        echo 'Error: The zip does not contain required file: ' . $file;
        exit();
      }
    }
  }
  echo 'Success';

  exit();
}




//********************************************************************************************
// Get Barchart Data
//********************************************************************************************

if (isset($_GET['action']) && trim($_GET['action']) == 'get_barchart') {

  header('Content-Type: application/json');
  $OUTPUT['type'] = 'Error';
  $TIME_STAMP     = intval($_POST['time_stamp']);
  if ($TIME_STAMP == 0) {
    $dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
  } else {
    $dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $TIME_STAMP;
  }

    sleep(3);
    if (!file_exists($dir . '/PCA_barchart.csv')) sleep(3);
    if (!file_exists($dir . '/PCA_barchart.csv')) sleep(3);
    if (!file_exists($dir . '/PCA_barchart.csv')) sleep(3);
    if (!file_exists($dir . '/PCA_barchart.csv')) sleep(3);
    if (!file_exists($dir . '/PCA_barchart.csv')) sleep(3);
    if (!file_exists($dir . '/PCA_barchart.csv')) sleep(3);
    if (!file_exists($dir . '/PCA_barchart.csv')) sleep(3);
    if (!file_exists($dir . '/PCA_barchart.csv')) sleep(3);



  if (!file_exists($dir . '/PCA_barchart.csv')) {

    if (! file_exists($dir . '/genes_samples.Rout')) {
      $OUTPUT['type'] = 'Pending';
      $OUTPUT['detail'] = 'File not exist.';
      echo json_encode($OUTPUT);
      exit();
    }
    else {
      $OUTPUT['type'] = 'Error';
      $OUTPUT['detail'] = 'No data found for PCA. <a href="' . $BXAF_CONFIG['BXAF_URL'] . 'app_data/cache/user_files_pca/' . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $TIME_STAMP . '/genes_samples.Rout" download>Download R log file</a>';
      echo json_encode($OUTPUT);
      exit();
    }

  }

  $file = fopen($dir . '/PCA_barchart.csv', "r") or die('No file.');
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


  //------------------------------------------------------------
  // Check Column
  $exist = false;
  foreach ($headers as $key => $header) {
    if (trim($header) == 'percentage of variance') {
      $exist = true;
      $variance_col_index = $key;
    }
  }
  if (!$exist) {
    $OUTPUT['detail'] = 'Colname not matched';
    echo json_encode($OUTPUT);
    exit();
  }

  //------------------------------------------------------------
  // Check Column
  $x = array();
  $y = array();
  $index = 1;
  foreach ($file_data as $row) {
    // $x[] = $row[0];
    $x[] = 'PC' . $index;
    $y[] = $row[$variance_col_index];
    $index++;
  }


  //----------------------------------------------------------------------------------------
  // Output Info
  $OUTPUT['type']                = 'Success';
  $OUTPUT['data']                = array('x' => $x, 'y' => $y);
  echo json_encode($OUTPUT);


  exit();

}









//********************************************************************************************
// Get Variables DataTable Info
//********************************************************************************************

if (isset($_GET['action']) && trim($_GET['action']) == 'get_variables_data_table') {

  header('Content-Type: application/json');
  $OUTPUT['type'] = 'Error';
  $TIME_STAMP     = intval($_POST['time_stamp']);
  $DIM            = trim($_POST['dim']);
  if ($TIME_STAMP == 0) {
    $dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
  } else {
    $dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $TIME_STAMP;
  }


  //----------------------------------------------------------------------------------------
  // Get Current Dim Index
  $file = fopen($dir . '/PCA_var.coord.csv', "r") or die('No file.');
  $headers = fgetcsv($file);
  fclose($file);
  $DIM_INDEX = 1;
  foreach ($headers as $key => $value) {
    if ($value == $DIM) $DIM_INDEX = $key;
  }


  //----------------------------------------------------------------------------------------
  // Active Data
  $file_list      = array(
    'contrib' => 'PCA_var.contrib.csv',
    'coord'   => 'PCA_var.coord.csv',
    'cor'     => 'PCA_var.cor.csv',
    'cos2'    => 'PCA_var.cos2.csv',
  );

  //----------------------------------------------------------------------------------------
  // Check File Exist
  foreach ($file_list as $file) {
    if (!file_exists($dir . '/' . $file)) {
      $OUTPUT['detail'] = 'File not exist: ' . $file;
      echo json_encode($OUTPUT);
      exit();
    }
  }

  //----------------------------------------------------------------------------------------
  // Get File Data
  $delimiter      = ",";
  $file_data      = array();
  $variable_list  = array();
  $file_data_temp = array();
  foreach ($file_list as $key => $file) {
    $index = 0;
    $file_data_temp = array();
    $file = fopen($dir . '/' . $file, "r") or die('No file.');
    while(($row = fgetcsv($file, 1000, $delimiter)) !== false){
      if (trim($row[0]) != '' && $index > 0) {
        $file_data_temp[] = $row;
      }
      $index++;
    }
    fclose($file);
    usort($file_data_temp, function($a, $b) {
      return strcmp($a[0], $b[0]);
    });
    $file_data[$key] = $file_data_temp;
  }

  // Variable List
  foreach ($file_data_temp as $row) {
    $variable_list[] = $row[0];
  }


  //----------------------------------------------------------------------------------------
  // Format Output PharData
  // array(
  //   '100m' => array(
  //     '100m',
  //     18.75 (contrib value),
  //     5.24  (coord value),
  //     0.24  (cor value),
  //     0.07  (cos2 value),
  //   ),
  //   'High Jump' => array(...),
  //   ...
  // )
  $output_data = array();
  $index = 0;
  foreach ($variable_list as $variable) {
    $output_data[$variable] = array($variable);
    foreach ($file_data as $key => $value) {
      $single_data = $value[$index];
      unset($single_data[0]);
      // $output_data[$variable][] = max($single_data);
      $output_data[$variable][] = $single_data[$DIM_INDEX];
    }
    $index++;
  }

  $output_data_final = array();
  foreach ($output_data as $row) {
    foreach ($row as $k => $v) {
      if ($k > 0) {
        $row[$k] = number_format($v, 3);
      }
    }
    $output_data_final[] = $row;
  }
  $output_data_final_active = $output_data_final;




  //*******************************************************************************************************
  // Supplementary Data
  if (file_exists($dir . '/' . 'PCA_quanti.sup.coord.csv')) {
    $file_list      = array(
      'coord'   => 'PCA_quanti.sup.coord.csv',
      'cor'     => 'PCA_quanti.sup.cor.csv',
      'cos2'    => 'PCA_quanti.sup.cos2.csv',
    );

    //----------------------------------------------------------------------------------------
    // Check File Exist
    foreach ($file_list as $file) {
      if (!file_exists($dir . '/' . $file)) {
        $OUTPUT['detail'] = 'File not exist: ' . $file;
        echo json_encode($OUTPUT);
        exit();
      }
    }

    //----------------------------------------------------------------------------------------
    // Get File Data
    $delimiter      = ",";
    $file_data      = array();
    $variable_list  = array();
    $file_data_temp = array();
    foreach ($file_list as $key => $file) {
      $index = 0;
      $file_data_temp = array();
      $file = fopen($dir . '/' . $file, "r") or die('No file.');
      while(($row = fgetcsv($file, 1000, $delimiter)) !== false){
        if (trim($row[0]) != '' && $index > 0) {
          $file_data_temp[] = $row;
        }
        $index++;
      }
      fclose($file);
      usort($file_data_temp, function($a, $b) {
        return strcmp($a[0], $b[0]);
      });
      $file_data[$key] = $file_data_temp;
    }

    // Variable List
    foreach ($file_data_temp as $row) {
      $variable_list[] = $row[0];
    }

    $output_data = array();
    $index = 0;
    foreach ($variable_list as $variable) {
      $output_data[$variable] = array($variable);
      foreach ($file_data as $key => $value) {
        $single_data = $value[$index];
        unset($single_data[0]);
        // $output_data[$variable][] = max($single_data);
        $output_data[$variable][] = $single_data[$DIM_INDEX];
      }
      $index++;
    }

    $output_data_final = array();
    foreach ($output_data as $row) {
      foreach ($row as $k => $v) {
        if ($k > 0) {
          $row[$k] = number_format($v, 3);
        }
      }
      $output_data_final[] = $row;
    }
    $output_data_final_supplementary = $output_data_final;
  }





  //*******************************************************************************************************
  // Qualitative Supplementary Data
  if (file_exists($dir . '/' . 'PCA_quali.sup.coord.csv')) {
    $file_list      = array(
      'coord'   => 'PCA_quali.sup.coord.csv',
      'cos2'    => 'PCA_quali.sup.cos2.csv',
    );

    //----------------------------------------------------------------------------------------
    // Check File Exist
    foreach ($file_list as $file) {
      if (!file_exists($dir . '/' . $file)) {
        $OUTPUT['detail'] = 'File not exist: ' . $file;
        echo json_encode($OUTPUT);
        exit();
      }
    }

    //----------------------------------------------------------------------------------------
    // Get File Data
    $delimiter      = ",";
    $file_data      = array();
    $variable_list  = array();
    $file_data_temp = array();
    foreach ($file_list as $key => $file) {
      $index = 0;
      $file_data_temp = array();
      $file = fopen($dir . '/' . $file, "r") or die('No file.');
      while(($row = fgetcsv($file, 1000, $delimiter)) !== false){
        if (trim($row[0]) != '' && $index > 0) {
          $file_data_temp[] = $row;
        }
        $index++;
      }
      fclose($file);
      usort($file_data_temp, function($a, $b) {
        return strcmp($a[0], $b[0]);
      });
      $file_data[$key] = $file_data_temp;
    }

    // Variable List
    foreach ($file_data_temp as $row) {
      $variable_list[] = $row[0];
    }

    $output_data = array();
    $index = 0;
    foreach ($variable_list as $variable) {
      $output_data[$variable] = array($variable);
      foreach ($file_data as $key => $value) {
        $single_data = $value[$index];
        unset($single_data[0]);
        // $output_data[$variable][] = max($single_data);
        $output_data[$variable][] = $single_data[$DIM_INDEX];
      }
      $index++;
    }

    $output_data_final = array();
    foreach ($output_data as $row) {
      foreach ($row as $k => $v) {
        if ($k > 0) {
          $row[$k] = number_format($v, 3);
        }
      }
      $output_data_final[] = $row;
    }
    $output_data_final_quantitative = $output_data_final;
  }



  //----------------------------------------------------------------------------------------
  // Output Info
  $OUTPUT['type']                = 'Success';
  $OUTPUT['data']                = $output_data_final_active;
  $OUTPUT['data_supplementary']  = $output_data_final_supplementary;
  $OUTPUT['data_quantitative']   = $output_data_final_quantitative;
  echo json_encode($OUTPUT);



  exit();

}








//********************************************************************************************
// Get Variables Plot Info
//********************************************************************************************
if (isset($_GET['action']) && trim($_GET['action']) == 'get_individuals_data_table') {

  header('Content-Type: application/json');
  $OUTPUT['type'] = 'Error';
  $TIME_STAMP     = intval($_POST['time_stamp']);
  $DIM            = trim($_POST['dim']);
  if ($TIME_STAMP == 0) {
    $dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'];
  } else {
    $dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $TIME_STAMP;
  }

  //----------------------------------------------------------------------------------------
  // Get Current Dim Index
  $file = fopen($dir . '/PCA_var.coord.csv', "r") or die('No file.');
  $headers = fgetcsv($file);
  fclose($file);
  $DIM_INDEX = 1;
  foreach ($headers as $key => $value) {
    if ($value == $DIM) $DIM_INDEX = $key;
  }

  //----------------------------------------------------------------------------------------
  // Active Data
  $file_list      = array(
    'contrib' => 'PCA_ind.contrib.csv',
    'coord'   => 'PCA_ind.coord.csv',
    'cos2'    => 'PCA_ind.cos2.csv',
  );

  //----------------------------------------------------------------------------------------
  // Check File Exist
  foreach ($file_list as $file) {
    if (!file_exists($dir . '/' . $file)) {
      $OUTPUT['detail'] = 'File not exist: ' . $file;
      echo json_encode($OUTPUT);
      exit();
    }
  }

  //----------------------------------------------------------------------------------------
  // Get File Data
  $delimiter      = ",";
  $file_data      = array();
  $variable_list  = array();
  $file_data_temp = array();
  foreach ($file_list as $key => $file) {
    $index = 0;
    $file_data_temp = array();
    $file = fopen($dir . '/' . $file, "r") or die('No file.');
    while(($row = fgetcsv($file, 1000, $delimiter)) !== false){
      if (trim($row[0]) != '' && $index > 0) {
        foreach ($row as $k => $v) {
          if ($k > 0) {
            $row[$k] = number_format($v, 3);
          }
        }
        $file_data_temp[] = $row;
      }
      $index++;
    }
    fclose($file);
    usort($file_data_temp, function($a, $b) {
      return strcmp($a[0], $b[0]);
    });
    $file_data[$key] = $file_data_temp;
  }

  // Variable List
  foreach ($file_data_temp as $row) {
    $variable_list[] = $row[0];
  }


  //----------------------------------------------------------------------------------------
  // Format Output PharData
  // array(
  //   '100m' => array(
  //     '100m',
  //     18.75 (contrib value),
  //     5.24  (coord value),
  //     0.24  (cor value),
  //     0.07  (cos2 value),
  //   ),
  //   'High Jump' => array(...),
  //   ...
  // )
  $output_data = array();
  $index = 0;
  foreach ($variable_list as $variable) {
    $output_data[$variable] = array($variable);
    foreach ($file_data as $key => $value) {
      $single_data = $value[$index];
      unset($single_data[0]);
      // $output_data[$variable][] = max($single_data);
      $output_data[$variable][] = $single_data[$DIM_INDEX];
    }
    $index++;
  }

  $output_data_final = array();
  foreach ($output_data as $row) {
    $output_data_final[] = $row;
  }
  // $output_data_final_active = $output_data_final;






  //----------------------------------------------------------------------------------------
  // Output Info
  $OUTPUT['type']                = 'Success';
  $OUTPUT['data']                = $output_data_final;
  echo json_encode($OUTPUT);



  exit();

}









//********************************************************************************************
// Upload Attributes File
//********************************************************************************************
if (isset($_GET['action']) && trim($_GET['action']) == 'upload_file') {

    header('Content-Type: application/json');
    $OUTPUT = array('type' => 'Error');

    $TIME_STAMP     = intval($_POST['time_stamp']);
    if ($TIME_STAMP == 0) {
        $dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/';
    } else {
        $dir = $BXAF_CONFIG['USER_FILES']['TOOL_PCA'] . $BXAF_CONFIG['BXAF_USER_CONTACT_ID'] . '/' . $TIME_STAMP . '/';
    }
    if (! file_exists($dir) ) {
        mkdir($dir, 0755, true);
    }

    // Upload File
    $format = ($_POST['format'] == 'txt') ? 'txt' : 'csv';
    $target_filename = 'PCA_attributes.' . $format;
    if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
        if(file_exists($dir . $target_filename)){
            if(file_exists($dir . 'PCA_attributes.csv_original') ) unlink($dir . 'PCA_attributes.csv_original');
            rename($dir . $target_filename, $dir . 'PCA_attributes.csv_original');
        }
        move_uploaded_file($_FILES["file"]["tmp_name"], $dir . $target_filename);
    }

    // Check Row Number
    $linecount1 = 0;
    $file = $dir . $target_filename;
    $handle = @fopen($file, "r");
    if ($handle) {
        while (($buffer = fgets($handle)) !== false) {
            $line = fgets($handle);
            $linecount1++;
        }
        fclose($handle);
    }

    $linecount2 = 0;
    $file = $dir . 'PCA_ind.coord.csv';
    $handle = @fopen($file, "r");
    if ($handle) {
        while (($buffer = fgets($handle)) !== false) {
            $line = fgets($handle);
            $linecount2++;
        }
        fclose($handle);
    }

    if ($linecount1 != $linecount2) {

        if(file_exists($dir . 'PCA_attributes.csv_original') ){
            unlink($dir . $target_filename);
            copy($dir . 'PCA_attributes.csv_original', $dir . $target_filename);
        }

        $OUTPUT['Detail'] = 'The number of rows in the uploaded attribute file is not correct.';
        echo json_encode($OUTPUT);
        exit();
    }

    // Output Info
    $OUTPUT['type']                = 'Success';
    $OUTPUT['name']                = $name;
    $OUTPUT['header']              = $file_data[0];
    $OUTPUT['data']                = array_slice($file_data, 1);
    echo json_encode($OUTPUT);
    exit();
}
