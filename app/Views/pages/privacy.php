<?php require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container doc-page">
  <div class="page-head"><div><h1>Privacy Policy</h1>
    <p class="muted">How Mpin collects, stores and protects information. Last updated: <?= e(date('F j, Y')) ?>.</p></div></div>
  <div class="doc-body">
    <h2>1. What we collect</h2>
    <p><strong>Accounts.</strong> If you register, we store your username, e-mail address, hashed password,
       display name and optional bio. Passwords are stored only as secure hashes and can never be read by us.</p>
    <p><strong>Poll answers.</strong> The answers you submit to polls, and — only if you choose to share one —
       an <em>approximate</em> location.</p>
    <p><strong>Technical data.</strong> A salted, one-way hash of your IP address for spam protection and
       de-duplication. We cannot reverse this hash to recover your IP.</p>

    <h2>2. How locations are protected</h2>
    <ul class="doc-list">
      <li>Choosing a location is always optional and done by you — GPS is only read with your explicit permission, in your browser.</li>
      <li>Coordinates are snapped to a ~5&nbsp;km grid with a randomized offset <em>before</em> being stored; the exact point you picked is discarded.</li>
      <li>Map areas with fewer than a few participants are suppressed (k-anonymity) so individuals cannot be isolated.</li>
      <li>The API never returns exact coordinates or any personal identifier.</li>
    </ul>

    <h2>3. What we never do</h2>
    <ul class="doc-list">
      <li>We never sell or share your data with third parties.</li>
      <li>We never store raw IP addresses, precise GPS coordinates, or tracking cookies for advertising.</li>
      <li>We never require an account to answer public polls.</li>
    </ul>

    <h2>4. Your rights</h2>
    <p>You can delete <strong>all</strong> your responses and their locations at any time from
       <a href="<?= e(url('/settings')) ?>">Settings → Privacy</a>. You may also request account deletion by
       deleting your polls and contacting the administrator.</p>

    <h2>5. Cookies</h2>
    <p>Mpin uses one session cookie for login, one optional "remember me" cookie, and one random guest token
       used only to prevent duplicate answers. Theme preference is stored in your browser's local storage.</p>

    <h2>6. Map data</h2>
    <p>Map tiles are provided by OpenStreetMap and CARTO. When you search a place name, the search text is
       sent to the Nominatim geocoding service (OpenStreetMap Foundation).</p>
  </div>
</section>
<?php $extraJs = []; $wide = false; ?>
