<?php
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$sections = is_array($combinedSections ?? null) ? $combinedSections : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medical Surveillance Report</title>
</head>
<body>
<style>
@page{size:A4 portrait;margin:10mm}
html,body{margin:0;padding:0;background:#fff;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-size:11pt}
.pdf-page{display:block;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-size:11pt}
.combined-stack{display:block}
.combined-section{background:#fff;overflow:hidden;break-after:page;page-break-after:always}
.combined-section:last-child{break-after:auto;page-break-after:auto}
.combined-section-head{display:none}
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
@media print{
  body{background:#fff}
  .app-topbar,.app-sidebar{display:none !important}
  .app-shell,.app-main,.app-page,.app-card{display:block !important;padding:0 !important;border:0 !important;background:#fff !important;box-shadow:none !important}
  .combined-section{border:0;border-radius:0;break-inside:avoid}
}
</style>
<div class="pdf-page">
    <div class="combined-stack">
        <?php foreach ($sections as $index => $section): ?>
            <section class="combined-section<?php echo $index > 0 ? ' page-break' : ''; ?> <?php echo $esc($section['page_class'] ?? ''); ?>" data-selector="<?php echo $esc($section['selector'] ?? ''); ?>" data-html="<?php echo $esc(base64_encode((string) ($section['html'] ?? ''))); ?>">
                <div class="combined-section-body"></div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
<script>
(function(){
    const sections = Array.prototype.slice.call(document.querySelectorAll('.combined-section'));

    sections.forEach(function(section){
        const selector = section.getAttribute('data-selector') || '';
        const html = atob(section.getAttribute('data-html') || '');
        const bodyHost = section.querySelector('.combined-section-body');
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        Array.prototype.slice.call(doc.querySelectorAll('style')).forEach(function(styleTag){
            const clonedStyle = document.createElement('style');
            clonedStyle.textContent = styleTag.textContent || '';
            bodyHost.appendChild(clonedStyle);
        });

        let target = selector ? doc.querySelector(selector) : null;
        if (!target && doc.body) {
            target = doc.body;
        }

        bodyHost.insertAdjacentHTML('beforeend', target ? target.outerHTML : '<div>No section content available.</div>');
    });

    window.setTimeout(function(){
        window.print();
    }, 150);
}());
</script>
</body>
</html>
