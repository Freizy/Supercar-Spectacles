<?php
// backup_database.php
session_start();
require_once '../config/database.php';

// Load the configuration directly from the new file
$db_config = require '../config/db_config.php';

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit();
}

// ... rest of the code as before
$host = $db_config['host'];
$dbname = $db_config['dbname'];
$username = $db_config['username'];
$password = $db_config['password'];

// Define the output filename
$filename = 'supercar_spectacles_backup_' . date('Y-m-d_H-i-s') . '.sql';

// Path to the mysqldump utility.
// You may need to change this path depending on your server's configuration.
// Common paths: /usr/bin/mysqldump, /usr/local/bin/mysqldump
$mysqldump_path = 'mysqldump'; // Assumes mysqldump is in the system's PATH

// Build the command to execute
$command = "{$mysqldump_path} --opt --host={$host} --user={$username} --password={$password} {$dbname}";

// Set headers to force the browser to download the file
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Execute the command and output the file directly to the browser
passthru($command);

// Set a success message and redirect
$_SESSION['success_message'] = "Database backup downloaded successfully.";

// Although the file is downloaded, a final redirect is good practice.
// However, note that a redirect after headers have been sent may not work as expected.
// We'll trust the download process to complete.

// exit(); // This is often used but can sometimes cause issues.

// It's more reliable to set the session message and then immediately exit,
// as the browser will already be handling the file download.
// We'll rely on the passthru() function to handle the output.
exit();
