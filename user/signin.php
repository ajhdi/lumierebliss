<?php
session_start();
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

$error = "";
$success = "";

if (isset($_GET['msg']) && $_GET['msg'] == 'success') {
    $success = "Account created successfully! Please sign in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['first_name'];
            $_SESSION['account_type'] = $user['account_type'];

            header("Location: home.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Lumiére and Bliss</title>
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

        /* ── Left decorative panel ── */
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

        /* ── Right form panel ── */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 52px 40px;
        }

        .form-inner {
            width: 100%;
            max-width: 400px;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Heading ── */
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
            font-size: 38px;
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

        /* ── Field groups ── */
        .field-group {
            margin-bottom: 16px;
            animation: fadeUp 0.4s ease both;
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

        .field-group input {
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
        }

        .field-group input:focus {
            border-color: var(--gold);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
        }

        .field-group input::placeholder {
            color: #ccc;
            font-size: 13px;
        }

        /* ── Label row (password + forgot) ── */
        .label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .label-row label {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
            transition: color 0.2s;
            margin: 0;
        }

        .label-row a {
            font-size: 11px;
            color: var(--muted);
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: color 0.2s, border-color 0.2s;
        }

        .label-row a:hover {
            color: var(--gold);
            border-color: var(--gold);
        }

        /* ── Password toggle ── */
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

        /* ── Alerts ── */
        .alert-custom {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
        }

        .alert-error {
            background: #fdf0ef;
            border: 1px solid #f5c6c3;
            color: var(--error);
        }

        .alert-success {
            background: #f0f5f0;
            border: 1px solid #b8d4b8;
            color: var(--success);
        }

        .alert-custom svg { flex-shrink: 0; }

        /* ── Submit button ── */
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
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            background: var(--dark-soft);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(26,26,26,0.18);
        }

        .btn-submit:active { transform: translateY(0); }

        /* ── Divider ── */
        .or-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0;
        }

        .or-divider::before,
        .or-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .or-divider span {
            font-size: 10px;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
        }

        /* ── Bottom links ── */
        .bottom-links {
            text-align: center;
        }

        .bottom-links p {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 16px;
        }

        .bottom-links p a {
            color: var(--dark);
            font-weight: 500;
            text-decoration: none;
            border-bottom: 1px solid var(--dark);
            padding-bottom: 1px;
            transition: color 0.2s, border-color 0.2s;
        }

        .bottom-links p a:hover {
            color: var(--gold);
            border-color: var(--gold);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--muted);
            text-decoration: none;
            letter-spacing: 0.05em;
            transition: color 0.2s;
        }

        .back-link:hover { color: var(--dark); }

        .back-link svg { transition: transform 0.2s; }
        .back-link:hover svg { transform: translateX(-3px); }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .side-panel { display: none; }
            .form-panel { padding: 36px 24px; }
        }
    </style>
</head>
<body>

<!-- Left decorative panel -->
<div class="side-panel">
    <div class="side-ornament"></div>
    <div class="side-logo">
        <span class="ampersand">&amp;</span>
        <h1>Lumiére<br>and Bliss</h1>
        <p>Take a break &amp; Breath</p>
    </div>
    <div class="side-quote">
        <em>"Give yourself a beak,<br>have a cool and nice relaxation."</em>
    </div>
    <div class="side-ornament-bottom"></div>
</div>

<!-- Right form panel -->
<div class="form-panel">
    <div class="form-inner">

        <div class="form-heading">
            <div class="step-label">Welcome Back</div>
            <h2>Sign in to<br>your account</h2>
            <p>Continue where you left off.</p>
        </div>

        <?php if($error): ?>
        <div class="alert-custom alert-error">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="7.5" stroke="#c0392b"/>
                <path d="M8 4.5v4M8 10.5v1" stroke="#c0392b" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if($success): ?>
        <div class="alert-custom alert-success">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="7.5" stroke="#5a7a5a"/>
                <path d="M5 8l2 2 4-4" stroke="#5a7a5a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form action="" method="POST" novalidate>

            <div class="field-group" style="animation-delay: 0.05s;">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="you@example.com"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                       required autofocus>
            </div>

            <div class="field-group" style="animation-delay: 0.1s;">
                <div class="label-row">
                    <label for="password">Password</label>
                    <a href="forgot_password.php">Forgot password?</a>
                </div>
                <div class="pw-wrap">
                    <input type="password" id="password" name="password"
                           placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="pw-toggle" onclick="togglePw()" aria-label="Show password">
                        <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Sign In &rarr;</button>

        </form>

        <div class="or-divider"><span>or</span></div>

        <div class="bottom-links">
            <p>Don't have an account? <a href="signup.php">Create one</a></p>
            <a href="index.php" class="back-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 5l-7 7 7 7"/>
                </svg>
                Back to Home
            </a>
        </div>

    </div>
</div>

<script>
    function togglePw() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eye-icon');
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        icon.style.opacity = isText ? '1' : '0.45';
    }
</script>

</body>
</html>