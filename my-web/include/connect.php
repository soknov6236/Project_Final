<?php 
	$conn = new mysqli("localhost","root","","nisai_db");
	if (!$conn) {
		die("Database connection failed: " . mysqli_connect_error());
	}
?>