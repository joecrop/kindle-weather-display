<?php


function get_forecast() { //{{{
   
	$myfile = fopen("forecast.txt", "r") or die("Unable to open file!");
	$response = fread($myfile, filesize("forecast.txt"));
	fclose($myfile);
	//print_r($response);

	$response = json_decode($response, true); //because of true, it's in an array
	return $response;
}//}}}

function get_current_conditions() { //{{{
   
	$myfile = fopen("current_conditions.txt", "r") or die("Unable to open file!");
	$response = fread($myfile, filesize("current_conditions.txt"));
	fclose($myfile);
	//print_r($response);

	$response = json_decode($response, true); //because of true, it's in an array
	return $response;
}//}}}

function pull_current_conditions() { //{{{
	require "api_key.php";
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://dataservice.accuweather.com/currentconditions/v1/40817_PC?apikey=" . $API_KEY,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => array(
	    "cache-control: no-cache"
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	$myfile = fopen("current_conditions.txt", "w") or die("Unable to open file!");
	fwrite($myfile, $response);
	fclose($myfile);
	//print_r($response);
	//
}//}}}

function pull_forecast()  //{{{
{
	require "api_key.php";
	$curl = curl_init();
	$url = "http://dataservice.accuweather.com/forecasts/v1/hourly/12hour/40817_PC?details=true&apikey=" . $API_KEY;
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $url,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => array(
	    "cache-control: no-cache"
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);
	  
	$myfile = fopen("forecast.txt", "r") or die("Unable to open file!");
	$old_response = fread($myfile, filesize("forecast.txt"));
	fclose($myfile);

	$old_response = json_decode($old_response, true);  
	$response = json_decode($response, true);
	if(is_array($old_response) && sizeof($old_response) > 1) {
		array_unshift($old_response[0], $response);
	}

	$myfile = fopen("forecast.txt", "w") or die("Unable to open file!");
	fwrite($myfile, json_encode($response));
	fclose($myfile);

}//}}}

?>
