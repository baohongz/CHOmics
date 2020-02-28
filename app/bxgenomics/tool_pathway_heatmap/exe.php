<?php

include_once('config.php');


if (isset($_GET['action']) && trim($_GET['action']) == 'select_set') {

  header('Content-Type: application/json');

  $OUTPUT['type']  = 'Error';
  $SET             = trim($_POST['set']);
  $SHOW_TYPE       = intval($_POST['show_type']);
  $COMPARISONS     = urldecode($_POST['comparisons']);
  $COMP_LIST       = category_text_to_idnames($COMPARISONS, 'name', 'comparison', $_SESSION['SPECIES_DEFAULT']);

  if (!$COMP_LIST) {
    $OUTPUT['detail'] = 'Error: No comparisons found.';
    echo json_encode($OUTPUT);
    exit();
  }


    if ($SET == 'PAGE List') {

        $PAGE_DATA = array();
        foreach ($COMP_LIST as $comparison_id => $comparison_name) {
            $PAGE_DATA_ROW = get_page_data($comparison_id);
            if($PAGE_DATA_ROW) $PAGE_DATA = array_merge($PAGE_DATA, $PAGE_DATA_ROW );
        }
        usort($PAGE_DATA, 'sort_zscore_absolute_value');

        if (! $PAGE_DATA) {
            $OUTPUT['detail'] = "PAGE result does not exist.";
            echo json_encode($OUTPUT);
            exit();
        }

        $OUTPUT['type'] = 'Success';
        $OUTPUT['data'] = array();
        $index = 0;
        foreach ($PAGE_DATA as $k => $v) {

            if($v['name'] != '' && ! in_array($v['name'], $OUTPUT['data']) ) {
                $OUTPUT['data'][] = $v['name'];
                $index++;

                if ($SHOW_TYPE == 10 && $index >= 10) break;
                if ($SHOW_TYPE == 20 && $index >= 20) break;
                if ($SHOW_TYPE == 50 && $index >= 50) break;
                if ($SHOW_TYPE == 100 && $index >= 100) break;
            }
        }
        echo json_encode($OUTPUT);
        exit();

    }

    else {

        $HOMER_DATA     = array();

        // Get All Data
        foreach ($COMP_LIST as $comparison_id => $comparison_name) {

            $HOMER_DATA_ROW = get_homer_data($comparison_id, $SET);

            if (!$HOMER_DATA_ROW) {
                $OUTPUT['detail'] = "HOMER result does not exist.";
                echo json_encode($OUTPUT);
                exit();
            }

            $HOMER_DATA[$comparison_id] = $HOMER_DATA_ROW;

            usort($HOMER_DATA[$comparison_id]['Up'], 'sort_logP_absolute_value');
            usort($HOMER_DATA[$comparison_id]['Down'], 'sort_logP_absolute_value');

        }

        $OUTPUT['type'] = 'Success';
        $OUTPUT['data'] = array('Up' => array(), 'Down' => array());

        foreach (array('Up', 'Down') as $direction) {

          $direction_data_all = array();
          foreach ($HOMER_DATA as $comparison_id  => $comparison_data) {
            $index = 0;
            foreach ($comparison_data[$direction] as $k => $v) {
              // if ($SHOW_TYPE == 10 && $index >= 10) continue;
              // if ($SHOW_TYPE == 20 && $index >= 20) continue;
              // if ($SHOW_TYPE == 50 && $index >= 50) continue;
              // if ($SHOW_TYPE == 100 && $index >= 100) continue;
              if (!in_array($v['pathway_name'], array_keys($direction_data_all))
                  || abs($direction_data_all[$v['pathway_name']]['logP']) < abs($v['logP'])) {
                $direction_data_all[$v['pathway_name']] = $v;
              }

              $index++;
            }
          }
          usort($direction_data_all, 'sort_logP_absolute_value');


          $index = 0;
          foreach ($direction_data_all as $k => $v) {
            if ($SHOW_TYPE == 10 && $index >= 10) continue;
            if ($SHOW_TYPE == 20 && $index >= 20) continue;
            if ($SHOW_TYPE == 50 && $index >= 50) continue;
            if ($SHOW_TYPE == 100 && $index >= 100) continue;
            if (!in_array($v['pathway_name'], $OUTPUT['data'][$direction])) {
              $OUTPUT['data'][$direction][] = $v['pathway_name'];
            }
            $index++;
          }

        }

        $OUTPUT['data']['Up']   = array_reverse($OUTPUT['data']['Up']);
        $OUTPUT['data']['Down'] = array_reverse($OUTPUT['data']['Down']);

        echo json_encode($OUTPUT);
        exit();

    }

    exit();
}






