<?php
	session_start();
	
	$A = 1000;
	$B = 700;
	$C = 700;
	$AB = 150;
	$AC = 300;
	$BC = 60;
	
	$n1 = sqrt($A/pi());
	$n2 = sqrt($B/pi());
	$n3 = sqrt($C/pi());
	$n4 = $AB;
	$n5 = $AC;
	$n6 = $BC;
	
	//////////////////////////////////////////////
	// Define Functions //////////////////////////
	//////////////////////////////////////////////
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
			//return 5 - 3*$x;
		}
	}
	// Solve the equation (Using the method of bisection)
	if(!function_exists('solve_function_1')) {
		function solve_function_1($a, $b, $y, $z, $yz){
			$lower = equation_1($a, $y, $z, $yz);
			$upper = equation_1($b, $y, $z, $yz);
			$middle = equation_1(($a+$b)/2, $y, $z, $yz);
			echo $lower.'------'.$upper.'<br>';
			if (abs($upper - $lower) < 0.000001){
				$_SESSION['result'] = $a;
				echo $b;
			} else {
				if ($middle * $lower <= 0){$a=$a; $b=($a+$b)/2; solve_function_1($a, $b, $y, $z, $yz);}
				else {$b=$b; $a=($a+$b)/2; solve_function_1($a, $b, $y, $z, $yz);}
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
				echo $b;
			} else {
				if ($middle * $lower <= 0){$a=$a; $b=($a+$b)/2; solve_function_2($a, $b, $y, $z, $yz);}
				else {$b=$b; $a=($a+$b)/2; solve_function_2($a, $b, $y, $z, $yz);}
			}
		}
	}
	
	echo area_check_center($n1, $n2); 
	echo '<br>';
	solve_function_2(0,asin($n2/$n1)-0.01, $n1, $n2, $n4);
	//exit();

	
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
	

	
	echo '<br>-----------------------<br>';
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
	
	
	
	$o1o2 = get_centers_line($n1, $n2, $n4);
	$o1o3 = get_centers_line($n1, $n3, $n5);
	$o2o3 = get_centers_line($n2, $n3, $n6);
	
	//$test = $_SESSION['centers_line']; 
	echo '<br>o1o2: '.$o1o2.'<br>o1o3: '.$o1o3.'<br>o2o3: '.$o2o3;
	
	// Figure out angle-O2O1O13, then we can get the position for O3
	$angle_o2o1o3 = acos( (pow($o1o2, 2) + pow($o1o3, 2) - pow($o2o3, 2))/(2 * $o1o2 * $o1o3) );
	echo '<br> o2o1o3: '.$angle_o2o1o3;
	
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
	echo '<br>'.$index;

	exit();
?>
<?php 
	

?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Untitled Document</title>
</head>

<body>

</body>
</html>