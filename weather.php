<?php
error_reporting(E_ERROR | E_PARSE);
require_once 'HTTP/Request2.php';
require_once "accuweather_api.php";
require_once "get_clock.php";

header("Content-type: image/png");
//header("refresh: 1");


$width = 800;
$height = 600;
$font = './helvetica.ttf';

if(isset($_GET["battery"])) {
   $battery = $_GET["battery"];
} else {
   $battery = "0%";
}
$batteryPercent = intval($battery)/100;

try
{
   $im = imagecreatetruecolor($width, $height);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 0, 0, 0);
   imagefill($im, 0, 0, $white);

   $path = "icons/battery.png";
   $icon = imagecreatefrompng($path);
   $scaledIcon = ScaleImage($icon, (int) 50);
   imagedestroy($icon);
   $iconWidth = imagesx($scaledIcon);
   $iconHeight = imagesy($scaledIcon);
   $points = array(
      4,4,
      4, $iconHeight-5,
      4+($iconWidth-11)*$batteryPercent, $iconHeight-5,
      4+($iconWidth-11)*$batteryPercent, 4,
   );
   imagefilledpolygon($scaledIcon, $points, 4, $black);
   imagecopy($im, $scaledIcon, (int) (($width - $iconWidth) - 10), 10, 0, 0, $iconWidth, $iconHeight);
   imagedestroy($scaledIcon);

   $numberOfDays = 1;
   $numberOfHours = 10;

   //$weather = get_current_conditions();
   $hourly_forecast = get_forecast();
   //print_r($hourly_forecast);
   
   $hourWidth = $width/$numberOfHours;
   $tempHeight = 120;
   $precipHeight = 80;
   $iconHeight = 60;
   $spacing = 5;
   $min_temp = 200;
   $max_temp = -200;
   $min_precip = 0;
   $max_precip = -200;
   $max_uv_index = -10;
   $min_uv_index = 0;
   for ($h = 0; $h < min($numberOfHours, count($hourly_forecast)); ++$h) {
      if($hourly_forecast[$h]["Temperature"]["Value"] < $min_temp) {
         $min_temp = $hourly_forecast[$h]["Temperature"]["Value"];
      }
      if($hourly_forecast[$h]["Temperature"]["Value"] > $max_temp) {
         $max_temp = $hourly_forecast[$h]["Temperature"]["Value"];
      }
      if($hourly_forecast[$h]["PrecipitationProbability"] > $max_precip) {
         $max_precip = $hourly_forecast[$h]["PrecipitationProbability"];
      }
      if($hourly_forecast[$h]["UVIndex"] > $max_uv_index) {
         $max_uv_index = $hourly_forecast[$h]["UVIndex"];
      }
   }
   if($max_precip < 50 && $max_precip > 20) {
      $max_precip = 50;
   } else if($max_precip <=20) {
      $max_precip = 0;
   }
   $min_temp -=1;
   if($max_temp - $min_temp < 10) {
      $max_temp = $min_temp + 10;
   }
   $temp_pixel_height = $tempHeight / ($max_temp - $min_temp) / 2;
   if($max_precip == $min_precip) {
      $precip_pixel_height = 0;
   } else {
      $precip_pixel_height = $precipHeight / ($max_precip - $min_precip) / 2;
   }
   if($max_uv_index == 0 && $precip_pixel_height == 0) {
      $precip_pixel_height = $precipHeight / 2;
   } else if($precip_pixel_height == 0) {
      $precip_pixel_height = $precipHeight / ($max_uv_index - $min_uv_index) / 2;
   }

   $todaysConditionsWidth = (int) ($width * 0.5);
   $todaysHeight = (int) ($height - 275);
   
   $tc = TodaysConditions($hourly_forecast[0], $todaysConditionsWidth, $todaysHeight, $hourly_forecast[1], $biggestFontSize);
   imagecopy($im, $tc, 0, 0, 0, 0, imagesx($tc), imagesy($tc));
   imagedestroy($tc);

   // Write time
   $time = date('D, F j ', $weather[0]["EpochDateTime"]);
   $sideMargins = 20;
   $headerFontSize = GetBestFontSize($time, $todaysConditionsWidth - $sideMargins, 0);
   date_default_timezone_set('America/Los_Angeles');
   $text = date('g:i A');
   $box = imagettfbbox($headerFontSize, 0, $font, $text);
   $textHeight = BoxHeight($box);
   $bottom = $textHeight;
   $box = imagettftext($im, $headerFontSize, 0, (415+$headerFontSize*3), $bottom+10, $black, $font, $text);

   //$clock = get_clock(300, 300);
   //imagecopy($im, $clock, 450, 50, 0, 0, imagesx($clock), imagesy($clock));

   $rec = Recomendations($hourly_forecast, 350,300, $biggestFontSize, $min_temp, $max_temp);
   imagecopy($im, $rec, 450, 50, 0, 0, imagesx($rec), imagesy($rec));

   imagesetthickness($im, 1);
    
   // Draw the hours

   //printf("min %0d, max: %0d, height: %0d\n", $min_precip, $max_precip, $precip_pixel_height);

   for ($h = 0; $h < $numberOfHours; ++$h)
   {
      $left = (int) ($width / $numberOfHours * $h);
      $top=$height - $tempHeight - $precipHeight - $iconHeight - $spacing * 3;
      $hour = HourConditions($hourly_forecast[$h], $hourly_forecast[$h+1], $hourWidth, $tempHeight, $biggestFontSize, $min_temp, $temp_pixel_height, $min_precip, $max_precip, $min_uv_index, $max_uv_index, $precip_pixel_height, $tempHeight, $precipHeight, $iconHeight, $spacing);
      imagecopy($im, $hour, $left, $top, 0, 0, imagesx($hour), imagesy($hour));
      imagedestroy($hour);
   }


/*
   // Draw the future days
   imageline($im, 0, $todaysHeight, $width, $todaysHeight, $black);
   $bottom = $height;
   $top = $todaysHeight + 1;
   $left = 0;
   $dayHeight = $bottom - $top;
   // Figure out tha font size we should use for the stats
   $finalStatsFontSize = 200;
   $finalIconSize = 200;
   for ($i = 1; $i <= $numberOfDays; ++$i)
   {
      $right = (int) ($width / $numberOfDays * $i);
      $dayWidth = $right - $left;
      $future = FutureConditions($weather->forecast->simpleforecast->forecastday[$i], $dayWidth, $dayHeight, $statsFontSize, $iconSize, true);
      if ($statsFontSize < $finalStatsFontSize)
         $finalStatsFontSize = $statsFontSize;
      if ($iconSize < $finalIconSize)
         $finalIconSize = $iconSize;
      imagedestroy($future);
      $left = $right + 1;
   }
   $left = 0;
   for ($i = 1; $i <= $numberOfDays; ++$i)
   {
      $right = (int) ($width / $numberOfDays * $i);
      $dayWidth = $right - $left;
      $future = FutureConditions($weather->forecast->simpleforecast->forecastday[$i], $dayWidth, $dayHeight, $finalStatsFontSize, $finalIconSize, false);
      imagecopy($im, $future, $left, $top, 0, 0, $dayWidth, $dayHeight);
      imagedestroy($future);
i`      if ($i < $numberOfDays)
      {
         imageline($im, $right, $todaysHeight, $right, $height, $black);
      }
      $left = $right + 1;
   }
 */
   // Convert image color space to grayscale so the Kindle can draw it
   imagefilter($im, IMG_FILTER_GRAYSCALE);
   $file = 'color.png';
   imagepng($im, $file);
   imagedestroy($im);
   system("convert color.png -set colorspace gray gray.png");

   $name = 'gray.png';
   $fp = fopen($name, 'rb');
   // dump the picture and stop the script
   fpassthru($fp);
}
catch (Exception $e)
{
   $file = basename($e->getFile());
   ReturnError('Exception thrown: ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in file ' . $file);
}


