<?php
require_once('hdbase.php');

$hd = new HandsetDetection();

$hd->detectInit();

$ret = $hd->detectAll('product_info, ajax, markup, display, rss');
?>
<!DOCTYPE html>
<head>
	<title>Handset Detection test</title>
</head>
<body>
	<h1>Handset Detection test</h1>
	<?php
	if ($ret) {
		$data = $hd->getDetect();
		echo '<pre>';
		print_r($data);
		echo '</pre>';
	}
	?>
</body>