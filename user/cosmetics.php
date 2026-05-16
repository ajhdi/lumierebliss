<?php 
include '../includes/header.php'; 
include '../includes/db_connect.php'; // Assuming you have a DB connection for the dynamic extras

// Fetch cosmetics from database
$query = "SELECT * FROM cosmetics ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<style>
    .cosmetic-hero {
        background: linear-gradient(rgba(26,26,26,0.4), rgba(26,26,26,0.4)), url('../assets/cosmetics-bg.jpg');
        background-size: cover;
        background-position: center;
        height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
    }

    .addon-card {
        background: white;
        border: 1px solid var(--border);
        transition: all 0.4s ease;
        height: 100%;
    }

    .addon-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.05);
    }

    .img-wrapper {
        height: 300px;
        overflow: hidden;
        background: #f9f6f0;
    }

    .img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }

    .addon-card:hover img {
        transform: scale(1.05);
    }

    .onsite-label {
        font-size: 10px;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--gold);
        font-weight: 700;
    }
</style>

<div class="cosmetic-hero">
    <div data-aos="fade-up">
        <h6 class="concept-label text-white mb-3">The Collection</h6>
        <h1 class="display-3 brand-serif">Complimentary Add-Ons</h1>
    </div>
</div>

<section>
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="brand-serif">Onsite Essentials</h2>
            <div class="gold-divider"></div>
            <p class="brand-description mx-auto" style="max-width: 600px;">
                These elements are curated for every session. While not available for online purchase, our team prepares them fresh for your arrival at our studio.
            </p>
        </div>

        <div class="row g-4">
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <div class="col-lg-3 col-md-6" data-aos="fade-up">
                <div class="addon-card">
                    <div class="img-wrapper">
                        <img src="../assets/uploads/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>">
                    </div>
                    <div class="p-4 text-center">
                        <span class="onsite-label">Available Onsite</span>
                        <h4 class="brand-serif mt-2"><?php echo $row['name']; ?></h4>
                        <p class="small text-muted mb-0"><?php echo $row['description']; ?></p>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>