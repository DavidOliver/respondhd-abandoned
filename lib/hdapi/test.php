<?php

	ini_set('display_errors', 1); error_reporting(E_ALL);
	
	$tests = array (
		array (
			'title' => 'Nokia N95', 
			'agent' => 'Mozilla/5.0 (SymbianOS/9.2; U; Series60/3.1 NokiaN95/31.0.017; Profile/MIDP-2.0 Configuration/CLDC-1.1 ) AppleWebKit/413 (KHTML, like Gecko) Safari/413', 
			'expected' => 'OK'),
		array (
			'title' => 'RIM BlackBerry 9000', 
			'agent' => 'BlackBerry9000/4.6.0.210 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/197', 
			'expected' => 'OK'),
		array (
			'title' => 'Apple iPhone', 
			'agent' => 'Mozilla/5.0 (iPhone; U; CPU like Mac OS X; es) AppleWebKit/420.1 (KHTML, like Gecko) Version/3.0 Mobile/4A93 Safari/419.3', 
			'expected' => 'OK'),
		array (
			'title' => 'Firefox 3.0',
			'agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9a1) Gecko/20061204 GranParadiso/3.0a1', 
			'expected' => FALSE),
		array (
			'title' => 'IE 8.0',
			'agent' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)',
			'expected' => FALSE),
		array (
			'title' => 'Apple iPad',
			'agent' => 'Mozilla/5.0 (iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7D11',
			'expected' => 'OK'),
		array (
			'title' => 'Nintendo Wii',
			'agent' => 'Opera/9.30 (Nintendo Wii; U; ; 2047-7; en)',
			'expected' => 'OK'),
		array (
			'title' => 'Sony PS3',
			'agent' => 'Mozilla/5.0 (PLAYSTATION 3; 2.00)',
			'expected' => 'OK')		
		);
	
	echo "<h1>Handset Detection Quick Test</h1>";
	echo "<p>Use this script to check your setup and make sure everything is A-OK.</p>";
	echo "<h3>Test Start</h3>";
	require_once('hdbase.php');
	echo "<br />Calling Handset Detection API Services:<br /><br/>";	
	echo "<table>";
	echo "<tr><td>Test Title</td><td>Test Result</td><td>Is this a Mobile ?</td><td>Is this a Tablet ?</td><td>Is this a Console ?</td></tr>";
	foreach($tests as $test) {
		$hd = new HandsetDetection();
		$hd->setTimeout(10);	
		$hd->detectInit();
		$hd->setDetectVar('user-agent', $test['agent']);
		$ret = $hd->detectAll('product_info');
		$data = $hd->getDetect();
		$error = $hd->getError();
		
		if ($ret) {
			if ($data['message'] == $test['expected']) {
				$result = "<span style='color:green;font-weight:bold;'>PASS</span>";
			} else {
				$result = "<span style='color:red;font-weight:bold;'>Failed with Error ($error)</span>";
			}
		} else {
			if(!$test['expected']) {
				$result = "<span style='color:green;font-weight:bold;'>PASS</span>";
			} else {
				$result = "<span style='color:red;font-weight:bold;'>Failed with Error ($error)</span>";
			}
		}
		$title = $test['title'];
		$ismobile = ($hd->ismobile()) ? 'YES' : 'NO' ;
		$istablet = ($hd->istablet()) ? 'YES' : 'NO' ;
		$isconsole = ($hd->isconsole()) ? 'YES' : 'NO' ;
		
		echo "<tr><td>$title</td><td>$result</td><td>$ismobile</td><td>$istablet</td><td>$isconsole</td></tr>";
		
	}
	echo "</table>";
	echo "<p>Thanks for using the quick test program.</p>";
	echo "<h3>Test End</h3>";
?>