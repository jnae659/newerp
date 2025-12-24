<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\AffiliateLinkClick;

// Check if referral code exists
$referralCode = '129006';
$user = User::where('referral_code', $referralCode)->first();

if ($user) {
    echo "✅ User found: {$user->name} (ID: {$user->id})\n";

    // Check existing clicks
    $clicks = AffiliateLinkClick::where('affiliate_id', $user->id)->get();
    echo "📊 Existing clicks for this affiliate: " . $clicks->count() . "\n";

    if ($clicks->count() > 0) {
        echo "Recent clicks:\n";
        foreach ($clicks->take(5) as $click) {
            echo "  - Click ID: {$click->id}, IP: {$click->ip_address}, Time: {$click->clicked_at}\n";
        }
    }
} else {
    echo "❌ User with referral code '{$referralCode}' not found\n";

    // Show some sample referral codes
    echo "Sample existing referral codes:\n";
    $sampleUsers = User::whereNotNull('referral_code')->take(5)->get();
    foreach ($sampleUsers as $sampleUser) {
        echo "  - {$sampleUser->name}: {$sampleUser->referral_code}\n";
    }
}

echo "\n🏁 Test completed\n";
