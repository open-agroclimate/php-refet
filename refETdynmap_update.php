<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<script src="js/mapiconmaker.js" type="text/javascript"></script>
<!--Change below API key to your key or include in your own passwords.php file.-->
<?php require_once('./passwords.php');?>
<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?key=<?php echo $JSapiKey;?>&sensor=false"></script>
<link href="http://jqueryui.com/resources/demos/style.css" rel="stylesheet" />
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>


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
<?php 
        //Get form inputs for the date specified by user. Then, make sure that requested date is between 2002-01-01 and yesterday.
        $startdate=strtotime($_REQUEST['date']);
        $yest = strtotime("-1 day");
        if(($startdate>=strtotime('2002-01-01')) && ($startdate<=$yest)){ ?>

<script type="text/javascript">
//Function to set up the Javascript calendar.
$(function() {
	    $("#datepicker").datepicker({showOn: 'button',buttonImage: 'images/calendar.gif',buttonImageOnly: true, changeMonth: true, changeYear: true, dateFormat: 'yy-mm-dd', minDate: (new Date(2002, 1 - 1, 1)), maxDate: '-1D'});
});

//Set up the map and its properties (map type, where to center it, user controls).
function initialize4() {

   var map = new google.maps.Map(document.getElementById("mapcanvasTEST_ET"), {
   center: new google.maps.LatLng(31.8,-81.5),
   zoom: 6,
   mapTypeId: google.maps.MapTypeId.TERRAIN
  }); 

<?php  
//Obtain metadata for displaying stations on Google map (below), and compute the reference ET for requested date. 
require_once( './cronos.php' );
require_once( './ETfunctionAPI.php' );

// Replace with your API key or include in your own passwords.php file.
$c = new CRONOS( $cronosAPIkey ); 

// Collect data from ECONET, RAWS, ASOS and AWOS networks for NC, SC, AL, FL, GA, and VA.
$results = $c->listStations( array( 'ECONET', 'RAWS', 'ASOS', 'AWOS' ), array( 'NC', 'SC', 'AL', 'FL', 'GA', 'VA' ), array(), array(), true );

// Collect the stations and metadata.
$stations = array();
$stninfo=array();

foreach( $results as $r ) {
  $stations[] = $r['station'];
  $stninfo[$r['station']] = $r;
  //specific formatting for certain elements (e.g. remove apostrophes from name and city)
  $stninfo[$r['station']]['elev'] = $r['elev(ft)'];
  $stninfo[$r['station']]['type'] = $r['network'];
  $stninfo[$r['station']]['name'] = str_replace("'", "", $r['name']);
  $stninfo[$r['station']]['city'] = str_replace("'", "", $r['city']);
  
} //end foreach

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
} //end foreach

    //Break date into year, month, and day. This is used below to feed a year to chart product, and for formatting date for form in infoWindow.
    list($Y,$M,$D) = explode("-",$start);

//Loop through results and put them into two separate arrays, $station and $value, using the list function.
while(list($station,$value)=each($stninfo)){

  // If reference ET estimates are not between 0 and 10, do not show on map (ie. continue to next iteration of loop).
if( !array_key_exists( 'etavg', $value ) || $value['etavg']<=0 || $value['etavg']>10){ 
   continue;
   } ?>

  var infoWindow;
  //Create a new point with the station lat/lon.
  var myLatLon = 
    new google.maps.LatLng(<?php echo $value['lat'];?> , <?php echo $value['lon'];?>);

   //Function to create a clickable marker and open an Info Window at each marker. 
  //Each marker has station metadata the reference ET value, a link to explain station type, 
  //a link to display the annual chart, and a link to obtain more information for that station from the CRONOS page hosted by the NC State Climate Office.
  function create<?php echo trim($station);?>Marker(myLatLon){
  //Set up the marker icon properties (width, height, color, shape, etc) based on unit requested. Colorize the marker based on the estimated reference ET value.
  var circlesymb = {
      path: google.maps.SymbolPath.CIRCLE,
      fillOpacity: 1,
      strokeWeight: 4,
   <?php
  //Unit of mm.
  If($_REQUEST['unit']=='mm'){
    If($value['etavg']>=0.000 && $value['etavg']<=0.500){ ?>
      fillColor: '#330066', //dark purple
      strokeColor: '#330066' //dark purple
    <?php }
    elseif($value['etavg']>0.500 && $value['etavg']<=1.000){ ?>
      fillColor: '#800080', //purple
      strokeColor: '#800080' //purple
    <?php }
    elseif($value['etavg']>1.000 && $value['etavg']<=1.500){ ?>
      fillColor: '#9900CC', //lt purple 
      strokeColor: '#9900CC' //lt purple 
    <?php }
    elseif($value['etavg']>1.500 && $value['etavg']<=2.000){ ?>
      fillColor: '#9966FF',  //purpley blue
      strokeColor: '#9966FF'  //purpley blue
    <?php }
    elseif($value['etavg']>2.000 && $value['etavg']<=2.500){ ?>
      fillColor: '#0000FF', //blue
      strokeColor: '#0000FF' //blue
    <?php }
    elseif($value['etavg']>2.500 && $value['etavg']<=3.000){ ?>
      fillColor: '#76A4FB', //lt blue
      strokeColor: '#76A4FB' //lt blue
    <?php }
    elseif($value['etavg']>3.000 && $value['etavg']<=3.500){ ?>
      fillColor: '#00CC99',  //greenish-blue
      strokeColor: '#00CC99'  //greenish-blue
    <?php }
    elseif($value['etavg']>3.500 && $value['etavg']<=4.000){ ?>
      fillColor: '#00CC00', //lime green
      strokeColor: '#00CC00' //lime green
    <?php }
    elseif($value['etavg']>4.000 && $value['etavg']<=4.500){ ?>
      fillColor: '#66FF33', //lt lime green
      strokeColor: '#66FF33' //lt lime green
    <?php }
    elseif($value['etavg']>4.500 && $value['etavg']<=5.000){ ?>
      fillColor: '#FFFF33', //yellow
      strokeColor: '#FFFF33' //yellow
    <?php }
    elseif($value['etavg']>5.000 && $value['etavg']<=5.500){ ?>
      fillColor: '#FFCC33', //lt orange
      strokeColor: '#FFCC33' //lt orange
    <?php }
    elseif($value['etavg']>5.500 && $value['etavg']<=6.000){ ?>
      fillColor: '#FF9900', //orange
      strokeColor: '#FF9900' //orange
    <?php }
    elseif($value['etavg']>6.000 && $value['etavg']<=6.500){ ?>
      fillColor: '#FF0000', //red
      strokeColor: '#FF0000' //red
    <?php }
    elseif($value['etavg']>6.500 && $value['etavg']<=7.000){ ?>
      fillColor: '#FF6699', //lt pink
      strokeColor: '#FF6699' //lt pink
    <?php }
    elseif($value['etavg']>7.000 && $value['etavg']<=7.500){ ?>
      fillColor: '#FF0066', //pink 
      strokeColor: '#FF0066' //pink 
    <?php }
    elseif($value['etavg']>7.500){ ?>
      fillColor: '#990033', //dark pink
      strokeColor: '#990033' //dark pink
    <?php }
  }
  //Unit of inches.
  elseif($_REQUEST['unit']=='inches'){
    if($value['etavg_inch']>=0.00 && $value['etavg_inch']<=0.02){ ?>
      fillColor: '#330066', //dark purple
      strokeColor: '#330066' //dark purple
    <?php }
    elseif($value['etavg_inch']>0.02 && $value['etavg_inch']<=0.04){ ?>
      fillColor: '#800080', //purple
      strokeColor: '#800080' //purple
    <?php }
    elseif($value['etavg_inch']>0.04 && $value['etavg_inch']<=0.06){ ?>
      fillColor: '#9900CC', //lt purple 
      strokeColor: '#9900CC' //lt purple 
    <?php }
    elseif($value['etavg_inch']>0.06 && $value['etavg_inch']<=0.08){ ?>
      fillColor: '#9966FF',  //purpley blue
      strokeColor: '#9966FF'  //purpley blue
    <?php }
    elseif($value['etavg_inch']>0.08 && $value['etavg_inch']<=0.10){ ?>
      fillColor: '#0000FF', //blue
      strokeColor: '#0000FF' //blue
    <?php }
    elseif($value['etavg_inch']>0.10 && $value['etavg_inch']<=0.12){ ?>
      fillColor: '#76A4FB', //lt blue
      strokeColor: '#76A4FB' //lt blue
    <?php }
    elseif($value['etavg_inch']>0.12 && $value['etavg_inch']<=0.14){ ?>
      fillColor: '#00CC99',  //greenish-blue
      strokeColor: '#00CC99'  //greenish-blue
    <?php }
    elseif($value['etavg_inch']>0.14 && $value['etavg_inch']<=0.16){ ?>
      fillColor: '#00CC00', //lime green
      strokeColor: '#00CC00' //lime green
    <?php }
    elseif($value['etavg_inch']>0.16 && $value['etavg_inch']<=0.18){ ?>
      fillColor: '#66FF33', //lt lime green
      strokeColor: '#66FF33' //lt lime green
    <?php }
    elseif($value['etavg_inch']>0.18 && $value['etavg_inch']<=0.20){ ?>
      fillColor: '#FFFF33', //yellow
      strokeColor: '#FFFF33' //yellow
    <?php }
    elseif($value['etavg_inch']>0.20 && $value['etavg_inch']<=0.22){ ?>
      fillColor: '#FFCC33', //lt orange
      strokeColor: '#FFCC33' //lt orange
    <?php }
    elseif($value['etavg_inch']>0.22 && $value['etavg_inch']<=0.24){ ?>
      fillColor: '#FF9900', //orange
      strokeColor: '#FF9900' //orange
    <?php }
    elseif($value['etavg_inch']>0.24 && $value['etavg_inch']<=0.26){ ?>
      fillColor: '#FF0000', //red
      strokeColor: '#FF0000' //red
    <?php }
    elseif($value['etavg_inch']>0.26 && $value['etavg_inch']<=0.28){ ?>
      fillColor: '#FF6699', //lt pink
      strokeColor: '#FF6699' //lt pink
    <?php }
    elseif($value['etavg_inch']>0.28 && $value['etavg_inch']<=0.30){ ?>
      fillColor: '#FF0066', //pink 
      strokeColor: '#FF0066' //pink 
    <?php }
    elseif($value['etavg_inch']>0.30){ ?>
      fillColor: '#990033', //dark pink
      strokeColor: '#990033' //dark pink
    <?php }
  }?>

     };
     var marker = new google.maps.Marker({ 
         position: myLatLon,
         icon: circlesymb,
         map: map 
      });
     
     google.maps.event.addListener(marker,'click',function(){
     var contentString = '<div class="wformat_3"><center><b><u>Daily ET Value:</u></b>' +
        '<?php if($_REQUEST['unit']=='mm'){
          $value['etavg']=sprintf("%6.1f",$value['etavg']);
	  echo $value['etavg'];
	  }else{
          $value['etavg_inch']=sprintf("%6.2f",$value['etavg_inch']);
	  echo $value['etavg_inch'];
	  }
    	  if($_REQUEST['unit']=='mm'){
	    echo " mm";
	  }
	  elseif($_REQUEST['unit']=='inches'){
	    echo " inches";
	  }?><br><br><form action=' +
          '"./refETdynchart_update.php?station=' +
          '<?php echo trim($station);?>&year=<?php echo $Y;?>' +
          '&unit=<?php echo $_REQUEST['unit'];?>" id="form" method="post" target="_blank">' +
          '<input type="submit" name="day" value="View timeseries"></form></center>' +
          '<hr><b><u>Station Information:</u></b><br><br><b>Name: </b><?php echo $value['name'];?>' +
          ' (<?php echo trim($station);?>)<br><b>Location: </b><?php echo $value['city'];?>' +
          ', <?php echo $value['state'];?><br><b>Elevation: </b><?php echo $value['elev'];?>' +
          ' feet above sea level<br><b>Type: </b><?php echo $value['type'];?>' + 
          ' <A href=# onClick=window.open(' +
          '"http://www.nc-climate.ncsu.edu/dynamic_scripts/cronos/types.php",' +              
          '"meta_info","width=500,height=1000,scrollbars=yes,resizable=yes")>' + 
          'what does this mean?</A><br><br>' + 
          '<form action="http://www.nc-climate.ncsu.edu/cronos/?station=' + 
          '<?php echo trim($station);?>" method="post" target="_blank">' +
          '<input type="submit" name="more_data" value="More data for this station"></form></div>';
          
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
      create<?php echo trim($station);?>Marker(myLatLon).setMap(map);
  
  <?php
  } ?> //ends the while loop. 
 } //Ends the function initialize4.
 google.maps.event.addDomListener(window, 'load', initialize4);
</script>
</head>
<body>
<!--Display the date specified and and give the option to show another date.-->
<form action="./refETdynmap_update.php" method="get">
<p><b><i>Select another date and unit of interest:</b></i></p>
                  <div class="demo">
                   <p>Date: <input type="text" name="date" id="datepicker" size="30" value="<?php echo $start;?>"  onblur="if (this.value == '') {this.value = '<?php echo $start;?>';}"
 onfocus="if (this.value == '<?php echo $start;?>') {this.value = '';}" onchange="this.form.submit()"/>
                    &nbsp&nbsp&nbsp Unit:
                  <select name="unit" onchange="this.form.submit()">
                    <?php if($_REQUEST['unit']=='inches'){
                    echo "<option value='inches'>inches</option>";
                    echo "<option value='mm'>mm</option>";
                    }elseif($_REQUEST['unit']=='mm'){
                    echo "<option value='mm'>mm</option>";
                    echo "<option value='inches'>inches</option>";
                    }?>
                  </select>
                  &nbsp&nbsp&nbsp&nbsp<!--input type="submit" value="Submit" class="button" /> -->
                   <br> &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp (YYYY-MM-DD)
                  </p></div>
</form><hr width=95%><center><p class="date"><b><?php

                //Break date into year, month, and day.
		list($Y,$M,$D) = explode("-",$start);
		  $next=date('Y-m-d',strtotime($start." +1 day"));
		  $prev=date('Y-m-d',strtotime($start." -1 day"));
		  echo "<A href='./refETdynmap_update.php?date=".$prev."&unit=".$_REQUEST['unit']."','prev_date')>Previous day</A>";
		$ET_date = date("F j, Y", mktime(0,0,0,$M,$D,$Y));
	          echo "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp ".$ET_date."&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp";
                  if($start!=date('Y-m-d', strtotime('yesterday'))){
                  echo "<A href='./refETdynmap_update.php?date=".$next."&unit=".$_REQUEST['unit']."','next_date')>Next day</A>";
                  }?></b></p>
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