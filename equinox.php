<?php
// CLASS EquiSol
// Compute date and times of equinoxes and soltices at a given year and timezone
// valid for years range 1000-3000
// Algorithms taken from Meeus Astronomical Algorithms, 2nd edition


/*

USAGE :

$equisol = new EquiSol();

//set timezone (optionnal)
//if not set, the class will be using UTC
$equisol->set_Timezone('Europe/Paris');

//set year (optionnal)
//if not set, the class will be using current server year
$equisol->set_Year(2022);
	
//get spring date
$springDate = $equisol->get_spring();

//or you can also pass desired year
$springDate = $equisol->get_spring(2054);

print_r($springDate);

//	DateTime Object
//	(
//   	 [date] => 2054-03-20 10:35:41.000000
//  	 [timezone_type] => 3
//   	 [timezone] => Europe/Paris
//	)


//you can also use
// $equisol->get_summer();
// $equisol->get_autumn();
// $equisol->get_winter();

//also
// $equisol->get_4season();

*/


class EquiSol{
	
	protected $timezone;
	protected $year;

	public function __construct() {
		
    		///default values for timezone and year
		$this->timezone= 'UTC';
		$this->year = date('Y');
	}

	public function set_Timezone($timezone){
		$this->timezone = $timezone;
	}

	public function set_Year($year){
		$this->year = $year;
	}

	///////// get all 4 season's date 
	public function get_4season($year=false){

  		/// use either passed, declared (or default) year
		if($year!=false) $y = $year; 
			else $y = $this->year; 

		return array(
			'spring' => $this->compute(0, $y),
			'summer' => $this->compute(1, $y),
			'autumn' => $this->compute(2, $y),
			'winter' => $this->compute(3, $y)
		);
	}


	///////// get SPRING date of a given year
	public function get_spring($year = false){

		return $this->getOneSeasonDate(0, $year);
	}

	///////// get SUMMER date of a given year
	public function get_summer($year = false){

		return $this->getOneSeasonDate(1, $year);
	}

	///////// get AUTUMN date of a given year
	public function get_autumn($year = false){

		return $this->getOneSeasonDate(2, $year);
	}

	///////// get WINTER date of a given year
	public function get_winter($year = false){

		return $this->getOneSeasonDate(3, $year);
	}
	
	///////// get season date from id
	///////// 0 = spring, 1 = summer, 2 = autumn, 3 = winter
	private function getOneSeasonDate($seasonID, $year){

		/// use either passed, declared (or default) year
		if($year!=false) $y = $year; 
			else $y = $this->year; 

		return $this->compute($seasonID, $y);
	}


	/////////////////////////////////////////
	/////////[ COMPUTING FUNCTIONS ]/////////
	/////////////////////////////////////////

	///////// compute event (Equiniox or Solstice)
	///////// Meeus Astronmical Algorithms Chapter 27
	private function compute( $i, $year ) {

		//find equinox or soltice mean time
		$jde0 = $this->equiSolMeanTime( $i, $year);

		//find delta 
		$T = ( $jde0 - 2451545.0) / 36525;
		$W = 35999.373*$T - 2.47;
		$dL = 1 + 0.0334*cos(deg2rad($W)) + 0.0007*cos(deg2rad(2*$W));

		//calculate the sum S of the 24 periodic terms
		$S = $this->periodicTerms24($T);

		//UT julian result
		$JDE = $jde0 + ( (0.00001*$S) / $dL ); 

		//convert ut jd to local jd
		$localJD = $this->jd2local($JDE);

		// convert local jd to php date obj
		$dateOBJ = $this->julianToDateObj($localJD);

		return $dateOBJ;
	} 


	///////// julian date to php DateTime 
	private function julianToDateObj($jd){

		$d = $this->julian2date($jd);
		$date = new DateTime($d['year'] ."-". $d['month'] ."-". $d['day'] ."T". $d['hour'] .":". $d['minute'] .":". $d['second'], 
			new DateTimeZone($this->timezone));

		return $date;
	}

	///////// Convert result JD to defined timezone
	private function jd2local($utJdate){

		// if defined timezone is different from UTC
		// convert ut to local
		if($this->timezone!='UTC'){

			return $this->utJulian2Local($utJdate);

		}else{ //if timezone is UTC

			return $utJdate;

		}//if($this->timezone!='UTC'){
	}


