</main>
<?php if (!empty($gated)): ?>
<div class="gate-bar">
  <p>See the whole mosaic</p>
  <a class="btn" href="/register.php">Sign up</a>
  <a class="btn light" href="/login.php">Log in</a>
</div>
<div class="modal" id="join" role="dialog" aria-modal="true" aria-labelledby="join-title">
  <a class="modal-bg" href="#" aria-label="Close"></a>
  <div class="modal-card">
    <a class="modal-close" href="#" aria-label="Close">&times;</a>
    <img src="/assets/logo.svg" alt="" width="58" height="48">
    <h2 id="join-title">Join mozaik to see more</h2>
    <p class="muted">Open pieces, save them to collections and add your own.</p>
    <a class="btn block" href="/register.php">Sign up</a>
    <a class="btn light block" href="/login.php">I already have an account</a>
  </div>
</div>
<?php endif ?>
</body>
</html>
