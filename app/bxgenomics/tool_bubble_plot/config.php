<?php

include_once('../config/config.php');

function get_stat_scale_color($value, $type='logFC') {
  if ($type == 'logFC') {
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
  if ($type == 'FDR') {
    if ($value > 0.05) {
      return '#9CA4B3';
    } else if ($value <= 0.01) {
      return '#015402';
    } else {
      return '#5AC72C';
    }
  }
  if ($type == 'PVal') {
    if ($value >= 0.01) {
      return '#9CA4B3';
    } else {
      return '#5AC72C';
    }
  }
  return;
}



$BXAF_CONFIG['BUBBLE_PLOT']['FIELDS'] = array(
  'Case_DiseaseState', 'Case_Tissue', 'Case_CellType', 'Case_Ethnicity',
  'Case_Gender', 'Case_Treatment', 'Case_SampleSource'
);


$COLOR_SCHEME_50  = array(
  '7B336A', '74E1C6', '304D28', 'DDDF45', '93389C', 'A7E047', 'DB8A2E', 'B4E190', 'D5AAA3', '4F28A5', 'C33BE0', '70C7DB',
  '582127', '3D545C', 'D79F6B', 'D77FA5', '86762C', '60E543', 'C7D9CE', 'D56737', 'DAB33E', '585C8E', '4C8F7C', '6C9DDB',
  'C585D4', '252422', '662FE3', 'C7B1D7', '98A73F', '311E41', '594527', '9D3554', '8B656F', '3E2674', '55AA39', '8E926E',
  '45752C', '6272CF', '91562D', 'DE51C9', 'E03D24', 'DE3E58', '63E489', 'D8428D', 'DCD493', '5BAF74', '7D61E1', '688C9F',
  '952E23', 'D77771'
);

$COLOR_SCHEME_20  = array(
  '1F77B4', 'AEC7E8', 'FD7F23', 'FDBB78', '2CA02C', '98DF8A', 'D62728', 'FD9896', '9467BD', 'C5B0D5', '8C564B', 'C49C94', 'E377C2', 'F7B6D2', '7F7F7F', 'C7C7C7', 'BCBE27', 'DBDB8D', '1FBECF', '9EDAE5'
);
$COLOR_SCHEME_10  = array(
  '1F77B4', 'FD7F23', '2CA02C', 'D62728', '9467BD', '8C564B', 'E377C2', '7F7F7F', 'BCBE27', '1FBECF'
);


?>