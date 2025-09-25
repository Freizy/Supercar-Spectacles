<?php

/**
 * Supercar Spectacles Admin Panel
 * Script to clear the application's cache.
 * This is a placeholder and should be customized based on your
 * specific caching implementation (e.g., file-based, Redis, Memcached).
 */

session_start();
require_once '../config/database.php';

// Check for admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php');
  exit();
}

// Define the directory where your cache files are stored.
// MAKE SURE this path is correct and writable by the web server.
$cache_dir = realpath(__DIR__ . '/../cache/');

if ($cache_dir && is_dir($cache_dir)) {
  // Function to recursively delete files and directories
  function delete_directory($dir)
  {
    if (!is_dir($dir)) {
      return false;
    }
    $items = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
      if ($item->isDir()) {
        rmdir($item->getRealPath());
      } else {
        unlink($item->getRealPath());
      }
    }
    return true;
  }

  try {
    // Clear the cache directory
    delete_directory($cache_dir);

    $_SESSION['success_message'] = "Cache cleared successfully!";
  } catch (Exception $e) {
    $_SESSION['error_message'] = "Failed to clear cache: " . $e->getMessage();
  }
} else {
  $_SESSION['error_message'] = "Cache directory not found or is not a directory.";
}

// Redirect back to the settings page
header('Location: settings.php');
exit();
