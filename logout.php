<?php
	session_start();
	session_unset(); // Remove session variables
	session_destroy(); // Destroy session

	// Prevent browser from caching the previous page
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Pragma: no-cache");
	header("Expires: 0");

	// Redirect to login page
	echo "<script>
		window.location.replace('index.php');
	</script>";
	exit();
?>
