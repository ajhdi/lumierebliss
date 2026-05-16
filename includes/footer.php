<style>
    .main-footer {
        background-color: var(--spa-dark) !important;
        border-top: 1px solid #2e2e2e;
        padding-top: 80px;
        padding-bottom: 40px;
        color: #ffffff;
    }

    /* Brand Typography */
    .footer-brand {
        font-family: 'Cormorant Garamond', serif;
        letter-spacing: 4px;
        color: var(--spa-gold) !important;
        font-weight: 400;
        text-transform: uppercase;
    }

    .footer-tagline {
        font-family: 'Cormorant Garamond', serif;
        font-style: italic;
        font-size: 1.1rem;
        color: var(--spa-gold-light);
        margin-bottom: 20px;
    }

    .footer-heading {
        font-family: 'DM Sans', sans-serif;
        font-size: 11px;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #ffffff;
        margin-bottom: 25px;
        font-weight: 700;
    }

    /* Interactive Elements */
    .footer-link {
        font-size: 13px;
        color: #888888 !important;
        text-decoration: none;
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: block;
        margin-bottom: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .footer-link:hover {
        color: var(--spa-gold) !important;
        transform: translateX(5px);
    }

    .footer-info-text {
        font-size: 13px;
        color: #888888;
        line-height: 1.8;
        margin-bottom: 0;
    }

    .social-icon {
        color: #ffffff;
        font-size: 18px;
        transition: 0.3s;
        margin-right: 20px;
        text-decoration: none;
    }

    .social-icon:hover {
        color: var(--spa-gold);
        transform: translateY(-3px);
    }

    .footer-bottom {
        border-top: 1px solid #2e2e2e;
        padding-top: 40px;
        margin-top: 50px;
    }

    .footer-bottom-text {
        font-size: 10px;
        letter-spacing: 2px;
        color: #555555;
        text-transform: uppercase;
    }

    /* Onsite Availability Styling */
    .onsite-badge {
        font-family: 'DM Sans', sans-serif;
        font-size: 11px;
        letter-spacing: 1px;
        color: var(--spa-gold);
        text-transform: uppercase;
        border: 1px solid rgba(201, 169, 110, 0.3);
        padding: 8px 15px;
        display: inline-block;
        margin-top: 10px;
    }
</style>

<footer class="main-footer mt-auto">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4 col-md-12">
                <h4 class="footer-brand mb-1">LUMIÉRE & BLISS</h4>
                <p class="footer-tagline">Where Healing Light Meets Therapeutic Bliss</p>
                <p class="footer-info-text pe-lg-4">
                    Inspired by the profound stillness of luxury wellness sanctuaries, Lumiére & Bliss is a temple of therapeutic excellence. 
                    We specialize in the art of the massage—where expert touch meets luminous serenity to restore your spirit.
                </p>
                <div class="d-flex mt-4">
                    <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-envelope-fill"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-4">
                <h6 class="footer-heading">Navigation</h6>
                <ul class="list-unstyled">
                    <li><a href="<?php echo isset($is_guest) ? 'index.php' : 'home.php'; ?>" class="footer-link">Home</a></li>
                    
                    <li><a href="<?php echo isset($is_guest) ? 'signin.php' : 'about.php'; ?>" class="footer-link">About</a></li>
                    <li><a href="<?php echo isset($is_guest) ? 'signin.php' : 'cosmetics.php'; ?>" class="footer-link">Cosmetics</a></li>
                    <li><a href="<?php echo isset($is_guest) ? 'signin.php' : 'treatment.php'; ?>" class="footer-link">Treatments</a></li>
                    <li><a href="<?php echo isset($is_guest) ? 'signin.php' : 'therapist.php'; ?>" class="footer-link">Therapists</a></li>
                    <li><a href="<?php echo isset($is_guest) ? 'signin.php' : 'room.php'; ?>" class="footer-link">Rooms</a></li>
                    <li><a href="<?php echo isset($is_guest) ? 'signin.php' : 'record.php'; ?>" class="footer-link">Record</a></li>
                    <li><a href="<?php echo isset($is_guest) ? 'signin.php' : 'promotion.php'; ?>" class="footer-link">Promotions</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-4">
                <h6 class="footer-heading">Contact Us</h6>
                <div class="footer-info-text">
                    <p class="mb-3">
                        <strong class="text-white small d-block mb-1">ADDRESS</strong>
                        Silang, Cavite 4118<br>
                        Philippines
                    </p>
                    <p class="mb-3">
                        <strong class="text-white small d-block mb-1">PHONE</strong>
                        097662982688
                    </p>
                    <p>
                        <strong class="text-white small d-block mb-1">EMAIL</strong>
                        lumiereandbliss@gmail.com
                    </p>
                </div>
            </div>

            <div class="col-lg-3 col-md-4">
                <h6 class="footer-heading">Member Circle</h6>
                <p class="footer-info-text mb-3">Sign up to receive exclusive monthly rituals and wellness updates.</p>
                <div class="onsite-badge">
                    Available Onsite
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="footer-bottom-text mb-0">
                        &copy; 2026 Lumiére and Bliss Wellness Studio. All Rights Reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <span class="footer-bottom-text">Beauty • Radiance • Luxury • Peace</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>