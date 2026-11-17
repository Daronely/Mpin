<?php require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container doc-page">
  <div class="page-head"><div>
    <span class="hero-badge"><?= mpin_icon('sparkles', 15) ?> About</span>
    <h1><?= mpin_logo(40, 'ab') ?> What is Mpin?</h1>
  </div></div>

  <div class="doc-body">
    <p class="lead"><strong>Mpin is an interactive community mapping and polling platform.</strong>
       Create a poll, share the link, and watch answers arrive on a live world map —
       every participant can drop an approximate pin and become part of the picture.</p>

    <h2><?= mpin_icon('map', 18) ?> How it works</h2>
    <ol class="how-steps">
      <li><strong>Create</strong> — Ask anything in seconds: yes/no, choices, ratings, numbers or open text. Pick a category and publish.</li>
      <li><strong>Share</strong> — Every poll gets a clean link like <code>mpin.metora.ir/poll/cs2-players</code>. Public polls are listed in Explore; private ones stay link-only.</li>
      <li><strong>Answer</strong> — Participants reply to your questions, then (optionally) drop a pin on the map showing roughly where they are.</li>
      <li><strong>Watch the world appear</strong> — Answers turn into clusters, heatmaps, charts and country rankings that update live.</li>
    </ol>

    <h2><?= mpin_icon('shield', 18) ?> Privacy first</h2>
    <p>Locations on Mpin are never exact. When someone drops a pin, the server immediately snaps it to a
       roughly 5&nbsp;km privacy grid with a deterministic offset, and map areas with fewer than a handful of
       participants stay hidden. People can answer anonymously in public polls, and any user can delete all
       of their responses with one click.</p>

    <h2><?= mpin_icon('cpu', 18) ?> Built to run anywhere</h2>
    <p>Mpin is deliberately boring technology: plain PHP 8 and MySQL with Leaflet and Chart.js on the front end.
       No build step, no Node.js, no WebSockets — it runs on any shared cPanel host, and it is fully open source.</p>

    <div class="cta-band slim">
      <div class="cta-band-inner">
        <h3>People Answer. The World Appears.</h3>
        <div class="hero-actions center">
          <a class="btn btn-primary btn-lg" href="<?= e(url('/create')) ?>">Create your first poll</a>
          <a class="btn btn-ghost btn-lg" href="<?= e(url('/explore')) ?>">Explore</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php $extraJs = []; $wide = false; ?>
