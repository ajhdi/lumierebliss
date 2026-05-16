<?php
include '../includes/header.php';
require_once '../config/db.php';


$stmt = $pdo->query("SELECT * FROM treatments WHERE status = 'available' AND type = 'individual' ORDER BY name ASC");
$individual_treatments = $stmt->fetchAll();

$pkg_sql = "SELECT t.*, 
            GROUP_CONCAT(t_sub.name SEPARATOR ' + ') as sub_names,
            (SELECT image FROM treatments t2 JOIN package_items pi ON t2.treatment_id = pi.treatment_id WHERE pi.package_id = t.treatment_id LIMIT 1) as img1,
            (SELECT image FROM treatments t3 JOIN package_items pi ON t3.treatment_id = pi.treatment_id WHERE pi.package_id = t.treatment_id LIMIT 1 OFFSET 1) as img2
            FROM treatments t 
            LEFT JOIN package_items pi ON t.treatment_id = pi.package_id
            LEFT JOIN treatments t_sub ON pi.treatment_id = t_sub.treatment_id
            WHERE t.status = 'available' AND t.type = 'package' 
            GROUP BY t.treatment_id
            ORDER BY t.name ASC";
$package_bundles = $pdo->query($pkg_sql)->fetchAll();
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

<style>
    :root {
        --pure-white: #ffffff;
        --studio-surface: #fdfbf7;
        --brand-gold: #c9a96e;
        --gold-light: #e8d5b0;
        --the-dark: #1a1a1a;
        --studio-mid: #2e2e2e;
        --muted-text: #8a8070;
        --lumiere-glow: linear-gradient(135deg, var(--the-dark), var(--studio-mid));
    }

    body {
        background-color: var(--studio-surface);
        font-family: 'DM Sans', sans-serif;
        font-size: 18px; 
        color: var(--the-dark);
        line-height: 1.6;
    }

    
    h1, h2, h3, h4, .serif-brand {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 600; 
        color: var(--the-dark);
        letter-spacing: -0.02em;
    }

    .hero-tagline {
        font-family: 'DM Sans', sans-serif;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 4px;
        color: var(--brand-gold);
        font-size: 0.9rem;
    }


    .treatment-card {
        border: none;
        background: var(--pure-white);
        border-radius: 0; 
        transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.4s ease;
    }

    .treatment-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(26, 26, 26, 0.08) !important;
    }

    .card-img-luxe {
        height: 280px;
        object-fit: cover;
        filter: grayscale(20%);
        transition: filter 0.4s ease;
    }

    .treatment-card:hover .card-img-luxe {
        filter: grayscale(0%);
    }

  
    .price-luxe {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        color: var(--the-dark);
        font-size: 1.2rem;
    }

    .duration-pill {
        font-size: 0.75rem;
        background-color: var(--studio-surface);
        color: var(--muted-text);
        padding: 4px 12px;
        border: 1px solid var(--gold-light);
    }

    .btn-luxe-dark {
        background: var(--lumiere-glow);
        color: var(--pure-white);
        border: none;
        border-radius: 0;
        padding: 12px 30px;
        font-weight: 500;
        transition: 0.3s;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1px;
    }

    .btn-luxe-dark:hover {
        background: var(--brand-gold);
        color: var(--pure-white);
    }

    .btn-luxe-outline {
        border: 1px solid var(--the-dark);
        color: var(--the-dark);
        border-radius: 0;
        text-transform: uppercase;
        font-size: 0.8rem;
        font-weight: 500;
        letter-spacing: 1px;
    }

    .bundle-wrapper {
        background-color: var(--pure-white);
        border: 1px solid var(--gold-light);
        margin-bottom: 80px;
    }

    .bundle-label {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        color: var(--brand-gold);
        font-size: 0.75rem;
        letter-spacing: 3px;
        text-transform: uppercase;
    }

    .treatment-card {
        border: none;
        border-radius: 20px;
        transition: all 0.3s ease;
        background: #fff;
    }
    .treatment-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.08) !important;
    }
    .category-pill {
        font-size: 0.7rem;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: #C5A059;
        font-weight: 700;
    }
    .price-tag {
        font-size: 1.25rem;
        font-weight: 800;
        color: #1a1a1a;
    }
    .duration-label {
        font-size: 0.85rem;
        color: #6c757d;
    }
    .btn-book {
        background: #1a1a1a;
        color: #fff;
        border-radius: 10px;
        padding: 8px 20px;
        font-size: 0.9rem;
        transition: 0.3s;
    }
    .btn-book:hover {
        background: #C5A059;
        color: #fff;
    }
</style>

