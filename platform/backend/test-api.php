<?php

// Simple API test script
$url = 'http://127.0.0.1:8000/api/v1/auth/login';
$data = [
    'email' => 'owner@demo-fashion.com',
    'password' => 'password',
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response:\n";
echo $response . "\n";

// Parse and display formatted
$json = json_decode($response, true);
if ($json) {
    echo "\n=== Parsed Response ===\n";
    echo "User: " . ($json['user']['name'] ?? 'N/A') . " (" . ($json['user']['email'] ?? 'N/A') . ")\n";
    if (isset($json['token'])) {
        echo "Token: " . substr($json['token'], 0, 20) . "...\n";
    }
    if (isset($json['stores'])) {
        echo "Stores:\n";
        foreach ($json['stores'] as $store) {
            echo "  - {$store['name']} (ID: {$store['id']}, Role: {$store['role']})\n";
        }
    }
}
