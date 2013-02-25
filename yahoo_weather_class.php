<?php


class yw_weather {
	private $yw_xml;
	private $yw_namespace = "http://xml.weather.yahoo.com/ns/rss/1.0";
	private $woeid;
 	private $current_temperature;
	
	
	function get_temperature() {
		$this->current_temperature = $this->get_forecast_weather_data()['condition']['temp'];
		return $this->current_temperature; 
	}

	function get_weather_data($woeid, $data = 'false') {
		$this->woeid = $woeid;
		
		$yw_url = 'http://weather.yahooapis.com/forecastrss?w='. $woeid .'&u=f';
		$yw_feed = file_get_contents($yw_url);
		if(!$yw_feed) echo "There was an error retrieving the weather data";
		$this->yw_xml = simplexml_load_string($yw_feed);
		$this->get_temperature();
		if($data == 'true')
			return $this->yw_xml;
		return 1;
	}

	function get_simple_weather_data() {
		$yw_channel = $this->yw_xml->channel->children($this->yw_namespace);

		foreach($yw_channel as $yw_meta => $channel_item)
				foreach($channel_item->attributes() as $yw_attribute => $attr)
					$yw_channel_data[$yw_meta][$yw_attribute] = $attr;

		return $yw_channel_data;
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
?>

<?php $local_woeid = 2424766; ?>

<?php
	$weather = new yw_weather();
	$weather->get_weather_data($local_woeid);
?>
<h1>Yahoo Weather Data</h1>
<h2>Yahoo Weather Data: RAW XML</h2>
<pre><?php print_r($weather->get_weather_data($local_woeid, 'true')); ?></pre>

<h2>Yahoo Weather Data: Simplified XML</h2>
<pre><?php print_r($weather->get_simple_weather_data()); ?></pre>


<h2>Yahoo Weather Data: Forecast XML</h2>
<pre><?php print_r($weather->get_forecast_weather_data()); ?></pre>

<h2>Yahoo Weather Data: Get Temperature from <?php echo $local_woeid; ?></h2>
<pre><?php echo $weather->get_temperature(); ?></pre>



