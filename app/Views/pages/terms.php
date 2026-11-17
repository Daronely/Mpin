<?php require_once MPIN_APP . '/Views/partials/icons.php'; ?>
<section class="container doc-page">
  <div class="page-head"><div><h1>Terms of Service</h1>
    <p class="muted">The rules of using Mpin. Last updated: <?= e(date('F j, Y')) ?>.</p></div></div>
  <div class="doc-body">
    <h2>1. Acceptance</h2>
    <p>By accessing Mpin you agree to these terms. If you do not agree, please do not use the service.</p>

    <h2>2. Your content</h2>
    <p>You keep ownership of the polls and answers you create. By publishing a public poll you grant everyone
       on Mpin the right to view and answer it, and to see the aggregated, privacy-protected results.
       Aggregated, anonymized statistics of public polls may be exposed through the public API.</p>

    <h2>3. Acceptable use</h2>
    <ul class="doc-list">
      <li>No spam, bots, or automated mass participation.</li>
      <li>No illegal, hateful, harassing, or deceptive content.</li>
      <li>No attempts to deanonymize participants or circumvent the privacy grid.</li>
      <li>No scraping that degrades service for others — use the public API instead.</li>
    </ul>
    <p>Moderators may close or remove content and suspend accounts that violate these rules.</p>

    <h2>4. No warranty</h2>
    <p>Mpin is provided "as is". Poll results are self-reported samples and are not statistically
       representative of any population. We make no guarantees about availability or fitness for a
       particular purpose.</p>

    <h2>5. Liability</h2>
    <p>To the maximum extent permitted by law, Mpin and its operators are not liable for any damages
       arising from the use of the service or reliance on its data.</p>

    <h2>6. Reporting</h2>
    <p>If you find content that breaks these terms, use the <em>Report</em> button on the poll page.
       Reports are reviewed by moderators.</p>
  </div>
</section>
<?php $extraJs = []; $wide = false; ?>
