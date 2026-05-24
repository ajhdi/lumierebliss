<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['therapist_id'])) {
    header('Location: schedule.php');
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM therapists WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $therapist = $stmt->fetch();

        if ($therapist && password_verify($password, $therapist['password'])) {
            $_SESSION['therapist_id'] = $therapist['therapist_id'];
            $_SESSION['therapist_name'] = $therapist['first_name'] . ' ' . $therapist['last_name'];
            header("Location: schedule.php");
            exit();
        } else {
            $error = "Invalid username, password, or account is inactive.";
        }
    } else {
        $error = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Therapist Login — Lumiére &amp; Bliss</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        /* ── Root Tokens ─────────────────────────────────────────── */
        :root {
            --gold:         #c9a96e;
            --gold-light:   #e8d5b0;
            --gold-pale:    #f5edd8;
            --dark:         #1a1a1a;
            --dark-soft:    #2e2e2e;
            --surface:      #fdfbf7;
            --white:        #ffffff;
            --muted:        #8a8070;
            --muted-light:  #b8ae9f;
            --border:       rgba(201,169,110,0.22);
            --border-mid:   rgba(201,169,110,0.40);
            --error-bg:     #fff8f0;
            --error-border: rgba(201,169,110,0.5);
            --error-text:   #7a4f1a;
        }

        /* ── Reset ───────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Page Layout ─────────────────────────────────────────── */
        html, body { height: 100%; }

        body {
            font-family: 'DM Sans', sans-serif;
            font-size: 18px;
            background-color: var(--surface);
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            overflow: hidden;
        }

        /* ── Left Panel — Visual / Brand Side ───────────────────── */
        .panel-brand {
            position: relative;
            background-color: var(--dark);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 3.5rem;
            overflow: hidden;
        }

        .panel-brand::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 120% 90% at 110% 10%, rgba(201,169,110,0.14) 0%, transparent 55%),
                radial-gradient(ellipse 80% 60% at -10% 80%, rgba(201,169,110,0.09) 0%, transparent 50%);
            pointer-events: none;
        }

        .arc-deco {
            position: absolute;
            top: -120px;
            right: -120px;
            width: 580px;
            height: 580px;
            border-radius: 50%;
            border: 0.5px solid rgba(201,169,110,0.15);
            pointer-events: none;
        }
        .arc-deco::before {
            content: '';
            position: absolute;
            inset: 50px;
            border-radius: 50%;
            border: 0.5px solid rgba(201,169,110,0.10);
        }
        .arc-deco::after {
            content: '';
            position: absolute;
            inset: 110px;
            border-radius: 50%;
            border: 0.5px solid rgba(201,169,110,0.07);
        }

        .brand-rule {
            position: absolute;
            top: 3.5rem;
            left: 3.5rem;
            right: 3.5rem;
            height: 0.5px;
            background: linear-gradient(to right, var(--gold), transparent);
        }

        .brand-monogram {
            position: absolute;
            top: 3.5rem;
            left: 3.5rem;
            margin-top: -0.25rem;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 1rem;
            letter-spacing: 0.35em;
            color: var(--gold);
            text-transform: uppercase;
        }

        .brand-copy {
            position: relative;
            z-index: 1;
        }

        .brand-copy .eyebrow {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.625rem;
            font-weight: 500;
            letter-spacing: 0.3em;
            color: var(--gold);
            text-transform: uppercase;
            margin-bottom: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-copy .eyebrow::before {
            content: '';
            display: block;
            width: 2rem;
            height: 0.5px;
            background: var(--gold);
            flex-shrink: 0;
        }

        .brand-copy h1 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(2.8rem, 4vw, 3.8rem);
            line-height: 1.08;
            color: var(--white);
            margin-bottom: 1.5rem;
        }

        .brand-copy h1 em {
            font-style: italic;
            color: var(--gold-light);
        }

        .brand-copy p {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.75;
            max-width: 28ch;
        }

        .brand-divider {
            width: 2.5rem;
            height: 0.5px;
            background: var(--gold);
            margin: 2rem 0;
        }

        /* ── Right Panel — Form Side ──────────────────────────────── */
        .panel-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 5vw;
            background-color: var(--surface);
            position: relative;
        }

        .panel-form::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 9rem; height: 9rem;
            border-bottom-left-radius: 9rem;
            background: radial-gradient(ellipse at top right, rgba(201,169,110,0.10), transparent 70%);
            pointer-events: none;
        }

        .form-wrap {
            width: 100%;
            max-width: 400px;
        }

        /* ── Form Header ─────────────────────────────────────────── */
        .form-header {
            margin-bottom: 2.8rem;
        }

        .form-header .access-label {
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.3em;
            color: var(--gold);
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1rem;
        }

        .form-header .access-label span {
            display: block;
            width: 1.5rem;
            height: 0.5px;
            background: var(--gold);
        }

        .form-header h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 2rem;
            color: var(--dark);
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.7;
        }

        /* ── Error Alert ─────────────────────────────────────────── */
        .alert-luxury {
            background: var(--error-bg);
            border: 0.5px solid var(--error-border);
            border-left: 2px solid var(--gold);
            border-radius: 0;
            padding: 0.9rem 1rem;
            margin-bottom: 1.8rem;
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
        }

        .alert-luxury svg { flex-shrink: 0; margin-top: 1px; }

        .alert-luxury p {
            font-size: 0.78rem;
            color: var(--error-text);
            line-height: 1.5;
        }

        /* ── Form Fields ─────────────────────────────────────────── */
        .field-group { margin-bottom: 1.4rem; }

        .field-group label {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--dark-soft);
            margin-bottom: 0.6rem;
        }

        .field-input-wrap { position: relative; }

        .field-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted-light);
            display: flex;
            pointer-events: none;
            transition: color 0.25s;
        }

        .field-input-wrap:focus-within .field-icon { color: var(--gold); }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.9rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            color: var(--dark);
            background: var(--white);
            border: 0.5px solid var(--border-mid);
            border-radius: 0;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s;
            -webkit-appearance: none;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.10);
        }

        input[type="text"]::placeholder,
        input[type="password"]::placeholder { color: var(--muted-light); }

        .toggle-pw {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted-light);
            padding: 0;
            display: flex;
            transition: color 0.2s;
        }

        .toggle-pw:hover { color: var(--gold); }

        /* ── Submit Button ───────────────────────────────────────── */
        .btn-submit {
            width: 100%;
            margin-top: 2rem;
            padding: 1rem 2rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--white);
            background: var(--dark);
            border: none;
            border-radius: 0;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 0;
            background: var(--gold);
            transition: width 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            z-index: 0;
        }

        .btn-submit:hover::before { width: 100%; }

        .btn-submit span { position: relative; z-index: 1; }

        .btn-submit svg {
            position: relative;
            z-index: 1;
            flex-shrink: 0;
            transition: transform 0.3s;
        }

        .btn-submit:hover svg { transform: translateX(4px); }

        /* ── Footer Caption ──────────────────────────────────────── */
        .form-footer {
            margin-top: 2.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-footer::before,
        .form-footer::after {
            content: '';
            flex: 1;
            height: 0.5px;
            background: var(--border);
        }

        .form-footer p {
            font-size: 0.65rem;
            font-weight: 500;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--muted-light);
            white-space: nowrap;
        }

        /* ── Responsive ──────────────────────────────────────────── */
        @media (max-width: 860px) {
            body { grid-template-columns: 1fr; overflow: auto; }
            .panel-brand { min-height: 38vh; padding: 2.5rem 2rem; overflow: hidden; }
            .brand-monogram { top: 2.5rem; left: 2rem; }
            .brand-rule { top: 2.5rem; left: 2rem; right: 2rem; }
            .brand-copy h1 { font-size: 2.4rem; }
            .panel-form { padding: 3rem 1.5rem; justify-content: flex-start; }
            .panel-form::before { display: none; }
        }

        /* ── Brand Tagline ───────────────────────────────────────── */
        .brand-tagline {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-weight: 400;
            font-size: 0.95rem;
            color: var(--gold-light);
            line-height: 1.65;
            margin-bottom: 1.8rem;
            max-width: 32ch;
            letter-spacing: 0.01em;
        }

        /* Dual identity pillars */
        .brand-pillars {
            display: flex;
            gap: 1.5rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 0.5px solid rgba(201,169,110,0.18);
        }

        .pillar { display: flex; flex-direction: column; gap: 0.3rem; }

        .pillar-name {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--gold);
            letter-spacing: 0.08em;
        }

        .pillar-desc {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.65rem;
            color: var(--muted);
            letter-spacing: 0.05em;
            line-height: 1.5;
        }

        .pillar-sep { width: 0.5px; background: rgba(201,169,110,0.2); flex-shrink: 0; }

        /* Ambient orbs */
        .light-orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            animation: floatOrb linear infinite;
            opacity: 0;
        }

        @keyframes floatOrb {
            0%   { transform: translateY(0px) scale(1);   opacity: 0; }
            15%  { opacity: 1; }
            85%  { opacity: 1; }
            100% { transform: translateY(-140px) scale(0.6); opacity: 0; }
        }

        .shimmer-line {
            position: absolute;
            left: 0; right: 0;
            height: 0.5px;
            background: linear-gradient(to right, transparent, rgba(201,169,110,0.35), transparent);
            animation: shimmerMove 8s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes shimmerMove {
            0%, 100% { top: 30%; opacity: 0.4; }
            50%       { top: 60%; opacity: 0.8; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .form-wrap  { animation: fadeUp 0.65s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .brand-copy { animation: fadeUp 0.7s 0.15s cubic-bezier(0.22, 1, 0.36, 1) both; }
    </style>
</head>
<body>

    <!-- ═══════════════════════════════════════════
         LEFT — Brand / Visual Panel
    ═══════════════════════════════════════════ -->
    <aside class="panel-brand" aria-hidden="true">
        <div class="arc-deco"></div>
        <div class="brand-rule"></div>
        <div class="brand-monogram">L&amp;B</div>

        <div class="shimmer-line"></div>
        <div id="orb-container"></div>

        <div class="brand-copy">
            <div class="eyebrow">Sanctuary of Skilled Care</div>
            <h1>
                Lumiére<br>
                <em>&amp; Bliss</em>
            </h1>
            <div class="brand-divider"></div>

            <p class="brand-tagline">
                "Where Healing Hands Meet<br>Luminous Serenity."
            </p>

            <p>
                A haven where expert touch and mindful presence restore the spirit — one session at a time.
            </p>

            <div class="brand-pillars">
                <div class="pillar">
                    <span class="pillar-name">Lumiére</span>
                    <span class="pillar-desc">Radiance · Beauty · Luxury</span>
                </div>
                <div class="pillar-sep"></div>
                <div class="pillar">
                    <span class="pillar-name">Bliss</span>
                    <span class="pillar-desc">Peace · Self-care · Serenity</span>
                </div>
            </div>
        </div>
    </aside>

    <!-- ═══════════════════════════════════════════
         RIGHT — Sign-In Form Panel
    ═══════════════════════════════════════════ -->
    <main class="panel-form">
        <div class="form-wrap">

            <div class="form-header">
                <div class="access-label">
                    <span></span>
                    Therapist Access
                </div>
                <h2>Welcome back</h2>
                <p>Sign in to your therapist portal — view your daily schedule and manage your client sessions.</p>
            </div>

            <?php if ($error): ?>
            <div class="alert-luxury" role="alert">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="8" cy="8" r="7.25" stroke="#c9a96e" stroke-width="1.25"/>
                    <path d="M8 5v3.5" stroke="#c9a96e" stroke-width="1.25" stroke-linecap="round"/>
                    <circle cx="8" cy="11" r="0.75" fill="#c9a96e"/>
                </svg>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" novalidate>

                <div class="field-group">
                    <label for="username">Username</label>
                    <div class="field-input-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="7.5" cy="5" r="3" stroke="currentColor" stroke-width="1.2"/>
                                <path d="M1 13.5c0-3.038 2.91-5.5 6.5-5.5s6.5 2.462 6.5 5.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="field-group">
                    <label for="password">Password</label>
                    <div class="field-input-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="2.5" y="6.5" width="10" height="7" rx="1" stroke="currentColor" stroke-width="1.2"/>
                                <path d="M4.5 6.5V4a3 3 0 0 1 6 0v2.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                                <circle cx="7.5" cy="10" r="1" fill="currentColor"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button
                            type="button"
                            class="toggle-pw"
                            aria-label="Show or hide password"
                            onclick="togglePassword(this)"
                        >
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" stroke="currentColor" stroke-width="1.2"/>
                                <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.2"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Sign In to Portal</span>
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M2 7h10M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>

            <div class="form-footer">
                <p>Lumiére &amp; Bliss &copy; <?php echo date('Y'); ?></p>
            </div>

        </div>
    </main>

    <script>
        function togglePassword(btn) {
            var input = document.getElementById('password');
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.innerHTML = isHidden
                ? `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                       <path d="M2 2l12 12M6.5 6.6A2 2 0 0 0 9.4 9.5M4.2 4.3C2.6 5.3 1 8 1 8s2.5 5 7 5c1.4 0 2.7-.4 3.8-1M6 3.1C6.3 3 6.7 3 7 3c4.5 0 7 5 7 5s-.6 1.2-1.7 2.3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                   </svg>`
                : `<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                       <path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" stroke="currentColor" stroke-width="1.2"/>
                       <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.2"/>
                   </svg>`;
        }

        (function() {
            var container = document.getElementById('orb-container');
            if (!container) return;

            var orbs = [
                { size: 180, left: '15%', bottom: '25%', color: 'rgba(201,169,110,0.06)', delay: 0,   dur: 14 },
                { size: 90,  left: '55%', bottom: '40%', color: 'rgba(232,213,176,0.08)', delay: 3,   dur: 11 },
                { size: 130, left: '70%', bottom: '15%', color: 'rgba(201,169,110,0.05)', delay: 6,   dur: 16 },
                { size: 60,  left: '30%', bottom: '60%', color: 'rgba(245,237,216,0.07)', delay: 1.5, dur: 9  },
                { size: 110, left: '80%', bottom: '55%', color: 'rgba(201,169,110,0.04)', delay: 4.5, dur: 13 },
            ];

            orbs.forEach(function(o) {
                var el = document.createElement('div');
                el.className = 'light-orb';
                el.style.cssText =
                    'width:'  + o.size + 'px;' +
                    'height:' + o.size + 'px;' +
                    'left:'   + o.left + ';' +
                    'bottom:' + o.bottom + ';' +
                    'background:radial-gradient(circle,' + o.color + ' 0%,transparent 70%);' +
                    'animation-duration:' + o.dur + 's;' +
                    'animation-delay:'    + o.delay + 's;';
                container.appendChild(el);
            });
        })();
    </script>

</body>
</html>