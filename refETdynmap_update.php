<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <script src="js/mapiconmaker.js" type="text/javascript"></script>
    <!--Change below API key to your key.-->
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=&sensor=false" type="text/javascript"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
</head>
<body>
<?php 
        //Get form inputs for the date specified by user. Then, make sure that requested date is between 2002-01-01 and yesterday.
        $startdate=strtotime($_REQUEST['date']);
        $yest = strtotime("-1 day");
        if(($startdate>=strtotime('2002-01-01')) && ($startdate<=$yest)){ ?>

<style type="text/css">
p.date {font-size:100%}
table#chart_table2{
 display:inline;
}
.wformat_3{
width: 275px;
height: 260px;
font-size: 14px;
}
div#mapcanvasTEST_ET {
	z-index: 0;
	width: 905px;
	height: 855px;
	border: 1px solid #000000;
	margin: 25px;
}
table#layer2 {
  z-index: 2;
  position: absolute;
  top: 55%;
  left: 75%;
  background-color: white;
  padding: 0px;
  
}

</style>
<link type="text/css" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.5/themes/base/jquery-ui.css" rel="stylesheet"/>
<script type="text/javascript" src="http://code.jquery.com/jquery-1.5.1.js"></script>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.15/jquery-ui.min.js"></script>
<script type="text/javascript">

//Function to set up the Javascript calendar.
$(function() {
	    $("#datepicker").datepicker({showOn: 'button',buttonImage: 'images/calendar.gif',buttonImageOnly: true, changeMonth: true, changeYear: true, dateFormat: 'yy-mm-dd', minDate: (new Date(2002, 1 - 1, 1)), maxDate: '-1D'});
});
</script>
<?php  
//Obtain metadata for displaying stations on Google map (below), and compute the reference ET for requested date. 

require_once( './cronos/cronos.php' );
require_once( './ETfunctionAPI.php' );

// Replace with your API key.
$c = new CRONOS( 'abc123' ); 

// Collect data from ECONET, RAWS, ASOS and AWOS networks for NC, SC, AL, FL, GA, and VA.
$results = $c->listStations( array( 'ECONET', 'RAWS', 'ASOS', 'AWOS' ), array( 'NC', 'SC', 'AL', 'FL', 'GA', 'VA' ), array(), array(), true );

// Collect the stations and metadata.
$stations = array();
$stninfo=array();

foreach( $results as $r ) {
  $stations[] = $r['station'];
  $stninfo[$r['station']]['elev'] = $r['elev'];
  $stninfo[$r['station']]['type'] = $r['network'];
  $stninfo[$r['station']]['lat'] = $r['lat'];
  $stninfo[$r['station']]['lon'] = $r['lon'];
  $stninfo[$r['station']]['name'] = $r['name'];
  $stninfo[$r['station']]['county'] = $r['county'];
  $stninfo[$r['station']]['city'] = $r['city'];
  $stninfo[$r['station']]['state'] = $r['state'];
  $stninfo[$r['station']]['startdate'] = $r['startdate'];
  $stninfo[$r['station']]['enddate'] = $r['enddate'];
}

// Define start and enddates.
$start=date('Y-m-d',strtotime($_REQUEST['date']));
$end=date('Y-m-d',strtotime($_REQUEST['date']));

// Get some data for requested date and stations.
$daily = $c->getDailyData( $stations, $start, $end );

