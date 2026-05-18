<?php
include '../includes/header.php';
// session_start();

if (!isset($_SESSION['booking_success'])) {
    header("Location: book_appointment.php");
    exit;
}

$b = $_SESSION['booking_success'];
unset($_SESSION['booking_success']);
$ref = str_pad($b['appointment_id'], 6, '0', STR_PAD_LEFT);
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap');
:root {
  --gold: #c9a96e; --dark: #1a1a1a; --surface: #f9f7f4; --border: #e8e2d9;
}
body { font-family: 'DM Sans', sans-serif; background: var(--surface); }
.success-page { max-width: 560px; margin: 60px auto; padding: 0 20px 80px; text-align: center; }
.check-icon {
  width: 80px; height: 80px; background: var(--dark); border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 24px; font-size: 32px;
  animation: pop .5s cubic-bezier(.36,.07,.19,.97) both;
}
@keyframes pop {
  0% { transform: scale(0); opacity: 0; }
  70% { transform: scale(1.1); }
  100% { transform: scale(1); opacity: 1; }
}
.ref { font-size: 12px; letter-spacing: 3px; text-transform: uppercase; color: var(--gold); margin-bottom: 8px; }
h1 { font-family: 'Cormorant Garamond', serif; font-size: 38px; font-weight: 300; margin-bottom: 8px; }
h1 em { font-style: italic; color: var(--gold); }
.sub { color: #888; font-size: 14px; margin-bottom: 40px; }
.summary-card {
  background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.06);
  overflow: hidden; margin-bottom: 24px; text-align: left;
}
.summary-card .header { background: var(--dark); padding: 20px 24px; color: white; }
.summary-card .header .title { font-family: 'Cormorant Garamond', serif; font-size: 18px; color: var(--gold); }
.summary-card .body { padding: 20px 24px; }
.row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
.row:last-child { border-bottom: none; }
.row .label { color: #888; }
.row .value { font-weight: 500; }
.total-row { background: var(--surface); padding: 14px 24px; display: flex; justify-content: space-between; font-weight: 700; }
.total-row .amount { color: var(--gold); font-size: 18px; }
.notice {
  background: #fff9ee; border: 1px solid #e8d5b0; border-radius: 12px;
  padding: 16px 20px; font-size: 13px; color: #7a5c1e; margin-bottom: 24px;
}
.btn-home {
  display: inline-block; background: var(--dark); color: white;
  padding: 14px 40px; border-radius: 100px; text-decoration: none;
  font-weight: 600; font-size: 14px; transition: all .25s;
}
.btn-home:hover { background: var(--gold); }
</style>

<div class="success-page">
  <div class="check-icon">✓</div>
  <p class="ref">Booking Ref #<?= $ref ?></p>
  <h1>You're all set, <em>enjoy</em>.</h1>
  <p class="sub">A confirmation has been sent to your email. We'll see you soon.</p>

  <div class="summary-card">
    <div class="header">
      <div class="title">Appointment Details</div>
    </div>
    <div class="body">
      <div class="row"><span class="label">Service</span><span class="value"><?= htmlspecialchars($b['service_name']) ?></span></div>
      <div class="row"><span class="label">Date</span><span class="value"><?= htmlspecialchars($b['date']) ?></span></div>
      <div class="row"><span class="label">Time</span><span class="value"><?= htmlspecialchars($b['time']) ?></span></div>
      <div class="row"><span class="label">Specialist</span><span class="value"><?= htmlspecialchars($b['therapist']) ?></span></div>
      <div class="row"><span class="label">Suite</span><span class="value"><?= htmlspecialchars($b['room']) ?></span></div>
    </div>
    <div class="total-row">
      <span>Total Due On-Site</span>
      <span class="amount">₱<?= $b['total'] ?></span>
    </div>
  </div>

  <div class="notice">
    <strong>Payment on-site.</strong> Please arrive 10 minutes early. Bring this reference number or check your email.
  </div>

  <a href="index.php" class="btn-home">Back to Home</a>
</div>

<?php include '../includes/footer.php'; ?>