<div class="container py-5">
    <div class="text-center mb-5 pt-4">
        <span class="hero-tagline">Lumiére Curations</span>
        <h1 class="display-4 mt-2">Signature Rituals</h1>
        <div class="mx-auto" style="width: 60px; height: 2px; background: var(--brand-gold); margin-top: 20px;"></div>
    </div>

    <div class="mb-5">
        <h2 class="mb-5">Individual <span style="font-weight: 300;">Treatments</span></h2>
        <div class="row g-4">
            <?php if (!empty($individual_treatments)): ?>
                <?php foreach ($individual_treatments as $t): ?>
                    <div class="col-md-4 col-lg-3"> 
                        <div class="card treatment-card h-100 shadow-sm">
                            <img src="../assets/img/treatments/<?= !empty($t['image']) ? $t['image'] : 'default.jpg' ?>" 
                                 class="card-img-top card-img-luxe">
                            
                            <div class="card-body d-flex flex-column p-4"> 
                                <h4 class="h5 mb-2"><?= htmlspecialchars($t['name']) ?></h4>
                                <div class="mb-4">
                                    <span class="duration-pill">
                                        <i class="bi bi-clock me-1"></i> <?= htmlspecialchars($t['duration_minutes']) ?> MINS
                                    </span>
                                </div>

                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price-luxe">₱<?= number_format($t['price'], 2) ?></span>
                                        <button type="button" class="btn btn-luxe-outline btn-sm px-3" 
                                                onclick='showDetails(<?= json_encode($t) ?>)'>
                                            Discover
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="py-5" style="background-color: var(--pure-white); border-top: 1px solid var(--gold-light);">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5">Exclusive <span style="font-weight: 300;">Collections</span></h2>
        </div>

        <?php if (!empty($package_bundles)): ?>
            <?php $counter = 0; foreach ($package_bundles as $t): 
                $is_reversed = ($counter % 2 !== 0) ? 'flex-row-reverse' : '';
                $text_align = ($counter % 2 !== 0) ? 'text-md-end' : 'text-md-start';
            ?>
                <div class="bundle-wrapper shadow-sm overflow-hidden">
                    <div class="row g-0 <?= $is_reversed ?>">
                        <div class="col-md-6 d-flex" style="min-height: 450px;">
                            <div class="w-50 h-100 border-end border-white" style="background: url('../assets/img/treatments/<?= $t['img1'] ?: 'default.jpg' ?>') center/cover no-repeat;"></div>
                            <div class="w-50 h-100" style="background: url('../assets/img/treatments/<?= $t['img2'] ?: 'default.jpg' ?>') center/cover no-repeat;"></div>
                        </div>

                        <div class="col-md-6 p-5 d-flex flex-column justify-content-center <?= $text_align ?>">
                            <span class="bundle-label mb-2">Signature Package</span>
                            <h2 class="display-6 mb-3"><?= htmlspecialchars($t['name']) ?></h2>
                            
                            <h6 class="mb-4" style="color: var(--brand-gold); letter-spacing: 1px;">
                                <?= htmlspecialchars($t['sub_names']) ?>
                            </h6>
                            
                            <p class="text-muted mb-4" style="font-size: 1rem;">
                                <?= htmlspecialchars($t['description']) ?>
                            </p>

                            <div class="d-flex flex-wrap gap-3 mb-5 <?= ($counter % 2 !== 0) ? 'justify-content-md-end' : '' ?>">
                                <span class="duration-pill"><i class="bi bi-clock me-1"></i> <?= htmlspecialchars($t['duration_minutes']) ?> MINS</span>
                                <span class="price-luxe" style="color: var(--brand-gold);">₱<?= number_format($t['price'], 2) ?></span>
                            </div>

                            <div class="<?= ($counter % 2 !== 0) ? 'text-md-end' : '' ?>">
                                <a href="appointment.php?tid=<?= $t['treatment_id'] ?>" class="btn btn-luxe-dark px-5">
                                    Reserve Bundle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php $counter++; endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--studio-surface);">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5">
                <div class="row">
                    <div class="col-md-5">
                        <img id="detailImage" src="" class="w-100 shadow-sm mb-4 mb-md-0" style="height: 350px; object-fit: cover;">
                    </div>
                    <div class="col-md-7">
                        <span class="hero-tagline" style="font-size: 0.7rem;">Treatment Insight</span>
                        <h2 id="detailName" class="display-6 mt-1 mb-3"></h2>
                        
                        <div class="mb-4">
                             <span id="detailDuration" class="duration-pill"></span>
                        </div>
                        
                        <p id="detailDescription" class="text-muted mb-5" style="font-size: 1.1rem;"></p>
                        
                        <div class="d-flex justify-content-between align-items-end border-top pt-4">
                            <div>
                                <span class="hero-tagline" style="font-size: 0.65rem;">Investment</span>
                                <h3 id="detailPrice" class="price-luxe mb-0"></h3>
                            </div>
                            <a href="" id="detailBookBtn" class="btn btn-luxe-dark">
                                Secure Session
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function showDetails(data) {
   
    document.getElementById('detailName').innerText = data.name;
    document.getElementById('detailDescription').innerText = data.description; // Description shows here
    document.getElementById('detailDuration').innerText = data.duration_minutes + " mins";
    document.getElementById('detailPrice').innerText = "₱" + parseFloat(data.price).toLocaleString(undefined, {minimumFractionDigits: 2});
    
   
    const imagePath = "../assets/img/treatments/" + (data.image ? data.image : 'default.jpg');
    document.getElementById('detailImage').src = imagePath;
    
    
    document.getElementById('detailBookBtn').href = "appointment.php?tid=" + data.treatment_id;
    
    
    var myModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    myModal.show();
}
</script>