<?php
//改文件用于测试在绕过建立表的情况下测试邮箱服务是否配置成功

// 引入 Laravel 自动加载器（必须有）
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ================= 改这里！=================
$send_email = '2633681826@qq.com';      // 你的发件邮箱
$send_auth_code = 'gjqqlscxhcirdiie';   // 你的授权码
$receive_email = '2633681826@qq.com'; // 收件人邮箱
// ==========================================

try {
    $mail = new PHPMailer(true);

    // SMTP 配置（适配QQ邮箱 587/TLS，避开SSL证书问题）
    $mail->isSMTP();
    $mail->Host = 'smtp.qq.com';
    $mail->SMTPAuth = true;
    $mail->Username = $send_email;
    $mail->Password = $send_auth_code;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS 加密
    $mail->Port = 587;                             // 587 端口

    // 收件人
    $mail->setFrom($send_email, 'Laravel测试');
    $mail->addAddress($receive_email);

    // 内容
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer 测试成功！';
    $mail->Body = '如果看到这封邮件，说明配置完全正确！';

    $mail->send();
    echo '✅ 邮件发送成功！快去邮箱看看吧！';
} catch (Exception $e) {
    echo "❌ 发送失败：" . $mail->ErrorInfo;
}