if (isset($_GET['action']) && trim($_GET['action']) == 'draw_heatmap') {

  header('Content-Type: application/json');
  $OUTPUT['type']  = 'Error';

  //----------------------------------------------------------------------
  // Get Compairson Info
  $SET             = trim($_POST['set']);
  $COMPARISONS     = trim($_POST['Comparison_List']);
  $COMP_LIST       = category_text_to_idnames($COMPARISONS, 'name', 'comparison', $_SESSION['SPECIES_DEFAULT']);
  if (!$COMP_LIST) {
    $OUTPUT['detail'] = 'Comparison not found.';
    echo json_encode($OUTPUT);
    exit();
  }


  // Get GeneSet Info
  $GENESETS        = trim($_POST['textarea_genesets_up']);

  $delimiter       = "\n";
  $geneset_rows    = explode($delimiter, $GENESETS);
  $GENESET_LIST    = array();
  foreach ($geneset_rows as $geneset) {
    if (trim($geneset) != '') $GENESET_LIST[] = trim($geneset);
  }

  // 2nd Get GeneSet Info (if exists)
  $GENESETS_2      = trim($_POST['textarea_genesets_down']);

  $delimiter       = "\n";
  $geneset_rows    = explode($delimiter, $GENESETS_2);
  $GENESET_LIST_2  = array();
  foreach ($geneset_rows as $geneset) {
    if (trim($geneset) != '') $GENESET_LIST_2[] = trim($geneset);
  }


  // Filter Data
  // 1. PAGE List
  if ($SET == 'PAGE List') {
    $dir            = $BXAF_CONFIG['PAGE_OUTPUT_HUMAN'];
    $PAGE_DATA      = array();

    foreach ($COMP_LIST as $comparison_index => $comparison_id) {
      $PAGE_DATA_ROW = get_page_data($comparison_index, $GENESET_LIST);

      if (!is_array($PAGE_DATA_ROW)) {
        $OUTPUT['detail'] = "PAGE result does not exist.";
        echo json_encode($OUTPUT);
        exit();
      }
      $PAGE_DATA[$comparison_index] = $PAGE_DATA_ROW;
    }

    $DATA = array(
      'xValues' => array_values($COMP_LIST),
      'yValues' => array_values($GENESET_LIST),
      'zValues' => array(),
      'text'    => array(),
      'zmax'    => 10,
      'zmin'    => -10,
      'colorscale' => array(
        array('1.0', 'rgb(165,0,38)'),
        array('0.888888888889', 'rgb(215,48,39)'),
        array('0.777777777778', 'rgb(244,109,67)'),
        array('0.666666666667', 'rgb(253,174,97)'),
        array('0.555555555556', 'rgb(254,224,144)'),
        array('0.444444444444', 'rgb(224,243,248)'),
        array('0.333333333333', 'rgb(171,217,233)'),
        array('0.222222222222', 'rgb(116,173,209)'),
        array('0.111111111111', 'rgb(69,117,180)'),
        array('1.0', 'rgb(49,54,149)'),
      ),
    );

    foreach ($GENESET_LIST as $geneset_key => $geneset) {
      $zValue_row = array();
      $text_row = array();
      foreach ($PAGE_DATA as $PAGE_DATA_ROW) {
        $zValue_row[] = $PAGE_DATA_ROW[$geneset_key]['z-score'];
        $text_row[]   = 'number of genes: ' . $PAGE_DATA_ROW[$geneset_key]['gene-number'] . '<br />p-value: ' . $PAGE_DATA_ROW[$geneset_key]['p-value'] . '<br />FDR: ' . $PAGE_DATA_ROW[$geneset_key]['FDR'] . '';
        // $zValue_row[] = 1;
      }
      $DATA['zValues'][] = $zValue_row;
      $DATA['text'][]    = $text_row;
    }
  }


  //----------------------------------------------------------------------
  // Filter Data
  // 2. Biological Process

  else {

    $dir             = $BXAF_CONFIG['PAGE_OUTPUT_HUMAN'];
    $HOMER_DATA      = array();


    foreach ($COMP_LIST as $comparison_index => $comparison_id) {

      $HOMER_DATA_ROW = get_homer_data($comparison_index, $SET, array('Up' => $GENESET_LIST, 'Down' => $GENESET_LIST_2));

      if (!$HOMER_DATA_ROW) {
        $OUTPUT['detail'] = "HOMER result does not exist.";
        echo json_encode($OUTPUT);
        exit();
      }
      $HOMER_DATA[$comparison_index] = $HOMER_DATA_ROW;
    }

    $DATA = array(
      'xValues'      => array_values($COMP_LIST),
      'yValues'      => array_values($GENESET_LIST),
      'zValues'      => array(),
      'text'         => array(),
      'geneNames'    => array(),
      'zmax'         => 10,
      'zmin'         => 0,
      'colorscale'   => array(
        array('1.0', 'rgb(165,0,38)'),
        array('0.8', 'rgb(215,48,39)'),
        array('0.6', 'rgb(244,109,67)'),
        array('0.4', 'rgb(253,174,97)'),
        array('0.2', 'rgb(254,224,144)'),
        array('0.0', 'rgb(224,243,248)')
      )
    );
    $DATA_2 = array(
      'xValues'      => array_values($COMP_LIST),
      'yValues'      => array_values($GENESET_LIST_2),
      'zValues'      => array(),
      'text'         => array(),
      'geneNames'    => array(),
      'zmax'         => 10,
      'zmin'         => 0,
      'colorscale'   => array(
        array('0.0', 'rgb(224,243,248)'),
        array('1.0', 'rgb(49,54,149)'),
      )
    );

    foreach ($GENESET_LIST as $pathway_key => $pathway) {
      $zValue_row   = array();
      $zValue_row_2 = array();
      $text_row     = array();
      $text_row_2   = array();
      $geneNames_row     = array();
      $geneNames_row_2   = array();
      foreach ($HOMER_DATA as $HOMER_DATA_ROW) {
        $zValue_row[]      = (-1) * $HOMER_DATA_ROW['Up'][$pathway_key]['logP'];
        $zValue_row_2[]    = (-1) * $HOMER_DATA_ROW['Down'][$pathway_key]['logP'];
        $geneNames_row[]   = (-1) * $HOMER_DATA_ROW['Up'][$pathway_key]['gene_names'];
        $geneNames_row_2[] = (-1) * $HOMER_DATA_ROW['Down'][$pathway_key]['gene_names'];
        $text_row[]        = 'logP: ' . $HOMER_DATA_ROW['Up'][$pathway_key]['logP'] . '<br />number of genes: ' . $HOMER_DATA_ROW['Up'][$pathway_key]['gene_number'] . '';
        $text_row_2[]      = 'logP: ' . $HOMER_DATA_ROW['Down'][$pathway_key]['logP'] . '<br />number of genes: ' . $HOMER_DATA_ROW['Down'][$pathway_key]['gene_number'] . '';

      }
      $DATA['zValues'][]        = $zValue_row;
      $DATA_2['zValues'][]      = $zValue_row_2;
      $DATA['text'][]           = $text_row;
      $DATA_2['text'][]         = $text_row_2;
      $DATA['geneNames'][]      = $geneNames_row;
      $DATA_2['geneNames'][]    = $geneNames_row_2;
    }
  }


  $OUTPUT['type']  = 'Success';
  $OUTPUT['data']  = array(
    'x'    => $DATA['xValues'],
    'y'    => $DATA['yValues'],
    'z'    => $DATA['zValues'],
    'text' => $DATA['text'],
    'type' => 'heatmap',
    'zmax' => $DATA['zmax'],
    'zmin' => $DATA['zmin'],
    'colorscale' => $DATA['colorscale'],
  );
  if ($SET != 'PAGE List') {
    $OUTPUT['data_2']  = array(
      'x'    => $DATA_2['xValues'],
      'y'    => $DATA_2['yValues'],
      'z'    => $DATA_2['zValues'],
      'text' => $DATA_2['text'],
      'type' => 'heatmap',
      'zmax' => $DATA_2['zmax'],
      'zmin' => $DATA_2['zmin'],
      'colorscale' => $DATA_2['colorscale'],
    );

  }
  $OUTPUT['layout']  = array(
    'width'     => 800,
    'height'    => max(500, count($GENESET_LIST) * 20 + 200),
    'xaxis'     => array('side' => 'top', 'tickangle' => -90),
    'margin'    => array('l' => 300, 't' => 200),
    'hoverinfo' => 'text',

  );
  echo json_encode($OUTPUT);


  exit();
}

?>