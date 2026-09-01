<?php

class Sms {

    public static function send($phoneNumber, $message) {
        $config = require __DIR__ . '/../../config/sms.php';

        // I-format ang numero papunta sa 09XXXXXXXXX na hinihiling ng Semaphore -
        // ang mga numero natin sa database ay naka-store sa +639XXXXXXXXX format.
        $formattedNumber = self::formatNumber($phoneNumber);

        $postData = [
            'apikey' => $config['api_key'],
            'number' => $formattedNumber,
            'message' => $message,
            'sendername' => $config['sender_name'],
        ];

        $ch = curl_init('https://api.semaphore.co/api/v4/messages');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log('SMS error (curl): ' . $curlError);
            return false;
        }

        if ($httpCode !== 200) {
            error_log('SMS error (HTTP ' . $httpCode . '): ' . $response);
            return false;
        }

        return true;
    }
    // Kapareho ng send(), pero ibinabalik ang raw na sagot ng Semaphore
    // para magamit sa pag-debug (tingnan ang AdminController::testSms).
    public static function sendDebug($phoneNumber, $message) {
        $config = require __DIR__ . '/../../config/sms.php';

        $formattedNumber = self::formatNumber($phoneNumber);

        $postData = [
            'apikey' => $config['api_key'],
            'number' => $formattedNumber,
            'message' => $message,
            'sendername' => $config['sender_name'],
        ];

        $ch = curl_init('https://api.semaphore.co/api/v4/messages');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return 'CURL ERROR: ' . $curlError;
        }

        return 'HTTP ' . $httpCode . "\n" . $response;
    }

    protected static function formatNumber($phoneNumber) {
        // +639171234567 -> 09171234567
        if (strpos($phoneNumber, '+63') === 0) {
            return '0' . substr($phoneNumber, 3);
        }
        return $phoneNumber;
    }
}