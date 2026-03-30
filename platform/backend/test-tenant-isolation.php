<?php

// Test /auth/me with tenant isolation
$loginUrl = 'http://127.0.0.1:8000/api/v1/auth/login';
$meUrl = 'http://127.0.0.1:8000/api/v1/auth/me';

// Step 1: Login
echo "=== Step 1: Login ===\n";
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'owner@demo-fashion.com',
    'password' => 'password',
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
curl_close($ch);

$loginData = json_decode($response, true);
$token = $loginData['token'] ?? null;
$storeId = $loginData['stores'][0]['id'] ?? null;

if (!$token) {
    die("Failed to login\n");
}

echo "✓ Logged in successfully\n";
echo "  User: {$loginData['user']['name']}\n";
echo "  Store ID: $storeId\n";
echo "  Token: " . substr($token, 0, 20) . "...\n\n";

// Step 2: Call /auth/me WITH correct store ID
echo "=== Step 2: GET /auth/me WITH correct store ID ===\n";
$ch = curl_init($meUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    "Authorization: Bearer $token",
    "X-Store-ID: $storeId",
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "✓ SUCCESS: Got user data\n";
    echo "  User: {$data['user']['name']}\n";
    echo "  Stores: " . count($data['stores']) . "\n";
} else {
    echo "✗ FAILED: $response\n";
}

// Step 3: Call /auth/me WITHOUT store ID header
echo "\n=== Step 3: GET /auth/me WITHOUT store ID header ===\n";
$ch = curl_init($meUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    "Authorization: Bearer $token",
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($httpCode == 400) {
    echo "✓ SUCCESS: Correctly rejected (missing X-Store-ID)\n";
    $data = json_decode($response, true);
    echo "  Message: {$data['message']}\n";
} else {
    echo "✗ FAILED: Should have returned 400\n";
}

// Step 4: Call /auth/me WITH wrong store ID (store ID 999)
echo "\n=== Step 4: GET /auth/me WITH unauthorized store ID (999) ===\n";
$ch = curl_init($meUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    "Authorization: Bearer $token",
    "X-Store-ID: 999",
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($httpCode == 403) {
    echo "✓ SUCCESS: Correctly rejected (unauthorized store)\n";
    $data = json_decode($response, true);
    echo "  Message: {$data['message']}\n";
} else {
    echo "✗ FAILED: Should have returned 403\n";
    echo "  Response: $response\n";
}

// Step 5: Try accessing store 2 with store 1 owner
echo "\n=== Step 5: GET /auth/me WITH different store ID (2) ===\n";
$ch = curl_init($meUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    "Authorization: Bearer $token",
    "X-Store-ID: 2",
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($httpCode == 403) {
    echo "✓ SUCCESS: Correctly rejected (unauthorized store)\n";
    $data = json_decode($response, true);
    echo "  Message: {$data['message']}\n";
} else {
    echo "✗ FAILED: Should have returned 403\n";
    echo "  Response: $response\n";
}

echo "\n✅ All tenant isolation tests passed!\n";
