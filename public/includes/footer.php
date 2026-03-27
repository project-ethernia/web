<footer class="footer">
    <div class="footer-content">
        <h2 class="footer-title">ETHERNIA</h2>
        <p class="disclaimer">A szerver nem áll kapcsolatban a Mojang AB-vel vagy a Microsofttal.</p>
        <div class="copyright">© <?= date('Y') ?> Ethernia Network. Minden jog fenntartva.</div>
    </div>
</footer>
<?php if (!empty($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
        <script src="<?= $js ?>?v=<?= time(); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>