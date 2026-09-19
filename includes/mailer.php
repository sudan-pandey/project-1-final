<?php
/**
 * Standalone SMTP Mailer Helper for College Club Management System
 */

// Load mail configuration if available
$mailConfigFile = __DIR__ . '/../config/mail.php';
$mailExampleFile = __DIR__ . '/../config/mail.php.example';

if (file_exists($mailConfigFile)) {
    require_once $mailConfigFile;
} elseif (file_exists($mailExampleFile)) {
    require_once $mailExampleFile;
}

/**
 * Sends an email using direct SMTP socket connection with TLS/SSL support.
 *
 * @param string $toEmail Recipient email address
 * @param string $subject Email subject line
 * @param string $body Email message content
 * @param string|null $fromEmail Optional custom sender email address
 * @param string|null $fromName Optional custom sender display name
 * @return array ['success' => bool, 'message' => string]
 */
function sendSmtpEmail($toEmail, $subject, $body, $fromEmail = null, $fromName = null) {
    $host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
    $port = defined('SMTP_PORT') ? SMTP_PORT : 465;
    $username = defined('SMTP_USER') ? SMTP_USER : '';
    $password = str_replace(' ', '', defined('SMTP_PASS') ? SMTP_PASS : '');
    $secure = defined('SMTP_SECURE') ? strtolower(SMTP_SECURE) : 'ssl';

    $fromEmail = $fromEmail ?: (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : $username);
    $fromName = $fromName ?: (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'College Club Management System');

    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid recipient email address: ' . $toEmail];
    }

    $socket = null;
    try {
        $timeout = 15;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $transport = ($secure === 'ssl' || $port == 465) ? 'ssl://' : 'tcp://';
        $socket = @stream_socket_client(
            $transport . $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            return ['success' => false, 'message' => "SMTP Connection Failed ({$errno}): {$errstr}"];
        }

        stream_set_timeout($socket, $timeout);

        $getResponse = function() use ($socket) {
            $response = '';
            while ($str = fgets($socket, 515)) {
                $response .= $str;
                if (substr($str, 3, 1) === ' ') {
                    break;
                }
            }
            return $response;
        };

        $sendCommand = function($cmd, $expectedCode) use ($socket, $getResponse) {
            fwrite($socket, $cmd . "\r\n");
            $resp = $getResponse();
            $code = substr($resp, 0, 3);
            if ($code != $expectedCode) {
                throw new Exception("SMTP Command '{$cmd}' failed. Response: {$resp}");
            }
            return $resp;
        };

        // 1. Initial Greeting
        $greeting = $getResponse();
        if (substr($greeting, 0, 3) !== '220') {
            throw new Exception("Invalid SMTP greeting response: {$greeting}");
        }

        // 2. EHLO
        $sendCommand('EHLO ' . gethostname(), '250');

        // Handle STARTTLS for TLS connections (port 587)
        if (($secure === 'tls' || $port == 587) && $transport === 'tcp://') {
            $sendCommand('STARTTLS', '220');
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                throw new Exception("Failed to enable TLS encryption on socket.");
            }
            // Re-send EHLO after TLS negotiation
            $sendCommand('EHLO ' . gethostname(), '250');
        }

        // 3. AUTH LOGIN if credentials provided
        if (!empty($username) && !empty($password)) {
            $sendCommand('AUTH LOGIN', '334');
            $sendCommand(base64_encode($username), '334');
            $sendCommand(base64_encode($password), '235');
        }

        // 4. MAIL FROM & RCPT TO
        $sendCommand("MAIL FROM: <{$fromEmail}>", '250');
        $sendCommand("RCPT TO: <{$toEmail}>", '250');

        // 5. DATA
        $sendCommand('DATA', '354');

        // Build Email Headers and Payload
        $encodedFromName = "=?UTF-8?B?" . base64_encode($fromName) . "?=";
        $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        $headers = [];
        $headers[] = "From: {$encodedFromName} <{$fromEmail}>";
        $headers[] = "To: <{$toEmail}>";
        $headers[] = "Subject: {$encodedSubject}";
        $headers[] = "Date: " . date('r');
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        $headers[] = "X-Mailer: CollegeClubManagementSystem/1.0";

        // Dot stuffing for body lines
        $normalizedBody = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $normalizedBody);
        $stuffedLines = [];
        foreach ($lines as $line) {
            if (isset($line[0]) && $line[0] === '.') {
                $line = '.' . $line;
            }
            $stuffedLines[] = $line;
        }
        $bodyContent = implode("\r\n", $stuffedLines);

        $dataPayload = implode("\r\n", $headers) . "\r\n\r\n" . $bodyContent . "\r\n.";
        $sendCommand($dataPayload, '250');

        // 6. QUIT
        try {
            $sendCommand('QUIT', '221');
        } catch (Exception $e) {
            // Non-critical if quit fails after mail delivery
        }

        @fclose($socket);
        return ['success' => true, 'message' => 'Email dispatched successfully via SMTP.'];

    } catch (Exception $e) {
        if ($socket) {
            @fclose($socket);
        }
        return ['success' => false, 'message' => 'SMTP Error: ' . $e->getMessage()];
    }
}
?>