function TodaysConditions($weather, $width, $height, $nextWeather,  &$headerFontSize) //{{{
{
   global $font;

   $sideMargins = 20;
   $verticalMargins = 5;

   $im = imagecreatetruecolor($width, $height);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 0, 0, 0);
   imagefill($im, 0, 0, $white);

   //print_r($weather);

   $time = date('D, F j ', $weather["EpochDateTime"]);
   $minutesElapsed = date('i');
   //$temperature = round($weather["Temperature"]["Value"] + ($minutesElapsed/60)*($nextWeather["Temperature"]["Value"] - $weather["Temperature"]["Value"]));
   $temperature = $weather["Temperature"]["Value"];
   $icon = $weather["WeatherIcon"];
   //$precipitation = round($weather["PrecipitationProbability"] + ($minutesElapsed/60)*($nextWeather["PrecipitationProbability"] - $weather["PrecipitationProbability"]));
   $precipitation = $weather["PrecipitationProbability"];
   //$snow = round($weather["SnowProbability"] + ($minutesElapsed/60)*($nextWeather["SnowProbability"] - $weather["SnowProbability"]));
   $snow = $weather["SnowProbability"];
   $summary = $weather["IconPhrase"];
   //$speed = round($weather["Wind"]["Speed"]["Value"] + ($minutesElapsed/60)*($nextWeather["Wind"]["Speed"]["Value"] - $weather["Wind"]["Speed"]["Value"]),1). " " . $weather["Wind"]["Speed"]["Unit"];
   $speed = $weather["Wind"]["Speed"]["Value"];
   //$direction = round($weather["Wind"]["Directiond"]["Degrees"] + ($minutesElapsed/60)*($nextWeather["Wind"]["Direction"]["Degrees"] - $weather["Wind"]["Direction"]["Degrees"]),1);
   $direction = $weather["Wind"]["Directiond"]["Degrees"];
  
   // Write day and date
   $headerFontSize = GetBestFontSize($time, $width - $sideMargins, 0);
   $text = $time;
   $box = imagettfbbox($headerFontSize, 0, $font, $text);
   $textHeight = BoxHeight($box);
   $bottom = $textHeight;
   $box = imagettftext($im, $headerFontSize, 0, $sideMargins, $bottom, $black, $font, $text);
/*
   // Write location
   $text = $weather->current_observation->display_location->full;
   $locationFontSize = min(GetBestFontSize($text, $width, 0), $headerFontSize * 0.66);
   $box = imagettfbbox($locationFontSize, 0, $font, $text);
   $bottom += $verticalMargins + BoxHeight($box);
   $box = imagettftext($im, $locationFontSize, 0, $sideMargins, $bottom, $black, $font, $text);
 */
   // Draw the weather icon
   $path = sprintf("icons_accuweather/%02d-s.png", $icon);
   $icon = imagecreatefrompng($path);
   $scaledIcon = ScaleImage($icon, (int) ($width * 0.5));
   imagedestroy($icon);
   $iconWidth = imagesx($scaledIcon);
   $iconHeight = imagesy($scaledIcon);
   imagecopy($im, $scaledIcon, (int) (($width - $iconWidth) / 2), $box[1], 0, 0, $iconWidth, $iconHeight);
   imagedestroy($scaledIcon);

   $bottom += $iconHeight;

   $bottom += $verticalMargins * 2;

   $statsWidth = $width - $sideMargins * 2;
   // We'll use the same font for the high/low temperatures and the precipitation, so
   // try multiple font sizes
   $highTempFontSize = GetBestTemperatureFontSize($min_temp, $statsWidth / 4, 0);
   $lowTempFontSize = GetBestTemperatureFontSize($max_temp, $statsWidth / 4, 0);
   $popFontSize = GetBestPrecipFontSize("0", $statsWidth / 5, 0);
   $smallFontSize = min($highTempFontSize, $lowTempFontSize, $popFontSize);
   // Draw the high/low temperatures.
   //$range = TemperatureRange($min_temp, $max_temp, $smallFontSize, $rangeWidth, $rangeHeight);

   // Draw the temperature
   $bigFontSize = GetBestTemperatureFontSize($temperature, $statsWidth / 3.5, 0);
   $temp = RenderTemperature($temperature, $bigFontSize, $tempWidth, $tempHeight);

   if($snow > 0) {
      // Draw the precipitation
      $precip = RenderSnow($snow, $smallFontSize, $precipWidth, $precipHeight);
   } else {
      // Draw the precipitation
      $precip = RenderPrecipitation($precipitation, $smallFontSize, $precipWidth, $precipHeight);
   }

   $wind = RenderWind($speed, $direction, $statsWidth / 4);

   $stats = Merge($wind, $temp, $precip, $statsWidth, 'middle', 'middle', 'mlr');
   imagecopy($im, $stats, (int) $sideMargins, $bottom, 0, 0, imagesx($stats), imagesy($stats));

   $bottom += imagesy($stats);
   imagedestroy($stats);
   $bottom += $verticalMargins;
   // Draw the conditions
   $conditions = RenderMultilineText($summary, $smallFontSize/2, $statsWidth, $textHeight);
/*
   // Draw the astro information
   $astro = RenderAstro($weather, $moon, $statsWidth, $height - $bottom);
   $astroHeight = imagesy($astro);
   $astroY = ($height - $bottom - $astroHeight) / 2 + $bottom;
 */
   imagecopy($im, $conditions, 0, ($height - $bottom - $textHeight) / 2 + $bottom, 0, 0, imagesx($conditions), imagesy($conditions));
   imagedestroy($conditions);
/*
   imagecopy($im, $astro, $sideMargins, $astroY, 0, 0, imagesx($astro), $astroHeight);
   imagedestroy($astro);
 */
   return $im;
} //}}}

