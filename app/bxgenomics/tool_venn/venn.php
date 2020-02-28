<?php

include_once(__DIR__ . '/config.php');


if(!function_exists('area_check_center')) {
	function area_check_center($x, $y){ // $x, $y are radius of two circles here
		$part1 = pow($x, 2) * ( asin($y/$x) - (1/2) * sin(2 * asin( $y/$x )));// Area of the segment of the larger circle
		$part2 = (1/2) * pi() * pow($y, 2);// Area of the smaller semi-circle
		return $part1 + $part2;
	}
}


// For the case where the two centers are on the same side of the common chord
// The last three parameters are radius of two circles and the overlap area
if(!function_exists('equation_1')) {
	function equation_1($x, $y, $z, $yz){
		return pow($y, 2)*($x - (1/2)*sin(2*$x)) + pow($z, 2)*(pi() - asin(($y/$z)*sin($x)) + (1/2)*sin(2 * asin(($y/$z) * sin($x)))) - $yz;
	}
}


// Solve the equation (Using the method of bisection)
if(!function_exists('solve_function_1')) {
	function solve_function_1($a, $b, $y, $z, $yz){
		$lower = equation_1($a, $y, $z, $yz);
		$upper = equation_1($b, $y, $z, $yz);
		$middle = equation_1(($a+$b)/2, $y, $z, $yz);
		if (abs($upper - $lower) < 0.000001){
			$_SESSION['result'] = $a;
		}
        else {
			if ($middle * $lower <= 0){
                $a=$a;
                $b=($a+$b)/2;
                solve_function_1($a, $b, $y, $z, $yz);
            }
			else {
                $b=$b;
                $a=($a+$b)/2;
                solve_function_1($a, $b, $y, $z, $yz);
            }
		}
	}
}

// For the case where the two centers are on the different sides of the common chord
// The last three parameters are radius of two circles and the overlap area
if(!function_exists('equation_2')) {
	function equation_2($x, $y, $z, $yz){
		return pow($y, 2)*($x - (1/2)*sin(2*$x)) + pow($z, 2)*( asin(($y/$z)*sin($x)) - (1/2)*sin(2 * asin(($y/$z) * sin($x)))) - $yz;
	}
}
// Solve the equation (Using the method of bisection)
if(!function_exists('solve_function_2')) {
	function solve_function_2($a, $b, $y, $z, $yz){
		$lower = equation_2($a, $y, $z, $yz);
		$upper = equation_2($b, $y, $z, $yz);
		$middle = equation_2(($a+$b)/2, $y, $z, $yz);
		if (abs($upper - $lower) < 0.000001){
			$_SESSION['result'] = $a;
		}
        else {
			if ($middle * $lower <= 0){
                $a=$a;
                $b=($a+$b)/2;
                solve_function_2($a, $b, $y, $z, $yz);
            }
			else {
                $b=$b;
                $a=($a+$b)/2;
                solve_function_2($a, $b, $y, $z, $yz);
            }
		}
	}
}


function get_centers_line($radius1, $radius2, $area) {

	if($area == area_check_center($radius1, $radius2)){ $centers_line = sqrt(pow($radius1, 2) - pow($radius2, 2));}
	if($area > area_check_center($radius1, $radius2)){
		// Two centers are on the same side of the common chord
		solve_function_1(0, asin($radius2/$radius1)-0.001, $radius1, $radius2, $area);
		$angle1 = $_SESSION['result'];
		$centers_line = $radius2 * ( sin( asin( ($radius1/$radius2)*sin($angle1) ) - $angle1 ) ) / sin($angle1);

	}
	if($area < area_check_center($radius1, $radius2)){
		// Two centers are on the different sides of the common chord
		solve_function_2(0, asin($radius2/$radius1)-0.001, $radius1, $radius2, $area);
		$angle1 = $_SESSION['result'];
		$centers_line = $radius2 * ( sin( asin( ($radius1/$radius2)*sin($angle1) ) + $angle1 ) ) / sin($angle1);
	}
	$_SESSION['centers_line'] = $centers_line;

	return $centers_line;
}





// $_GET: action (=venn), sets (=3), labelA, labelB, labelC, A, B, C, AB, AC, BC, ABC, size, type
// $_GET: action (=venn), sets (=2), labelA, labelB, A, B, AB, size, type

