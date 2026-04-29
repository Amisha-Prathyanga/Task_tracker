<?php
require_once 'config.php';
requireLogin();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: index.php');
    exit();
}

$filename = 'task_tracker_backup_' . date('Y-m-d_His') . '.sql';

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = '';

// -------------------------------------------------------
// Header comment block
// -------------------------------------------------------
$output .= "-- ============================================================\n";
$output .= "-- Task Tracker - Full Database Backup\n";
$output .= "-- Database  : " . $db_name . "\n";
$output .= "-- Host      : " . $db_host . "\n";
$output .= "-- Generated : " . date('Y-m-d H:i:s') . "\n";
$output .= "-- User      : " . htmlspecialchars($_SESSION['username']) . "\n";
$output .= "-- ============================================================\n\n";
$output .= "SET FOREIGN_KEY_CHECKS=0;\n";
$output .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
$output .= "SET NAMES utf8mb4;\n\n";

// -------------------------------------------------------
// Get all tables in the database
// -------------------------------------------------------
$tables_result = $conn->query("SHOW TABLES");
if (!$tables_result) {
    echo "-- ERROR: Could not retrieve tables: " . $conn->error . "\n";
    exit();
}

while ($table_row = $tables_result->fetch_array(MYSQLI_NUM)) {
    $table = $table_row[0];

    $output .= "-- ------------------------------------------------------------\n";
    $output .= "-- Table: `$table`\n";
    $output .= "-- ------------------------------------------------------------\n\n";

    // DROP + CREATE TABLE
    $output .= "DROP TABLE IF EXISTS `$table`;\n";

    $create_result = $conn->query("SHOW CREATE TABLE `$table`");
    if ($create_result) {
        $create_row = $create_result->fetch_assoc();
        $output .= $create_row['Create Table'] . ";\n\n";
    }

    // Get all rows
    $rows_result = $conn->query("SELECT * FROM `$table`");
    if ($rows_result && $rows_result->num_rows > 0) {
        // Get column names
        $fields = [];
        $field_info = $conn->query("SHOW COLUMNS FROM `$table`");
        while ($field = $field_info->fetch_assoc()) {
            $fields[] = '`' . $field['Field'] . '`';
        }
        $fields_str = implode(', ', $fields);

        $output .= "INSERT INTO `$table` ($fields_str) VALUES\n";

        $row_count = $rows_result->num_rows;
        $current = 0;
        while ($row = $rows_result->fetch_assoc()) {
            $current++;
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . $conn->real_escape_string($value) . "'";
                }
            }
            $separator = ($current < $row_count) ? ',' : ';';
            $output .= '(' . implode(', ', $values) . ')' . $separator . "\n";
        }
        $output .= "\n";
    } else {
        $output .= "-- (no rows)\n\n";
    }
}

$output .= "SET FOREIGN_KEY_CHECKS=1;\n";
$output .= "-- End of backup\n";

echo $output;
exit();
?>