function Recomendations($weather, $width, $height, &$headerFontSize) //{{{
{
   global $font;

   $sideMargins = 20;
   $verticalMargins = 5;

   $im = imagecreatetruecolor($width, $height);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 0, 0, 0);
   imagefill($im, 0, 0, $white);

   $is_windy = 0;
   $is_icy = 0;
   $is_rainy = 0;
   $is_snow = 0;
   $is_uv = 0;
   $snow_prob = 0;
   $uv_index_max = 0;
   for ($h = 0; $h < 10; ++$h)
   {
      $snow = $weather[$h]["SnowProbability"];
      $temperature = $weather[$h]["Temperature"]["Value"];
      $precipitation = $weather[$h]["PrecipitationProbability"];
      $speed = $weather[$h]["Wind"]["Speed"]["Value"];
      $direction = $weather[$h]["Wind"]["Direction"]["Degrees"];
      $uv_index = $weather[$h]["UVIndex"];

      if($speed > 15)
         $is_windy = 1;
      if($temperature < 37)
         $is_icy = 1;
      if($precipitation > 50 && $h < 10)
         $is_rainy = 1;
      if($snow > $snow_prob) {
         $is_snow = 1;
         $snow_prob = $snow;
      }
      if($uv_index > $uv_index_max) {
         $uv_index_max = $uv_index;
      }
   }

   if($uv_index_max > 2) {
      $is_uv = 1;
   }

   $numIcons = ($is_windy + $is_icy + $is_rainy + $is_snow + $is_uv);
   if($numIcons == 1)
      $numIcons = 1.6; //scale width
   $margin = 10;

   $summary = "";
   $iconX = 0;
   if($is_windy) {
      $summary .= "High Wind Advisory\n";
      $path = "icons/windy.png";
      $icon = imagecreatefrompng($path);
      $scaledIcon = ScaleImage($icon, (int) ($width/$numIcons - ($numIcons-1)*$margin));
      imagedestroy($icon);
      $iconWidth = imagesx($scaledIcon);
      $iconHeight = imagesy($scaledIcon);
      if($numIcons == 1.6)
         imagecopy($im, $scaledIcon, 50, 20, 0, 0, $iconWidth, $iconHeight);
      else
         imagecopy($im, $scaledIcon, $iconX, 20, 0, 0, $iconWidth, $iconHeight);
      imagedestroy($scaledIcon);
      $iconX += $iconWidth + $margin;
   }
   if($is_rainy) {
      $summary .= "Bring an Umbrella\n";
      $path = "icons/umbrella.png";
      $icon = imagecreatefrompng($path);
      $scaledIcon = ScaleImage($icon, (int) ($width/$numIcons - ($numIcons-1)*$margin));
      imagedestroy($icon);
      $iconWidth = imagesx($scaledIcon);
      $iconHeight = imagesy($scaledIcon);
      if($numIcons == 1.6)
         imagecopy($im, $scaledIcon, 50, 20, 0, 0, $iconWidth, $iconHeight);
      else
         imagecopy($im, $scaledIcon, $iconX, 20, 0, 0, $iconWidth, $iconHeight);
      imagedestroy($scaledIcon);
      $iconX += $iconWidth + $margin;
   }
   if($is_icy) {
      $summary .= "Possible Icy Conditions\n";
      $path = "icons/snowandfreezingrain.png";
      $icon = imagecreatefrompng($path);
      $scaledIcon = ScaleImage($icon, (int) ($width/$numIcons - ($numIcons-1)*$margin));
      imagedestroy($icon);
      $iconWidth = imagesx($scaledIcon);
      $iconHeight = imagesy($scaledIcon);
      if($numIcons == 1.6)
         imagecopy($im, $scaledIcon, 50, 20, 0, 0, $iconWidth, $iconHeight);
      else
         imagecopy($im, $scaledIcon, $iconX, 20, 0, 0, $iconWidth, $iconHeight);
      imagedestroy($scaledIcon);
      $iconX += $iconWidth + $margin;
   }
   if($is_snow) {
      $summary .= "Possibility of Snow = $snow_prob %\n";
      $path = "icons/snow.png";
      $icon = imagecreatefrompng($path);
      $scaledIcon = ScaleImage($icon, (int) ($width/$numIcons - ($numIcons-1)*$margin));
      imagedestroy($icon);
      $iconWidth = imagesx($scaledIcon);
      $iconHeight = imagesy($scaledIcon);
      if($numIcons == 1.6)
         imagecopy($im, $scaledIcon, 50, 20, 0, 0, $iconWidth, $iconHeight);
      else
         imagecopy($im, $scaledIcon, $iconX, 20, 0, 0, $iconWidth, $iconHeight);
      imagedestroy($scaledIcon);
      $iconX += $iconWidth + $margin;
   }
   if($uv_inxed_max > 10) {
      $summary .= "Apply Sunscreen!\nYou will burn in 10 minutes.\n";
      $path = "icons/UVIndex.png";
      $icon = imagecreatefrompng($path);
      $scaledIcon = ScaleImage($icon, (int) ($width/$numIcons - ($numIcons-1)*$margin));
      imagedestroy($icon);
      $iconWidth = imagesx($scaledIcon);
      $iconHeight = imagesy($scaledIcon);
      if($numIcons == 1.6)
         imagecopy($im, $scaledIcon, 50, 20, 0, 0, $iconWidth, $iconHeight);
      else
         imagecopy($im, $scaledIcon, $iconX, 20, 0, 0, $iconWidth, $iconHeight);
      imagedestroy($scaledIcon);
      $iconX += $iconWidth + $margin;
   } else if($uv_index_max > 7) {
      $summary .= "Apply Sunscreen!\nYou will burn in 15-25 minutes.\n";
      $path = "icons/UVIndex.png";
      $icon = imagecreatefrompng($path);
      $scaledIcon = ScaleImage($icon, (int) ($width/$numIcons - ($numIcons-1)*$margin));
      imagedestroy($icon);
      $iconWidth = imagesx($scaledIcon);
      $iconHeight = imagesy($scaledIcon);
      if($numIcons == 1.6)
         imagecopy($im, $scaledIcon, 50, 20, 0, 0, $iconWidth, $iconHeight);
      else
         imagecopy($im, $scaledIcon, $iconX, 20, 0, 0, $iconWidth, $iconHeight);
      imagedestroy($scaledIcon);
      $iconX += $iconWidth + $margin;
   } else if($uv_index_max > 5) {
      $summary .= "Apply Sunscreen!\nYou will burn in 30 minutes.\n";
      $path = "icons/UVIndex.png";
      $icon = imagecreatefrompng($path);
      $scaledIcon = ScaleImage($icon, (int) ($width/$numIcons - ($numIcons-1)*$margin));
      imagedestroy($icon);
      $iconWidth = imagesx($scaledIcon);
      $iconHeight = imagesy($scaledIcon);
      if($numIcons == 1.6)
         imagecopy($im, $scaledIcon, 50, 20, 0, 0, $iconWidth, $iconHeight);
      else
         imagecopy($im, $scaledIcon, $iconX, 20, 0, 0, $iconWidth, $iconHeight);
      imagedestroy($scaledIcon);
      $iconX += $iconWidth + $margin;
   } else if($uv_index_max > 2) {
      $summary .= "Apply Sunscreen!\nYou will burn in 45 minutes.\n";
      $path = "icons/UVIndex.png";
      $icon = imagecreatefrompng($path);
      $scaledIcon = ScaleImage($icon, (int) ($width/$numIcons - ($numIcons-1)*$margin));
      imagedestroy($icon);
      $iconWidth = imagesx($scaledIcon);
      $iconHeight = imagesy($scaledIcon);
      if($numIcons == 1.6)
         imagecopy($im, $scaledIcon, 50, 20, 0, 0, $iconWidth, $iconHeight);
      else
         imagecopy($im, $scaledIcon, $iconX, 20, 0, 0, $iconWidth, $iconHeight);
      imagedestroy($scaledIcon);
      $iconX += $iconWidth + $margin;
   }

   
   if($numIcons == 0) {
      $clock = get_clock(290, 290);
      imagecopy($im, $clock, 0, 0, 0, 0, imagesx($clock), imagesy($clock));
      //$summary = "Have a Great Day";
   } else {
      $headerFontSize = GetBestFontSize($summary, $width - $sideMargins, 0);
      $textHeight = BoxHeight($box);
      $bottom = $iconHeight;
      $statsWidth = $width - $sideMargins * 2;
      // Draw the conditions
      $conditions = RenderMultilineText($summary, $headerFontSize*0.8, $statsWidth, $textHeight);
      imagecopy($im, $conditions, 0, ($height - $bottom - $textHeight) / 2 + $bottom, 0, 0, imagesx($conditions), imagesy($conditions));
      imagedestroy($conditions);
   }
