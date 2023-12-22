<?php

function Connect()
{
	$dbhost = "localhost";
	$dbuser = "root";
	$dbpass = "";
	$dbname = "carrentalp";

	try {
		$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

		if ($conn->connect_error) {
			header("Location: error.php?message=" . urlencode("Failed to connect to the database."));
			exit;
		}

		return $conn;
	} catch (Exception $e) {
		header("Location: error.php?message=" . urlencode("An unexpected error occurred."));
		exit;
	}
}

?>
