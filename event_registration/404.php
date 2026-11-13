<?php
// 404.php - Friendly "page not found" page.
// Apache shows it for missing pages and for blocked folders (see .htaccess).
require_once __DIR__ . '/includes/functions.php';

http_response_code(404);

$pageTitle = 'Page not found';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-5 text-center">
                <div class="display-4 text-muted">404</div>
                <h1 class="h3">Page not found</h1>
                <p class="text-muted">The page you are looking for does not exist, or you are not allowed to open it.</p>
                <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary">Go to Home Page</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>