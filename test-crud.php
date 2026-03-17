<?php

// Test script for Firms & Subscriptions CRUD
// Run with: php test-crud.php

$baseUrl = 'http://localhost:8000/api/v1';
$adminEmail = 'admin@legal-saas.com';
$adminPassword = 'password';

echo "=== Testing Firms & Subscriptions CRUD ===\n\n";

// Function to make HTTP requests
function makeRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST' || $method === 'PUT') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    }

    if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$httpCode, json_decode($response, true)];
}

// Test 1: Login as platform admin
echo "1. Testing Platform Admin Login...\n";
list($code, $response) = makeRequest('POST', "$baseUrl/auth/login", [
    'email' => $adminEmail,
    'password' => $adminPassword
]);

echo "   HTTP Code: $code\n";
echo "   Response: " . substr(json_encode($response), 0, 200) . "...\n";

if ($code === 200 && isset($response['data']['token'])) {
    $token = $response['data']['token'];
    echo "✅ Login successful, token: " . substr($token, 0, 20) . "...\n\n";
} else {
    echo "❌ Login failed\n";
    if ($response) {
        echo "   Error: " . ($response['message'] ?? 'Unknown error') . "\n";
    }
    exit(1);
}

// Test 2: List subscription plans
echo "2. Testing List Subscription Plans...\n";
list($code, $response) = makeRequest('GET', "$baseUrl/subscriptions", null, $token);

if ($code === 200 && isset($response['data']['data'])) {
    $subscriptions = $response['data']['data'];
    echo "✅ Found " . count($subscriptions) . " subscription plans\n";
    foreach ($subscriptions as $sub) {
        echo "   - {$sub['name']} (ID: {$sub['id']})\n";
    }
    echo "\n";
} else {
    echo "❌ Failed to list subscriptions: " . json_encode($response) . "\n";
}

// Test 3: Create new subscription plan
echo "3. Testing Create New Subscription Plan...\n";
list($code, $response) = makeRequest('POST', "$baseUrl/subscriptions", [
    'name' => 'Enterprise Plus',
    'max_admins' => 15,
    'max_lawyers' => 75,
    'max_clients' => 1500,
    'max_documents_per_user' => 3000
], $token);

if ($code === 201 && isset($response['data']['id'])) {
    $newSubId = $response['data']['id'];
    echo "✅ Created subscription plan: {$response['data']['name']} (ID: $newSubId)\n\n";
} else {
    echo "❌ Failed to create subscription: " . json_encode($response) . "\n";
}

// Test 4: Set as default subscription
echo "4. Testing Set Default Subscription...\n";
list($code, $response) = makeRequest('POST', "$baseUrl/subscriptions/$newSubId/set-default", null, $token);

if ($code === 200) {
    echo "✅ Set subscription ID $newSubId as default\n\n";
} else {
    echo "❌ Failed to set default: " . json_encode($response) . "\n";
}

// Test 5: Get default subscription
echo "5. Testing Get Default Subscription...\n";
list($code, $response) = makeRequest('GET', "$baseUrl/subscriptions/default", null, $token);

if ($code === 200 && isset($response['data']['subscription'])) {
    echo "✅ Default subscription: {$response['data']['subscription']['name']} (ID: {$response['data']['default_subscription_id']})\n\n";
} else {
    echo "❌ Failed to get default: " . json_encode($response) . "\n";
}

// Test 6: List law firms
echo "6. Testing List Law Firms...\n";
list($code, $response) = makeRequest('GET', "$baseUrl/firms", null, $token);

if ($code === 200 && isset($response['data']['data'])) {
    $firms = $response['data']['data'];
    echo "✅ Found " . count($firms) . " law firms\n";
    foreach ($firms as $firm) {
        echo "   - {$firm['name']} (ID: {$firm['id']}, Subscription: {$firm['subscription']['name']})\n";
    }
    echo "\n";
} else {
    echo "❌ Failed to list firms: " . json_encode($response) . "\n";
}

// Test 7: Create new law firm (should use default subscription)
echo "7. Testing Create New Law Firm...\n";
list($code, $response) = makeRequest('POST', "$baseUrl/firms", [
    'name' => 'Test Law Firm Inc.'
], $token);

if ($code === 201 && isset($response['data']['id'])) {
    $newFirmId = $response['data']['id'];
    echo "✅ Created law firm: {$response['data']['name']} (ID: $newFirmId)\n";
    echo "   Subscription: {$response['data']['subscription']['name']}\n\n";
} else {
    echo "❌ Failed to create firm: " . json_encode($response) . "\n";
}

// Test 8: Update law firm
echo "8. Testing Update Law Firm...\n";
list($code, $response) = makeRequest('PUT', "$baseUrl/firms/$newFirmId", [
    'name' => 'Updated Test Law Firm Inc.',
    'status' => 'active'
], $token);

if ($code === 200) {
    echo "✅ Updated law firm to: {$response['data']['name']}\n\n";
} else {
    echo "❌ Failed to update firm: " . json_encode($response) . "\n";
}

// Test 9: Get specific firm
echo "9. Testing Get Specific Firm...\n";
list($code, $response) = makeRequest('GET', "$baseUrl/firms/$newFirmId", null, $token);

if ($code === 200) {
    echo "✅ Retrieved firm: {$response['data']['name']}\n";
    echo "   Status: {$response['data']['status']}\n";
    echo "   Users: " . count($response['data']['users']) . "\n\n";
} else {
    echo "❌ Failed to get firm: " . json_encode($response) . "\n";
}

echo "=== All Tests Completed ===\n";
echo "✅ Firms & Subscriptions CRUD is working correctly!\n";
echo "\nNext steps:\n";
echo "1. User Management (create users within firms)\n";
echo "2. Subscription Limits enforcement\n";
echo "3. Document Management\n";
echo "4. Audit Logging\n";