<?php
session_start();
$page_title = "Contact Us";
require_once 'includes/header.php';
require_once 'includes/database.php';

// 检查是否存在 email_config.php 文件
if (file_exists('config/email_config.php')) {
    require_once 'config/email_config.php';
} elseif (file_exists('email_config.php')) {
    require_once 'email_config.php';
} else {
    // 如果不存在，定义一些默认值
    define('EMAIL_HOST', 'smtp.qq.com');
    define('EMAIL_PORT', 587);
    define('EMAIL_USER', 'your_email@qq.com');
    define('EMAIL_PASS', 'your_auth_code');
    define('EMAIL_FROM', 'your_email@qq.com');
    define('EMAIL_FROM_NAME', 'Game Store');
}

// 初始化变量
$success = '';
$error = '';
$email_sent = false;
$email_error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // 获取表单数据
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // 验证输入
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = "All fields are required!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address!";
        } elseif (strlen($name) < 2 || strlen($name) > 100) {
            $error = "Name must be between 2 and 100 characters!";
        } elseif (strlen($subject) < 5 || strlen($subject) > 200) {
            $error = "Subject must be between 5 and 200 characters!";
        } elseif (strlen($message) < 10 || strlen($message) > 5000) {
            $error = "Message must be between 10 and 5000 characters!";
        } else {
            // 创建数据库实例
            $db = new Database();
            
            // 检查 feedback 表是否存在，如果不存在则创建（根据您的截图，应该有这些字段）
            try {
                // 尝试插入数据（假设表已存在）
                $sql = "INSERT INTO feedback (name, email, subject, message, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                $db->query($sql, [$name, $email, $subject, $message]);
                
            } catch (Exception $table_error) {
                // 如果表不存在，创建它
                error_log("Table error, creating feedback table: " . $table_error->getMessage());
                
                // 根据截图中的字段创建表
                $createTableSQL = "CREATE TABLE IF NOT EXISTS `feedback` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) NOT NULL,
                    `email` varchar(255) NOT NULL,
                    `subject` varchar(200) NOT NULL,
                    `message` text NOT NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `is_read` tinyint(1) DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
                
                $db->query($createTableSQL);
                
                // 再次尝试插入
                $sql = "INSERT INTO feedback (name, email, subject, message) 
                        VALUES (?, ?, ?, ?)";
                $db->query($sql, [$name, $email, $subject, $message]);
            }
            
            // 尝试发送邮件
            $email_sent = false;
            $email_error = '';
            
            try {
                // 首先检查是否安装了 PHPMailer
                if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                    // 使用 PHPMailer 发送邮件
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    
                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host       = EMAIL_HOST;
                        $mail->SMTPAuth   = true;
                        $mail->Username   = EMAIL_USER;
                        $mail->Password   = EMAIL_PASS;
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = EMAIL_PORT;
                        
                        // Recipients
                        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
                        $mail->addAddress($email, $name);
                        $mail->addReplyTo(EMAIL_FROM, EMAIL_FROM_NAME);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Thank you for contacting GameHub - ' . $subject;
                        $mail->Body    = "
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; color: white;'>
                                    <h1 style='margin: 0;'>GameHub</h1>
                                    <p style='margin: 10px 0 0;'>Thank you for contacting us!</p>
                                </div>
                                
                                <div style='padding: 30px; background: #f9f9f9;'>
                                    <h2 style='color: #333;'>Hello $name,</h2>
                                    <p style='color: #666; line-height: 1.6;'>
                                        We have received your message and will get back to you as soon as possible.
                                    </p>
                                    
                                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;'>
                                        <h3 style='color: #444; margin-top: 0;'>Your Message Details:</h3>
                                        <p><strong>Subject:</strong> $subject</p>
                                        <p><strong>Message:</strong></p>
                                        <p style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>" . nl2br(htmlspecialchars($message)) . "</p>
                                    </div>
                                    
                                    <p style='color: #666; line-height: 1.6;'>
                                        Our support team typically responds within 24-48 hours during business days.
                                    </p>
                                    
                                    <p style='color: #666; line-height: 1.6;'>
                                        If you have any urgent questions, please call our support line at +1 (555) 123-4567.
                                    </p>
                                    
                                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                                        <p style='color: #888; font-size: 14px;'>
                                            Best regards,<br>
                                            <strong>The GameHub Team</strong><br>
                                            <a href='mailto:support@gamehub.com' style='color: #667eea;'>support@gamehub.com</a>
                                        </p>
                                    </div>
                                </div>
                                
                                <div style='background: #f0f0f0; padding: 20px; text-align: center; font-size: 12px; color: #888;'>
                                    <p style='margin: 0;'>
                                        © " . date('Y') . " GameHub. All rights reserved.<br>
                                        123 Gaming Street, Tech City, TC 10001
                                    </p>
                                </div>
                            </div>
                        ";
                        
                        $mail->AltBody = "Hello $name,\n\nThank you for contacting GameHub. We have received your message regarding: $subject\n\nYour message: $message\n\nWe will get back to you within 24-48 hours.\n\nBest regards,\nThe GameHub Team";
                        
                        $mail->send();
                        $email_sent = true;
                        
                    } catch (Exception $mail_error) {
                        $email_error = "Mailer Error: " . $mail_error->getMessage();
                        error_log("PHPMailer error: " . $mail_error->getMessage());
                    }
                } else {
                    // 如果没有 PHPMailer，尝试使用 PHP 的 mail() 函数
                    $to = $email;
                    $email_subject = "Thank you for contacting GameHub - $subject";
                    
                    $email_body = "Hello $name,\n\n";
                    $email_body .= "Thank you for contacting GameHub. We have received your message and will get back to you as soon as possible.\n\n";
                    $email_body .= "Your Message Details:\n";
                    $email_body .= "Subject: $subject\n";
                    $email_body .= "Message: $message\n\n";
                    $email_body .= "Our support team typically responds within 24-48 hours during business days.\n\n";
                    $email_body .= "Best regards,\n";
                    $email_body .= "The GameHub Team\n";
                    $email_body .= "support@gamehub.com\n";
                    
                    $headers = "From: " . EMAIL_FROM . "\r\n";
                    $headers .= "Reply-To: " . EMAIL_FROM . "\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    
                    if (mail($to, $email_subject, $email_body, $headers)) {
                        $email_sent = true;
                    } else {
                        $email_error = "Failed to send email using PHP mail() function";
                        error_log("PHP mail() function failed");
                    }
                }
                
            } catch (Exception $e) {
                $email_error = "Email configuration error: " . $e->getMessage();
                error_log("Email sending error: " . $e->getMessage());
            }
            
            // 构建成功消息
            $success = "Thank you for your message, $name! ";
            $success .= "We have received your feedback and will get back to you as soon as possible. ";
            
            if ($email_sent) {
                $success .= "A confirmation email has been sent to $email.";
            } else {
                $success .= "(Note: Could not send confirmation email due to technical issues.)";
            }
            
            // 清除表单数据
            $_POST = [];
        }
        
    } catch (Exception $e) {
        $error = "Sorry, there was an error submitting your message. Please try again later.";
        error_log("Contact form error: " . $e->getMessage());
    }
}
?>

