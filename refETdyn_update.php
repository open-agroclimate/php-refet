<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <!--Change below API key to your key or include in your own passwords.php file.-->
    <?php require_once('./passwords.php');?>
    <script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=<?php echo $JSapiKey;?>&sensor=false"></script>
    <!-- The scripts below are required to get the datepicker to work. These will need to be updated periodically.-->
  <link href="http://jqueryui.com/resources/demos/style.css" rel="stylesheet" />
  <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
  <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
  <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
  <style type="text/css">
.infowindow3{
	font-size: 14px;
}
div#mapcanvasTEST_ET {
	z-index: 0;
	width: 905px;
	height: 855px;
	border: 1px solid #000000;
	margin: 25px;
}
</style>
<script type="text/javascript">

//Function to set up the Javascript calendar.
  $(function() {
	    $("#datepicker").datepicker({showOn: 'both', buttonImage: 'images/calendar.gif', buttonImageOnly: true, changeMonth: true, changeYear: true, dateFormat: 'yy-mm-dd', minDate: (new Date(2002, 1 - 1, 1)), maxDate: '-1D'});
  });

//Set up the map and its properties (location, type, and user controls).
function initialize3() {
  var map = new google.maps.Map(document.getElementById("mapcanvasTEST_ET"), {
  center: new google.maps.LatLng(31.8,-81.5), 
  zoom: 6,
  mapTypeId: google.maps.MapTypeId.TERRAIN
  });
  
  <?php
//Obtain metadata for displaying stations on Google map (below) or include in your own passwords.php file..
require_once( './cronos.php' );

// Replace with your API key.
$c = new CRONOS( $cronosAPIkey ); 

// Collect data from ECONET, RAWS, ASOS and AWOS networks for NC, SC, AL, FL, GA, and VA.
$results = $c->listStations( array( 'ECONET', 'RAWS', 'ASOS', 'AWOS' ), array( 'NC', 'SC', 'AL', 'FL', 'GA', 'VA' ), array(), array(), true );

$stninfo=array();
$count=1;

$total=count($results);

//loop through results
foreach( $results as $r ) {
  $stninfo = $r;
  //specific formatting for certain elements (e.g. remove apostrophes from name and city)
  $stninfo['elev'] = $r['elev(ft)'];
  $stninfo['type'] = $r['network'];
  $stninfo['startdate']=date("F j, Y", strtotime($r['startdate']));
  $stninfo['enddate'] = date("F j, Y", strtotime($r['enddate']));
  $stninfo['name'] = str_replace("'", "", $r['name']);
  $stninfo['city'] = str_replace("'", "", $r['city']);
  
  //run this is counter is less than total number of results from above array
  if($count<=$total){ ?>
  var infoWindow;
  //Create a new point with the station lat/lon.
  var myLatLon = 
    new google.maps.LatLng(<?php echo $stninfo['lat'];?> , <?php echo $stninfo['lon'];?>);

  //Function to create a clickable marker and open an Info Window at each marker. 
  //Each marker has station metadata and a link to explain station type.
  function create<?php echo trim($stninfo['station']);?>Marker(myLatLon){
  //Set up the marker icon properties (width, height, color, shape, etc).  
  var circlesymb = {
      path: google.maps.SymbolPath.CIRCLE,
      fillColor: "black",
      fillOpacity: 1,
      strokeColor: "black",
      strokeWeight: 4
   };
  var marker = new google.maps.Marker({ 
      position: myLatLon,
      icon: circlesymb,
      map: map 
   });
  //set up infowindow
  google.maps.event.addListener(marker,'click',function(){
    var contentString = '<div class="infowindow3">' +
    '<p style="font-weight:bold;font-size:14px;text-decoration:underline;text-align:center;">' +
    'Station Information:</p><p style="text-align:left;"><b>Name: </b><?php echo $stninfo['name'];?>' +
    ' (<?php echo trim($stninfo['station']);?>)<br><b>Location: </b> <?php echo $stninfo['city'];?>' +
    ', <?php echo $stninfo['state'];?> <br><b>Elevation: </b> <?php echo $stninfo['elev'];?>' +
    ' feet above sea level<br><b>Type: </b> <?php echo $stninfo['type'];?>' +
    ' <A href=# onClick=window.open' +
    '("http://www.nc-climate.ncsu.edu/dynamic_scripts/cronos/types.php",' +
    '"meta_info","width=500,height=1000,scrollbars=yes,resizable=yes")>' +
    'what does this mean?</A><br><b>Start Date: </b> <?php echo $stninfo['startdate'];?>' +
    '<br><b>End Date: </b> <?php echo $stninfo['enddate'];?></p></div>';
    
  if(infoWindow){
  infoWindow.close();
  }
  infoWindow = new google.maps.InfoWindow();
  infoWindow.setContent(contentString);
  infoWindow.open(map,marker);
    
  }); //end event listener
  return marker;
  } //end function createMarker
  
  //Add the stations to the map.
  create<?php echo trim($stninfo['station']);?>Marker(myLatLon).setMap(map);
  <?php
  } //end if statement
  $count++;
  } ?> //ends the foreach loop.
 } //Ends function initialize3

google.maps.event.addDomListener(window, 'load', initialize3);
</script>
</head>
<body>
<form action="refETdynmap.php" method="get">
<p><b><i>Please select your date and unit of interest.</b></i></p>
                  <div class="demo">
                   <p>Date: <input type="text" name="date" id="datepicker" size="30"/>
                    &nbsp&nbsp&nbsp Unit:
                  <select name="unit">
                    <option value="inches">inches</option>
                    <option value="mm">mm</option>
                  </select>
                  &nbsp&nbsp&nbsp&nbsp<input type="submit" value="Submit" class="button" />
            &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp<A href='http://www.nc-climate.ncsu.edu/et'>Back to Main Page</A>
                   <br> &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp (YYYY-MM-DD)
                  </p></div>
                  <hr width=95%>
                  <center><div id="mapcanvasTEST_ET"></div></center>
</form>
</body>
</html>