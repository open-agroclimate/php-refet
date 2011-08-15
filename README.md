Reference Evapotranspiration (ET) Tool Version 1.0
Author: Heather Dinon

This reference ET tool uses Google Map technology to display the data in two
different ways-- in a map-based format or as a time series plot. The map-based
product is a daily reference ET estimate based on the Penman-Monteith equation
in the UN Food and Agriculture Organization paper number 56 (FAO56), and
allows the user to select a date from January 1, 2002 to yesterday. From the
dynamic map product, users can display an annual time series for a particular
station. 

For more detailed information on the FAO56 Penman-Monteith method, please refer 
to the UN FAO56 Penman-Monteith documentation: 
http://www.fao.org/docrep/X0490E/X0490E00.htm 

Usage
=========================
```
git clone git://github.com/open-agroclimate/php-refet.git
cd php-refet
git submodules init
git submodules update
```

Known Limitations/Caveats
==========================

1) Due to a limitation with the CRONOS API (currently being fixed), the six
daily meteorological input parameters for the reference ET estimate
(min/max temperature, min/max relative humidity, average wind speed, and
average solar radiation) will be computed from hourly data even if <80% of
the hourly data is available to compute these daily values. This could lead to
bad values being output from the reference ET calculation.

2) Currently, the link to the "Main Page" points to the reference ET tool
hosted by the State Climate Office --this will be fixed soon.

3) User needs to obtain an API key from Google for their server or local
machine. Sign up for this here: http://code.google.com/apis/maps/signup.html