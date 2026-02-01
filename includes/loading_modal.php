<?php
/**
 * Global loading modal - included on all platforms (via navbar or directly).
 * Show with: showLoading() or showLoading('Saving...')
 * Hide with: hideLoading()
 */
?>
<div id="global-loading-modal" class="global-loading-modal" aria-hidden="true" aria-label="Loading">
  <div class="global-loading-backdrop"></div>
  <div class="global-loading-content">
    <div class="global-loading-spinner"></div>
    <p id="global-loading-message" class="global-loading-message">Loading…</p>
  </div>
</div>
