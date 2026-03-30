<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Database Check ===\n\n";

// Count users
$userCount = User::count();
echo "Total users: $userCount\n\n";

// Find the specific user
$user = User::where('email', 'owner@demo-fashion.com')->first();

if ($user) {
    echo "✓ User found!\n";
    echo "  ID: " . $user->id . "\n";
    echo "  Name: " . $user->name . "\n";
    echo "  Email: " . $user->email . "\n";
    echo "  Status: " . $user->status . "\n";
    echo "  Password hash: " . substr($user->password, 0, 20) . "...\n";
    
    // Test password
    echo "\n--- Password Test ---\n";
    $testPassword = 'password';
    $matches = Hash::check($testPassword, $user->password);
    echo "Password '$testPassword' matches: " . ($matches ? 'YES' : 'NO') . "\n";
    
    // List all users
    echo "\n=== All Users ===\n";
    $allUsers = User::all();
    foreach ($allUsers as $u) {
        echo "- {$u->name} ({$u->email})\n";
    }
} else {
    echo "✗ User NOT found!\n";
    echo "\n=== All Users in Database ===\n";
    $allUsers = User::all();
    foreach ($allUsers as $u) {
        echo "- {$u->name} ({$u->email})\n";
    }
}