// e.g.: venn.php?action=venn&sets=2&labelA=A&labelB=B&A=1000&B=700&AB=300&size=500&type=png
// e.g.: venn.php?action=venn&sets=3&labelA=A&labelB=B&labelC=C&A=1000&B=700&C=600&AB=300&AC=250&BC=100&ABC=30&size=500&type=png
//
// When type == 'png', it return image URL, otherwise, it return HTML for interactive venn diragram display
//

if(isset($_GET['action']) && $_GET['action']=="venn"){

    if(! isset($_GET['labelA']) || $_GET['labelA'] == '') $_GET['labelA'] = 'A';
    if(! isset($_GET['labelB']) || $_GET['labelB'] == '') $_GET['labelB'] = 'B';
    if(! isset($_GET['labelC']) || $_GET['labelC'] == '') $_GET['labelC'] = 'C';

    if(! isset($_GET['size']) || intval($_GET['size']) == 0) $_GET['size'] = 500;
    if(! isset($_GET['type']) || $_GET['type'] == '') $_GET['type'] = 'html';

    $_GET['sets'] = intval($_GET['sets']);
    $_GET['A'] = intval($_GET['A']);
    $_GET['B'] = intval($_GET['B']);
    $_GET['C'] = intval($_GET['C']);
    $_GET['AB'] = intval($_GET['AB']);
    $_GET['AC'] = intval($_GET['AC']);
    $_GET['BC'] = intval($_GET['BC']);
    $_GET['ABC'] = intval($_GET['ABC']);

    if($_GET['sets'] != 2 && $_GET['sets'] != 3){
        if(isset($_GET['C']) && intval($_GET['C']) > 0) $_GET['sets'] = 3;
        else $_GET['sets'] = 2;
    }


	if($_GET['sets']=='2' && $_GET['type']=='png'){

		$A = $_GET['A'];
		$B = $_GET['B'];
		$AB = $_GET['AB'];

		if($A>=$B){$n1 = sqrt($A/pi());$n2 = sqrt($B/pi());$n4 = $AB;$label1 = $_GET['labelA'];$label2 = $_GET['labelB'];}
		else if($A<$B){$n1 = sqrt($B/pi());$n2 = sqrt($A/pi());$n4 = $AB;$label1 = $_GET['labelB'];$label2 = $_GET['labelA'];}

		solve_function_2(0,asin($n2/$n1)-0.01, $n1, $n2, $n4);


		/////////////////////////////////////////////////////////////////////////////////////////////////////
		// Figure out the lengths of lines of centres，inorder to determine positions of the three centers //
		/////////////////////////////////////////////////////////////////////////////////////////////////////

		// Take a look at circle A and circle B first
		// Determine whether the smaller circle is in the larger circle. Then determine whether the two centers are on the different sides of the common chord.
		if ($AB > $B){
            // echo 'Wrong value because AB > B.';
            exit();
        }
		if ($AB == $B){
            exit();
        }

		if($AB == area_check_center($n1, $n2)){
			// The center of the smaller circle lies on the common chord
			$o1o2 = sqrt(pow($n1, 2) - pow($n2, 2));
		}

		else if ($AB > area_check_center($n1, $n2)){
			// Two centers are on the same side of the common chord
			solve_function_1(0, asin($n2/$n1)-0.001, $n1, $n2, $n4); //We
			$angle1 = $_SESSION['result'];
			$o1o2 = $n2 * ( sin( asin( ($n1/$n2)*sin($angle1) ) - $angle1 ) ) / sin($angle1);
		}

		else if ($AB < area_check_center($n1, $n2)){
			// Two centers are on the different sides of the common chord
			solve_function_2(0, asin($n2/$n1)-0.001, $n1, $n2, $n4); // The second parameter must be controlled because asin(x) will return "NAN" if x>1.
			$angle1 = $_SESSION['result'];
			$o1o2 = $n2 * ( sin( asin( ($n1/$n2)*sin($angle1) ) - $angle1 ) ) / sin($angle1);
		}



		$o1o2 = get_centers_line($n1, $n2, $n4);

		// Positions for centers of three circles
		$o1x = 0; $o1y = 0; $o2x = $o1o2; $o2y = 0;


		// Can be changed.
		$width = $_GET['size'];
		$height = $_GET['size'] + 20*4;

		$coef = min($width/($n1 + $n2 + $o2x + 10), $height/($n1 + 10));

		$o1x_plot = $n1 * $coef + 10;
		$o1y_plot = $n1 * $coef + 10;
		$o2x_plot = ($n1 + $o2x) * $coef + 10;
		$o2y_plot = $n1 * $coef + 10;
		$n1_plot = $n1 * $coef;
		$n2_plot = $n2 * $coef;

		$icon1y_plot = $o1y_plot + $n1 * $coef + 40;
		$icon2y_plot = $icon1y_plot + 20;
		$icon3y_plot = $icon1y_plot + 40;



		// Create a canvas
		$im = imagecreatetruecolor($width, $height);
		$white = imagecolorallocate($im, 255, 255, 255);
		$black = imagecolorallocate($im, 0, 0, 0);
		imagefill($im, 0, 0, $white);
		$red = imagecolorallocatealpha($im, 255, 223, 195, 70);
		$red_dark = imagecolorallocatealpha($im, 235, 113, 12, 0);
		$blue = imagecolorallocatealpha($im, 194, 218, 235, 70);
		$blue_dark = imagecolorallocatealpha($im, 67, 142, 185, 70);
		$green = imagecolorallocatealpha($im, 175, 219, 175, 70);
		$green_dark = imagecolorallocatealpha($im, 0, 102, 0, 0);


		// Draw a white rectangle
		//imagefilledrectangle($im, 1, 1, 50, 25, $blue);
		//imagefilledrectangle($im, 20, 20, 70, 55, $red);


		imagefilledellipse($im, $o1x_plot, $o1y_plot, 2*$n1_plot, 2*$n1_plot, $red);
		imagefilledellipse($im, $o2x_plot, $o2y_plot, 2*$n2_plot, 2*$n2_plot, $blue);

		$font = dirname(__FILE__).'/arial.ttf';

		//imagettftext($im, 14, 0, 40, 50, $red, $font, 'fdas');
		imagefilledrectangle($im, 10, $icon1y_plot-16, 26, $icon1y_plot, $red);
		imagettftext($im, 14, 0, 40, $icon1y_plot, $black, $font, $label1 . ': '.pow($n1,2)*pi().')');


		imagefilledrectangle($im, 10, $icon2y_plot-16, 26, $icon2y_plot, $blue);
		imagettftext($im, 14, 0, 40, $icon2y_plot, $black, $font, $label2. ': ' .pow($n2,2)*pi().')');

		imagefilledrectangle($im, 10, $icon3y_plot-16, 26, $icon3y_plot, $red);
		imagefilledrectangle($im, 10, $icon3y_plot-16, 26, $icon3y_plot, $blue);
		imagettftext($im, 14, 0, 40, $icon3y_plot, $black, $font, 'Intersection ('.$label1.', '.$label2.'): '.$n4.'');


		// Save the image
        $current_time = time();
		imagepng($im, $BXAF_CONFIG['BXAF_VENN_DATA_DIR'] . $current_time . 'venn.png');
		imagedestroy($im);

        // echo "<img src='" . $BXAF_CONFIG['BXAF_VENN_DATA_URL'] . $current_time . 'venn.png' . "' />";
        echo $BXAF_CONFIG['BXAF_VENN_DATA_URL'] . $current_time . 'venn.png';

		exit();
	}


	else if($_GET['sets']=='3' && $_GET['type']=='png'){

				$A = $_GET['A'];
				$B = $_GET['B'];
				$C = $_GET['C'];
				$AB = $_GET['AB'];
				$AC = $_GET['AC'];
				$BC = $_GET['BC'];
				$ABC = $_GET['ABC'];

				if($A>=$B && $B>=$C){$n1 = sqrt($A/pi());$n2 = sqrt($B/pi());$n3 = sqrt($C/pi());$n4 = $AB;$n5 = $AC;$n6 = $BC;$label1 = $_GET['labelA'];$label2 = $_GET['labelB'];$label3 = $_GET['labelC'];}

				else if($A>=$C && $C>=$B){$n1 = sqrt($A/pi());$n2 = sqrt($C/pi());$n3 = sqrt($B/pi());$n4 = $AC;$n5 = $AB;$n6 = $BC;$label1 = $_GET['labelA'];$label2 = $_GET['labelC'];$label3 = $_GET['labelB'];}

				else if($B>=$A && $A>=$C){$n1 = sqrt($B/pi());$n2 = sqrt($A/pi());$n3 = sqrt($C/pi());$n4 = $AB;$n5 = $BC;$n6 = $AC;$label1 = $_GET['labelB'];$label2 = $_GET['labelA'];$label3 = $_GET['labelC'];}

				else if($B>=$C && $C>=$A){$n1 = sqrt($B/pi());$n2 = sqrt($C/pi());$n3 = sqrt($A/pi());$n4 = $BC;$n5 = $AB;$n6 = $AC;$label1 = $_GET['labelB'];$label2 = $_GET['labelC'];$label3 = $_GET['labelA'];}

				else if($C>=$A && $A>=$B){$n1 = sqrt($C/pi());$n2 = sqrt($A/pi());$n3 = sqrt($B/pi());$n4 = $AC;$n5 = $BC;$n6 = $AB;$label1 = $_GET['labelC'];$label2 = $_GET['labelA'];$label3 = $_GET['labelB'];}

				else if($C>=$B && $B>=$A){$n1 = sqrt($C/pi());$n2 = sqrt($B/pi());$n3 = sqrt($A/pi());$n4 = $BC;$n5 = $AC;$n6 = $AB;$label1 = $_GET['labelC'];$label2 = $_GET['labelB'];$label3 = $_GET['labelA'];}


				solve_function_2(0,asin($n2/$n1)-0.01, $n1, $n2, $n4);


				/////////////////////////////////////////////////////////////////////////////////////////////////////
				// Figure out the lengths of lines of centres，inorder to determine positions of the three centers //
				/////////////////////////////////////////////////////////////////////////////////////////////////////

				// Take a look at circle A and circle B first
				// Determine whether the smaller circle is in the larger circle. Then determine whether the two centers are on the different sides of the common chord.
				if ($AB > $B){echo 'Wrong value because AB > B.'; exit();}
				if ($AB == $B){$ABC = $BC; exit();}

				if($AB == area_check_center($n1, $n2)){
					// The center of the smaller circle lies on the common chord
					$o1o2 = sqrt(pow($n1, 2) - pow($n2, 2));
				}

				else if ($AB > area_check_center($n1, $n2)){
					// Two centers are on the same side of the common chord
					solve_function_1(0, asin($n2/$n1)-0.001, $n1, $n2, $n4); //We
					$angle1 = $_SESSION['result'];
					$o1o2 = $n2 * ( sin( asin( ($n1/$n2)*sin($angle1) ) - $angle1 ) ) / sin($angle1);
				}

				else if ($AB < area_check_center($n1, $n2)){
					// Two centers are on the different sides of the common chord
					solve_function_2(0, asin($n2/$n1)-0.001, $n1, $n2, $n4); // The second parameter must be controlled because asin(x) will return "NAN" if x>1.
					$angle1 = $_SESSION['result'];
					$o1o2 = $n2 * ( sin( asin( ($n1/$n2)*sin($angle1) ) - $angle1 ) ) / sin($angle1);
				}




				$o1o2 = get_centers_line($n1, $n2, $n4);
				$o1o3 = get_centers_line($n1, $n3, $n5);
				$o2o3 = get_centers_line($n2, $n3, $n6);

				// Figure out angle-O2O1O13, then we can get the position for O3
				$angle_o2o1o3 = acos( (pow($o1o2, 2) + pow($o1o3, 2) - pow($o2o3, 2))/(2 * $o1o2 * $o1o3) );

				// Positions for centers of three circles
				$o1x = 0; $o1y = 0; $o2x = $o1o2; $o2y = 0;
				$o3x = $o1o3 * cos($angle_o2o1o3); $o3y = $o1o3 * sin($angle_o2o1o3);


				// For each point on the plane, check whether it is in the common area by checking the radius.
				$index = 0;
				for($i = 0; $i < intval($n1+2*$n2); $i++ ){
					for ($j = 0; $j < intval($n1+2*$n3); $j++){
						if(sqrt(pow($i-$o1x,2)+pow($j-$o1y,2))<$n1 && sqrt(pow($i-$o2x,2)+pow($j-$o2y,2))<$n2 && sqrt(pow($i-$o3x,2)+pow($j-$o3y,2))<$n3){
							$index = $index + 1;
						}
					}
				}


				// Can be changed.
				$width = $_GET['size'];
				$height = $_GET['size'] + 20*7;

				$coef = min($width/($n1 + $n2 + $o2x + 10), $height/($n1 + $n3 + $o3y + 10));

				$o1x_plot = $n1 * $coef + 10;
				$o1y_plot = ($n3 + $o3y) * $coef + 10;
				$o2x_plot = ($n1 + $o2x) * $coef + 10;
				$o2y_plot = ($n3 + $o3y) * $coef + 10;
				$o3x_plot = ($n1 + $o3x) * $coef + 10;
				$o3y_plot = $n3 * $coef + 10;
				$n1_plot = $n1 * $coef;
				$n2_plot = $n2 * $coef;
				$n3_plot = $n3 * $coef;

				$icon1y_plot = $o1y_plot + $n1 * $coef + 40;
				$icon2y_plot = $icon1y_plot + 20;
				$icon3y_plot = $icon1y_plot + 40;
				$icon4y_plot = $icon1y_plot + 60;
				$icon5y_plot = $icon1y_plot + 80;
				$icon6y_plot = $icon1y_plot + 100;
				$icon7y_plot = $icon1y_plot + 120;


				// Create a canvas
				$im = imagecreatetruecolor($width, $height);
				$white = imagecolorallocate($im, 255, 255, 255);
				$black = imagecolorallocate($im, 0, 0, 0);
				imagefill($im, 0, 0, $white);
				$red = imagecolorallocatealpha($im, 255, 223, 195, 70);
				$red_dark = imagecolorallocatealpha($im, 235, 113, 12, 0);
				$blue = imagecolorallocatealpha($im, 194, 218, 235, 70);
				$blue_dark = imagecolorallocatealpha($im, 67, 142, 185, 70);
				$green = imagecolorallocatealpha($im, 175, 219, 175, 70);
				$green_dark = imagecolorallocatealpha($im, 0, 102, 0, 0);


				// Draw a white rectangle
				//imagefilledrectangle($im, 1, 1, 50, 25, $blue);
				//imagefilledrectangle($im, 20, 20, 70, 55, $red);


				imagefilledellipse($im, $o1x_plot, $o1y_plot, 2*$n1_plot, 2*$n1_plot, $red);
				imagefilledellipse($im, $o2x_plot, $o2y_plot, 2*$n2_plot, 2*$n2_plot, $blue);
				imagefilledellipse($im, $o3x_plot, $o3y_plot, 2*$n3_plot, 2*$n3_plot, $green);

				$font = dirname(__FILE__).'/arial.ttf';

				//imagettftext($im, 14, 0, 40, 50, $red, $font, 'Venn Diagram');
				imagefilledrectangle($im, 10, $icon1y_plot-16, 26, $icon1y_plot, $red);
				imagettftext($im, 14, 0, 40, $icon1y_plot, $black, $font, $label1.': '.pow($n1,2)*pi().'');


				imagefilledrectangle($im, 10, $icon2y_plot-16, 26, $icon2y_plot, $blue);
				imagettftext($im, 14, 0, 40, $icon2y_plot, $black, $font, $label2.': '.pow($n2,2)*pi().'');

				imagefilledrectangle($im, 10, $icon3y_plot-16, 26, $icon3y_plot, $green);
				imagettftext($im, 14, 0, 40, $icon3y_plot, $black, $font, $label3.': '.pow($n3,2)*pi().'');

				imagefilledrectangle($im, 10, $icon4y_plot-16, 26, $icon4y_plot, $red);
				imagefilledrectangle($im, 10, $icon4y_plot-16, 26, $icon4y_plot, $blue);
				imagettftext($im, 14, 0, 40, $icon4y_plot, $black, $font, 'Intersection ('.$label1.', '.$label2.'): '.$n4.'');

				imagefilledrectangle($im, 10, $icon5y_plot-16, 26, $icon5y_plot, $red);
				imagefilledrectangle($im, 10, $icon5y_plot-16, 26, $icon5y_plot, $green);
				imagettftext($im, 14, 0, 40, $icon5y_plot, $black, $font, 'Intersection ('.$label1.', '.$label3.'): '.$n5.'');

				imagefilledrectangle($im, 10, $icon6y_plot-16, 26, $icon6y_plot, $blue);
				imagefilledrectangle($im, 10, $icon6y_plot-16, 26, $icon6y_plot, $green);
				imagettftext($im, 14, 0, 40, $icon6y_plot, $black, $font, 'Intersection ('.$label2.', '.$label3.'): '.$n6.'');

				imagefilledrectangle($im, 10, $icon7y_plot-16, 26, $icon7y_plot, $blue);
				imagefilledrectangle($im, 10, $icon7y_plot-16, 26, $icon7y_plot, $green);
				imagefilledrectangle($im, 10, $icon7y_plot-16, 26, $icon7y_plot, $red);
				imagettftext($im, 14, 0, 40, $icon7y_plot, $black, $font, 'Intersection ('.$label1.', '.$label2.', '.$label3.'): '.$ABC.'');


				// Save the image
				$current_time = time();
				imagepng($im, $BXAF_CONFIG['BXAF_VENN_DATA_DIR'] . $current_time.'venn.png');
				imagedestroy($im);

                // echo "<img src='" . $BXAF_CONFIG['BXAF_VENN_DATA_URL'] . $current_time . 'venn.png' . "' />";
                echo $BXAF_CONFIG['BXAF_VENN_DATA_URL'] . $current_time . 'venn.png';

				exit();
	}


    // Return HTML
    else if($_GET['type'] != 'png'){

        echo '<div id="MyVennDiagram">' . "\n\n";

        echo '    <script src="' . $BXAF_CONFIG['BXAF_SYSTEM_URL'] . 'library/jquery/jquery.min.js"></script>' . "\n";
        echo '    <script src="' . $BXAF_CONFIG['BXAF_APP_URL'] . 'bxgenomics/tool_venn/js/d3.js"></script>' . "\n";
        echo '    <script src="' . $BXAF_CONFIG['BXAF_APP_URL'] . 'bxgenomics/tool_venn/js/venn.js"></script>' . "\n\n";

    	echo '
    <style>
        .venntooltip {
            position: absolute;
            text-align: center;
            width: 128px;
            height: 22px;
            background: #333;
            color: #ddd;
            padding: 2px;
            border: 0px;
            border-radius: 8px;
            opacity: 0;
        }
    </style>';


        echo '
    <script type="text/javascript">
        $(document).ready(function(){

    	var sets = [';

    	if($_GET['sets']==3){
    		echo '
    			 {"sets": [0], "label": "'.$_GET['labelA'].'", "size": '.intval($_GET['A']).'},
    			 {"sets": [1], "label": "'.$_GET['labelB'].'", "size": '.intval($_GET['B']).'},
    			 {"sets": [2], "label": "'.$_GET['labelC'].'", "size": '.intval($_GET['C']).'},
    			 {"sets": [0, 1], "size": '.intval($_GET['AB']).'},
    			 {"sets": [0, 2], "size": '.intval($_GET['AC']).'},
    			 {"sets": [1, 2], "size": '.intval($_GET['BC']).'},
    			 {"sets": [0, 1, 2], "size": '.intval($_GET['ABC']).'}];';
    	}
    	else if($_GET['sets']==2) {
    		echo '
    			 {"sets": [0], "label": "'.$_GET['labelA'].'", "size": '.intval($_GET['A']).'},
    			 {"sets": [1], "label": "'.$_GET['labelB'].'", "size": '.intval($_GET['B']).'},
    			 {"sets": [0, 1], "size": '.intval($_GET['AB']).'}];';
    	}

    	echo    '
    	var chart = venn.VennDiagram().width('.$_GET['size'].').height('.$_GET['size'].');

    	var div = d3.select("#venn")
    	div.datum(sets).call(chart);

    	var tooltip = d3.select("#venn").append("div")
    		.attr("class", "venntooltip");

    	div.selectAll("path")
    		.style("stroke-opacity", 0)
    		.style("stroke", "#fff")
    		.style("stroke-width", 0)

    	div.selectAll("g")
    		.on("mouseover", function(d, i) {
    			// sort all the areas relative to the current item
    			venn.sortAreas(div, d);

    			// Display a tooltip with the current size
    			tooltip.transition().duration(400).style("opacity", .9);
    			tooltip.text(d.size);

    			// highlight the current path
    			var selection = d3.select(this).transition("tooltip").duration(400);
    			selection.select("path")
    				.style("stroke-width", 3)
    				.style("fill-opacity", d.sets.length == 1 ? .4 : .1)
    				.style("stroke-opacity", 1);
    		})

    		.on("mousemove", function() {
    			tooltip.style("left", (d3.event.pageX) + "px")
    				   .style("top", (d3.event.pageY - 28) + "px");
    		})

    		.on("mouseout", function(d, i) {
    			tooltip.transition().duration(400).style("opacity", 0);
    			var selection = d3.select(this).transition("tooltip").duration(400);
    			selection.select("path")
    				.style("stroke-width", 0)
    				.style("fill-opacity", d.sets.length == 1 ? .25 : .0)
    				.style("stroke-opacity", 0);
    		});

        });
    </script>' . "\n\n";

        echo '	<div id="venn"></div>' . "\n\n";
        echo '</div>' . "\n\n";

    	exit();

    }

}

?>