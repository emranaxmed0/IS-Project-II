<?php
session_start();

// 1. Session & Auth Check
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Services/SMSService.php';

// Helper function to format phone numbers to international standard required by AT Sandbox (+254...)
function formatKenyanPhoneNumber($phone) {
    $phone = preg_replace('/[^\d+]/', '', trim($phone));
    if (substr($phone, 0, 1) === '0') {
        return '+254' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) === '254' && substr($phone, 0, 1) !== '+') {
        return '+' . $phone;
    }
    return $phone;
}

// Extract extension officer ID safely
$senderId = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiverId  = filter_input(INPUT_POST, 'receiver_id', FILTER_VALIDATE_INT);
    $cropName    = trim($_POST['crop_name'] ?? '');
    $subject     = trim($_POST['subject'] ?? 'Ushauri wa Kilimo');
    $messageText = trim($_POST['message_text'] ?? '');
    $sendSms     = isset($_POST['send_sms']) ? true : false;

    if ($receiverId && !empty($messageText)) {
        try {
            // 2. Fetch target farmer details from lowercase `user` table
            $userStmt = $pdo->prepare("SELECT user_id, user_name, phone_number FROM `user` WHERE user_id = :id");
            $userStmt->execute([':id' => $receiverId]);
            $farmer = $userStmt->fetch(PDO::FETCH_ASSOC);

            if (!$farmer) {
                $_SESSION['msg_error'] = "Mkulima aliyeteuliwa hakupatikana.";
                header("Location: extensiondashboard.php?farmer_id={$receiverId}");
                exit();
            }

            $farmerName  = $farmer['user_name'] ?? 'Mkulima';
            $rawPhone    = $farmer['phone_number'] ?? '';
            $phoneNumber = formatKenyanPhoneNumber($rawPhone);
            $smsStatus   = 'Pending';

            // 3. Dispatch SMS via Africa's Talking API if checkbox is checked
            if ($sendSms && !empty($phoneNumber)) {
                $smsService = new SMSService();
                $smsContent = "ShambaGuide: Habari {$farmerName}, umepata ushauri kuhusu zao la {$cropName}:\n" . $messageText;
                
                // Trigger API Request
                $smsResult = $smsService->sendSMS($phoneNumber, $smsContent);

                if (!empty($smsResult['success'])) {
                    $smsStatus = 'Sent';
                } else {
                    $smsStatus = 'Failed: ' . ($smsResult['error'] ?? 'Unknown AT Error');
                }
            }

            // 4. Save record to `Messages` table matching DB schema
            $stmt = $pdo->prepare("
                INSERT INTO Messages (sender_id, receiver_id, crop_name, subject, message_text, sms_status)
                VALUES (:sender_id, :receiver_id, :crop_name, :subject, :message_text, :sms_status)
            ");
            
            $stmt->execute([
                ':sender_id'    => $senderId,
                ':receiver_id'  => $receiverId,
                ':crop_name'    => $cropName,
                ':subject'      => $subject,
                ':message_text' => $messageText,
                ':sms_status'   => $smsStatus
            ]);

            $_SESSION['msg_success'] = "Ujumbe umetumwa! (Hali ya SMS: {$smsStatus} kwenda {$phoneNumber})";

        } catch (PDOException $e) {
            $_SESSION['msg_error'] = "Hitilafu ya Database: " . $e->getMessage();
        }
    } else {
        $_SESSION['msg_error'] = "Tafadhali hakikisha maelezo ya ujumbe yamejazwa kwa usahihi.";
    }
}

// Redirect back to extension dashboard with active farmer preserved
header("Location: extensiondashboard.php?farmer_id={$receiverId}");
exit();