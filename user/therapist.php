<?php
include '../includes/header.php';
require_once '../config/db.php';


$stmt = $pdo->query("SELECT * FROM therapists WHERE status = 'active' ORDER BY first_name ASC");
$therapists = $stmt->fetchAll();


$sched_stmt = $pdo->query("SELECT therapist_id, time_start FROM therapist_schedule ORDER BY time_start ASC");
$all_schedules = $sched_stmt->fetchAll(PDO::FETCH_GROUP);
?>

<style>
  
  
    @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap');

    :root {
        --pure-white: #ffffff;
        --studio-surface: #fdfbf7;
        --brand-gold: #c9a96e;
        --gold-light: #e8d5b0;
        --the-dark: #1a1a1a;
        --studio-mid: #2e2e2e;
        --muted-text: #8a8070;
    }

    body {
        background-color: var(--studio-surface);
        font-family: 'DM Sans', sans-serif;
        font-size: 18px; 
        color: var(--the-dark);
    }

    
    h2, h4, .serif-title {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 600; 
        color: var(--the-dark);
    }

   
    .therapist-card {
        background-color: var(--pure-white);
        border: none;
        border-radius: 0; 
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
    }

    .therapist-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(26, 26, 26, 0.05);
    }

    
    .portrait-wrapper {
        position: relative;
        width: 100%;
        padding-top: 133.33%; 
        overflow: hidden;
    }

    .portrait-img {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: cover;
        filter: grayscale(20%);
        transition: filter 0.4s ease;
    }

    .therapist-card:hover .portrait-img {
        filter: grayscale(0%);
    }

  
    .card-body-compact {
        padding: 1.5rem 1rem !important;
        text-align: center;
    }

    .specialty-label {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: 0.7rem;
        letter-spacing: 2px;
        color: var(--brand-gold);
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }


    .btn-luxe {
        background-color: var(--the-dark);
        color: var(--pure-white);
        font-family: 'DM Sans', sans-serif;
        font-weight: 500;
        font-size: 0.9rem;
        border-radius: 0; 
        padding: 10px 25px;
        border: 1px solid var(--the-dark);
        transition: all 0.3s ease;
    }

    .btn-luxe:hover {
        background-color: transparent;
        color: var(--the-dark);
    }

  
    .modal-content {
        border-radius: 0;
        background-color: var(--studio-surface);
    }

    :root { --gold: #C5A059; --dark: #1a1a1a; }
    body { background-color: #fdfbf7; }

    .therapist-card {
        border: none;
        
        background: #ffffff;
        transition: all 0.4s ease;
        border: 1px solid rgba(0,0,0,0.02);
    }

    .therapist-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.05) !important;
    }

    .avatar-circle {
        width: 70px;
        height: 70px;
        background: #f8f9fa;
        color: var(--gold);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 auto 15px auto;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }

    .btn-book-specialist {
        background: var(--dark);
        color: #fff;
        border-radius: 50px;
        padding: 10px 20px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: 0.3s;
        text-decoration: none;
        display: block;
        width: 100%;
    }

    .btn-book-specialist:hover {
        background: var(--gold);
        color: #fff;
    }
  
    .portrait-container {
        position: relative;
        width: 100%;
        padding-top: 133.33%;
        overflow: hidden;
    }

    .portrait-img, .portrait-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover; 
    }

    .therapist-card {
        transition: transform 0.3s ease;
    }

    .therapist-card:hover {
        transform: translateY(-10px);
    }
    
    .portrait-container {
        position: relative;
        width: 100%;
        padding-top: 133.33%; 
        overflow: hidden;
    }

    .portrait-img, .portrait-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .therapist-card .card-body {
        padding: 1rem !important; 
    }

    .therapist-card h5 {
        font-size: 1.1rem; 
        margin-bottom: 2px !important;
    }

    .specialty-text {
        font-size: 0.75rem; 
        color: #C5A059;
        letter-spacing: 1px;
        margin-bottom: 12px !important;
    }

    .btn-view-profile {
        font-size: 0.85rem;
        padding: 6px 0;
    }

