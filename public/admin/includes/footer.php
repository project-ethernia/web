</main>
</div>
<script src="/admin/assets/js/globals.js?v=<?= time(); ?>"></script>
<?php if (!empty($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
        <script src="<?= $js ?>?v=<?= time(); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>