<?php
require_once 'config.php';

echo "Database connection test...\n";
if ($db->connect_error) {
    echo "Connection failed: " . $db->connect_error . "\n";
    exit(1);
}
echo "Connected successfully!\n";

// Check if cekler table exists
$result = $db->query('SHOW TABLES LIKE "cekler"');
if ($result->num_rows > 0) {
    echo "cekler table exists!\n";
    
    // Check table structure
    $result = $db->query('DESCRIBE cekler');
    echo "Table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // Check data count
    $result = $db->query('SELECT COUNT(*) as count FROM cekler');
    $count = $result->fetch_assoc()['count'];
    echo "Total records: " . $count . "\n";
    
    if ($count > 0) {
        // Show sample data
        $result = $db->query('SELECT * FROM cekler LIMIT 3');
        echo "Sample data:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- ID: " . $row['id'] . ", Çek No: " . ($row['cek_no'] ?? 'N/A') . ", Tutar: " . ($row['tutar'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "cekler table does not exist!\n";
    
    // Check what tables exist
    $result = $db->query('SHOW TABLES');
    echo "Available tables:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Tables_in_' . DB_NAME] . "\n";
    }
}
?>