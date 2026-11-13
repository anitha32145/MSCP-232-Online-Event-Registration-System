<?php
// includes/footer.php
// ------------------------------------------------------------
// The bottom of every page: closes the page content, shows the footer bar
// and loads the Bootstrap script.
//
// A page can set $pageScript (for example 'validation.js') BEFORE including
// this file, to load its own JavaScript file from assets/js.
?>
</main>

<footer class="bg-dark text-light text-center py-3 mt-auto">
    <small>&copy; <?php echo date('Y'); ?> Online Event Registration System | MCA Project, IGNOU</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($pageScript)): ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo e($pageScript); ?>"></script>
<?php endif; ?>
</body>
</html>