/*
   imagecopy($im, $astro, $sideMargins, $astroY, 0, 0, imagesx($astro), $astroHeight);
   imagedestroy($astro);
 */
   return $im;
} //}}}

function HourConditions($weather, $weatherNext, $width, $height, $biggestFontSize, $tempMin, $tempPixelHeight, $precipMin, $precipMax, $UVIndexMin, $UVIndexMax, $precipPixelHeight, $tempHeight, $precipHeight, $iconHeight, $spacing) //{{{
{
   global $font;

   $im = imagecreatetruecolor($width, $tempHeight + $precipHeight + $iconHeight + $spacing * 3);
   $white = imagecolorallocate($im, 255, 255, 255);
   imagefill($im, 0, 0, $white);


   $time = date('gA', $weather["EpochDateTime"]);
   $temperature = $weather["Temperature"]["Value"];
   $temperatureNext = $weatherNext["Temperature"]["Value"];
   $icon = $weather["WeatherIcon"];
   $precipitation = $weather["PrecipitationProbability"];
   $uv_index = $weather["UVIndex"];
/*
   // Draw the time
   $fontSize = min(GetBestFontSize('10AM', $width, $height / 4), $biggestFontSize);
   $time = RenderText($time, $fontSize, $textWidth, $textHeight);
*/

   $fontSize = 16;
   // Draw the temperature
   $temp = RenderTemperatureGraph($temperature, $temperatureNext, $fontSize, $width, $tempHeight, $tempMin, $tempPixelHeight, $time, $spacing);

   if($precipMax == 0) {
      //plot UV index instead
      $precip = RenderUVIndexGraph($uv_index, $fontSize, $width, $precipHeight, $UVIndexMin, $precipPixelHeight);
      /*if($max_uv_index == $min_uv_index) {
         $precip_pixel_Height = $precipHeight / 2;
      } else {
         $precip_pixel_height = $precipHeight / ($max_uv_index - $min_uv_index) / 2;
      }*/
   } else {
      $precip = RenderPrecipitationGraph($precipitation, $fontSize, $width, $precipHeight, $precipMin, $precipPixelHeight);
      //print($precipMin ." ".$precipMax." ".$precipPixelHeight." ".$precipitation."\n");
      /*if($max_precip == $min_precip) {
         $precip_pixel_height = $precipHeight / 2;
      } else {
         $precip_pixel_height = $precipHeight / ($max_precip - $min_precip) / 2;
      }*/
   }

   imagecopy($im, $temp, 0, $precipHeight, 0, 0, imagesx($temp), imagesy($temp));
   imagedestroy($temp);
   imagecopy($im, $precip, 0, 0, 0, 0, imagesx($precip), imagesy($precip));
   imagedestroy($precip);

   // Draw the weather icon
   $path = sprintf("icons_accuweather/%02d-s.png", $icon);
   $icon = imagecreatefrompng($path);
   $scaledIcon = ScaleImage($icon, (int) ($iconHeight*1.8));
   imagedestroy($icon);
   $iconWidth = imagesx($scaledIcon);
   imagecopy($im, $scaledIcon, ($width-$iconWidth)/2, $precipHeight + $tempHeight + $spacing*3, 0, 0, imagesx($scaledIcon), imagesy($scaledIcon));
   imagedestroy($scaledIcon);

   // Draw the precipitation
   //$precip = RenderPrecipitation($precip, $fontSize, $precipWidth, $precipHeight);

   //$stats = Merge($temp, $scaledIcon, $precip, $width, 'middle', 'middle');

   //return Stack($time, $stats, ($height - imagesy($time) - imagesy($stats)) / 2);
   return $im;
} //}}}

function IconName($url) //{{{
{
   $name = basename(parse_url($url, PHP_URL_PATH));
   return substr($name, 0, strpos($name, '.'));
}//}}}

function FormatTime($hour, $minute) //{{{
{
   if ($hour > 12)
   {
      $hour -= 12;
      $ampm = 'PM';
   }
   else
      $ampm = 'AM';
   return sprintf('%2d:%02d %s', $hour, $minute, $ampm);
}//}}}

function TemperatureRange($high, $low, $fontSize, &$width, &$height) //{{{
{
   $high = RenderTemperature($high, $fontSize, $width, $height);
   $highWidth = $width;
   $highHeight = $height;
   $low = RenderTemperature($low, $fontSize, $width, $height);
   $lowWidth = $width;
   $lowHeight = $height;

   $width = max($highWidth, $lowWidth) + 1;
   $height = $highHeight + $lowHeight + 5;
   $im = imagecreatetruecolor($width, $height);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 0, 0, 0);
   imagefill($im, 0, 0, $white);
   imagecopy($im, $high, $width - $highWidth - 1, 0, 0, 0, imagesx($high), imagesy($high));
   imagecopy($im, $low, $width - $lowWidth - 1, $height - $lowHeight - 1, 0, 0, imagesx($low), imagesy($low));
   imagesetthickness($im, 2);
   $mid = (int) ($height / 2);
   imageline($im, 0, $mid, $width, $mid, $black);
   imagedestroy($high);
   imagedestroy($low);

   return $im;
} //}}}
 
function RenderTemperature($temp, $fontSize, &$width, &$height) //{{{
{
   // Put a space in front the temperature to avoid truncating the beginning
   // a character (as imagettftext is wont to do). Don't append the space
   // if we're displaying a 3 digit temperature.
   if ($temp < 100 && $temp > -10)
      return RenderText(' ' . $temp . '°', $fontSize, $width, $height);
   else
      return RenderText($temp . '°', $fontSize, $width, $height);
} //}}}

function RenderWind($speed, $direction, $width)//{{{ 
{
   $iconSize = (int) ($width/2);
   $fontSize = GetBestFontSize($speed, $width, 0);

   $text = RenderText($speed, $fontSize/1.1, $textWidth, $textHeight);
   $path = "icons/wind_arrow.png";
   $icon = imagecreatefrompng($path);
   $white = imagecolorallocate($icon, 255, 255, 255);
   $icon_rotate = imagerotate($icon, -($direction-90), $white);
   $scaledIcon = ScaleImage($icon_rotate, $iconSize);

   $icon_padded = imagecreatetruecolor($width, imagesy($icon_rotate));
   $white = imagecolorallocate($icon_padded, 255, 255, 255);
   imagefill($icon_padded, 0, 0, $white);
   imagecopy($icon_padded, $scaledIcon, ($width - imagesx($scaledIcon))/2, 0, 0, 0, imagesx($scaledIcon), imagesy($scaledIcon));

   imagedestroy($icon);
   imagedestroy($icon_rotate);
   imagedestroy($scaledIcon);

   $wind = Stack($icon_padded, $text, 10);
   return $wind;
}//}}}

