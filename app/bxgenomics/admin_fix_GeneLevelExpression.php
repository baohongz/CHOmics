<?php

// php admin_fix_GeneLevelExpression.php ./GeneLevelExpression_log2.txt.gz ./GeneLevelExpression.txt
// php admin_fix_GeneLevelExpression.php ./GeneLevelExpression_log2.txt.Sample.gz ./GeneLevelExpression.txt.Sample
// /public/programs/tabix/latest/bgzip GeneLevelExpression.txt
// /public/programs/tabix/latest/bgzip GeneLevelExpression.txt.Sample
// /public/programs/tabix/latest/tabix -s 2 -b 1 -e 1 -0 GeneLevelExpression.txt.gz
// /public/programs/tabix/latest/tabix -s 1 -b 2 -e 2 -0 GeneLevelExpression.txt.Sample.gz

$argvLength         = sizeof($argv);
$input                = $argv[1];
$output                = $argv[2];

if ($argvLength != 3){
    echo "This tool converts GeneLevelExpression value from log2 to linear" . "\n";
    echo "Usage:   php admin_fix_GeneLevelExpression.php <GeneLevelExpression txt.gz file>      <Output text File>" . "\n";

    echo "\n";
    exit();
}


if (!is_file($input)){
    echo "Error. The file: {$input} cannot be read." . "\n";
    echo "\n";
    exit();
}


$fpout = fopen($output, 'w');
$fp = gzopen($input, 'r');

$headerSize = 0;
while (!feof($fp)){
    $currentOutput = fgetcsv($fp, 0, "\t");
    if($headerSize == 0) $headerSize = sizeof($currentOutput);
    if (sizeof($currentOutput) != $headerSize){
        continue;
    }
    $currentOutput[2] = round(pow(2, $currentOutput[2]), 3);
    fputcsv($fpout, $currentOutput, "\t");
}

gzclose($fp);


fclose($foutput);

?>