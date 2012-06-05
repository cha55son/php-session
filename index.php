<?php
	function debug($var) {
		echo '<br /><pre>';
		var_dump($var);
		echo '</pre><br />';
	}
	function announce($var) {
		echo '<h3>'.$var.'</h3>';
	}

	ob_start();
	require __DIR__.'/db/Zebra_Database.php';
	$db = new Zebra_Database();
	$db->debug = true;
	$db->connect('localhost', 'session', 's3ss10n', 'session');
	$db->set_charset('utf8');

	require __DIR__.'/Session.php';
	$session = new Session($db);
	$_SESSION['custom'] = uniqid();
	$data = ob_get_contents();
	ob_end_clean();
?>
<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" href="/session/css/bootstrap.min.css" />
		<title>Chason Session Test</title>
	</head>
	<body>
		<div class="container">
			<?php echo $data; ?>
			HTML Document
			<?php $db->show_debug_console(); ?>
		</div>
	</body>
</html>