function RenderTemperatureGraph($temp, $tempNext, $fontSize, $width, $height, $min, $pixel, $time, $spacing) //{{{
{
   // Put a space in front the temperature to avoid truncating the beginning
   // a character (as imagettftext is wont to do). Don't append the space
   // if we're displaying a 3 digit temperature.
   //if ($temp < 100 && $temp > -10)
      //return RenderText(' ' . $temp . '°', $fontSize, $width, $height);
   //else
      //return RenderText($temp . '°', $fontSize, $width, $height);

   //printf("width %0d, height: %0d, pixel: %0.4f", $width, $height, $pixel);
   $im = imagecreatetruecolor($width, $height+$spacing);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 64, 64, 64);
   $gray = imagecolorallocate($im, 224, 224, 224);
   imagefill($im, 0, 0, $white);


   $y1 = ($temp-$min)*$pixel + 20;
   $y2 = ($tempNext-$min)*$pixel + 20;
   $points = array(
      0,$height-20,
      0, $height-$y1,
      $width, $height-$y2,
      $width, $height-20,
   );
   //print("temp=$temp, tempNext=$tempNext, min=$min, pixel=$pixel, y1=$y1, y2=$y2, height=$height \n");

   $text = RenderText($temp . '°', $fontSize, $textWidth, $textHeight);
   imagecopy($im, $text, 4, $height-$y1-($textHeight*1.4), 0, 0, imagesx($text), imagesy($text));
   imagedestroy($text);

   $text = RenderText($time, $fontSize, $textWidth, $textHeight);
   imagecopy($im, $text, ($width-$textWidth)/2, $height-2-$textHeight+$spacing, 0, 0, imagesx($text), imagesy($text));
   imagedestroy($text);

   imagefilledpolygon($im, $points, 4, $gray);
   imageline($im, 0, $height-$y1, $width, $height-$y2, $black);
   imageline($im, 0, $height-$y1+1, $width, $height-$y2+1, $black);

   return $im;

}//}}}

function RenderPrecipitationGraph($precip, $fontSize, $width, $height, $min, $pixel) //{{{
{
   //printf("width %0d, height: %0d, pixel: %0.4f", $width, $height, $pixel);
   $im = imagecreatetruecolor($width, $height);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 128, 128, 128);
   $gray = imagecolorallocate($im, 224, 224, 224);
   imagefill($im, 0, 0, $white);


   $y = ($precip-$min)*$pixel + 5;
   $points = array(
      0,$height,
      0, $height-$y,
      $width, $height-$y,
      $width, $height,
   );
   //print("temp=$temp, tempNext=$tempNext, min=$min, pixel=$pixel, y1=$y1, y2=$y2, height=$height \n");

   $text = RenderText($precip . '%', $fontSize, $textWidth, $textHeight);
   $path = "icons/raindrop.png";
   $icon = imagecreatefrompng($path);
   $scaledIcon = ScaleImage($icon, 6);
   imagedestroy($icon);

   $dropWidth = $textWidth + imagesx($scaledIcon) + 5;
   $im2 = Merge($scaledIcon, null, $text, $dropWidth, 'bottom', 'middle', 'rml');
   imagecopy($im, $im2, ($width-$textWidth)/2, $height-$y-($textHeight*1.4), 0, 0, imagesx($im2), imagesy($im2));
   //imagedestroy($text);
   imagedestroy($im2);


   imagefilledpolygon($im, $points, 4, $gray);
   imageline($im, 0, $height-$y, $width, $height-$y, $black);
   imageline($im, 0, $height-$y+1, $width, $height-$y+1, $black);
   imageline($im, 0, $height-$y+2, $width, $height-$y+2, $black);
   imageline($im, 0, $height-$y+3, $width, $height-$y+3, $black);

   return $im;

}//}}}

function RenderUVIndexGraph($precip, $fontSize, $width, $height, $min, $pixel) //{{{
{
   //printf("width %0d, height: %0d, pixel: %0.4f", $width, $height, $pixel);
   $im = imagecreatetruecolor($width, $height);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 128, 128, 128);
   $gray = imagecolorallocate($im, 224, 224, 224);
   imagefill($im, 0, 0, $white);


   $y = ($precip-$min)*$pixel + 5;
   $points = array(
      0,$height,
      0, $height-$y,
      $width, $height-$y,
      $width, $height,
   );
   //print("temp=$temp, tempNext=$tempNext, min=$min, pixel=$pixel, y1=$y1, y2=$y2, height=$height \n");

   $text = RenderText($precip . " ", $fontSize, $textWidth, $textHeight);
   $path = "icons/uv_index.png";
   $icon = imagecreatefrompng($path);
   $scaledIcon = ScaleImage($icon, 20);
   imagedestroy($icon);

   $dropWidth = $textWidth + imagesx($scaledIcon) + 5;
   $im2 = Merge($scaledIcon, null, $text, $dropWidth, 'bottom', 'middle', 'rml');
   imagecopy($im, $im2, ($width-$textWidth)/2, $height-$y-($textHeight*1.4), 0, 0, imagesx($im2), imagesy($im2));
   //imagedestroy($text);
   imagedestroy($im2);


   imagefilledpolygon($im, $points, 4, $gray);
   imageline($im, 0, $height-$y, $width, $height-$y, $black);
   imageline($im, 0, $height-$y+1, $width, $height-$y+1, $black);
   imageline($im, 0, $height-$y+2, $width, $height-$y+2, $black);
   imageline($im, 0, $height-$y+3, $width, $height-$y+3, $black);

   return $im;

}//}}}

function RenderPrecipitation($precip, $fontSize, &$width, &$height) //{{{
{
   // Figure out how big to make the raindrop icon: use the size of a 0
   $dropChar = RenderText('1', $fontSize, $width, $height);
   $dropCharWidth = $width;
   imagedestroy($dropChar);
   // Figure out the space to leave between the raindrop and the number
   $spaceWidth = max(1, $fontSize / 10);
   // Put a space in front the precip to avoid truncating the beginning
   // character (as imagettftext is wont to do). Don't append the space
   // if we're displaying a 3 digit precip.
   $text = RenderText($precip, $fontSize, $textWidth, $height);
   if ($precip < 100)
   {
      imagedestroy($text);
      $text = RenderText(' ' . $precip, $fontSize, $textWidth, $height);
   }
   $text = TrimImage($text, 1);
   $textWidth = imagesx($text);
   $percent = RenderText('%', $fontSize / 2, $percentWidth, $percentHeight);
   $text = Merge($text, null, $percent, $textWidth + $percentWidth, 'top');
   $textWidth += $percentWidth;
   $path = "icons/raindrop.png";
   $icon = imagecreatefrompng($path);
   $scaledIcon = ScaleImage($icon, (int) $dropCharWidth);
   imagedestroy($icon);

   $width = $textWidth + imagesx($scaledIcon) + $spaceWidth;
   $im = Merge($scaledIcon, null, $text, $width, 'bottom', 'middle', 'rml');
   $height = imagesy($im);
   return $im;
}//}}}

 function RenderSnow($precip, $fontSize, &$width, &$height) //{{{
{
   // Figure out how big to make the raindrop icon: use the size of a 0
   $dropChar = RenderText('1', $fontSize, $width, $height);
   $dropCharWidth = $width;
   imagedestroy($dropChar);
   // Figure out the space to leave between the raindrop and the number
   $spaceWidth = max(1, $fontSize / 10);
   // Put a space in front the precip to avoid truncating the beginning
   // character (as imagettftext is wont to do). Don't append the space
   // if we're displaying a 3 digit precip.
   $text = RenderText($precip, $fontSize, $textWidth, $height);
   if ($precip < 100)
   {
      imagedestroy($text);
      $text = RenderText(' ' . $precip, $fontSize, $textWidth, $height);
   }
   $text = TrimImage($text, 1);
   $textWidth = imagesx($text);
   $percent = RenderText('%', $fontSize / 2, $percentWidth, $percentHeight);
   $text = Merge($text, null, $percent, $textWidth + $percentWidth, 'top');
   $textWidth += $percentWidth;
   $path = "icons/snowflake.png";
   $icon = imagecreatefrompng($path);
   $scaledIcon = ScaleImage($icon, (int) $dropCharWidth);
   imagedestroy($icon);

   $width = $textWidth + imagesx($scaledIcon) + $spaceWidth;
   $im = Merge($scaledIcon, null, $text, $width, 'bottom', 'middle', 'rml');
   $height = imagesy($im);
   return $im;
}//}}}

