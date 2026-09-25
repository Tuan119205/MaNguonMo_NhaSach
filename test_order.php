<?php
require_once "./functions/database_functions.php";
require_once "./db_migration.php";
runDatabaseMigrations();

$conn = db_connect();
$orderId = insertIntoOrder($conn, 2, 250000, date('Y-m-d H:i:s'), 'Trần Công Tuấn', '123 Cầu Giấy', 'Hà Nội', '100000', 'Việt Nam', 'bank_transfer', 'chờ_xử_lý', '0384163051', 'Giao trong giờ hành chính');

echo "Successfully created order ID: " . $orderId . "\n";

$itemQuery = "INSERT INTO order_items (orderid, book_isbn, item_price, quantity) VALUES ('$orderId', '64568', '250000', 1)";
$res = mysqli_query($conn, $itemQuery);
echo "Order items insert result: " . ($res ? "OK" : mysqli_error($conn)) . "\n";

$orders = getUserOrders($conn, 2);
echo "User 2 orders count: " . count($orders) . "\n";
echo "First order details:\n";
print_r($orders[0]);

// Clean up test order
mysqli_query($conn, "DELETE FROM order_items WHERE orderid = '$orderId'");
mysqli_query($conn, "DELETE FROM orders WHERE orderid = '$orderId'");
echo "Cleaned up test order.\n";
