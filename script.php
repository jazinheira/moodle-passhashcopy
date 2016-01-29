<?php

$mysql_moodle_host = "";
$mysql_moodle_user = "";
$mysql_moodle_pass = "";
$mysql_moodle_db = ""; //Moodle database name
$mysql_moodle_table = "mdl_user"; //Usually mdl_user
$mysql_moodle_emailfield = "email";
$mysql_moodle_passwordfield = "password";

$mysql_ext_host = "";
$mysql_ext_user = "";
$mysql_ext_pass = "";
$mysql_ext_db = ""; //External database name
$mysql_ext_table = "users"; //User table with email and password hash
$mysql_ext_emailfield = "email"; //Field name with the email address
$mysql_ext_passwordfield = "encrypted_password"; //Field name with the password hashes

//Connect to external database
$extdb = new mysqli($mysql_ext_host, $mysql_ext_user, $mysql_ext_pass, $mysql_ext_db);
if ($extdb->connect_errno) {
    echo "Failed to connect to ". $extdb->host_info .": (" . $extdb->connect_errno . ") " . $extdb->connect_error;
}
echo "Connected to " . $extdb->host_info . "\n";

//Get password hashes and user emails from external database
$query = "SELECT " . $mysql_ext_emailfield . "," . $mysql_ext_passwordfield . " FROM " . $mysql_ext_table . " ORDER BY " . $mysql_ext_emailfield . " ASC";
$usersres = $extdb->query($query);

$usersres->data_seek(0);
while ($row = $usersres->fetch_assoc()) {
	$users[] = array( 'email' => $row[$mysql_ext_emailfield], 'password' => $row[$mysql_ext_passwordfield]); //Add each unique user email and password hash to $users array
	echo "Adding user " . $row[$mysql_ext_emailfield] . " to array \n";
}

echo "Found " . count($users) . " users in external database \n";

$usersres->close(); //Destroy $courseres result to save memory
mysqli_close($extdb); //Disconnect from external database


//Connect to moodle database
$moodledb = new mysqli($mysql_moodle_host, $mysql_moodle_user, $mysql_moodle_pass, $mysql_moodle_db);
if ($moodledb->connect_errno) {
	echo "Failed to connect to ". $moodledb->host_info .": (" . $moodledb->connect_errno . ") " . $moodledb->connect_error;
}
echo "Connected to " . $moodledb->host_info . "\n";

//Update the password hash in the moodle database
foreach ($users as $user) {
	$query = "UPDATE ".$mysql_moodle_table." SET ".$mysql_moodle_passwordfield."  = '".$user['password']."' WHERE ".$mysql_moodle_emailfield." = '".$user['email']."'";
	if ($moodledb->query($query)) {
		echo ("Successfully updated password for " . $user['email'] . "\n");
	}
	else {
		echo ("Unable to update password for " . $user['email'] . "ERROR ". $moodledb->error."\n");
		$badusers[] = $user;
	}
}
unset($users);
mysqli_close($moodledb); //Disconnect from external database

if (isset($badusers)) {
	//We had some users that weren't able to be updated; print these.
	echo("\n\n--------\nUnable to update password for the following users:\n");
	foreach ($badusers as $baduser) {
		echo($baduser['email']."\n");
	}
}
?>