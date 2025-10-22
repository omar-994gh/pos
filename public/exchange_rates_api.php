<?php
// public/exchange_rates_api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ExchangeRate.php';

// Check authentication
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$exchangeRate = new ExchangeRate($db);
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($path === 'rates') {
                // Get all exchange rates
                $rates = $exchangeRate->getAllRates();
                echo json_encode(['success' => true, 'data' => $rates]);
            } elseif ($path === 'settings') {
                // Get system settings
                $settings = $exchangeRate->getSystemSettings();
                echo json_encode(['success' => true, 'data' => $settings]);
            } elseif ($path === 'convert') {
                // Convert price
                $amount = (float)($_GET['amount'] ?? 0);
                $from = $_GET['from'] ?? 'USD';
                $to = $_GET['to'] ?? 'SYP';
                
                $converted = $exchangeRate->convertPrice($amount, $from, $to);
                echo json_encode([
                    'success' => true, 
                    'data' => [
                        'original_amount' => $amount,
                        'converted_amount' => $converted,
                        'from_currency' => $from,
                        'to_currency' => $to
                    ]
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($path === 'rates') {
                // Update exchange rate
                if (!Auth::isAdmin()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Admin access required']);
                    exit;
                }
                
                $fromCurrency = $input['from_currency'] ?? '';
                $toCurrency = $input['to_currency'] ?? '';
                $rate = (float)($input['rate'] ?? 0);
                
                if (empty($fromCurrency) || empty($toCurrency) || $rate <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid input data']);
                    exit;
                }
                
                $result = $exchangeRate->setRate($fromCurrency, $toCurrency, $rate);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Exchange rate updated']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update exchange rate']);
                }
            } elseif ($path === 'settings') {
                // Update system settings
                if (!Auth::isAdmin()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Admin access required']);
                    exit;
                }
                
                $enabled = (int)($input['exchange_rate_enabled'] ?? 0);
                $baseCurrency = $input['base_currency'] ?? 'SYP';
                $usdToSypRate = (float)($input['usd_to_syp_rate'] ?? 15000.0);
                $tryToSypRate = (float)($input['try_to_syp_rate'] ?? 500.0);
                
                $result = $exchangeRate->updateSystemSettings($enabled, $baseCurrency, $usdToSypRate, $tryToSypRate);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Settings updated']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update settings']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>

