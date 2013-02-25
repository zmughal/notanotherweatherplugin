<?php
class ca_weather {
	private $w_xml;
	private $yw_namespace = "http://xml.weather.yahoo.com/ns/rss/1.0";
	private $woeid;
	private $current_temperature;
	private $location_data;
	
	
	function set_location($location_data_array) {
		$this->location_data = new ca_location();
		$this->location_data = $location_data_array;
	}
	function get_temperature() {
		
		$forecast_weather_data = $this->get_forecast_weather_data();
		$condition_data = $forecast_weather_data['condition'];
		$this->current_temperature = $condition_data['temp'];
		return $this->current_temperature; 
	}
	
	function get_weather_data($woeid, $data = 'false') {
		$this->woeid = $woeid;
		$yw_url = 'http://weather.yahooapis.com/forecastrss?w='. $woeid .'&u=f';
		$yw_feed = get_url_contents($yw_url);
		if(!$yw_feed) echo "There was an error retrieving the weather data";
		$this->yw_xml = simplexml_load_string($yw_feed);
		$this->get_temperature();
		if($data == 'true')
			return $this->yw_xml;
		return 1;
	}
	
	function get_forecast_weather_data() {
		$yw_forecast = $this->yw_xml->channel->item->children($this->yw_namespace);
		foreach($yw_forecast as $yw_meta => $yw_item)
			foreach($yw_item->attributes() as $yw_attr => $attr ) {
				if($yw_attr == 'day') $day = $attr;
				if($yw_meta == 'forecast') {
					$yw_forecast_data[$yw_meta][$day . ''][$yw_attr] = $attr;
				}
				else $yw_forecast_data[$yw_meta][$yw_attr] = $attr;
			}
		return $yw_forecast_data;
	}
}

class ca_location {
	private $ip_address;
	private $city;
	private $region;
	private $lat;
	private $long;
	
	function get_city() {
		return $this->city;
	}
	
	function get_region() {
		return $this->region;
	}
	function set_location($location_array) {
		
		$this->ip_address = $location_array['ip'];
		$this->city = $location_array['city'];
		$this->region = $location_array['regionname'];
		$this->lat = $location_array['lat'];
		$this->long = $location_array['long'];
		
	}
	
	function get_location_data() {
		return array( 'ip' => $this->ip_address, 'city' => $this->city, 'region' => $this->region, 'lat' => $this->lat, 'long' => $this->long);
	}
}

function get_url_contents($url){
	$crl = curl_init();
    $timeout = 5;
    curl_setopt ($crl, CURLOPT_URL,$url);
    curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
    $contents = curl_exec($crl);
    curl_close($crl);
    return $contents;
}


function get_current_temperature() {
	$location_data = get_city_from_ip(get_ip_address());
	//echo '<pre>' . print_r($location_data) . '</pre>';
	//echo '<pre>' . print_r($location_data->get_location_data()) . '</pre>';
	$city = $location_data->get_city();
	$weather = new ca_weather();
	$weather->set_location($location_data);
	$weather->get_weather_data(get_weoid($city));
	echo "It's " . $weather->get_temperature() . 'F&#176 in<br/>' . $city . ', '. ucwords(strtolower($location_data->get_region())) . '<br/>';
}

function get_loc() {
	if (empty($this->w_xml)) return -1;
	$cname = $this->w_xml->getElementsByTagName('city')->item(0)->getAttribute('data');	
	return $cname;
}

function get_weoid($city_name) {
	$yahoo_where_url = "http://where.yahooapis.com/v1/places";
	$yahoo_where_api_key = "27IVTiXV34FTS3ixzXSjW7JtS34dLjFghrwbopTr_mD_.qZ3zzSfSzePcq5lKl.GH6EyKzet.5M0kEMhlmfzjvn4Ksu0OCU-";

	if(empty($city_name)) $city_name = 'New York';

	$yahoo_where_query = ".q('" . urlencode($city_name) ."')";
	$url_post = $yahoo_where_url . $yahoo_where_query . "?appid=" . $yahoo_where_api_key;

	$weather_feed = get_url_contents($url_post);
	$objDOM = new DOMDocument();
	$objDOM->loadXML($weather_feed);
	$woeid = $objDOM->getElementsByTagName("place")->item(0)->getElementsByTagName("woeid")->item(0)->nodeValue;
	return $woeid;
}

function get_ip_address() {
	$ip = $_SERVER['REMOTE_ADDR'];
	
	if ($ip == '::1') $ip = '99.16.197.55';
	return $ip;
}

function get_city_from_ip($ip) {
	$ipinfo_api_key = 'ec5236554da5193607669a8e24b9652045442d0f3e2ade1007a8981421ea28fe';
	
	$file= 'http://api.ipinfodb.com/v3/ip-city/?key='. $ipinfo_api_key . '&ip='. $ip . '&format=xml';
	//echo '<a href="' . $file . '">IPLocation </a>';
	$data = get_url_contents($file);
	//echo '<pre>' . print_r($data) . '</pre>';
	preg_match_all("/<latitude>(.*?)<\/latitude>/s",$data,$arrTreffer); 
	$lat = $arrTreffer[1][0];
	preg_match_all("/<longitude>(.*?)<\/longitude>/s",$data,$arrTreffer);
	$lng = $arrTreffer[1][0];
	preg_match_all("/<regionName>(.*?)<\/regionName>/s",$data,$matches);
	$regionname = $matches[1][0];
	
	$file = 'http://maps.googleapis.com/maps/api/geocode/xml?latlng='.$lat.','.$lng.'&sensor=false';

	// Debug : Display Google Api Location Data
	// Check and make sure you're receiving valid data
	//echo "<h3>Google Maps API Location Data</h3>";
	//echo '<a href="' . $file . '"> Google Maps API Location for Latitude ( ' .$lat . ' ) and Longitude (' . $lng . ').</a>' . "\n";

	$data = get_url_contents($file);

	preg_match_all("/<address_component>(.*?)<\/address_component>/s",$data,$arrTreffer);

	preg_match_all("/<long_name>(.*?)<\/long_name>/s",$arrTreffer[1][3],$city);

	$city = $city[1][0];
	
	$location_data = new ca_location();
	$location_data->set_location(array(
		'ip' => $ip,
		'city' => $city,
		'regionname' => $regionname,
		'lat' => $lat,
		'long' => $lng
		));
	return $location_data;
}