function RenderAstro($weather, $moon, $width, $height) //{{{
{
   $iconSize = (int) ($width / 2 / 4);
   $fontSize = GetBestFontSize(" Rise: 12:00 AM ", $width / 2 - $iconSize, 0);

   $sunrise = FormatTime($weather->sun_phase->sunrise->hour, $weather->sun_phase->sunrise->minute);
   $sunset = FormatTime($weather->sun_phase->sunset->hour, $weather->sun_phase->sunset->minute);
   $moonAgeString = sprintf('%02d', $moon);

   date_default_timezone_set($weather->current_observation->local_tz_long);
   $month = $weather->forecast->simpleforecast->forecastday[0]->date->month;
   $day = $weather->forecast->simpleforecast->forecastday[0]->date->day;
   $year = $weather->forecast->simpleforecast->forecastday[0]->date->year;
   $moonTimes = GetMoonTimes($year, $month, $day, $weather->current_observation->display_location->city, $weather->current_observation->display_location->state);
   $moonrise = date('g:i A', $moonTimes['rise']);
   $moonset = date('g:i A', $moonTimes['set']);
   if ($moonTimes['rise'] < $moonTimes['set'])
   {
      $firstMoonEvent = ' Rise: ';
      $secondMoonEvent = ' Set: ';
      $firstMoonTime = $moonrise;
      $secondMoonTime = $moonset;
   }
   else
   {
      $firstMoonEvent = ' Set: ';
      $secondMoonEvent = ' Rise: ';
      $firstMoonTime = $moonset;
      $secondMoonTime = $moonrise;
   }

   $astroWidth = $width / 2;
   $sun = AstroTimes('icons/sun.png', ' Rise: ', $sunrise, ' Set: ', $sunset, $astroWidth, $fontSize);
   $moon = AstroTimes("moons/NH-moon{$moonAgeString}.gif", $firstMoonEvent, $firstMoonTime, $secondMoonEvent, $secondMoonTime, $astroWidth, $fontSize);
   $astro = Merge($sun, null, $moon, imagesx($sun) + imagesx($moon) + 10);

   return $astro;
}//}}}

function AstroTimes($icon, $str1, $time1, $str2, $time2, $width, $fontSize) //{{{
{
   global $font;

   if (strpos($icon, '.gif'))
      $icon = imagecreatefromgif($icon);
   else
      $icon = imagecreatefrompng($icon);

   $str1box = imagettfbbox($fontSize, 0, $font, $str1);
   $time1box = imagettfbbox($fontSize, 0, $font, $time1);
   $str2box = imagettfbbox($fontSize, 0, $font, $str2);
   $time2box = imagettfbbox($fontSize, 0, $font, $time2);
   $textHeight = max(BoxHeight($str1box), BoxHeight($time1box)) + max(BoxHeight($str2box), BoxHeight($time2box));

   $str1 = RenderText($str1, $fontSize, $s1width, $s1height);
   $time1 = RenderText($time1, $fontSize, $t1width, $t1height);
   $event1 = Merge($str1, null, $time1, $width - imagesx($icon));

   $str2 = RenderText($str2, $fontSize, $s2width, $s2height);
   $time2 = RenderText($time2, $fontSize, $t2width, $t2height);
   $event2 = Merge($str2, null, $time2, $width - imagesx($icon));

   $events = Stack($event1, $event2, $fontSize / 2);

   return Merge($icon, null, $events, $width);
}//}}}

function FutureConditions($dayInfo, $dayWidth, $dayHeight, &$statsFontSize, &$iconSize, $calculatetSizes) //{{{
{
   $dayFontSize = GetBestFontSize(" Wednesday ", $dayWidth, $dayHeight / 4);

   $im = imagecreatetruecolor($dayWidth, $dayHeight);
   $white = imagecolorallocate($im, 255, 255, 255);
   imagefill($im, 0, 0, $white);

   $day = RenderText(' ' . $dayInfo->date->weekday . ' ', $dayFontSize, $textWidth, $textHeight);
   $verticalMargin = 3;
   imagecopy($im, $day, (int)(($dayWidth - $textWidth) / 2), $verticalMargin, 0, 0, $textWidth, $textHeight);
   $top = imagesy($day) + $verticalMargin;
   imagedestroy($day);

   $statsWidth = $dayWidth * 0.9;
   if ($calculatetSizes)
   {
      $highTempFontSize = GetBestTemperatureFontSize($dayInfo["Temperature"]["Value"], $statsWidth / 2, 0);
      $lowTempFontSize = GetBestTemperatureFontSize($dayInfo["Temperature"]["Value"], $statsWidth / 2, 0);
      $popFontSize = GetBestPrecipFontSize($dayInfo["PrecipitationProbability"], $statsWidth / 2, 0);
      $fontSize = $statsFontSize = min($highTempFontSize, $lowTempFontSize, $popFontSize);
   }
   else
      $fontSize = $statsFontSize;
   // Draw the high/low temperatures
   $range = TemperatureRange($dayInfo["Temperature"]["Value"], $dayInfo["Temperature"]["Value"],
      $fontSize, $rangeWidth, $rangeHeight);

   // Draw the precipitation
   $precip = RenderPrecipitation($dayInfo["PrecipitationProbability"], $fontSize, $precipWidth, $precipHeight);

   $stats = Merge($range, null, $precip, $statsWidth);
   $statsHeight = imagesy($stats);
   $statsY = $dayHeight - $statsHeight - $verticalMargin;
   imagecopy($im, $stats, (int) (($dayWidth - imagesx($stats)) / 2), $statsY, 0, 0, imagesx($stats), $statsHeight);
   imagedestroy($stats);

   // Draw the weather icon
   //$path = "icons/$icon.png";
   $path = "icons/1.png";
   $icon = imagecreatefrompng($path);
   if ($calculatetSizes)
      $iconSize = min(($dayHeight - $top - $statsHeight - $verticalMargin) * 0.8, $dayWidth * 0.8);
   $scaledIcon = ScaleImage($icon, (int) $iconSize);
   imagedestroy($icon);
   $iconWidth = imagesx($scaledIcon);
   $iconHeight = imagesy($scaledIcon);
   imagecopy($im, $scaledIcon, (int) (($dayWidth - $iconWidth) / 2), ($statsY - $top - $iconHeight) / 2 + $top, 0, 0, $iconWidth, $iconHeight);
   imagedestroy($scaledIcon);
   $top += $iconHeight;

   return $im;
}//}}}

