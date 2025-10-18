<?php
// JWT Test
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';
require_once 'api/flutter/flutter_api.php';

echo "=== JWT TEST ===\n";

class JWTTest extends FlutterAPI {
    public function testJWT() {
        // Test token oluştur
        $test_token = $this->generateJWT(1, 1, 'super_admin');
        echo "Generated Token: " . substr($test_token, 0, 50) . "...\n";
        
        // Token'ı doğrula
        $validated = $this->validateJWT($test_token);
        
        if ($validated) {
            echo "✅ JWT Token validation SUCCESS\n";
            echo "Validated Data: " . json_encode($validated) . "\n";
        } else {
            echo "❌ JWT Token validation FAILED\n";
        }
        
        return $test_token;
    }
}

$test = new JWTTest();
$token = $test->testJWT();

echo "\n=== TEST COMPLETED ===\n";
?>
