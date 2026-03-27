</div>
    </main>
</div>
<script src="/admin/assets/js/sidebar.js?v=<?= time(); ?>"></script>
<?php if (isset($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
        <script src="<?= $js ?>?v=<?= time(); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>