function Indent($image, $indent) //{{{
{
   $im = imagecreatetruecolor($indent, 1);
   $white = imagecolorallocate($im, 255, 255, 255);
   imagefill($im, 0, 0, $white);
   return Merge($im, null, $image, $indent + imagesx($image));
}//}}}

function Merge($left, $center, $right, $width, $valign = 'middle', $halign = 'middle', $order = 'lmr') //{{{
{
   $leftWidth = imagesx($left);
   $leftHeight = imagesy($left);
   if ($center)
   {
      $centerWidth = imagesx($center);
      $centerHeight = imagesy($center);
   }
   else
   {
      $centerWidth = 0;
      $centerHeight = 0;
   }
   $rightWidth = imagesx($right);
   $rightHeight = imagesy($right);

   $height = max($leftHeight, $centerHeight, $rightHeight);

   $im = imagecreatetruecolor($width, $height);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 0, 0, 0);
   imagefill($im, 0, 0, $white);
   if ($valign == 'middle')
   {
      $lefty = (int) (($height - $leftHeight) / 2);
      $righty = (int) (($height - $rightHeight) / 2);
      $centery = (int) (($height - $centerHeight) / 2);
   }
   else if ($valign == 'bottom')
   {
      $lefty = $height - $leftHeight;
      $righty = $height - $rightHeight;
      $centery = $height - $centerHeight;
   }
   else // top align
      $lefty = $righty = $centery = 0;

   if ($halign == 'middle')
   {
      $centerx = ($width - $centerWidth) / 2;
   }
   else if ($halign == 'gap') // equal gaps
   {
      $centerx = ($width - $leftWidth - $centerWidth - $rightWidth) / 2 + $leftWidth;
   }
   for ($i = 0; $i < 3; ++$i)
   {
      switch ($order[$i])
      {
         case 'l' :
            imagecopy($im, $left, 0, $lefty, 0, 0, $leftWidth, $leftHeight);
            imagedestroy($left);
            break;

         case 'm' :
            if ($center)
            {
               imagecopy($im, $center, (int) $centerx, $centery, 0, 0, $centerWidth, $centerHeight);
               imagedestroy($center);
            }
            break;

         case 'r' :
            imagecopy($im, $right, $width - $rightWidth, $righty, 0, 0, $rightWidth, $rightHeight);
            imagedestroy($right);
            break;
      }
   }

   return $im;
}//}}}

function Stack($top, $bottom, $spacing = 0) //{{{
{
   $topWidth = imagesx($top);
   $topHeight = imagesy($top);
   $bottomWidth = imagesx($bottom);
   $bottomHeight = imagesy($bottom);

   $width = max($topWidth, $bottomWidth);
   $height = $topHeight + $bottomHeight + $spacing;

   $im = imagecreatetruecolor($width, $height);
   $white = imagecolorallocate($im, 255, 255, 255);
   imagefill($im, 0, 0, $white);
   imagecopy($im, $top, 0, 0, 0, 0, $topWidth, $topHeight);
   imagecopy($im, $bottom, 0, $topHeight + $spacing, 0, 0, $bottomWidth, $bottomHeight);

   imagedestroy($top);
   imagedestroy($bottom);

   return $im;
}//}}}

function RenderText($text, $fontSize, &$width, &$height) //{{{
{
   global $font;

   $box = imagettfbbox($fontSize, 0, $font, $text);
   $width = BoxWidth($box);
   $height = BoxHeight($box);
   $im = imagecreatetruecolor($width + 1, $height + 1);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 0, 0, 0);
   imagefill($im, 0, 0, $white);
   imagettftext($im, $fontSize, 0, 0, abs($box[5]), $black, $font, $text);
   $im = TrimImage($im, 0);
   $width = imagesx($im);
   return $im;
}//}}}

function RenderMultilineText($text, $fontSize, $width, &$height) //{{{
{
   global $font;

   $words = explode(' ', $text);
   $lines = array();
   $height = 0;
   $line = '';
   while (count($words))
   {
      if ($line != '')
         $tryLine = trim($line . ' ' . $words[0]);
      else
         $tryLine = trim($words[0]);
      $box = imagettfbbox(' ' . $fontSize, 0, $font, $tryLine);
      // If the word fits on the line
      if (BoxWidth($box) < $width)
      {
         $line = $tryLine;
         array_splice($words, 0, 1);
      }
      // Else (doesn't fit)
      else
      {
         // If there's no room for this word even on an empty line
         if ($line == '')
         {
            // Give up
            $words = array();
            break;
         }
         // Else save the line and start building a new line
         else
         {
            $lines[] = array($line, $box);
            $height += BoxHeight($box);
            $line = '';
         }
      }
   }
   if ($line != '')
   {
      $lines[] = array($line, $box);
      $height += BoxHeight($box);
   }

   $im = imagecreatetruecolor($width + 1, $height + count($lines));
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 0, 0, 0);
   imagefill($im, 0, 0, $white);
   $pos = 0;
   foreach ($lines as $line)
   {
      $box = imagettftext($im, $fontSize, 0, 0, $pos + abs($line[1][5]), $black, $font, ' ' . $line[0]);
      $pos += BoxHeight($box) + 1;
   }

   return $im;
}//}}}

function BoxWidth($box) //{{{
{
   return abs($box[4] - $box[0]);
}//}}}

function BoxHeight($box) //{{{
{
   return abs($box[5] - $box[1]);
}//}}}

function ScaleImage($image, $newWidth) //{{{
{
   $oldWidth = imagesx($image);
   $oldHeight = imagesy($image);
   $newHeight = (int) ($oldHeight * $newWidth / $oldWidth);
   $newWidth = (int) $newWidth;

   $im = imagecreatetruecolor($newWidth, $newHeight);
   imagealphablending($im, false);
   imagesavealpha($im, true);
   $white = imagecolorallocate($im, 255, 255, 255);
   //imagefill($im, 0, 0, $white);

   imagecopyresampled($im, $image , 0, 0, 0, 0, $newWidth, $newHeight, $oldWidth, $oldHeight);

   return $im;
}//}}}

// Find the font size that will fit in the given space. Width or height can be zero for "don't care"
function GetBestFontSize($text, $width, $height) //{{{
{
   global $font;

   $lowFontSize = 1;
   $highFontSize = 200;
   $fontSize = 12;
   while ($highFontSize - $lowFontSize > 1)
   {
      $box = imagettfbbox($fontSize, 0, $font, $text);
      if ($width != 0)
      {
         if (BoxWidth($box) < $width - 1)
         {
            $lowFontSize = $fontSize;
         }
         else if (BoxWidth($box) > $width)
         {
            $highFontSize = $fontSize;
         }
         else
            break;
         $fontSize = ($highFontSize - $lowFontSize) / 2 + $lowFontSize;
      }
   }

   if ($height != 0 && BoxHeight($box) > $height)
   {
      $highFontSize = $fontSize;
      $lowFontSize = 1;
      $fontSize = ($highFontSize - $lowFontSize) / 2;
      while ($highFontSize - $lowFontSize > 1)
      {
         $box = imagettfbbox($fontSize, 0, $font, $text);
         if (BoxHeight($box) < $height - 1)
         {
            $lowFontSize = $fontSize;
         }
         else if (BoxHeight($box) > $height)
         {
            $highFontSize = $fontSize;
         }
         else
            break;
         $fontSize = ($highFontSize - $lowFontSize) / 2 + $lowFontSize;
      }
   }

   return $fontSize;
}//}}}

