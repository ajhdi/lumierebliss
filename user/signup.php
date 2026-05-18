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
            
            /* CRITICAL NOTE: Do not use your regular account password here.
               Go to your Google Account > Security > 2-Step Verification > App Passwords.
               Generate a unique 16-character string password for "Mail" and insert it here.
            */
            $mail->Password   = 'efrscvjtyzktibya'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('lumiereandbliss@gmail.com', 'Lumiére and Bliss');
            $mail->addAddress($email, $first . ' ' . $last);

            // Content Setup matching your minimalist design concept
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
            $show_otp_modal = true; // Trigger OTP layout verification overlay interface
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
        $show_otp_modal = true; // Keep open if they made a mistake
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --cream: #fdfbf7;
            --warm-white: #f9f6f0;
            --gold: #c9a96e;
            --gold-light: #e8d5b0;
            --dark: #1a1a1a;
            --dark-soft: #2e2e2e;
            --muted: #8a8070;
            --border: #ede8df;
            --error: #c0392b;
            --success: #5a7a5a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--cream);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: stretch;
        }

        .side-panel {
            width: 340px;
            flex-shrink: 0;
            background: var(--dark);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 36px;
        }

        .side-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 200px 200px at 20% 20%, rgba(201,169,110,0.18) 0%, transparent 70%),
                radial-gradient(ellipse 180px 180px at 80% 75%, rgba(201,169,110,0.12) 0%, transparent 70%);
        }

        .side-ornament {
            position: absolute;
            top: 32px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        .side-ornament-bottom {
            position: absolute;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        .side-logo {
            position: relative;
            text-align: center;
            z-index: 1;
        }

        .side-logo .ampersand {
            font-family: 'Cormorant Garamond', serif;
            font-size: 90px;
            font-weight: 300;
            font-style: italic;
            color: var(--gold);
            line-height: 1;
            opacity: 0.9;
            display: block;
            margin-bottom: 16px;
        }

        .side-logo h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 400;
            letter-spacing: 0.12em;
            color: #fff;
            line-height: 1.5;
            text-transform: uppercase;
        }

        .side-logo p {
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            color: var(--gold-light);
            letter-spacing: 0.22em;
            text-transform: uppercase;
            margin-top: 10px;
            opacity: 0.75;
        }

        .side-quote {
            position: relative;
            z-index: 1;
            margin-top: 48px;
            text-align: center;
            border-top: 1px solid rgba(201,169,110,0.25);
            padding-top: 32px;
        }

        .side-quote em {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 15px;
            color: rgba(255,255,255,0.55);
            line-height: 1.7;
        }

        .form-panel {
            flex: 1;
            overflow-y: auto;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 52px 40px;
        }

        .form-inner {
            width: 100%;
            max-width: 560px;
        }

        .form-heading {
            margin-bottom: 36px;
        }

        .form-heading .step-label {
            font-size: 10px;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--gold);
            font-weight: 500;
            margin-bottom: 10px;
        }

        .form-heading h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
            font-weight: 400;
            color: var(--dark);
            line-height: 1.2;
        }

        .form-heading p {
            font-size: 13px;
            color: var(--muted);
            margin-top: 6px;
            font-weight: 300;
        }

        .section-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0 20px;
        }

        .section-divider span {
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            white-space: nowrap;
            font-weight: 500;
        }

        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .field-group {
            position: relative;
            margin-bottom: 4px;
        }

        .field-group label {
            display: block;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
            transition: color 0.2s;
        }

        .field-group:focus-within label {
            color: var(--dark);
        }

        .field-group input,
        .field-group select {
            width: 100%;
            background: var(--warm-white);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 16px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            color: var(--dark);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            appearance: none;
            -webkit-appearance: none;
        }

        .field-group input:focus,
        .field-group select:focus {
            border-color: var(--gold);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
        }

        .field-group input::placeholder {
            color: #ccc;
            font-size: 13px;
        }

        .select-wrap {
            position: relative;
        }

        .select-wrap::after {
            content: '';
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 8px;
            height: 5px;
            background: var(--muted);
            clip-path: polygon(0 0, 100% 0, 50% 100%);
            pointer-events: none;
        }

        .pw-rules {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .pw-rule {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: var(--muted);
            transition: color 0.25s;
        }

        .pw-rule .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--border);
            transition: background 0.25s;
            flex-shrink: 0;
        }

        .pw-rule.met { color: var(--success); }
        .pw-rule.met .dot { background: var(--success); }

        .pw-wrap {
            position: relative;
        }

        .pw-wrap input { padding-right: 44px; }

        .pw-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            padding: 2px;
            line-height: 1;
            transition: color 0.2s;
        }

        .pw-toggle:hover { color: var(--dark); }

        .alert-custom {
            background: #fdf0ef;
            border: 1px solid #f5c6c3;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: var(--error);
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
        }

        .btn-submit {
            width: 100%;
            background: var(--dark);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.25s, transform 0.15s, box-shadow 0.25s;
            margin-top: 32px;
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            background: var(--dark-soft);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(26,26,26,0.18);
        }

        .signin-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: var(--muted);
        }

        .signin-link a {
            color: var(--dark);
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px solid var(--dark);
            padding-bottom: 1px;
            transition: color 0.2s, border-color 0.2s;
        }

        .signin-link a:hover {
            color: var(--gold);
            border-color: var(--gold);
        }

        /* ── CUSTOM MINIMALIST OTP OVERLAY ── */
        .otp-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(26, 26, 26, 0.6);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .otp-card {
            background: var(--cream);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            animation: modalFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .otp-input {
            letter-spacing: 8px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            border-radius: 10px;
            border: 1px solid var(--border);
            padding: 12px;
            background: var(--warm-white);
            color: var(--dark);
            outline: none;
            width: 100%;
            margin: 20px 0;
        }

        .otp-input:focus {
            border-color: var(--gold);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .row-fields { display: grid; gap: 12px; }
        .cols-4-3-3-2 { grid-template-columns: 4fr 3fr 3fr 2fr; }
        .cols-7-5 { grid-template-columns: 7fr 5fr; }
        .cols-1-1 { grid-template-columns: 1fr 1fr; }
        .cols-6-6 { grid-template-columns: 6fr 6fr; }

        @media (max-width: 900px) {
            .side-panel { display: none; }
            .form-panel { padding: 36px 24px; }
        }

        @media (max-width: 600px) {
            .cols-4-3-3-2, .cols-7-5, .cols-1-1, .cols-6-6 { grid-template-columns: 1fr; }
        }

        .form-inner { animation: fadeUp 0.5s ease both; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<?php if ($show_otp_modal): ?>
<div class="otp-overlay">
    <div class="otp-card">
        <div class="form-heading mb-3">
            <div class="step-label">Verification Required</div>
            <h3 class="font-serif" style="font-family:'Cormorant Garamond', serif; font-size:28px;">Enter OTP Code</h3>
            <p>We have sent a 6-digit confirmation key to your email address.</p>
        </div>
        <form action="" method="POST">
            <input type="text" name="otp_code" class="otp-input" placeholder="000000" maxlength="6" pattern="\d{6}" required autocomplete="off">
            <button type="submit" name="verify_otp_btn" class="btn-submit m-0 w-100">Verify &amp; Activate Account</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="side-panel">
    <div class="side-ornament"></div>
    <div class="side-logo">
        <span class="ampersand">&amp;</span>
        <h1>Lumiére<br>and Bliss</h1>
        <p>Take a break &amp; Breath</p>
    </div>
    <div class="side-quote">
        <em>"Give yourself a break,<br>have a cool and nice relaxation."</em>
    </div>
    <div class="side-ornament-bottom"></div>
</div>

<div class="form-panel">
    <div class="form-inner">

        <div class="form-heading">
            <div class="step-label">New Account</div>
            <h2>Create your account</h2>
            <p>Join us and start booking your perfect celebration.</p>
        </div>

        <?php if($error): ?>
        <div class="alert-custom">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="7.5" stroke="#c0392b"/>
                <path d="M8 4.5v4M8 10.5v1" stroke="#c0392b" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form action="" method="POST" id="signup-form" novalidate>
            <input type="hidden" name="register_attempt" value="1">

            <div class="section-divider"><span>Personal Information</span></div>

            <div class="row-fields cols-4-3-3-2" style="margin-bottom:12px;">
                <div class="field-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" placeholder="e.g. Maria" value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>" required>
                </div>
                <div class="field-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" placeholder="Optional" value="<?= isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : '' ?>">
                </div>
                <div class="field-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" placeholder="e.g. Santos" value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>" required>
                </div>
                <div class="field-group">
                    <label for="suffix">Suffix</label>
                    <input type="text" id="suffix" name="suffix" placeholder="Jr." value="<?= isset($_POST['suffix']) ? htmlspecialchars($_POST['suffix']) : '' ?>">
                </div>
            </div>

            <div class="row-fields cols-1-1" style="margin-bottom:12px;">
                <div class="field-group">
                    <label for="birthdate">Birthdate</label>
                    <input type="date" id="birthdate" name="birthdate" max="<?= date('Y-m-d', strtotime('-18 years')); ?>" value="<?= isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : '' ?>" required>
                </div>
                <div class="field-group">
                    <label for="gender">Gender</label>
                    <div class="select-wrap">
                        <select id="gender" name="gender" required>
                            <option value="" disabled <?= !isset($_POST['gender']) ? 'selected' : '' ?>>Select</option>
                            <option value="Male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="section-divider"><span>Contact Details</span></div>

            <div class="row-fields cols-7-5" style="margin-bottom:12px;">
                <div class="field-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                </div>
                <div class="field-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number" placeholder="09xxxxxxxxx" maxlength="11" value="<?= isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : '' ?>" required>
                </div>
            </div>

            <div class="section-divider"><span>Security</span></div>

            <div class="row-fields cols-6-6" style="margin-bottom:4px;">
                <div class="field-group">
                    <label for="password">Password</label>
                    <div class="pw-wrap">
                        <input type="password" id="password" name="password" placeholder="Create a strong password" required autocomplete="new-password">
                        <button type="button" class="pw-toggle" onclick="togglePw('password', this)" aria-label="Show password">
                            <svg id="eye-pw" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="field-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="pw-wrap">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required autocomplete="new-password">
                        <button type="button" class="pw-toggle" onclick="togglePw('confirm_password', this)" aria-label="Show password">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="pw-rules" id="pw-rules">
                <div class="pw-rule" id="rule-len"><span class="dot"></span> 8+ characters</div>
                <div class="pw-rule" id="rule-upper"><span class="dot"></span> Uppercase letter</div>
                <div class="pw-rule" id="rule-num"><span class="dot"></span> Number</div>
                <div class="pw-rule" id="rule-match"><span class="dot"></span> Passwords match</div>
            </div>

            <button type="submit" class="btn-submit">Receive Verification OTP &rarr;</button>

            <p class="signin-link">Already have an account? <a href="signin.php">Sign In</a></p>
        </form>
    </div>
</div>

<script>
    function togglePw(id, btn) {
        const input = document.getElementById(id);
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        btn.querySelector('svg').style.opacity = isText ? '1' : '0.45';
    }

    const pwInput = document.getElementById('password');
    const confInput = document.getElementById('confirm_password');

    function checkRules() {
        const val = pwInput.value;
        const conf = confInput.value;
        toggle('rule-len',   val.length >= 8);
        toggle('rule-upper', /[A-Z]/.test(val));
        toggle('rule-num',   /[0-9]/.test(val));
        toggle('rule-match', val.length > 0 && val === conf);
    }

    function toggle(id, met) {
        const el = document.getElementById(id);
        if(el) el.classList.toggle('met', met);
    }

    pwInput.addEventListener('input', checkRules);
    confInput.addEventListener('input', checkRules);

    document.getElementById('contact_number').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 11);
    });

    document.querySelectorAll('.field-group').forEach((el, i) => {
        el.style.animation = `fadeUp 0.4s ease ${0.05 + i * 0.04}s both`;
    });
</script>
</body>
</html>