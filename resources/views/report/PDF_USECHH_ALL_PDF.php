<?php
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$sections = is_array($combinedSections ?? null) ? $combinedSections : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Combined USECHH Reports</title>
</head>
<body>
<style>
@page{size:A4 portrait;margin:10mm}
html,body{margin:0;padding:0;background:#fff;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-size:11pt}
.pdf-page{display:block;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-size:11pt}
.combined-stack{display:block}
.combined-section{display:block;background:#fff;overflow:hidden;break-after:page;page-break-after:always}
.combined-section:last-child{break-after:auto;page-break-after:auto}
.combined-section-body{padding:0}
.combined-section-body .app-topbar,
.combined-section-body .app-sidebar,
.combined-section-body .app-shell > .app-sidebar,
.combined-section-body .report-actions,
.combined-section-body .screen-only,
.combined-section-body .actions,
.combined-section-body .actions-right,
.combined-section-body .signature-actions,
.combined-section-body .remarks-actions,
.combined-section-body .save-btn,
.combined-section-body .btn,
.combined-section-body .next,
.combined-section-body .record-tabs,
.combined-section-body .stepper,
.combined-section-body .subnav,
.combined-section-body .app-breadcrumbs,
.combined-section-body .top-actions{display:none !important}
.combined-section-body .app-shell,
.combined-section-body .app-main,
.combined-section-body .app-page,
.combined-section-body .app-card{display:block !important;padding:0 !important;border:0 !important;background:transparent !important;box-shadow:none !important}
.combined-section-body input,
.combined-section-body textarea,
.combined-section-body select{pointer-events:none}
.combined-section-body .content,
.combined-section-body .flow{padding:0 !important;margin:0 !important;min-height:auto !important;height:auto !important}
.page-break{page-break-before:always;break-before:page}
</style>
<div class="pdf-page">
    <div class="combined-stack">
        <?php foreach ($sections as $index => $section): ?>
            <section class="combined-section<?php echo $index > 0 ? ' page-break' : ''; ?> <?php echo $esc($section['page_class'] ?? ''); ?>">
                <div class="combined-section-body">
                    <?php echo $section['styles'] ?? ''; ?>
                    <?php echo $section['content_html'] ?? ''; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