// Compute the reference ET per station per day.
foreach( $daily as $d ) {
  
  // Format the day of year for reference ET estimate
  $doy=date('z',strtotime($d['ob']));
  $doy=$doy+1;
  
  // Exclude the six meteorological input parameters if any are NULL (ie. do not compute reference ET if input parameters are NULL). 
  // Also, include sravg!='' argument in this if statement for ECONET and RAWS networks which record solar radiation.
  if($stninfo[$d['station']]['type']=='ECONET' || $stninfo[$d['station']]['type']=='RAWS'){
  if($d['sravg']!='' && $d['tempmax']!='' && $d['tempmin']!='' && $d['wsavg']!='' && $d['rhmax']!='' && $d['rhmin']!=''){
  $stninfo[$d['station']]['etavg']=HargreavesRad_ET_estimate($stninfo[$d['station']]['type'],$d['sravg'],$d['tempmax'],$d['tempmin'],$d['wsavg'],$d['rhmax'],$d['rhmin'],$doy,$stninfo[$d['station']]['elev'],$stninfo[$d['station']]['lat'],$stninfo[$d['station']]['lon']);
  $stninfo[$d['station']]['etavg_inch']=($stninfo[$d['station']]['etavg']*0.03937007874);
  }
  }else{
  //exclude sravg!='' argument for ASOS/AWOS since this parameter is always NULL for those networks (they do not record solar radiation).
  if($d['tempmax']!='' && $d['tempmin']!='' && $d['wsavg']!='' && $d['rhmax']!='' && $d['rhmin']!=''){
  $stninfo[$d['station']]['etavg']=HargreavesRad_ET_estimate($stninfo[$d['station']]['type'],$d['sravg'],$d['tempmax'],$d['tempmin'],$d['wsavg'],$d['rhmax'],$d['rhmin'],$doy,$stninfo[$d['station']]['elev'],$stninfo[$d['station']]['lat'],$stninfo[$d['station']]['lon']);
  $stninfo[$d['station']]['etavg_inch']=($stninfo[$d['station']]['etavg']*0.03937007874);
  } 
  }

}
?>

