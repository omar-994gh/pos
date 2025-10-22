<?php

$dbPath = '../config/config.sqlite';
echo $dbPath;
// Tables to preserve - data will NOT be deleted from these tables
$tablesToPreserve = [
    'Licenses',
    'System_Settings',
    'ActivationCodes',
    'Invoice_Settings',
    'Users'
];

//echo "<h2>POS Database Cleanup Script</h2>";
//echo "<p>Starting database cleanup process...</p>";

// Use a try-catch block for robust error handling
try {
    // 1. Establish a connection to the SQLite database
    $pdo = new PDO("sqlite:" . $dbPath);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //echo "<p>Connected to database successfully.</p>";

    // 2. Fetch all table names from the database
    $query = "SELECT name FROM sqlite_master WHERE type='table'";
    $stmt = $pdo->query($query);
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    //echo "<p>Found " . count($allTables) . " tables in the database.</p>";
    //echo "<p>The following tables will be **preserved**: " . implode(', ', $tablesToPreserve) . "</p>";

    // 3. Loop through all tables and clear data if they are not in the preservation list
    //echo "<h3>Clearing Data from Tables:</h3>";
    foreach ($allTables as $table) {
        if (!in_array($table, $tablesToPreserve)) {
            // Check if the table actually exists before attempting to delete
            $tableExistsQuery = "SELECT name FROM sqlite_master WHERE type='table' AND name = ?";
            $tableExistsStmt = $pdo->prepare($tableExistsQuery);
            $tableExistsStmt->execute([$table]);
            
            if ($tableExistsStmt->fetch()) {
                // Execute the DELETE statement
                echo "<p style='color: #d9534f;'>- Deleting all data from table: **" . htmlspecialchars($table) . "**</p>";
                $pdo->exec("DELETE FROM " . $table);
            } else {
                echo "<p style='color: #f0ad4e;'>- Skipping table '" . htmlspecialchars($table) . "': does not exist.</p>";
            }
        } else {
            //echo "<p style='color: #5cb85c;'>- Skipping table '" . htmlspecialchars($table) . "': it is in the preservation list.</p>";
        }
    }

    //echo "<h3>Cleanup Completed!</h3>";
    //echo "<p style='color: #337ab7;'>Database cleanup process finished successfully. The data in the specified tables has been removed.</p>";
    
    // Close the database connection
    $pdo = null;

} catch (PDOException $e) {
    // Handle any PDO-related errors
    //echo "<h3>An error occurred!</h3>";
    //echo "<p style='color: #d9534f;'>Database Error: " . $e->getMessage() . "</p>";
    
    // Attempt to close the connection in case of an error
    $pdo = null;
    exit();

} catch (Exception $e) {
    // Handle any other general errors
    //echo "<h3>An error occurred!</h3>";
    //echo "<p style='color: #d9534f;'>General Error: " . $e->getMessage() . "</p>";
    
    exit();
}
