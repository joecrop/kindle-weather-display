<?php
function draw_forcast_box($forcast_item) {
  
   $im = imagecreatetruecolor($width, $height);
   $white = imagecolorallocate($im, 255, 255, 255);
   $black = imagecolorallocate($im, 0, 0, 0);
   imagefill($im, 0, 0, $white);

}
?>
