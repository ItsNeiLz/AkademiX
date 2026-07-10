<?php if (is_logged_in()): ?>
        </div><!-- .content-wrapper -->
    </main><!-- .main-content -->
    <?php endif; ?>

    <!-- Main JS -->
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
    <?php if (is_logged_in()): ?>
    <script src="<?= base_url('assets/js/notifications.js') ?>"></script>
    <?php endif; ?>
    <?php if (isset($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
        <script src="<?= base_url($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