	///////// equinox or soltice mean time
	private function equiSolMeanTime( $k, $year ) { 

		$y=($year-2000)/1000;
		switch( $k ) {
			case 0: 
		  		//spring
		  		return 2451623.80984 + 365242.37404*$y + 0.05169*pow($y,2) - 0.00411*pow($y,3) - 0.00057*pow($y,4); 
		  	break;
		  	case 1: 
		  		//summer
		  		return  2451716.56767 + 365241.62603*$y + 0.00325*pow($y,2) + 0.00888*pow($y,3) - 0.00030*pow($y,4);
		  	break;
		  	case 2:
		  		//autumn 
		  		return  2451810.21715 + 365242.01767*$y - 0.11575*pow($y,2) + 0.00337*pow($y,3) + 0.00078*pow($y,4); 
		  	break;
		  	case 3:
		  		//winter 
		  		return  2451900.05952 + 365242.74049*$y - 0.06223*pow($y,2) - 0.00823*pow($y,3) + 0.00032*pow($y,4); 
		  	break;
		}
	} 



	///////// Calculate 24 Periodic Terms
	///////// Meeus Astronmical Algorithms page 179 Table 27.C
	private function periodicTerms24( $t ) {

		$a = array(485,203,199,182,156,136,77,74,70,58,52,50,45,44,29,18,17,16,14,12,12,12,9,8);
		$b = array(324.96,337.23,342.08,27.85,73.14,171.52,222.54,296.72,243.58,119.81,297.17,21.02,
				247.54,325.15,60.93,155.12,288.79,198.04,199.76,95.39,287.11,320.81,227.73,15.45);
		$c = array(1934.136,32964.467,20.186,445267.112,45036.886,22518.443,
				65928.934,3034.906,9037.513,33718.147,150.678,2281.226,
				29929.562,31555.956,4443.417,67555.328,4562.452,62894.029,
				31436.921,14577.848,31931.756,34777.259,1222.114,16859.074);

		$s = 0;
		for( $i=0; $i<24; $i++ ) { 
			$s += $a[$i]*cos(deg2rad( $b[$i] + ($c[$i]*$t) )); 
		}

		return $s;
	}

	///////// convert julian to date 
	private function julian2date($julian) {

		$julian += 0.5;             
		$hms = ($julian - floor($julian)) * 86400.0;
		$a = floor($julian) + 1 + floor(($julian - 1867216.25) / 36524.25) - floor((($julian - 1867216.25) / 36524.25) / 4) + 1524;
		$b = floor(($a - 122.1) / 365.25);
		$c = floor(365.25 * $b);
		$d  = floor(($a - $c) / 30.6001);
		$month = floor(($d < 14) ? ($d - 1) : ($d - 13));
		$year = floor(($month > 2) ? ($b - 4716) : ($b - 4715));
		$day = floor($a - $c - floor(30.6001 * $d) + ($julian - floor($julian)));
		$hour = floor($hms / 3600);
		$minute = floor(($hms / 60) % 60);
		$second	= floor($hms % 60);
		
		$date = compact('year', 'month', 'day', 'hour', 'minute', 'second');
		
		return $date;
	}


	/////// Convert ut Julian date to local julian date
	private function utJulian2Local($utJulianDate, $timeZone = false){

		if($timeZone==false)
			$timeZone = $this->timezone;
	 
		$oneJulianHour = '0.04167';
		$offsetFromUt = $this->getTimeZoneOffsetFromUt($timeZone, $utJulianDate);
		
		if($offsetFromUt>0){ 
			return $utJulianDate+($offsetFromUt*$oneJulianHour);
		}elseif($offsetFromUt<0){ 
			return $utJulianDate-(abs($offsetFromUt)*$oneJulianHour);
		}else{ 
			return $utJulianDate; 
		}
	}

	/////// Get timezone offset
	private function getTimeZoneOffsetFromUt($originTz, $julian) {

	    $originDtz = new DateTimeZone($originTz);
	    $remoteDtz = new DateTimeZone('UTC');
	    $dateArray=$this->julian2date($julian);
	    $date = $dateArray['year'] . "-" . $dateArray['month'] . "-" . $dateArray['day'] . " " . $dateArray['hour'] . ":" . $dateArray['minute'] . ":" . $dateArray['second'];
	    $originDt = new DateTime($date, $originDtz);
	    $remoteDt = new DateTime($date, $remoteDtz);
	    $offset = $originDtz->getOffset($originDt) - $remoteDtz->getOffset($remoteDt);
	    return ($offset/60)/60;
	}
}
















