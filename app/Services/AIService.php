<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/db.php';


class AIService {
    private string $apiKey;
    private PDO $db;

    public function __construct() {
        $this->apiKey = getenv('GEMINI_API_KEY') ?: 'AQ.Ab8RN6LzDFotfIWcCEH6d3QzdaKiPmt83FoOQMAyyEtG9m1afA';

        global $pdo; 
        $this->db = $pdo;
    }

    /**
     * Generates an agricultural advice response in Swahili via Gemini API.
     */
    public function generateAdvice(string $userQuery, string $cropName = 'Kilimo', string $region = 'Kenya'): string {
    $retrievedContext = $this->retrieveDocumentContext($userQuery, $cropName);    
        // Active model endpoints array
        $models = [
            'gemini-3.8-flash',
            'gemini-3.5-flash-lite'
        ];

        $systemPrompt = "Wewe ni Mtaalamu wa Kilimo nchini Kenya (ShambaGuide AI).\n" .
                        "Unatoa ushauri sahihi, wa kueleweka, na wenye tija kwa wakulima wadogo.\n" .
                        "Jibu maswali kwa Lugha ya Kiswahili pekee. Mkulima anatoka eneo la {$region} na anauliza kuhusu zao la {$cropName}.\n\n" .
                        "TUMIA TAARIFA HIZI KUTOKA KWENYE MIONGOZO YA KILIMO (DOCUMENT CONTEXT):\n" .
                        "{$retrievedContext}\n\n" .
                        "Kanuni: Ikiwa taarifa zilizotolewa hapo juu zina jibu la swali, zitumie kikamilifu. Ikiwa hazina, toa ushauri bora wa kitaalamu wa kilimo.";
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nSwali la mkulima: " . $userQuery]
                    ]
                ]
            ]
        ];

        $lastError = '';

        foreach ($models as $model) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . trim($this->apiKey);

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            // Disable SSL certificate verification for local XAMPP testing
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (!empty($aiText)) {
                    return $aiText;
                }
            }

            if (!empty($curlError)) {
                return "cURL Network Error: " . $curlError;
            }

            if (!empty($response)) {
                $errorData = json_decode($response, true);
                $apiErrorMessage = $errorData['error']['message'] ?? $response;
                $lastError = "API Error (HTTP {$httpCode} on {$model}): " . $apiErrorMessage;

                // Stop loop on standard client errors like 400 or 403
                if ($httpCode !== 503 && $httpCode !== 429 && $httpCode !== 404) {
                    break;
                }
            }
        }

        return $lastError ?: "Samahani, sikufanikiwa kuwasiliana na mfumo wa AI kwa sasa.";
    }

    private function retrieveDocumentContext(string $query, string $cropName): string {
        try {
            $sql = "SELECT chunk_text, crop_type FROM document_chunks";
            $params = [];

            if (!empty($cropName) && strtolower($cropName) !== 'kilimo') {
                $sql .= " WHERE LOWER(crop_type) = LOWER(:crop)";
                $params['crop'] = $cropName;
            }

            $sql .= " LIMIT 4";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return "Hakuna mwelekeo wa moja kwa moja uliopatikana kwenye stakabadhi kwa sasa.";
            }

            $contextArray = [];
            foreach ($rows as $row) {
                $contextArray[] = "[Zao: {$row['crop_type']}]\n" . $row['chunk_text'];
            }

            return implode("\n\n---\n\n", $contextArray);
        } catch (Exception $e) {
            return "Hakuna mwelekeo wa moja kwa moja uliopatikana.";
        }
    }
}