<?php
	function debug($var) {
		echo '<br /><pre>';
		var_dump($var);
		echo '</pre><br />';
	}
	require 'db/Zebra_Database.php';
	$db = new Zebra_Database();
	$db->debug = true;
	$db->connect('localhost', 'session', 's3ss10n', 'session');
	$db->set_charset('utf8');
	require 'Session.php';
	$session = new Session($db);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Chason Session Test</title>
	</head>
	<body>
		HI
		<?php $db->show_debug_console(); ?>
	</body>
</html>