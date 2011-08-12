<?php
// Function to calculate Penman-Monteith estimate of reference evapotranspiration using inputs (and required units to be read into the function)
//of station type (limited to ASOS, AWOS, RAWS, and ECONet), daily avg SR in W/m2, daily max/min temp in degreesC,
//daily avg wind speed in m/s, daily min/max relative humidity in %, day of year, elevation in feet above sea level, and lat/lon in degrees.
//Please note: ASOS and AWOS networks do not report solar radiation so Hargreaves' solar radiation estimation technique is used.
//Also note: Wind speed is interpolated using a log profile relation from height of observation (10m for ASOS/AWOS) to a 2m level.

 function HargreavesRad_ET_estimate($stationtype,$sravg,$tempmax,$tempmin,$wsavg,$rhmax,$rhmin,$jday,$elev,$lat,$lon) {

        // Pre-defined constants/values
        $Gsc = 0.0820;  // Solar constant (in MJ m^-2 min^-1)
        $G = 0;  // Soil heat flux; set to zero because it can be ignored in 24-hour periods
        $sb = 4.903E-9;  // Stefan-Boltzman constant (in MJ K^-4 m^-2 day^-1)
        $a = 0.23;  // Albedo
        $k = 0.18; // Adjustment coefficient for Hargreaves solar radiation estimate
        $pi = pi(); // Pi
        
        // Convert elevation to meters, latitude to radians, and min/max temps to Kelvin.
        $elev_m = $elev*0.3048;
        $lat_rad = $lat*($pi/180);
        $tempmaxK = $tempmax+273.16;
        $tempminK = $tempmin+273.16;
        
        // Calculate station pressure (in kPa) as a function of elevation.
        $pres = 101.3*pow(((293-(0.0065*$elev_m))/293),5.26);
        
        // Calculate psychometric constant (in kPa/°C).
        $y = $pres*(0.665E-3);
        
        // Calculate saturation vapor pressure (in kPa) as a function of tempmax and tempmin.
        $Emax = 0.6108*exp((17.27*$tempmax)/($tempmax+237.3));
        $Emin = 0.6108*exp((17.27*$tempmin)/($tempmin+237.3));
        $Es = ($Emax+$Emin)/2;
        
        // Calculate actual vapor pressure (in kPa) as a function of relative humidity and saturation vapor pressure.
        $Ea = ((($rhmax/100)*$Emin) + (($rhmin/100)*$Emax))/2;
        
	// Calculate tempmean.
	$tempmean=($tempmax+$tempmin)/2;
        
        // Calculate slope of saturation vapor pressure curve as a function of mean temperature.
	$D = (4098*(0.6108*exp((17.27*$tempmean)/($tempmean+237.3))))/(pow($tempmean+237.3,2));
        
	// Calculate the inverse relative distance Earth-Sun, solar declination, sunset hour angle, and the extra-terrestrial radiation (in MJ m^-2 day^-1). 
        $Dr = 1+0.033*cos((2*$pi/365)*$jday);
        $Sd = 0.409*sin((2*$pi/365)*$jday-1.39);
        $Ws = acos((-tan($lat_rad))*(tan($Sd)));
        $Ra = (24*60/$pi)*$Gsc*$Dr*(($Ws*sin($lat_rad)*sin($Sd))+(cos($lat_rad)*cos($Sd)*sin($Ws)));
        
        // Calculate clear sky radiation (in MJ m^-2 day^-1).
        $Rso = (0.75+((2E-5)*$elev_m))*$Ra;
        
        // Calculate Hargreaves shortwave radiation estimate.
        $RsHAR = ($k*sqrt($tempmax-$tempmin))*$Ra;
	
	// Convert incoming measured solar radiation to the correct units (MJ/m2/day from W/m2).
        $Rs = $sravg*0.0864;
        
        // Calculate net shortwave radiation (if station type is ECONET/RAWS, use observed incoming SR) in MJ/m2/day and net longwave radiation in MJ/m2/day.
	// Use these for calculating net radiation estimate.
        // Also, note that upper limit of incoming to clear sky radiation ratio has been set to a value of 1. 
        if($stationtype=='AWOS' || $stationtype=='ASOS'){
		$Rns = (1-$a)*$RsHAR;
	}
	else {
		$Rns= (1-$a)*$Rs;
		}
        if ($stationtype=='AWOS' || $stationtype=='ASOS'){
		$Ratio = $RsHAR/$Rso;
		if($Ratio>1){
		$Ratio=1;
		}
		$Rnl = $sb*((pow($tempmaxK,4)+pow($tempminK,4))/2)*(0.34-0.14*sqrt($Ea))*((1.35*($Ratio))-0.35);
		}
		else {
		$Ratio= $Rs/$Rso;
		if($Ratio>1){
		$Ratio=1;
		}
		$Rnl = $sb*((pow($tempmaxK,4)+pow($tempminK,4))/2)*(0.34-0.14*sqrt($Ea))*((1.35*($Ratio))-0.35);
		}
        $R = $Rns - $Rnl;
	
	// Convert ASOS, AWOS, and ECONet 10m wind speed to 2m wind speed (exponential relationship based on equ 47 in FAO paper 56).
	If($stationtype=='ASOS' || $stationtype=='AWOS' || $stationtype=='ECONET'){
	$wsavg_2m = $wsavg*(4.87/log(672.58));
	}
        // Convert RAWS 6.1m wind speed to 2m wind speed (exponential relationship based on equ 47 in FAO paper 56).
	elseif($stationtype=='RAWS'){
	$wsavg_2m = $wsavg*(4.87/log(408.16));
	}
    
        // Use net daily radiation estimate to get the ensemble calculation of reference evapotranspiration (in mm day^-1). 
        $Part1 = (900*$wsavg_2m)/($tempmean+273);
        $Part2 = $Es-$Ea;
        $Part3 = 0.408*($R-$G);
        $Part4 = $D/($D+$y*(1+0.34*$wsavg_2m));
        $Part5 = $y/($D+$y*(1+0.34*$wsavg_2m));
        $ensemble1 = $Part3*$Part4;
        $ensemble2 = $Part1*$Part2*$Part5;
        $ETHARens = $ensemble1+$ensemble2;        
                
        // If the ensemble calculation of reference evapotranspiration is negative, set it to zero. Then, return reference ET estimate.
        if($ETHARens < 0)
            $ETHARens = 0;
	return($ETHARens);
 } // End of reference ET estimate function
?>