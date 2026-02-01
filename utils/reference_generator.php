<?php
function generateReferenceId($conn) {
    $count = 0;
    do {
        // Generate a 5-character alphanumeric code
        $random_code = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $reference_id = "TCK-" . $random_code;

        // Check for duplicate ticket id
        $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_ticket WHERE reference_id = ?");
        $stmt->bind_param("s", $reference_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    } while ($count > 0);

    return $reference_id;
}
?>
