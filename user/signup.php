<?php
// Start session at the very top to hold temporary registration info and OTP states
session_start();

require_once '../config/db.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Adjust paths below to where your vendor auto-loader or downloaded files sit
require '../vendor/autoload.php'; 
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

$error = "";
$show_otp_modal = false;

// ── PROCESS 1: INITIAL REGISTRATION SUBMISSION ──
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_attempt'])) {
    $first = trim($_POST['first_name']);
    $middle = trim($_POST['middle_name']);
    $last = trim($_POST['last_name']);
    $suffix = trim($_POST['suffix']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact_number']);
    $gender = $_POST['gender'];
    $bday_input = $_POST['birthdate'];
    $pass = $_POST['password'];
    $conf_pass = $_POST['confirm_password'];

    // Strict Age Check (18+)
    $bday = new DateTime($bday_input);
    $today = new DateTime();
    $age = $today->diff($bday)->y;

    // Check if email already exists in the database before sending OTP
    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$email]);
    
    if ($check_stmt->fetch()) {
        $error = "This email address is already registered.";
    } elseif ($age < 18) {
        $error = "You must be at least 18 years old to create an account.";
    } elseif ($pass !== $conf_pass) {
        $error = "Passwords do not match.";
    } elseif (strlen($pass) < 8 || !preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
        $error = "Password must be 8+ chars, including an uppercase letter and a number.";
    } else {
        // Form validations passed! Generate OTP
        $otp = rand(100000, 999999);
        
        // Cache data safely in session memory
        $_SESSION['temp_user'] = [
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'suffix' => $suffix,
            'email' => $email,
            'contact_number' => $contact,
            'gender' => $gender,
            'birthdate' => $bday_input,
            'password' => password_hash($pass, PASSWORD_DEFAULT)
        ];
        $_SESSION['email_otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 600; // Code valid for 10 minutes

        // Send OTP email via SMTP
        $mail = new PHPMailer(true);
        try {
            // Server Settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lumiereandbliss@gmail.com';
            $mail->Password   = 'efrscvjtyzktibya'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('lumiereandbliss@gmail.com', 'Lumiére and Bliss');
            $mail->addAddress($email, $first . ' ' . $last);

            // Content Setup
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Account - Lumiére and Bliss';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 20px; border: 1px solid #ede8df; background-color: #fdfbf7;'>
                    <h2 style='color: #1a1a1a; font-family: serif; text-align: center;'>Lumiére and Bliss</h2>
                    <p style='color: #2e2e2e; font-size: 14px;'>Hello " . htmlspecialchars($first) . ",</p>
                    <p style='color: #8a8070; font-size: 14px;'>Thank you for choosing to register with us. Use the verification code below to validate your account registration:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #c9a96e; padding: 10px 20px; background: #f9f6f0; border: 1px dashed #c9a96e; border-radius: 6px;'>" . $otp . "</span>
                    </div>
                    <p style='color: #8a8070; font-size: 12px; text-align: center;'>This code will expire in 10 minutes. If you did not request this, please safely ignore this notification.</p>
                </div>";

            $mail->send();
            $show_otp_modal = true;
        } catch (Exception $e) {
            $error = "Verification email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}

// ── PROCESS 2: VERIFY OTP ENTRIES ──
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp_btn'])) {
    $user_otp = trim($_POST['otp_code']);
    
    if (!isset($_SESSION['email_otp']) || !isset($_SESSION['temp_user'])) {
        $error = "Session expired. Please sign up again.";
    } elseif (time() > $_SESSION['otp_expiry']) {
        $error = "The verification code has expired. Please register again.";
        unset($_SESSION['email_otp'], $_SESSION['temp_user'], $_SESSION['otp_expiry']);
    } elseif ($user_otp == $_SESSION['email_otp']) {
        // OTP matches completely! Write back into the production DB
        $u = $_SESSION['temp_user'];
        
        $sql = "INSERT INTO users (first_name, middle_name, last_name, suffix, email, contact_number, birthdate, gender, password, account_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'guest')";

        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([$u['first_name'], $u['middle_name'], $u['last_name'], $u['suffix'], $u['email'], $u['contact_number'], $u['birthdate'], $u['gender'], $u['password']]);
            
            // Clean up session trace variables completely
            unset($_SESSION['email_otp'], $_SESSION['temp_user'], $_SESSION['otp_expiry']);
            
            header("Location: signin.php?msg=success");
            exit();
        } catch (PDOException $e) {
            $error = "An error occurred writing to the database. Please try again.";
        }
    } else {
        $error = "Invalid verification code. Please check your inbox and try again.";
        $show_otp_modal = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Lumiére and Bliss</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <style>
        :root {
            --cream: #fdfbf7;
            --warm-white: #f9f6f0;
            --gold: #c9a96e;
            --gold-light: #e8d5b0;
            --gold-dim: rgba(201,169,110,0.15);
            --dark: #1a1a1a;
            --dark-soft: #2e2e2e;
            --muted: #8a8070;
            --border: rgba(201,169,110,0.25);
            --border-soft: #ede8df;
            --error: #c0392b;
            --success: #5a7a5a;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; overflow: hidden; font-size: 14px; }

        body {
            background: var(--dark);
            font-family: 'DM Sans', sans-serif;
            height: 100vh;
            display: flex;
            align-items: stretch;
        }

        /* ─────────────────────────────────────────
           LEFT — Cinematic image panel
        ───────────────────────────────────────── */
        .panel-visual {
            width: 55%;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
            height: 100vh;
        }

        .panel-visual-bg {
            position: absolute;
            inset: 0;
            background: linear-gradient(160deg, #0e0d0b 0%, #1c1a15 40%, #251f12 100%);
        }

        .panel-visual-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.08'/%3E%3C/svg%3E");
            background-size: 200px 200px;
            opacity: 0.4;
            mix-blend-mode: overlay;
        }

        .panel-visual-glow {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 30% 60%, rgba(201,169,110,0.14) 0%, transparent 65%),
                radial-gradient(ellipse 40% 40% at 75% 20%, rgba(201,169,110,0.08) 0%, transparent 60%);
            z-index: 1;
        }

        .panel-lines {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
        }

        .panel-lines svg { width: 100%; height: 100%; }

        .panel-visual-content {
            position: absolute;
            inset: 0;
            z-index: 3;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 36px 40px;
        }

        .brand-mark { display: flex; flex-direction: column; gap: 8px; }

        .brand-mark-wordmark {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-mark-line {
            width: 32px;
            height: 1px;
            background: var(--gold);
            opacity: 0.7;
        }

        .brand-mark-name {
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: var(--gold-light);
            opacity: 0.85;
        }

        .brand-mark-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 26px;
            font-weight: 300;
            line-height: 1.1;
            color: #fff;
            padding-left: 46px;
        }

        .brand-mark-title em {
            font-style: italic;
            color: var(--gold-light);
        }

        /* Step indicator */
        .panel-steps {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 28px 0;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .step-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px solid rgba(201,169,110,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .step-dot-inner {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(201,169,110,0.3);
        }

        .step-dot.active {
            border-color: var(--gold);
            background: rgba(201,169,110,0.1);
        }

        .step-dot.active .step-dot-inner { background: var(--gold); }

        .step-connector {
            width: 1px;
            height: 20px;
            background: rgba(201,169,110,0.2);
            margin-left: 11px;
        }

        .step-label-text {
            font-size: 11px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.35);
            font-weight: 500;
        }

        .step-item.active .step-label-text { color: var(--gold-light); opacity: 0.9; }

        .panel-editorial {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .panel-editorial-rule {
            width: 48px;
            height: 1px;
            background: var(--gold);
            opacity: 0.6;
        }

        .panel-editorial-quote {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            font-weight: 300;
            font-style: italic;
            line-height: 1.6;
            color: rgba(255,255,255,0.55);
            max-width: 260px;
        }

        .panel-editorial-meta {
            font-size: 10px;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: rgba(201,169,110,0.55);
            font-weight: 500;
        }

        .panel-stamp {
            position: absolute;
            right: 36px;
            top: 50%;
            transform: translateY(-50%) rotate(90deg);
            transform-origin: center;
            z-index: 4;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .panel-stamp span {
            font-size: 9px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: rgba(201,169,110,0.35);
            font-weight: 500;
            white-space: nowrap;
        }

        .panel-stamp-dot {
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: var(--gold);
            opacity: 0.35;
            flex-shrink: 0;
        }

        .panel-visual::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0;
            width: 60px;
            background: linear-gradient(to right, transparent, rgba(26,26,26,0.7));
            z-index: 5;
        }

        /* ─────────────────────────────────────────
           RIGHT — Form panel
        ───────────────────────────────────────── */
        .panel-form {
            flex: 1;
            background: var(--cream);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-y: auto;
            height: 100vh;
        }

        .form-corner {
            position: absolute;
            top: 0; right: 0;
            width: 120px; height: 120px;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .form-scroll {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 36px 48px 28px;
            position: relative;
            z-index: 1;
        }

        .form-index {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
        }

        .form-index-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 13px;
            font-weight: 400;
            font-style: italic;
            color: var(--gold);
        }

        .form-index-rule {
            flex: 1;
            max-width: 32px;
            height: 1px;
            background: var(--gold);
            opacity: 0.5;
        }

        .form-index-label {
            font-size: 10px;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 500;
        }

        .form-heading { margin-bottom: 22px; }

        .form-heading h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 34px;
            font-weight: 300;
            line-height: 1.1;
            color: var(--dark);
            letter-spacing: -0.01em;
        }

        .form-heading h2 em {
            font-style: italic;
            color: var(--gold);
        }

        .form-heading p {
            font-size: 13px;
            color: var(--muted);
            margin-top: 8px;
            font-weight: 300;
        }

        /* Section label */
        .section-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 18px 0 14px;
        }

        .section-label span {
            font-size: 9px;
            letter-spacing: 0.26em;
            text-transform: uppercase;
            color: var(--muted);
            white-space: nowrap;
            font-weight: 600;
        }

        .section-label::before,
        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-soft);
        }

        .section-label::before { flex: 0 0 0px; }

        /* Fields grid */
        .fields-grid { display: grid; gap: 10px; margin-bottom: 10px; }
        .grid-4col { grid-template-columns: 4fr 3fr 3fr 2fr; }
        .grid-2col { grid-template-columns: 1fr 1fr; }
        .grid-7-5 { grid-template-columns: 7fr 5fr; }

        .field-group { position: relative; }

        .field-label-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 6px;
        }

        .field-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
            transition: color 0.2s;
        }

        .field-group:focus-within .field-label { color: var(--dark); }

        .field-input-wrap { position: relative; }

        .field-input-wrap input,
        .field-input-wrap select {
            width: 100%;
            background: #fff;
            border: 1px solid var(--border-soft);
            border-bottom: 2px solid var(--border-soft);
            border-radius: 2px;
            padding: 10px 13px;
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            color: var(--dark);
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.2s;
            appearance: none;
            -webkit-appearance: none;
        }

        .field-input-wrap input:focus,
        .field-input-wrap select:focus {
            border-color: var(--border-soft);
            border-bottom-color: var(--gold);
            background: #fff;
            box-shadow: 0 4px 20px rgba(201,169,110,0.08);
        }

        .field-input-wrap input::placeholder {
            color: #cdc9c1;
            font-size: 13px;
            font-weight: 300;
        }

        .select-arrow {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: var(--muted);
        }

        /* Password */
        .pw-toggle {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            padding: 4px;
            line-height: 1;
            transition: color 0.2s;
            display: flex;
            align-items: center;
        }

        .pw-toggle:hover { color: var(--dark); }

        .field-input-wrap input[type="password"],
        .field-input-wrap input[type="text"].has-toggle { padding-right: 40px; }

        /* Password rules */
        .pw-rules {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 16px;
            margin-top: 8px;
            padding: 10px 14px;
            background: var(--warm-white);
            border-radius: 2px;
            border: 1px solid var(--border-soft);
        }

        .pw-rule {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--muted);
            transition: color 0.25s;
        }

        .pw-rule .dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--border-soft);
            transition: background 0.25s;
            flex-shrink: 0;
        }

        .pw-rule.met { color: var(--success); }
        .pw-rule.met .dot { background: var(--success); }

        /* Alerts */
        .alert-custom {
            border-radius: 2px;
            padding: 12px 16px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
            border-left: 2px solid;
        }

        .alert-error {
            background: rgba(192,57,43,0.05);
            border-color: var(--error);
            color: var(--error);
        }

        .alert-custom svg { flex-shrink: 0; margin-top: 1px; }

        /* Submit */
        .btn-submit {
            width: 100%;
            background: var(--dark);
            color: #fff;
            border: none;
            border-radius: 2px;
            padding: 14px 24px;
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s, box-shadow 0.3s;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(201,169,110,0.12) 50%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .btn-submit:hover::before { opacity: 1; }

        .btn-submit:hover {
            background: var(--dark-soft);
            transform: translateY(-1px);
            box-shadow: 0 12px 32px rgba(26,26,26,0.22);
        }

        .btn-submit:active { transform: translateY(0); }

        .btn-arrow {
            width: 20px;
            height: 1px;
            background: rgba(255,255,255,0.5);
            position: relative;
            transition: width 0.3s;
            flex-shrink: 0;
        }

        .btn-arrow::after {
            content: '';
            position: absolute;
            right: 0; top: -3px;
            width: 7px; height: 7px;
            border-right: 1px solid rgba(255,255,255,0.5);
            border-top: 1px solid rgba(255,255,255,0.5);
            transform: rotate(45deg);
        }

        .btn-submit:hover .btn-arrow { width: 28px; }

        .form-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            margin-top: 16px;
        }

        .form-footer-signin {
            font-size: 13px;
            color: var(--muted);
            font-weight: 300;
        }

        .form-footer-signin a {
            color: var(--dark);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid var(--dark);
            padding-bottom: 1px;
            transition: color 0.2s, border-color 0.2s;
        }

        .form-footer-signin a:hover { color: var(--gold); border-color: var(--gold); }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: var(--muted);
            text-decoration: none;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 500;
            transition: color 0.2s;
        }

        .back-link svg { transition: transform 0.2s; }
        .back-link:hover { color: var(--dark); }
        .back-link:hover svg { transform: translateX(-3px); }

        .form-watermark {
            padding: 10px 48px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-watermark-line {
            width: 24px;
            height: 1px;
            background: var(--border-soft);
        }

        .form-watermark-text {
            font-size: 9px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(138,128,112,0.5);
            font-weight: 500;
        }

        /* ── OTP OVERLAY ── */
        .otp-overlay {
            position: fixed;
            inset: 0;
            background: rgba(26,26,26,0.75);
            backdrop-filter: blur(6px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .otp-card {
            background: var(--cream);
            border: 1px solid var(--border-soft);
            border-radius: 2px;
            padding: 44px 40px;
            max-width: 420px;
            width: 90%;
            position: relative;
            box-shadow: 0 30px 70px rgba(0,0,0,0.25);
            animation: modalFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Gold top accent bar */
        .otp-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        .otp-index {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .otp-index-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 13px;
            font-style: italic;
            color: var(--gold);
        }

        .otp-index-rule { width: 24px; height: 1px; background: var(--gold); opacity: 0.5; }

        .otp-index-label {
            font-size: 10px;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 500;
        }

        .otp-heading h3 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 30px;
            font-weight: 300;
            color: var(--dark);
            line-height: 1.1;
        }

        .otp-heading h3 em { font-style: italic; color: var(--gold); }

        .otp-heading p {
            font-size: 13px;
            color: var(--muted);
            margin-top: 8px;
            font-weight: 300;
            line-height: 1.5;
        }

        .otp-input-wrap { margin: 24px 0 20px; position: relative; }

        .otp-input {
            width: 100%;
            background: #fff;
            border: 1px solid var(--border-soft);
            border-bottom: 2px solid var(--gold);
            border-radius: 2px;
            padding: 14px 16px;
            font-size: 26px;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 400;
            letter-spacing: 10px;
            text-align: center;
            color: var(--dark);
            outline: none;
            transition: box-shadow 0.25s;
        }

        .otp-input:focus {
            box-shadow: 0 4px 24px rgba(201,169,110,0.12);
        }

        .otp-input::placeholder {
            color: #ddd;
            letter-spacing: 8px;
        }

        .otp-hint {
            font-size: 11px;
            color: var(--muted);
            text-align: center;
            letter-spacing: 0.04em;
        }

        .otp-hint span {
            color: var(--gold);
            font-weight: 500;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.97) translateY(12px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .anim { animation: fadeUp 0.55s cubic-bezier(0.22,0.61,0.36,1) both; }
        .anim-1 { animation-delay: 0.06s; }
        .anim-2 { animation-delay: 0.12s; }
        .anim-3 { animation-delay: 0.18s; }
        .anim-4 { animation-delay: 0.24s; }
        .anim-5 { animation-delay: 0.30s; }
        .anim-6 { animation-delay: 0.36s; }
        .anim-7 { animation-delay: 0.42s; }
        .anim-8 { animation-delay: 0.48s; }

        /* Responsive */
        @media (max-width: 1100px) {
            .panel-visual { width: 42%; }
            .grid-4col { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 900px) {
            html, body { overflow: auto; height: auto; }
            body { height: auto; }
            .panel-visual { display: none; }
            .panel-form { min-height: 100vh; height: auto; }
            .form-scroll { padding: 40px 32px 24px; }
            .form-watermark { padding: 10px 32px 20px; }
        }

        @media (max-width: 600px) {
            .form-scroll { padding: 32px 20px 20px; }
            .grid-4col, .grid-2col, .grid-7-5 { grid-template-columns: 1fr; }
            .form-heading h2 { font-size: 30px; }
        }
    </style>
</head>
<body>

<?php if ($show_otp_modal): ?>
<div class="otp-overlay">
    <div class="otp-card">
        <div class="otp-index">
            <span class="otp-index-num">02</span>
            <div class="otp-index-rule"></div>
            <span class="otp-index-label">Verification Required</span>
        </div>

        <div class="otp-heading" style="margin-bottom: 4px;">
            <h3>Enter your<br><em>OTP code</em></h3>
            <p>A 6-digit verification key has been sent to your email address. It expires in <span style="color: var(--gold); font-weight: 500;">10 minutes</span>.</p>
        </div>

        <?php if($error && $show_otp_modal): ?>
        <div class="alert-custom alert-error" style="margin-top: 16px; margin-bottom: 0;">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="7.5" stroke="#c0392b"/>
                <path d="M8 4.5v4M8 10.5v1" stroke="#c0392b" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="otp-input-wrap">
                <input type="text" name="otp_code" class="otp-input"
                       placeholder="· · · · · ·"
                       maxlength="6" pattern="\d{6}" required autocomplete="off"
                       inputmode="numeric">
            </div>
            <p class="otp-hint" style="margin-bottom: 20px;">Enter the code exactly as received. <span>No spaces.</span></p>
            <button type="submit" name="verify_otp_btn" class="btn-submit" style="margin-top: 0;">
                <span>Verify &amp; Activate Account</span>
                <div class="btn-arrow"></div>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════
     LEFT — Visual panel
════════════════════════════════════ -->
<div class="panel-visual">
    <div class="panel-visual-bg"></div>
    <div class="panel-visual-glow"></div>

    <div class="panel-lines">
        <svg viewBox="0 0 460 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
            <polyline points="48,40 48,48 56,48" fill="none" stroke="#c9a96e" stroke-width="0.8" opacity="0.5"/>
            <polyline points="412,852 412,860 404,860" fill="none" stroke="#c9a96e" stroke-width="0.8" opacity="0.5"/>
            <line x1="72" y1="48" x2="180" y2="48" stroke="#c9a96e" stroke-width="0.5" opacity="0.25"/>
            <line x1="48" y1="72" x2="48" y2="170" stroke="#c9a96e" stroke-width="0.5" opacity="0.25"/>
            <polygon points="230,438 242,450 230,462 218,450" fill="none" stroke="#c9a96e" stroke-width="0.6" opacity="0.3"/>
            <polygon points="230,420 248,450 230,480 212,450" fill="none" stroke="#c9a96e" stroke-width="0.35" opacity="0.15"/>
            <path d="M 80 720 Q 230 640 380 720" fill="none" stroke="#c9a96e" stroke-width="0.4" opacity="0.15"/>
            <line x1="48" y1="830" x2="220" y2="830" stroke="#c9a96e" stroke-width="0.5" opacity="0.25"/>
        </svg>
    </div>

    <div class="panel-visual-content">
        <div class="brand-mark">
            <div class="brand-mark-wordmark">
                <div class="brand-mark-line"></div>
                <div class="brand-mark-name">New Account</div>
            </div>
            <div class="brand-mark-title">
                Lumiére<br><em>&amp; Bliss</em>
            </div>
        </div>

        <!-- Step indicators -->
        <div class="panel-steps">
            <div class="step-item active">
                <div class="step-dot active"><div class="step-dot-inner"></div></div>
                <span class="step-label-text">Personal Details</span>
            </div>
            <div class="step-connector"></div>
            <div class="step-item active">
                <div class="step-dot active"><div class="step-dot-inner"></div></div>
                <span class="step-label-text">Contact &amp; Security</span>
            </div>
            <div class="step-connector"></div>
            <div class="step-item">
                <div class="step-dot"><div class="step-dot-inner"></div></div>
                <span class="step-label-text">Verify via OTP</span>
            </div>
            <div class="step-connector"></div>
            <div class="step-item">
                <div class="step-dot"><div class="step-dot-inner"></div></div>
                <span class="step-label-text">Account Activated</span>
            </div>
        </div>

        <div class="panel-editorial">
            <div class="panel-editorial-rule"></div>
            <div class="panel-editorial-quote">
                "Give yourself a break — have a cool and nice relaxation."
            </div>
            <div class="panel-editorial-meta">Take a break &amp; breathe</div>
        </div>
    </div>

    <div class="panel-stamp">
        <span>Lumiére and Bliss</span>
        <div class="panel-stamp-dot"></div>
        <span>Luxury Wellness</span>
    </div>
</div>

<!-- ═══════════════════════════════════
     RIGHT — Form panel
════════════════════════════════════ -->
<div class="panel-form">

    <div class="form-corner">
        <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <line x1="30" y1="0" x2="120" y2="0" stroke="#c9a96e" stroke-width="0.6" opacity="0.3"/>
            <line x1="120" y1="0" x2="120" y2="90" stroke="#c9a96e" stroke-width="0.6" opacity="0.3"/>
            <line x1="80" y1="0" x2="120" y2="0" stroke="#c9a96e" stroke-width="1.2" opacity="0.5"/>
            <line x1="120" y1="0" x2="120" y2="40" stroke="#c9a96e" stroke-width="1.2" opacity="0.5"/>
        </svg>
    </div>

    <div class="form-scroll">

        <div class="form-index anim anim-1">
            <span class="form-index-num">01</span>
            <div class="form-index-rule"></div>
            <span class="form-index-label">Create Account</span>
        </div>

        <div class="form-heading anim anim-2">
            <h2>Join<br><em>Lumiére</em></h2>
            <p>Start booking your perfect celebration with us.</p>
        </div>

        <?php if($error && !$show_otp_modal): ?>
        <div class="alert-custom alert-error anim anim-2">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="7.5" stroke="#c0392b"/>
                <path d="M8 4.5v4M8 10.5v1" stroke="#c0392b" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form action="" method="POST" id="signup-form" novalidate>
            <input type="hidden" name="register_attempt" value="1">

            <!-- Personal Information -->
            <div class="section-label anim anim-3"><span>Personal Information</span></div>

            <div class="fields-grid grid-4col anim anim-3">
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="first_name">First Name</label>
                    </div>
                    <div class="field-input-wrap">
                        <input type="text" id="first_name" name="first_name"
                               placeholder="e.g. Maria"
                               value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>" required>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="middle_name">Middle Name</label>
                    </div>
                    <div class="field-input-wrap">
                        <input type="text" id="middle_name" name="middle_name"
                               placeholder="Optional"
                               value="<?= isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : '' ?>">
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="last_name">Last Name</label>
                    </div>
                    <div class="field-input-wrap">
                        <input type="text" id="last_name" name="last_name"
                               placeholder="e.g. Santos"
                               value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>" required>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="suffix">Suffix</label>
                    </div>
                    <div class="field-input-wrap">
                        <input type="text" id="suffix" name="suffix"
                               placeholder="Jr."
                               value="<?= isset($_POST['suffix']) ? htmlspecialchars($_POST['suffix']) : '' ?>">
                    </div>
                </div>
            </div>

            <div class="fields-grid grid-2col anim anim-4" style="margin-top: 10px;">
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="birthdate">Birthdate</label>
                    </div>
                    <div class="field-input-wrap">
                        <input type="date" id="birthdate" name="birthdate"
                               max="<?= date('Y-m-d', strtotime('-18 years')); ?>"
                               value="<?= isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : '' ?>" required>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="gender">Gender</label>
                    </div>
                    <div class="field-input-wrap" style="position: relative;">
                        <select id="gender" name="gender" required>
                            <option value="" disabled <?= !isset($_POST['gender']) ? 'selected' : '' ?>>Select gender</option>
                            <option value="Male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                        <span class="select-arrow">
                            <svg width="10" height="6" viewBox="0 0 10 6" fill="none">
                                <path d="M1 1l4 4 4-4" stroke="#8a8070" stroke-width="1.4" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Contact Details -->
            <div class="section-label anim anim-5"><span>Contact Details</span></div>

            <div class="fields-grid grid-7-5 anim anim-5">
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="email">Email Address</label>
                    </div>
                    <div class="field-input-wrap">
                        <input type="email" id="email" name="email"
                               placeholder="you@example.com"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="contact_number">Contact Number</label>
                    </div>
                    <div class="field-input-wrap">
                        <input type="tel" id="contact_number" name="contact_number"
                               placeholder="09xxxxxxxxx" maxlength="11"
                               value="<?= isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : '' ?>" required>
                    </div>
                </div>
            </div>

            <!-- Security -->
            <div class="section-label anim anim-6"><span>Security</span></div>

            <div class="fields-grid grid-2col anim anim-6">
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="password">Password</label>
                    </div>
                    <div class="field-input-wrap">
                        <input type="password" id="password" name="password"
                               placeholder="Create a strong password"
                               required autocomplete="new-password">
                        <button type="button" class="pw-toggle" onclick="togglePw('password', this)" aria-label="Toggle password">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="confirm_password">Confirm Password</label>
                    </div>
                    <div class="field-input-wrap">
                        <input type="password" id="confirm_password" name="confirm_password"
                               placeholder="Repeat your password"
                               required autocomplete="new-password">
                        <button type="button" class="pw-toggle" onclick="togglePw('confirm_password', this)" aria-label="Toggle confirm password">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="pw-rules anim anim-7" id="pw-rules">
                <div class="pw-rule" id="rule-len"><span class="dot"></span>8+ characters</div>
                <div class="pw-rule" id="rule-upper"><span class="dot"></span>Uppercase letter</div>
                <div class="pw-rule" id="rule-num"><span class="dot"></span>One number</div>
                <div class="pw-rule" id="rule-match"><span class="dot"></span>Passwords match</div>
            </div>

            <div class="anim anim-7">
                <button type="submit" class="btn-submit">
                    <span>Send Verification OTP</span>
                    <div class="btn-arrow"></div>
                </button>
            </div>

        </form>

        <div class="form-footer anim anim-8">
            <p class="form-footer-signin">
                Already have an account? <a href="signin.php">Sign In</a>
            </p>
            <a href="index.php" class="back-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M19 12H5M12 5l-7 7 7 7"/>
                </svg>
                Back to Home
            </a>
        </div>

    </div>

    <div class="form-watermark">
        <div class="form-watermark-line"></div>
        <span class="form-watermark-text">Lumiére and Bliss · Luxury Wellness</span>
    </div>

</div>

<script>
    function togglePw(id, btn) {
        const input = document.getElementById(id);
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        btn.querySelector('svg').style.opacity = isText ? '1' : '0.4';
    }

    const pwInput  = document.getElementById('password');
    const confInput = document.getElementById('confirm_password');

    function checkRules() {
        const val  = pwInput.value;
        const conf = confInput.value;
        toggleRule('rule-len',   val.length >= 8);
        toggleRule('rule-upper', /[A-Z]/.test(val));
        toggleRule('rule-num',   /[0-9]/.test(val));
        toggleRule('rule-match', val.length > 0 && val === conf);
    }

    function toggleRule(id, met) {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('met', met);
    }

    pwInput.addEventListener('input', checkRules);
    confInput.addEventListener('input', checkRules);

    document.getElementById('contact_number').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 11);
    });

    // OTP: numeric only
    const otpInput = document.querySelector('.otp-input');
    if (otpInput) {
        otpInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
        otpInput.focus();
    }
</script>
</body>
</html>