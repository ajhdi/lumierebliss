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

        html { font-size: 14px; }

        body {
            background: var(--dark);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            overflow: hidden;
        }

        /* ─────────────────────────────────────────
           LEFT — Cinematic image panel
        ───────────────────────────────────────── */
        .panel-visual {
            width: 55%;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }

        /* Fallback gradient when no hero image is present */
        .panel-visual-bg {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(160deg, #0e0d0b 0%, #1c1a15 40%, #251f12 100%);
        }

        /* Subtle canvas texture */
        .panel-visual-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.08'/%3E%3C/svg%3E");
            background-size: 200px 200px;
            opacity: 0.4;
            mix-blend-mode: overlay;
        }

        /* Gold atmospheric glow */
        .panel-visual-glow {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 30% 60%, rgba(201,169,110,0.14) 0%, transparent 65%),
                radial-gradient(ellipse 40% 40% at 75% 20%, rgba(201,169,110,0.08) 0%, transparent 60%);
            z-index: 1;
        }

        /* Geometric line ornaments */
        .panel-lines {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
        }

        .panel-lines svg {
            width: 100%;
            height: 100%;
        }

        /* Content inside visual panel */
        .panel-visual-content {
            position: absolute;
            inset: 0;
            z-index: 3;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 36px 44px;
        }

        /* Brand mark top-left */
        .brand-mark {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

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
            font-size: 28px;
            font-weight: 300;
            line-height: 1.1;
            color: #fff;
            padding-left: 46px;
        }

        .brand-mark-title em {
            font-style: italic;
            color: var(--gold-light);
        }

        /* Bottom editorial block */
        .panel-editorial {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .panel-editorial-rule {
            width: 48px;
            height: 1px;
            background: var(--gold);
            opacity: 0.6;
        }

        .panel-editorial-quote {
            font-family: 'Cormorant Garamond', serif;
            font-size: 16px;
            font-weight: 300;
            font-style: italic;
            line-height: 1.55;
            color: rgba(255,255,255,0.65);
            max-width: 320px;
        }

        .panel-editorial-meta {
            font-size: 10px;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: rgba(201,169,110,0.65);
            font-weight: 500;
        }

        /* Vertical text stamp */
        .panel-stamp {
            position: absolute;
            right: 40px;
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
            color: rgba(201,169,110,0.4);
            font-weight: 500;
            white-space: nowrap;
        }

        .panel-stamp-dot {
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: var(--gold);
            opacity: 0.4;
            flex-shrink: 0;
        }

        /* Right edge vignette */
        .panel-visual::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 80px;
            background: linear-gradient(to right, transparent, rgba(26,26,26,0.8));
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
        }

        /* Top corner ornament */
        .form-corner {
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 120px;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .form-corner svg {
            position: absolute;
            top: 0;
            right: 0;
        }

        /* Scroll content */
        .form-scroll {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px 44px;
            position: relative;
            z-index: 1;
        }

        /* Index badge */
        .form-index {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
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

        /* Heading */
        .form-heading {
            margin-bottom: 28px;
        }

        .form-heading h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 36px;
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
            font-size: 14px;
            color: var(--muted);
            margin-top: 10px;
            font-weight: 300;
            letter-spacing: 0.01em;
        }

        /* Alerts */
        .alert-custom {
            border-radius: 4px;
            padding: 14px 18px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 32px;
            border-left: 2px solid;
        }

        .alert-error {
            background: rgba(192,57,43,0.05);
            border-color: var(--error);
            color: var(--error);
        }

        .alert-success {
            background: rgba(90,122,90,0.07);
            border-color: var(--success);
            color: var(--success);
        }

        .alert-custom svg { flex-shrink: 0; margin-top: 1px; }

        /* Fields */
        .field-group {
            margin-bottom: 16px;
        }

        .field-label-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 8px;
        }

        .field-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
            transition: color 0.2s;
        }

        .field-group:focus-within .field-label {
            color: var(--dark);
        }

        .field-forgot {
            font-size: 11px;
            color: var(--muted);
            text-decoration: none;
            letter-spacing: 0.04em;
            border-bottom: 1px solid transparent;
            transition: color 0.2s, border-color 0.2s;
        }

        .field-forgot:hover {
            color: var(--gold);
            border-color: rgba(201,169,110,0.5);
        }

        .field-input-wrap {
            position: relative;
        }

        .field-input-wrap input {
            width: 100%;
            background: #fff;
            border: 1px solid var(--border-soft);
            border-bottom: 2px solid var(--border-soft);
            border-radius: 2px;
            padding: 11px 14px;
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            color: var(--dark);
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.2s;
            letter-spacing: 0.01em;
        }

        .field-input-wrap input:focus {
            border-color: var(--border-soft);
            border-bottom-color: var(--gold);
            background: #fff;
            box-shadow: 0 4px 20px rgba(201,169,110,0.08);
        }

        .field-input-wrap input::placeholder {
            color: #cdc9c1;
            font-size: 14px;
            font-weight: 300;
        }

        /* Password toggle */
        .pw-toggle {
            position: absolute;
            right: 16px;
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
            margin-top: 8px;
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
        }

        .btn-arrow::after {
            content: '';
            position: absolute;
            right: 0;
            top: -3px;
            width: 7px;
            height: 7px;
            border-right: 1px solid rgba(255,255,255,0.5);
            border-top: 1px solid rgba(255,255,255,0.5);
            transform: rotate(45deg);
        }

        .btn-submit:hover .btn-arrow { width: 28px; }

        /* Divider */
        .form-divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 20px 0;
        }

        .form-divider::before,
        .form-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-soft);
        }

        .form-divider span {
            font-size: 9px;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 500;
        }

        /* Bottom links */
        .form-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .form-footer-signup {
            font-size: 13px;
            color: var(--muted);
            font-weight: 300;
        }

        .form-footer-signup a {
            color: var(--dark);
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid var(--dark);
            padding-bottom: 1px;
            transition: color 0.2s, border-color 0.2s;
            letter-spacing: 0.02em;
        }

        .form-footer-signup a:hover {
            color: var(--gold);
            border-color: var(--gold);
        }

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

        /* Bottom watermark */
        .form-watermark {
            padding: 12px 44px 24px;
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

        /* Animation */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .anim { animation: fadeUp 0.6s cubic-bezier(0.22,0.61,0.36,1) both; }
        .anim-1 { animation-delay: 0.1s; }
        .anim-2 { animation-delay: 0.18s; }
        .anim-3 { animation-delay: 0.26s; }
        .anim-4 { animation-delay: 0.34s; }
        .anim-5 { animation-delay: 0.42s; }
        .anim-6 { animation-delay: 0.5s; }

        /* Responsive */
        @media (max-width: 960px) {
            body { overflow: auto; }

            .panel-visual {
                display: none;
            }

            .panel-form {
                min-height: 100vh;
            }

            .form-scroll {
                padding: 48px 32px;
            }

            .form-watermark {
                padding: 16px 32px 28px;
            }
        }

        @media (max-width: 480px) {
            .form-scroll { padding: 40px 24px; }
            .form-heading h2 { font-size: 38px; }
            .form-watermark { padding: 16px 24px 24px; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════
     LEFT — Visual panel
════════════════════════════════════ -->
<div class="panel-visual">
    <div class="panel-visual-bg"></div>
    <div class="panel-visual-glow"></div>

    <!-- Geometric SVG ornaments -->
    <div class="panel-lines">
        <svg viewBox="0 0 600 900" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
            <!-- Corner bracket top-left -->
            <polyline points="56,48 56,56 64,56" fill="none" stroke="#c9a96e" stroke-width="0.8" opacity="0.5"/>
            <!-- Corner bracket bottom-right -->
            <polyline points="544,844 544,852 536,852" fill="none" stroke="#c9a96e" stroke-width="0.8" opacity="0.5"/>
            <!-- Horizontal rule top -->
            <line x1="80" y1="56" x2="200" y2="56" stroke="#c9a96e" stroke-width="0.5" opacity="0.3"/>
            <!-- Vertical rule left -->
            <line x1="56" y1="80" x2="56" y2="180" stroke="#c9a96e" stroke-width="0.5" opacity="0.3"/>
            <!-- Central diamond -->
            <polygon points="300,440 314,454 300,468 286,454" fill="none" stroke="#c9a96e" stroke-width="0.6" opacity="0.35"/>
            <polygon points="300,420 334,454 300,488 266,454" fill="none" stroke="#c9a96e" stroke-width="0.4" opacity="0.2"/>
            <!-- Subtle arc -->
            <path d="M 100 700 Q 300 600 500 700" fill="none" stroke="#c9a96e" stroke-width="0.4" opacity="0.18"/>
            <!-- Horizontal rule bottom -->
            <line x1="56" y1="820" x2="260" y2="820" stroke="#c9a96e" stroke-width="0.5" opacity="0.3"/>
        </svg>
    </div>

    <!-- Content -->
    <div class="panel-visual-content">
        <div class="brand-mark">
            <div class="brand-mark-wordmark">
                <div class="brand-mark-line"></div>
                <div class="brand-mark-name">Est. Experience</div>
            </div>
            <div class="brand-mark-title">
                Lumiére<br><em>&amp; Bliss</em>
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

    <!-- Vertical stamp -->
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

    <!-- Corner ornament SVG -->
    <div class="form-corner">
        <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <line x1="30" y1="0" x2="120" y2="0" stroke="#c9a96e" stroke-width="0.6" opacity="0.3"/>
            <line x1="120" y1="0" x2="120" y2="90" stroke="#c9a96e" stroke-width="0.6" opacity="0.3"/>
            <line x1="80" y1="0" x2="120" y2="0" stroke="#c9a96e" stroke-width="1.2" opacity="0.5"/>
            <line x1="120" y1="0" x2="120" y2="40" stroke="#c9a96e" stroke-width="1.2" opacity="0.5"/>
        </svg>
    </div>

    <div class="form-scroll">

        <!-- Index badge -->
        <div class="form-index anim anim-1">
            <span class="form-index-num">01</span>
            <div class="form-index-rule"></div>
            <span class="form-index-label">Member Access</span>
        </div>

        <!-- Heading -->
        <div class="form-heading anim anim-2">
            <h2>Welcome<br><em>back</em></h2>
            <p>Sign in to continue your journey.</p>
        </div>

        <!-- Alerts -->
        <?php if($error): ?>
        <div class="alert-custom alert-error anim anim-2">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="7.5" stroke="#c0392b"/>
                <path d="M8 4.5v4M8 10.5v1" stroke="#c0392b" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <?php if($success): ?>
        <div class="alert-custom alert-success anim anim-2">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="7.5" stroke="#5a7a5a"/>
                <path d="M5 8l2 2 4-4" stroke="#5a7a5a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="" method="POST" novalidate>

            <div class="field-group anim anim-3">
                <div class="field-label-row">
                    <label class="field-label" for="email">Email Address</label>
                </div>
                <div class="field-input-wrap">
                    <input type="email" id="email" name="email"
                           placeholder="you@example.com"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                           required autofocus>
                </div>
            </div>

            <div class="field-group anim anim-4">
                <div class="field-label-row">
                    <label class="field-label" for="password">Password</label>
                    <a href="forgot_password.php" class="field-forgot">Forgot password?</a>
                </div>
                <div class="field-input-wrap">
                    <input type="password" id="password" name="password"
                           placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="pw-toggle" onclick="togglePw()" aria-label="Toggle password visibility">
                        <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="anim anim-5">
                <button type="submit" class="btn-submit">
                    <span>Sign In</span>
                    <div class="btn-arrow"></div>
                </button>
            </div>

        </form>

        <div class="form-divider anim anim-6">
            <span>or</span>
        </div>

        <div class="form-footer anim anim-6">
            <p class="form-footer-signup">
                New to Lumiére? <a href="signup.php">Create an account</a>
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
    function togglePw() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eye-icon');
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        icon.style.opacity = isText ? '1' : '0.45';
    }
</script>

</body>
</html>