// Find the font size that will fit in the given space. Width or height can be zero for "don't care"
function GetBestTemperatureFontSize($text, $width, $height) //{{{
{
   global $font;

   $lowFontSize = 1;
   $highFontSize = 200;
   $fontSize = ($highFontSize - $lowFontSize) / 2;
   if ($width != 0)
   {
      while ($highFontSize - $lowFontSize > 1)
      {
         $temp = RenderTemperature($text, $fontSize, $tempWidth, $tempHeight);
         imagedestroy($temp);
         if ($tempWidth < $width)
         {
            $lowFontSize = $fontSize;
         }
         else if ($tempWidth > $width)
         {
            $highFontSize = $fontSize;
         }
         else
            break;
         $fontSize = ($highFontSize - $lowFontSize) / 2 + $lowFontSize;
      }
   }

   if ($height != 0 && $tempHeight > $height)
   {
      $highFontSize = $fontSize;
      $lowFontSize = 1;
      $fontSize = ($highFontSize - $lowFontSize) / 2;
      while ($highFontSize - $lowFontSize > 1)
      {
         $temp = RenderTemperature($text, $fontSize, $tempWidth, $tempHeight);
         imagedestroy($temp);
         if ($tempHeight < $height)
         {
            $lowFontSize = $fontSize;
         }
         else if ($tempHeight > $height)
         {
            $highFontSize = $fontSize;
         }
         else
            break;
         $fontSize = ($highFontSize - $lowFontSize) / 2 + $lowFontSize;
      }
   }

   return $fontSize;
}//}}}

// Find the font size that will fit in the given space. Width or height can be zero for "don't care"
function GetBestPrecipFontSize($text, $width, $height)//{{{
{
   global $font;

   $lowFontSize = 1;
   $highFontSize = 200;
   $fontSize = 12;
   if ($width != 0)
   {
      while ($highFontSize - $lowFontSize > 1)
      {
         $pop = RenderPrecipitation($text, $fontSize, $popWidth, $popHeight);
         imagedestroy($pop);
         if ($popWidth < $width)
         {
            $lowFontSize = $fontSize;
         }
         else if ($popWidth > $width)
         {
            $highFontSize = $fontSize;
         }
         else
            break;
         $fontSize = ($highFontSize - $lowFontSize) / 2 + $lowFontSize;
      }
   }

   if ($height != 0 && $popHeight > $height)
   {
      $highFontSize = $fontSize;
      $lowFontSize = 1;
      $fontSize = ($highFontSize - $lowFontSize) / 2;
      while ($highFontSize - $lowFontSize > 1)
      {
         $pop = RenderPrecipitation($text, $fontSize, $popWidth, $popHeight);
         imagedestroy($pop);
         if ($popHeight < $height - 1)
         {
            $lowFontSize = $fontSize;
         }
         else if ($popHeight > $height)
         {
            $highFontSize = $fontSize;
         }
         else
            break;
         $fontSize = ($highFontSize - $lowFontSize) / 2 + $lowFontSize;
      }
   }

   return $fontSize;
}//}}}

function ReturnError($message)//{{{
{
   global $width;
   global $height;
   $text = RenderMultilineText($message, 36, $width, $height);
   $im = imagecreatetruecolor($width, $height);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 0, 0, 0);
   imagefill($im, 0, 0, $white);
   imagecopy($im, $text, 0, 0, 0, 0, imagesx($text), imagesy($text));

   // Convert image color space to grayscale so the Kindle can draw it
   if (class_exists('Imagick'))
   {
      $file = tempnam('.', 'png');
      imagepng($im, $file);
      imagedestroy($im);

      $im = new Imagick($file);
      $im->transformImageColorspace(imagick::COLORSPACE_GRAY);
      echo $im;
      unlink($file);
   }
   // ImageMagick isn't installed in our debug environment, but there's
   // no Kindle there either so it's OK
   else
   {
      imagepng($im);
      imagedestroy($im);
   }
   exit(0);
}//}}}

function GetMoonTimes($year, $month, $date, $city, $state) //{{{
{
   for ($retry = 0; $retry < 3; ++$retry)
   {
      try
      {
         $query =  new HTTP_Request2("http://aa.usno.navy.mil/rstt/onedaytable?ID=AA&year=$year&month=$month&day=$date&state=$state&place=$city", HTTP_Request2::METHOD_GET,
            array('connect_timeout' => 2, 'timeout' => 4));
         $query->setHeader(array(
            'Host' => 'aa.usno.navy.mil',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:28.0) Gecko/20100101 Firefox/28.0',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection' => 'keep-alive'
         ));
         $response = $query->send();
         $status = $response->getStatus();
         if ($status == 200)
         {
            $body = $response->getBody();

            $rise = strtotime("$year-$month-$date " . ExtractTime($body, 'Moonrise'));
            $set = strtotime("$year-$month-$date " . ExtractTime($body, 'Moonset'));

            return array('rise' => $rise, 'set' => $set);
         }
      }
      catch (Exception $e)
      {
      }
   }
   return null;
}//}}}

function ExtractTime($body, $label)//{{{
{
   $pos = strpos($body, 'on preceding day');
   $pos = strpos($body, $label, $pos);
   $pos = strpos($body, '<td>', $pos) + 4;
   $end = strpos($body, '</td>', $pos);
   $time = substr($body, $pos, $end - $pos);
   $time = str_replace('a.m.', 'AM', $time);
   $time = str_replace('p.m.', 'PM', $time);
   return $time;
}//}}}

// Trims white space from the left and right sides of an image. Destroys the input
// image, returns the trimmed image
function TrimImage($im, $newMargin)//{{{
{
   $width = imagesx($im);
   $height = imagesy($im);

   $left = 0;
   while ($left < $width)
   {
      for ($row = 0; $row < $height; ++$row)
      {
         if (imagecolorat($im, $left, $row) != 0xffffff)
            break;
      }
      if ($row == $height)
         ++$left;
      else
         break;
   }
   $right = $width - 1;
   while ($right > $left)
   {
      for ($row = 0; $row < $height; ++$row)
      {
         if (imagecolorat($im, $right, $row) != 0xffffff)
            break;
      }
      if ($row == $height)
         --$right;
      else
         break;
   }

   $trimmed = imagecreatetruecolor($right - $left + 1 + $newMargin * 2, $height);
   $white = imagecolorallocate($trimmed, 255, 255, 255);
   imagefill($trimmed, 0, 0, $white);
   imagecopy($trimmed, $im, 0, $newMargin, $left, 0, $right - $left + 1, $height);

   imagedestroy($im);
   return $trimmed;
}//}}}

?>
