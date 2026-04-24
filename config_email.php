<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'classes/PHPMailer/src/Exception.php';
require 'classes/PHPMailer/src/PHPMailer.php';
require 'classes/PHPMailer/src/SMTP.php';

function enviarEmail($destinatario, $assunto, $corpo) {
    $mail = new PHPMailer(true);

    try {
        // Configurações do Servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';           // Altere para o seu host SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'rhacipa@gmail.com';     // Seu e-mail
        $mail->Password   = 'gufs odft jcqs nqkq';        // Sua senha (ou senha de app)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Remetente e Destinatário
        $mail->setFrom('seu-email@gmail.com', 'SGC - Banco de Talentos');
        $mail->addAddress($destinatario);

        // Conteúdo do E-mail
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body    = $corpo;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Erro ao enviar: {$mail->ErrorInfo}";
    }
}