<script type="text/javascript">
//If Gbrowser is compatible, create the map properties listed (map type, where to center it, user controls).
function initialize4() {
  if (GBrowserIsCompatible()) { 
var map = new GMap2(document.getElementById("mapcanvasTEST_ET"));
map.setUIToDefault();
map.setCenter(new GLatLng(31.8,-81.5), 6);
map.setMapType(G_PHYSICAL_MAP);
map.disableScrollWheelZoom();
map.disableDoubleClickZoom();

<?php
    //Break date into year, month, and day. This is used below to feed a year to chart product, and for formatting date to display on webpage.
    list($Y,$M,$D) = explode("-",$start);

$broked = array();
//Loop through results and put them into two seperate arrays, $station and $data, using the list function.
while(list($station,$data)=each($stninfo)){

  // If reference ET estimates are not between 0 and 10, do not show on map (ie. continue to next iteration of loop).
if( !array_key_exists( 'etavg', $data ) || $data['etavg']<=0 || $data['etavg']>10){ 
   continue;
   }
   ?>

//Create a new point with the station lat/lon.
var myLatLon = new GLatLng(<?php echo $data['lat']; ?>, <?php echo $data['lon']; ?>);

//Set up the marker icon properties (width, height, color, shape, etc) based on unit requessted. Colorize the marker based on the estimated reference ET value.
var iconOptions = {};
  iconOptions.width = 12;
  iconOptions.height = 12;
  iconOptions.primaryColor = <?php
  //Unit of mm.
  If($_REQUEST['unit']=='mm'){
    If($data['etavg']>=0.000 && $data['etavg']<=0.500){ ?>
      '#330066'; //dark purple
    <?php }
    elseif($data['etavg']>0.500 && $data['etavg']<=1.000){ ?>
      '#800080'; //purple
    <?php }
    elseif($data['etavg']>1.000 && $data['etavg']<=1.500){ ?>
      '#9900CC'; //lt purple 
    <?php }
    elseif($data['etavg']>1.500 && $data['etavg']<=2.000){ ?>
      '#9966FF';  //purpley blue
    <?php }
    elseif($data['etavg']>2.000 && $data['etavg']<=2.500){ ?>
      '#0000FF'; //blue
    <?php }
    elseif($data['etavg']>2.500 && $data['etavg']<=3.000){ ?>
      '#76A4FB'; //lt blue
    <?php }
    elseif($data['etavg']>3.000 && $data['etavg']<=3.500){ ?>
      '#00CC99';  //greenish-blue
    <?php }
    elseif($data['etavg']>3.500 && $data['etavg']<=4.000){ ?>
      '#00CC00'; //lime green
    <?php }
    elseif($data['etavg']>4.000 && $data['etavg']<=4.500){ ?>
      '#66FF33'; //lt lime green
    <?php }
    elseif($data['etavg']>4.500 && $data['etavg']<=5.000){ ?>
      '#FFFF33'; //yellow
    <?php }
    elseif($data['etavg']>5.000 && $data['etavg']<=5.500){ ?>
      '#FFCC33'; //lt orange
    <?php }
    elseif($data['etavg']>5.500 && $data['etavg']<=6.000){ ?>
      '#FF9900'; //orange
    <?php }
    elseif($data['etavg']>6.000 && $data['etavg']<=6.500){ ?>
      '#FF0000'; //red
    <?php }
    elseif($data['etavg']>6.500 && $data['etavg']<=7.000){ ?>
      '#FF6699'; //lt pink
    <?php }
    elseif($data['etavg']>7.000 && $data['etavg']<=7.500){ ?>
      '#FF0066'; //pink 
    <?php }
    elseif($data['etavg']>7.500){ ?>
      '#990033'; //dark pink
    <?php }
  }
  //Unit of inches.
  elseif($_REQUEST['unit']=='inches'){
    If($data['etavg_inch']>=0.00 && $data['etavg_inch']<=0.02){ ?>
      '#330066'; //dark purple
    <?php }
    elseif($data['etavg_inch']>0.02 && $data['etavg_inch']<=0.04){ ?>
      '#800080'; //purple
    <?php }
    elseif($data['etavg_inch']>0.04 && $data['etavg_inch']<=0.06){ ?>
      '#9900CC'; //lt purple 
    <?php }
    elseif($data['etavg_inch']>0.06 && $data['etavg_inch']<=0.08){ ?>
      '#9966FF';  //purpley blue
    <?php }
    elseif($data['etavg_inch']>0.08 && $data['etavg_inch']<=0.10){ ?>
      '#0000FF'; //blue
    <?php }
    elseif($data['etavg_inch']>0.10 && $data['etavg_inch']<=0.12){ ?>
      '#76A4FB'; //lt blue
    <?php }
    elseif($data['etavg_inch']>0.12 && $data['etavg_inch']<=0.14){ ?>
      '#00CC99';  //greenish-blue
    <?php }
    elseif($data['etavg_inch']>0.14 && $data['etavg_inch']<=0.16){ ?>
      '#00CC00'; //lime green
    <?php }
    elseif($data['etavg_inch']>0.16 && $data['etavg_inch']<=0.18){ ?>
      '#66FF33'; //lt lime green
    <?php }
    elseif($data['etavg_inch']>0.18 && $data['etavg_inch']<=0.20){ ?>
      '#FFFF33'; //yellow
    <?php }
    elseif($data['etavg_inch']>0.20 && $data['etavg_inch']<=0.22){ ?>
      '#FFCC33'; //lt orange
    <?php }
    elseif($data['etavg_inch']>0.22 && $data['etavg_inch']<=0.24){ ?>
      '#FF9900'; //orange
    <?php }
    elseif($data['etavg_inch']>0.24 && $data['etavg_inch']<=0.26){ ?>
      '#FF0000'; //red
    <?php }
    elseif($data['etavg_inch']>0.26 && $data['etavg_inch']<=0.28){ ?>
      '#FF6699'; //lt pink
    <?php }
    elseif($data['etavg_inch']>0.28 && $data['etavg_inch']<=0.30){ ?>
      '#FF0066'; //pink 
    <?php }
    elseif($data['etavg_inch']>0.30){ ?>
      '#990033'; //dark pink
    <?php }
  }?>
  
  iconOptions.label = "";
  iconOptions.labelSize = 0;
  iconOptions.labelColor = '#000000';
  iconOptions.shape = "roundrect";
  
  //Create a new variable for the above marker specifications.
  var icon = MapIconMaker.createFlatIcon(iconOptions);

  //Function to create a clickable marker and open an Info Window at each marker. 
  //Each marker contains station metadata, the reference ET value, a link to explain station type, 
  //a link to display the annual chart, and a link to obtain more information for that station from the CRONOS page hosted by the NC State Climate Office.
  function create<?php echo $station;?>Marker(myLatLon) {

  //Set up our GMarkerOptions object
  markerOptionsThree = { icon:icon };
  var marker = new GMarker(myLatLon, markerOptionsThree);
  marker.station = "<?php echo $station;?>";
  GEvent.addListener(marker, "click", function() {
    marker.openInfoWindowHtml("<div class='wformat_3'><?php echo "<center><b><u>Daily ET Value:</u></b>";
      If($_REQUEST['unit']=='mm'){
          $data['etavg']=sprintf("%6.1f",$data['etavg']);
	   echo $data['etavg'];
	  }
	  else{
          $data['etavg_inch']=sprintf("%6.2f",$data['etavg_inch']);
	   echo $data['etavg_inch'];
	  }
    	  If($_REQUEST['unit']=='mm'){
	    echo " mm";
	  }
	  elseif($_REQUEST['unit']=='inches'){
	    echo " inches";
	  }
echo "<br><br><form action='refETdynchart.php?station=".$station."&year=".$Y."&unit=".$_REQUEST['unit']."' method='post' target='_blank'><input type='submit' name='day' value='View timeseries'></form></center>";
echo "<hr><b><u>Station Information:</u></b>"; echo "<br><br><b>Name: </b>"; echo $data['name']; echo " ("; echo $station; echo ")"; echo "<br><b>Location: </b>"; echo $data['city'].", ".$data['state']; echo "<br><b>Elevation: </b>"; echo $data['elev']; echo " feet above sea level"; echo "<br><b>Type: </b>"; echo $data['type']; 
echo " <A href=# onClick=window.open('http://www.nc-climate.ncsu.edu/dynamic_scripts/cronos/types.php','meta_info','width=500,height=1000,scrollbars=yes,resizable=yes')>what does this mean?</A>"; echo "<br><b>Start Date: </b>"; echo $data['startdate']; echo "<br><b>End Date: </b>"; echo $data['enddate']; echo "<br><form action='http://www.nc-climate.ncsu.edu/cronos/?station=".$station."' method='post' target='_blank'><input type='submit' name='more_data' value='More data for this station'></form></div>";?>
    ");
  });
  return marker;
}

//Add the stations to the map.
  map.addOverlay(create<?php echo $station;?>Marker(myLatLon));
  <?php
  } ?> //ends the while loop.
   }  //Ends the if GBrowserIsCompatible statement.   
 } //Ends the function initialize4.

//Execute onload and onunload here instead of in the body tag (see Google Maps API example).
if(window.addEventListener){
	window.addEventListener("load",initialize4,false);
}
else{
	window.attachEvent("onload",initialize4);	
}
if(window.addEventListener){
 window.addEventListener( "unload", GUnload, false ); 
} 
else {
 window.attachEvent( "onunload", GUnload ); 
}
</script>
<!--Display the date specified and and give the option to show another date.-->
<form action="refETdynmap.php" method="get">
<p><b><i>Select another date and unit of interest:</b></i></p>
                  <div class="demo">
                   <p>Date: <input type="text" name="date" id="datepicker" size="30"/>
                    &nbsp&nbsp&nbsp Unit:
                  <select name="unit">
                    <option value="inches">inches</option>
                    <option value="mm">mm</option>
                  </select>
                  &nbsp&nbsp&nbsp&nbsp<input type="submit" value="Submit" class="button" /> &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<A href='http://www.nc-climate.ncsu.edu/et'>Back to Main Page</A>
                   <br> &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp (YYYY-MM-DD)
                  </p></div>
</form><hr width=95%><center><p class="date"><b><?php
                //Break date into year, month, and day.
		list($Y,$M,$D) = explode("-",$start);
		  $next=date('Y-m-d',strtotime($start." +1 day"));
		  $prev=date('Y-m-d',strtotime($start." -1 day"));
		  echo "<A href='refETdynmap.php?date=".$prev."&unit=".$_REQUEST['unit']."','prev_date')>Previous day</A>";
		$ET_date = date("F j, Y", mktime(0,0,0,$M,$D,$Y));
	          echo "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp ".$ET_date."&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp";
                  echo "<A href='refETdynmap.php?date=".$next."&unit=".$_REQUEST['unit']."','next_date')>Next day</A>";?></b></p>
    <div id="mapcanvasTEST_ET"></div></center>
    <?php
  //Set up color key for unit of mm.
      If($_REQUEST['unit']=='mm'){?>
      <table id="layer2" border="1">
      <tr>
      <th><center>ET value<br>(mm)</center></th>
      <th>Color</th>
      </tr>
      <tr>
      <td>0.0-0.5</td>
      <td bgcolor='#330066'></td>
      </tr>
      <tr>
      <td>0.5-1.0</td>
      <td bgcolor='#800080'></td>
      </tr>
      <tr>
      <td>1.0-1.5</td>
      <td bgcolor='#9900CC'></td>
      </tr>
      <tr>
      <td>1.5-2.0</td>
      <td bgcolor='#9966FF'></td>
      </tr>
      <tr>
      <td>2.0-2.5</td>
      <td bgcolor='#0000FF'></td>
      </tr>
      <tr>
      <td>2.5-3.0</td>
      <td bgcolor='#76A4FB'></td>
      </tr>
      <tr>
      <td>3.0-3.5</td>
      <td bgcolor='#00CC99'></td>
      </tr>
      <tr>
      <td>3.5-4.0</td>
      <td bgcolor='#00CC00'></td>
      </tr>
      <tr>
      <td>4.0-4.5</td>
      <td bgcolor='#66FF33'></td>
      </tr>
      <tr>
      <td>4.5-5.0</td>
      <td bgcolor='#FFFF33'></td>
      </tr>
      <tr>
      <td>5.0-5.5</td>
      <td bgcolor='#FFCC33'></td>
      </tr>
      <tr>
      <td>5.5-6.0</td>
      <td bgcolor='#FF9900'></td>
      </tr>
      <tr>
      <td>6.0-6.5</td>
      <td bgcolor='#FF0000'></td>
      </tr>
      <tr>
      <td>6.5-7.0</td>
      <td bgcolor='#FF6699'></td>
      </tr>
      <tr>
      <td>7.0-7.5</td>
      <td bgcolor='#FF0066'></td>
      </tr>
      <tr>
      <td>> 7.5</td>
      <td bgcolor='#990033'></td>
      </tr>
      </table>
      <?php }
  //Set up color key for unit of inches.
      elseif($_REQUEST['unit']=='inches'){ ?>
      <table id="layer2" border="1">
      <tr>
      <th><center>ET value<br>(inches)</center></th>
      <th>Color</th>
      </tr>
      <tr>
      <td>0.00-0.02</td>
      <td bgcolor='#330066'></td>
      </tr>
      <tr>
      <td>0.02-0.04</td>
      <td bgcolor='#800080'></td>
      </tr>
      <tr>
      <td>0.04-0.06</td>
      <td bgcolor='#9900CC'></td>
      </tr>
      <tr>
      <td>0.06-0.08</td>
      <td bgcolor='#9966FF'></td>
      </tr>
      <tr>
      <td>0.08-0.10</td>
      <td bgcolor='#0000FF'></td>
      </tr>
      <tr>
      <td>0.10-0.12</td>
      <td bgcolor='#76A4FB'></td>
      </tr>
      <tr>
      <td>0.12-0.14</td>
      <td bgcolor='#00CC99'></td>
      </tr>
      <tr>
      <td>0.14-0.16</td>
      <td bgcolor='#00CC00'></td>
      </tr>
      <tr>
      <td>0.16-0.18</td>
      <td bgcolor='#66FF33'></td>
      </tr>
      <tr>
      <td>0.18-0.20</td>
      <td bgcolor='#FFFF33'></td>
      </tr>
      <tr>
      <td>0.20-0.22</td>
      <td bgcolor='#FFCC33'></td>
      </tr>
      <tr>
      <td>0.22-0.24</td>
      <td bgcolor='#FF9900'></td>
      </tr>
      <tr>
      <td>0.24-0.26</td>
      <td bgcolor='#FF0000'></td>
      </tr>
      <tr>
      <td>0.26-0.28</td>
      <td bgcolor='#FF6699'></td>
      </tr>
      <tr>
      <td>0.28-0.30</td>
      <td bgcolor='#FF0066'></td>
      </tr>
      <tr>
      <td> > 0.30</td>
      <td bgcolor='#990033'></td>
      </tr>
      </table>
      <?php } 
     } 
     // If requested date is not between 2002-01-01 and yesterday, report this error message.
     else{
        echo "<p><b><i><font size='4'>***Please go back and select a date between January 1, 2002 and yesterday.</i></b></p></font>";
     } ?>
</body>
</html>