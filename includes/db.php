<?php
require_once __DIR__ . '/../config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Errore di connessione: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

function getConnection(): mysqli {
    global $conn;
    return $conn;
}

function query(string $sql, string $types = '', ...$params) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) die("Prepare error: " . $conn->error . " — $sql");
    if ($types !== '' && count($params) > 0) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

function queryOne(string $sql, string $types = '', ...$params): ?array {
    $result = query($sql, $types, ...$params);
    return $result ? ($result->fetch_assoc() ?: null) : null;
}

function queryAll(string $sql, string $types = '', ...$params): array {
    $result = query($sql, $types, ...$params);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function execute(string $sql, string $types = '', ...$params) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) die("Prepare error: " . $conn->error . " — $sql");
    if ($types !== '' && count($params) > 0) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt;
}
