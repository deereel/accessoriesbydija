<?php ?>
    </main>
  </div>
</div>
</body>
</html>

<script>
// Admin scroll-to-top handler
(function(){
  const btn = document.getElementById('adminScrollTopBtn');
  if (!btn) return;
  window.addEventListener('scroll', function() {
    if (window.scrollY > 300) btn.classList.add('show'); else btn.classList.remove('show');
  });
  btn.addEventListener('click', function() { window.scrollTo({ top: 0, behavior: 'smooth' }); });
})();
</script>
