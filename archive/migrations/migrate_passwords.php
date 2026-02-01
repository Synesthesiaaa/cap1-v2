<?php
/**
 * Password Migration Script
 * 
 * Migrates all plain text passwords to hashed passwords using password_hash()
 * 
 * Usage: php migrate_passwords.php
 * 
 * WARNING: This script modifies the database. Make a backup first!
 */

require_once __DIR__ . '/db.php';

echo "Password Migration Script\n";
echo "========================\n\n";

// Get all users with plain text passwords (passwords less than 60 characters are likely plain text)
$sql = "SELECT user_id, email, password FROM tbl_user WHERE LENGTH(password) < 60";
$result = $conn->query($sql);

$userCount = 0;
$userErrors = 0;

if ($result && $result->num_rows > 0) {
    echo "Found {$result->num_rows} users with plain text passwords.\n\n";
    
    while ($row = $result->fetch_assoc()) {
        $userId = $row['user_id'];
        $email = $row['email'];
        $plainPassword = $row['password'];
        
        // Hash the password
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        // Update the database
        $updateSql = "UPDATE tbl_user SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($updateSql);
        
        if ($stmt) {
            $stmt->bind_param("si", $hashedPassword, $userId);
            if ($stmt->execute()) {
                $userCount++;
                echo "✓ Migrated password for user: {$email} (ID: {$userId})\n";
            } else {
                $userErrors++;
                echo "✗ Failed to migrate password for user: {$email} (ID: {$userId}) - {$stmt->error}\n";
            }
            $stmt->close();
        } else {
            $userErrors++;
            echo "✗ Failed to prepare statement for user: {$email} (ID: {$userId})\n";
        }
    }
} else {
    echo "No users with plain text passwords found.\n";
}

echo "\n";

// Get all technicians with plain text passwords
$sql = "SELECT technician_id, email, password FROM tbl_technician WHERE LENGTH(password) < 60";
$result = $conn->query($sql);

$techCount = 0;
$techErrors = 0;

if ($result && $result->num_rows > 0) {
    echo "Found {$result->num_rows} technicians with plain text passwords.\n\n";
    
    while ($row = $result->fetch_assoc()) {
        $techId = $row['technician_id'];
        $email = $row['email'];
        $plainPassword = $row['password'];
        
        // Hash the password
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        // Update the database
        $updateSql = "UPDATE tbl_technician SET password = ? WHERE technician_id = ?";
        $stmt = $conn->prepare($updateSql);
        
        if ($stmt) {
            $stmt->bind_param("si", $hashedPassword, $techId);
            if ($stmt->execute()) {
                $techCount++;
                echo "✓ Migrated password for technician: {$email} (ID: {$techId})\n";
            } else {
                $techErrors++;
                echo "✗ Failed to migrate password for technician: {$email} (ID: {$techId}) - {$stmt->error}\n";
            }
            $stmt->close();
        } else {
            $techErrors++;
            echo "✗ Failed to prepare statement for technician: {$email} (ID: {$techId})\n";
        }
    }
} else {
    echo "No technicians with plain text passwords found.\n";
}

echo "\n";
echo "Migration Summary:\n";
echo "==================\n";
echo "Users migrated: {$userCount}\n";
echo "User errors: {$userErrors}\n";
echo "Technicians migrated: {$techCount}\n";
echo "Technician errors: {$techErrors}\n";
echo "\n";
echo "Migration complete!\n";

$conn->close();
