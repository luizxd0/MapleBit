<?php
if (basename($_SERVER['PHP_SELF']) === 'footer.php') {
    http_response_code(403);
    exit('403 - Access Forbidden');
}
?>
            </section>
        </div>
    </main>
    <footer class="maple-footer">
        <div>
            <span class="maple-mark" aria-hidden="true">🍁</span>
            <strong><?php echo htmlspecialchars($servername, ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
        <p>Built on MapleBit for a private GMS v83 development world.</p>
        <small>MapleStory is a trademark of Nexon. This is an independent, non-commercial test project.</small>
    </footer>
</div>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
        crossorigin="anonymous"></script>
<script src="assets/js/login.js"></script>
<script>
    function roll(imgName, imgSource) {
        var image = document.getElementById(imgName);
        if (image) image.src = imgSource;
    }
    function goBack() {
        window.history.back();
    }
</script>
</body>
</html>