<div class="container contact-section">
    <h1><i class="fas fa-envelope"></i> Contact Us</h1>
    <p class="contact-intro">Have questions, feedback, or need support? We're here to help! Fill out the form below and we'll get back to you as soon as possible.</p>
    
    <!-- 显示消息 -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> 
            <div class="alert-content">
                <?php echo htmlspecialchars($success); ?>
                <?php if ($email_error && !$email_sent): ?>
                    <small style="display: block; margin-top: 5px; color: #666;">
                        <i class="fas fa-info-circle"></i> Email notification could not be sent due to server configuration.
                    </small>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="contact-content">
        <!-- 联系信息 -->
        <div class="contact-info">
            <h2><i class="fas fa-info-circle"></i> Contact Information</h2>
            <div class="contact-grid">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Our Location</h3>
                    <p>123 Gaming Street</p>
                    <p>Tech City, TC 10001</p>
                    <p>United States</p>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>Phone Numbers</h3>
                    <p>Support: +1 (555) 123-4567</p>
                    <p>Sales: +1 (555) 987-6543</p>
                    <p>24/7 Support Available</p>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email Addresses</h3>
                    <p>Support: support@gamehub.com</p>
                    <p>Business: contact@gamehub.com</p>
                    <p>Careers: careers@gamehub.com</p>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Business Hours</h3>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                    <p>Saturday: 10:00 AM - 4:00 PM</p>
                    <p>Sunday: Closed</p>
                </div>
            </div>
            
            <!-- 额外信息 -->
            <div class="additional-info">
                <h3><i class="fas fa-question-circle"></i> Frequently Asked Questions</h3>
                <div class="faq-list">
                    <div class="faq-item">
                        <h4>How long does it take to get a response?</h4>
                        <p>We typically respond within 24-48 hours during business days.</p>
                    </div>
                    <div class="faq-item">
                        <h4>Do you offer phone support?</h4>
                        <p>Yes, phone support is available during business hours.</p>
                    </div>
                    <div class="faq-item">
                        <h4>Where can I find my order information?</h4>
                        <p>You can check your order status in your account dashboard.</p>
                    </div>
                    <div class="faq-item">
                        <h4>Will I receive a confirmation email?</h4>
                        <p>Yes, you should receive a confirmation email immediately after submitting the form.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 联系表单 -->
        <div class="contact-form">
            <div class="form-header">
                <h2><i class="fas fa-paper-plane"></i> Send us a Message</h2>
                <p>Fill out the form below and we'll get back to you promptly.</p>
                <?php if (file_exists('vendor/phpmailer/phpmailer/src/PHPMailer.php')): ?>
                    <div class="mailer-status">
                        <i class="fas fa-check-circle"></i> PHPMailer is available for email delivery
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="" id="contactForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-user"></i> Full Name *
                        </label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               placeholder="Enter your full name"
                               minlength="2" maxlength="100"
                               required>
                        <small class="form-text">Minimum 2 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address *
                        </label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="Enter your email address"
                               required>
                        <small class="form-text">We'll send a confirmation to this email</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="subject">
                        <i class="fas fa-tag"></i> Subject *
                    </label>
                    <input type="text" id="subject" name="subject" 
                           value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"
                           placeholder="What is this regarding?"
                           minlength="5" maxlength="200"
                           required>
                    <small class="form-text">Brief description of your inquiry</small>
                </div>
                
                <div class="form-group">
                    <label for="message">
                        <i class="fas fa-comment"></i> Message *
                    </label>
                    <textarea id="message" name="message" rows="8" 
                              placeholder="Please provide details about your inquiry..."
                              minlength="10" maxlength="5000"
                              required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    <div class="textarea-footer">
                        <small class="form-text">Be as detailed as possible</small>
                        <div class="char-counter">
                            <span id="messageCharCount">0</span> / 5000 characters
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Clear Form
                    </button>
                </div>
                
                <div class="form-notice">
                    <p><i class="fas fa-info-circle"></i> Your message will be saved in our system and you'll receive a confirmation email.</p>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.contact-section {
    padding: 40px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.contact-section h1 {
    color: #333;
    margin-bottom: 10px;
    text-align: center;
    font-size: 2.5rem;
}

