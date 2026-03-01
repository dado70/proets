<?php
declare(strict_types=1);

namespace ProETS\Services;

use ProETS\Core\Config;
use ProETS\Core\Database;

class MailerService
{
    /**
     * Invia una email tramite PHPMailer (se disponibile) o mail() nativo
     */
    public static function send(
        string $to,
        string $toName,
        string $subject,
        string $bodyHtml,
        ?string $fromEmail = null,
        ?string $fromName  = null
    ): bool {
        $fromEmail ??= Config::get('app.smtp_from', 'noreply@proets.it');
        $fromName  ??= Config::get('app.smtp_from_name', 'ProETS');

        // Se PHPMailer è disponibile tramite Composer
        if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
            return self::sendViaPHPMailer($to, $toName, $subject, $bodyHtml, $fromEmail, $fromName);
        }

        // Fallback: mail() nativo
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "X-Mailer: ProETS/1.0\r\n";
        return mail($to, $subject, $bodyHtml, $headers);
    }

    private static function sendViaPHPMailer(
        string $to, string $toName, string $subject, string $bodyHtml,
        string $fromEmail, string $fromName
    ): bool {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $host   = self::getSetting('smtp_host');
            $port   = (int)self::getSetting('smtp_port', '587');
            $user   = self::getSetting('smtp_user');
            $pass   = self::getSetting('smtp_pass');
            $secure = self::getSetting('smtp_secure', 'tls');

            if ($host) {
                $mail->isSMTP();
                $mail->Host       = $host;
                $mail->Port       = $port;
                $mail->SMTPSecure = $secure === 'ssl' ? 'ssl' : 'tls';
                $mail->SMTPAuth   = !empty($user);
                if (!empty($user)) { $mail->Username = $user; $mail->Password = $pass; }
            }

            $mail->CharSet  = 'UTF-8';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            $mail->AltBody = strip_tags($bodyHtml);
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('[ProETS] Mailer error: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendPasswordReset(string $email, string $nome, string $url): bool
    {
        $template = Database::fetchOne(
            "SELECT oggetto, corpo_html FROM email_templates WHERE codice = 'reset_password' AND company_id IS NULL"
        );
        $subject = $template ? $template['oggetto'] : 'Reimposta la tua password — ProETS';
        $body    = $template ? $template['corpo_html'] : "<p>Gentile {$nome},</p><p>Clicca qui per reimpostare la password: <a href=\"{$url}\">{$url}</a></p>";
        $body    = strtr($body, ['{{nome_utente}}' => $nome, '{{link_reset}}' => $url, '{{nome_associazione}}' => 'ProETS']);
        return self::send($email, $nome, $subject, $body);
    }

    public static function sendQuotaRequest(
        string $email, string $nome, float $importo, int $anno, string $urlPagamento,
        array $company = []
    ): bool {
        $nomeAssoc = $company['ragione_sociale'] ?? 'ProETS';
        $iban      = $company['iban'] ?? '';
        $template  = Database::fetchOne(
            "SELECT oggetto, corpo_html FROM email_templates WHERE codice = 'richiesta_quota' AND (company_id = ? OR company_id IS NULL) ORDER BY company_id DESC LIMIT 1",
            [$company['id'] ?? 0]
        );
        $subject   = $template ? $template['oggetto'] : "Rinnovo quota {$anno} — {$nomeAssoc}";
        $body      = $template ? $template['corpo_html'] : "<p>Gentile {$nome},</p><p>Quota {$anno}: € {$importo}</p>";
        $body      = strtr($body, [
            '{{nome_socio}}' => $nome, '{{anno}}' => $anno,
            '{{importo}}' => number_format($importo,2,',','.'),
            '{{link_pagamento}}' => $urlPagamento,
            '{{nome_associazione}}' => $nomeAssoc,
            '{{iban_associazione}}' => $iban,
        ]);
        return self::send($email, $nome, strtr($subject, ['{{anno}}'=>$anno,'{{nome_associazione}}'=>$nomeAssoc]), $body);
    }

    public static function sendWelcome(array $socio, array $company): bool
    {
        if (empty($socio['email'])) return false;
        $nomeAssoc = $company['ragione_sociale'] ?? 'ProETS';
        $template  = Database::fetchOne(
            "SELECT oggetto, corpo_html FROM email_templates WHERE codice = 'lettera_iscrizione' AND (company_id = ? OR company_id IS NULL) ORDER BY company_id DESC LIMIT 1",
            [$company['id'] ?? 0]
        );
        $subject = $template ? $template['oggetto'] : "Benvenuto in {$nomeAssoc}";
        $body    = $template ? $template['corpo_html'] : "<p>Benvenuto {$socio['nome']}!</p>";
        $body    = strtr($body, [
            '{{nome_socio}}'       => $socio['nome'] . ' ' . $socio['cognome'],
            '{{numero_tessera}}'   => $socio['numero_tessera'] ?? '',
            '{{tipo_socio}}'       => $socio['tipo_socio'] ?? '',
            '{{data_iscrizione}}'  => $socio['data_iscrizione'] ?? '',
            '{{anno}}'             => date('Y'),
            '{{importo_quota}}'    => '',
            '{{nome_associazione}}' => $nomeAssoc,
            '{{email_associazione}}' => $company['email'] ?? '',
        ]);
        return self::send($socio['email'], $socio['nome'].' '.$socio['cognome'], $subject, $body);
    }

    private static function getSetting(string $key, string $default = ''): string
    {
        $val = Database::fetchColumn(
            "SELECT valore FROM app_settings WHERE chiave = ? AND company_id IS NULL",
            [$key]
        );
        return $val !== false ? (string)$val : $default;
    }
}
