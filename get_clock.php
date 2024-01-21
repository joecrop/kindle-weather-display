<?php

function get_clock($width, $height) //{{{
{

   date_default_timezone_set('America/Los_Angeles');

    $th = (((date('U')+date('Z')) % 43200)) / (4320/2*PI());
    $tm = (date('U')+date('Z')) % 3600  / (360 /2*PI());
    $ts = (date('U')+date('Z')) % 60    / (6   /2*PI());

    $sirka = $width;
    $vyska = $height;

    $stredx = $sirka/2; //center x
    $stredy = $vyska/2; //center y
   
    $txt = "";//date('g:i A');
   
    $r = min($sirka, $vyska)/2-(min($sirka, $vyska)/10);

    $img = imagecreate($sirka,$vyska);
    $color = imagecolorallocate($img, 255, 255, 255);
    imagefill ($img, 0, 0, $color);

    $color = imagecolorallocate($img, 0, 0, 0);

    $hx = $stredx + $r*0.60 * sin($th);
    $hy = $stredy - $r*0.60 * cos($th);
    $mx = $stredx + $r * sin($tm);
    $my = $stredy - $r * cos($tm);
    $sx = $stredx + $r * sin($ts);
    $sy = $stredy - $r * cos($ts);

    $colorcopy = imagecolorallocate($img, 128, 128, 128);
    $gray = imagecolorallocate($img, 224, 224, 224);

    imagefilledellipse($img, $width/2,$height/2,$width-50, $height-50, $gray);

    //imagestring ($img, 4, $sirka *.3, $vyska*.3, date("H:i:s"), $colorcopy);
   imagestring ($img, 4, $sirka *.3, $vyska*.3, $txt, $colorcopy);

    for($i=0; $i<12; $i++)
    {
        $bod = $i / (1.2/2*PI());

        $bodx = $stredx + ($r*1.0) * sin($bod);
        $body = $stredy - ($r*1.0) * cos($bod);

        for($j=1; $j<4; $j++) {
           Imagesetpixel($img, $bodx, $body, $colorcopy);
           Imagesetpixel($img, $bodx-$j, $body, $colorcopy);
           Imagesetpixel($img, $bodx+$j, $body, $colorcopy);
           Imagesetpixel($img, $bodx-$j, $body-$j, $colorcopy);
           Imagesetpixel($img, $bodx+$j, $body+$j, $colorcopy);
           Imagesetpixel($img, $bodx, $body-$j, $colorcopy);
           Imagesetpixel($img, $bodx, $body+$j, $colorcopy);
        }
    }

    imageline($img, $stredx-2, $stredy, $hx-2, $hy, $colorcopy);
    imageline($img, $stredx+2, $stredy, $hx+2, $hy, $colorcopy);
    imageline($img, $stredx-1, $stredy, $hx-1, $hy, $colorcopy);
    imageline($img, $stredx  , $stredy, $hx  , $hy, $colorcopy);
    imageline($img, $stredx+1, $stredy, $hx+1, $hy, $colorcopy);

    imageline($img, $stredx-1, $stredy, $mx-1, $my, $color);
    imageline($img, $stredx+1, $stredy, $mx+1, $my, $color);
    imageline($img, $stredx, $stredy, $mx, $my, $color);

   //seconds
    //imageline($img, $stredx, $stredy, $sx, $sy, $color);

   return $img;
 
} //}}}
 
?>