</style>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="display-4">The <span style="color: var(--brand-gold);">Artisans</span> of Bliss</h2>
        <p style="color: var(--muted-text);">Professional hands dedicated to your restoration.</p>
    </div>
    
    <div class="row gx-5 gy-5"> 
        <?php foreach ($therapists as $th): ?>
            <div class="col-lg-4 col-md-6">
                <div class="therapist-card shadow-sm">
                    
                    <div class="portrait-wrapper">
                        <?php if(!empty($th['profile_picture'])): ?>
                            <img src="../assets/img/therapists/<?= $th['profile_picture'] ?>" class="portrait-img">
                        <?php else: ?>
                            <div class="portrait-img d-flex align-items-center justify-content-center bg-light">
                                <span style="color: var(--muted-text); font-family: 'Cormorant Garamond';">Lumiére</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body-compact">
                        <div class="specialty-label"><?= htmlspecialchars($th['specialty']) ?></div>
                        <h4 class="mb-3"><?= htmlspecialchars($th['first_name'] . ' ' . $th['last_name']) ?></h4>
                        
                        <div class="d-grid">
                            <button class="btn btn-luxe" 
                                    onclick='showTherapistDetails(<?= json_encode($th) ?>, <?= json_encode($all_schedules[$th['therapist_id']] ?? []) ?>)'>
                                VIEW PROFILE
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</div>
</div>
</div>
<div class="modal fade" id="therapistDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-5">
                <div class="row mb-4">
                    <div class="col-4">
                        <img id="modalImg" src="" class="img-fluid shadow-sm" style="border: 1px solid var(--gold-light);">
                    </div>
                    <div class="col-8">
                        <h2 id="modalName" class="mb-1"></h2>
                        <div id="modalSpecialty" class="specialty-label" style="font-size: 0.8rem;"></div>
                        <p id="modalGender" class="small text-muted mb-0"></p>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="fw-bold text-uppercase small mb-2" style="letter-spacing: 1px;">Professional Journey</h6>
                    <p id="modalExperience" class="text-muted" style="line-height: 1.6;"></p>
                </div>

                <div>
                    <h6 class="fw-bold text-uppercase small mb-3" style="letter-spacing: 1px;">Studio Availability</h6>
                    <div id="modalSchedule" class="d-flex flex-wrap gap-2">
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<script>
function showTherapistDetails(data, schedules) {
    document.getElementById('modalName').innerText = data.first_name + " " + (data.middle_name ? data.middle_name + " " : "") + data.last_name;
    document.getElementById('modalSpecialty').innerText = data.specialty;
    document.getElementById('modalGender').innerText = "Practitioner Gender: " + data.gender;
    document.getElementById('modalExperience').innerText = data.work_experience || "Specialist profile is currently being updated.";
    
    document.getElementById('modalImg').src = data.profile_picture ? "../assets/img/therapists/" + data.profile_picture : "../assets/img/therapists/default_therapist.png";

    const container = document.getElementById('modalSchedule');
    container.innerHTML = '';

    if (schedules && schedules.length > 0) {
        schedules.forEach(slot => {
            const span = document.createElement('span');
           
            span.style = "background: var(--the-dark); color: var(--gold-light); padding: 8px 15px; font-size: 0.8rem; font-weight: 500;";
            
            const time = new Date('1970-01-01T' + slot.time_start + 'Z').toLocaleTimeString('en-US', {
                timeZone: 'UTC', hour: 'numeric', minute: 'numeric', hour12: true
            });
            
            span.innerText = time;
            container.appendChild(span);
        });
    } else {
        container.innerHTML = '<span class="text-muted small">By appointment only.</span>';
    }

    new bootstrap.Modal(document.getElementById('therapistDetailModal')).show();
}
</script>