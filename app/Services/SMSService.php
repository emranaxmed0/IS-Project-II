<?php

class SMSService {
    private string $username;
    private string $apiKey;
    private string $apiUrl;

    public function __construct() {
        // Set to 'sandbox' for testing, or your registered Africa's Talking app username for production
        $this->username = getenv('AT_USERNAME') ?: 'sandbox';
        
        // Replace with your Africa's Talking API Key from https://dashboard.africastalking.com/
        $this->apiKey   = getenv('AT_API_KEY') ?: 'atsk_b977a953421b6801f00a3e1d6c738e31cf2fb9c8888f5a7299d5e8b5c4172354082f73d4';

        // Select API endpoint based on username
        if (strtolower($this->username) === 'sandbox') {
            $this->apiUrl = "https://api.sandbox.africastalking.com/version1/messaging";
        } else {
            $this->apiUrl = "https://api.africastalking.com/version1/messaging";
        }
    }

    /**
     * Sends SMS to a single recipient
     * 
     * @param string $toPhoneNumber Phone number in format (0712345678 or +254712345678)
     * @param string $message Text content of the SMS
     * @return array Result containing status boolean and response data/error
     */
    public function sendSMS(string $toPhoneNumber, string $message): array {
        $formattedPhone = $this->formatKenyanPhoneNumber($toPhoneNumber);
        
        if (empty($formattedPhone)) {
            return [
                'success' => false, 
                'error'   => 'Namba ya simu si sahihi (Invalid Kenyan phone number)'
            ];
        }

        $postData = [
            'username' => $this->username,
            'to'       => $formattedPhone,
            'message'  => $message
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'apiKey: ' . trim($this->apiKey)
        ]);

        // SSL Bypass for local localhost/XAMPP environments
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'cURL Error: ' . $curlError];
        }

        if ($httpCode === 201 || $httpCode === 200) {
            $responseData = json_decode($response, true);
            return [
                'success' => true,
                'data'    => $responseData
            ];
        }

        return [
            'success' => false, 
            'error'   => 'HTTP Error ' . $httpCode . ': ' . $response
        ];
    }

    /**
     * Formats local phone numbers into international E.164 format (+254...)
     */
    private function formatKenyanPhoneNumber(string $phone): string {
        $phone = preg_replace('/[^0-9+]/', '', trim($phone));
        
        if (substr($phone, 0, 1) === '0') {
            return '+254' . substr($phone, 1);
        } elseif (substr($phone, 0, 3) === '254') {
            return '+' . $phone;
        } elseif (substr($phone, 0, 4) === '+254') {
            return $phone;
        }
        
        return $phone;
    }
}