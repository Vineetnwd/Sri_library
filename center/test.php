<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . "/../system/db.php";

echo "Starting DB Setup for Expenses...<br>\n";

$sql1 = "CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `center_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if(mysqli_query($conn, $sql1)){
    echo "Table expense_categories created successfully.<br>\n";
} else {
    echo "Error creating expense_categories: " . mysqli_error($conn) . "<br>\n";
}

$sql2 = "CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `center_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `remark` text DEFAULT NULL,
  `source` varchar(50) DEFAULT 'Cash',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if(mysqli_query($conn, $sql2)){
    echo "Table expenses created successfully.<br>\n";
} else {
    echo "Error creating expenses: " . mysqli_error($conn) . "<br>\n";
}

echo "Setup Complete!\n";