.contact-intro {
    text-align: center;
    color: #666;
    font-size: 1.1rem;
    max-width: 800px;
    margin: 0 auto 40px;
    line-height: 1.6;
}

.contact-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-top: 30px;
}

.contact-info h2 {
    color: #333;
    margin-bottom: 25px;
    padding-bottom: 10px;
    border-bottom: 2px solid #007bff;
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.contact-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s, box-shadow 0.3s;
    text-align: center;
}

.contact-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
}

.contact-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 24px;
}

.contact-card h3 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.contact-card p {
    color: #666;
    margin: 8px 0;
    font-size: 0.95rem;
    line-height: 1.4;
}

.additional-info {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.08);
}

.additional-info h3 {
    color: #333;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.faq-item {
    border-left: 3px solid #007bff;
    padding-left: 15px;
}

.faq-item h4 {
    color: #444;
    margin-bottom: 5px;
    font-size: 1rem;
}

.faq-item p {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
}

.contact-form {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.08);
}

.form-header {
    margin-bottom: 30px;
}

.form-header h2 {
    color: #333;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-header p {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 10px;
}

.mailer-status {
    background: #e8f5e8;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 8px 12px;
    border-radius: 5px;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    font-family: inherit;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 150px;
}

.form-text {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.textarea-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 5px;
}

.char-counter {
    font-size: 12px;
    color: #666;
}

.char-counter span {
    font-weight: 500;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.form-notice {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.form-notice p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    font-weight: 500;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-lg {
    padding: 15px 30px;
    font-size: 18px;
}

.alert {
    padding: 15px 20px;
    margin-bottom: 30px;
    border-radius: 8px;
    border: 1px solid transparent;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.alert-content {
    flex: 1;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-error {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
}

@media (max-width: 992px) {
    .contact-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}

@media (max-width: 768px) {
    .contact-section {
        padding: 20px 15px;
    }
    
    .contact-section h1 {
        font-size: 2rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .contact-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .contact-form {
        padding: 20px;
    }
    
    .contact-card {
        padding: 20px;
    }
    
    .additional-info {
        padding: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('messageCharCount');
    
    // 字符计数器
    if (messageTextarea && charCount) {
        // 初始化计数器
        charCount.textContent = messageTextarea.value.length;
        
        // 更新计数器
        messageTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            
            // 如果接近限制，改变颜色
            if (this.value.length > 4500) {
                charCount.style.color = '#dc3545';
            } else if (this.value.length > 4000) {
                charCount.style.color = '#ffc107';
            } else {
                charCount.style.color = '#666';
            }
        });
    }
    
    // 表单验证
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const subject = document.getElementById('subject').value.trim();
            const message = messageTextarea.value.trim();
            
            let errors = [];
            
            // 验证姓名
            if (name.length < 2 || name.length > 100) {
                errors.push('Name must be between 2 and 100 characters.');
            }
            
            // 验证邮箱
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                errors.push('Please enter a valid email address.');
            }
            
            // 验证主题
            if (subject.length < 5 || subject.length > 200) {
                errors.push('Subject must be between 5 and 200 characters.');
            }
            
            // 验证消息
            if (message.length < 10 || message.length > 5000) {
                errors.push('Message must be between 10 and 5000 characters.');
            }
            
            // 如果有错误，阻止提交并显示错误
            if (errors.length > 0) {
                e.preventDefault();
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }
            
            // 显示提交确认
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // 在实际应用中，这里会有AJAX提交
            // 现在只是模拟提交成功
            return true;
        });
    }
    
    // 自动调整文本区域高度
    if (messageTextarea) {
        messageTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // 触发一次以设置初始高度
        messageTextarea.dispatchEvent(new Event('input'));
    }
    
    // 添加表单字段的焦点效果
    const formInputs = contactForm.querySelectorAll('input, textarea');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
});
</script>

<?php 
require_once 'includes/footer.php';
?>