<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'dija_accessories';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "ALTER TABLE users
    ADD COLUMN security_question_1 VARCHAR(255),
    ADD COLUMN security_answer_1_hash VARCHAR(255),
    ADD COLUMN security_question_2 VARCHAR(255),
    ADD COLUMN security_answer_2_hash VARCHAR(255)";

    $pdo->exec($sql);
    echo "Table 'users' updated successfully.";

} catch (PDOException $e) {
    echo "Error updating table: " . $e->getMessage();
}
?>