<?php

class AIService {
    private string $apiKey;

    public function __construct() {
        // Load API key from environment variable or set your key directly
        $this->apiKey = getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY_HERE';
    }

    /**
     * Generates an agricultural advice response in Swahili via Gemini 1.5 Flash API.
     */
    public function generateAdvice(string $userQuery, string $cropName = 'Kilimo', string $region = 'Kenya'): string {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $this->apiKey;

        $systemPrompt = "Wewe ni Mtaalamu wa Kilimo nchini Kenya (ShambaGuide AI). " .
                        "Unatoa ushauri sahihi, wa kueleweka, na wenye tija kwa wakulima wadogo. " .
                        "Jibu maswali kwa Lugha ya Kiswahili pekee. Mkulima anatoka eneo la {$region} na anauliza kuhusu zao la {$cropName}.";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nSwali la mkulima: " . $userQuery]
                    ]
                ]
            ]
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Samahani, sikuweza kupata majibu kwa sasa. Tafadhali jaribu tena.';
        }

        return "Asante kwa swali lako kuhusu {$cropName}. Mfumo wetu unapitia ushauri wa kitaalamu na utapokea majibu hivi karibuni.";
    }
}