<?php
// includes/footer.php
?>
    <footer class="sc-footer">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-4 col-md-6">
                    <h4 class="text-white mb-3" style="font-family: 'Outfit', sans-serif; font-weight: 700;">
                        ⛺ SIMPEL-<span class="text-accent-theme">CAMP</span>
                    </h4>
                    <p>Platform sewa alat camping dan pendakian terlengkap. Kami menyediakan peralatan berkualitas tinggi untuk memastikan petualangan alam Anda aman dan nyaman.</p>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5>Tautan</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?= BASE_URL ?>/">Beranda</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/login.php">Katalog</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>/#cara-sewa">Cara Sewa</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5>Hubungi Kami</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-whatsapp text-success"></i> +62 812-3456-7890
                        </li>
                        <li class="mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-envelope text-accent-theme"></i> info@simpelcamp.com
                        </li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5>Lokasi</h5>
                    <p><i class="bi bi-geo-alt-fill text-danger me-1"></i> Jl. Pegunungan No. 123,<br>Kota Petualang, Indonesia</p>
                    <div class="d-flex gap-3 mt-2">
                        <a href="#" class="fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="fs-5"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="fs-5"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>
            </div>
            <div class="row mt-4 pt-3 border-top border-secondary">
                <div class="col text-center">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="<?= ASSETS_URL ?>/js/app.js"></script>

    <?php if(isset($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>
</body>
</html>
