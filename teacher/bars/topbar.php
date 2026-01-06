<div class="top-bar">
    <div class="container-fluid d-flex justify-content-between align-items-center">

        <!-- Logo -->
        <div class="logo d-flex align-items-center gap-2">
            <img src="<?= $_ENV['PAGE_ICON'] ?>" height="30" width="30">
            <span class="fw-semibold"><?= $_ENV['PAGE_HEADER'] ?></span>
        </div>

        <!-- Logout Button (Always Visible) -->
        <button
            class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1"
            data-bs-toggle="modal"
            data-bs-target="#logoutModal"
            title="Logout">
                <i class="bi bi-box-arrow-right"></i>
                <span class="logout-text">Logout</span>
        </button>

    </div>
</div>

<div class="modal fade" id="logoutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    Confirm Logout
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to logout from your account?
                </p>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>

                <!-- REAL logout -->
                <a href="logout/" class="btn btn-danger">
                    Yes, Logout
                </a>
            </div>

        </div>
    </div>
</div>
