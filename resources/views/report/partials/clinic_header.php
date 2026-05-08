<?php
$clinicHeaderImage = trim((string) ($clinicHeaderUrl ?? $clinicLogoUrl ?? ''));
$clinicHeaderName = trim((string) ($clinicName ?? 'Medis SHAMS'));
?>
<div class="clinic-report-header">
    <?php if ($clinicHeaderImage !== ''): ?>
        <img src="<?php echo htmlspecialchars($clinicHeaderImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($clinicHeaderName, ENT_QUOTES, 'UTF-8'); ?> header">
    <?php else: ?>
        <div class="clinic-report-header__fallback"><?php echo htmlspecialchars($clinicHeaderName, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
</div>
