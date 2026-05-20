<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';
$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
medis_render_navigation_start([
    'clinicName' => $clinicName ?? 'Medis SHAMS',
    'clinicLogoUrl' => $clinicLogoUrl ?? null,
    'username' => $username ?? 'User',
    'active' => 'dashboard',
    'pageSubtitle' => 'Clinic overview, recent activity, and records that still need attention',
]);
$stats = $dashboardStats ?? [];
$actionItems = $dashboardActionItems ?? [];
$recentCompanies = $recentCompanies ?? collect();
$recentPatients = $recentPatients ?? collect();
$recentSurveillance = $recentSurveillance ?? collect();
$recentAudiometry = $recentAudiometry ?? collect();
$incompleteRecords = $dashboardIncompleteRecords ?? collect();
$displayStats = [
    'company_total' => 24,
    'patient_total' => 186,
    'surveillance_total' => 92,
    'audiometry_total' => 68,
    'pending_total' => 17,
];
$displayActionItems = [
    ['label' => 'Pending Declarations', 'count' => 9, 'note' => 'Patients or doctors still need to complete signatures.'],
    ['label' => 'Incomplete Examinations', 'count' => 5, 'note' => 'Surveillance forms started but not fully completed.'],
    ['label' => 'Completed Surveillance', 'count' => 42, 'note' => 'Records ready for review and follow-up reporting.'],
    ['label' => 'Audiometry Records', 'count' => 31, 'note' => 'Audiometry activity stored under this clinic.'],
];
?>
<style>
.app-page,.app-card{overflow:auto}
.dashboard-grid{display:grid;gap:20px;min-height:auto;padding-bottom:24px}
.hero{display:grid;gap:8px}
.hero h2{margin:0;font-size:2rem}
.hero p{margin:0;color:#6b7280;max-width:760px;line-height:1.7}
.stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}
.stat{border:1px solid #e5e7eb;border-radius:18px;padding:16px;background:#fff}
.stat span{display:block;color:#6b7280;font-size:.85rem}
.stat strong{display:block;margin-top:10px;font-size:1.9rem;line-height:1}
.stat small{display:block;margin-top:8px;color:#64748b;font-size:.8rem}
.panel{border:1px solid #e5e7eb;border-radius:20px;padding:18px;background:#fff}
.panel h3{margin:0;font-size:1.05rem}
.panel-copy{margin:6px 0 0;color:#6b7280;font-size:.92rem}
.overview-grid{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(0,1fr);gap:16px}
.chart-card{border:1px solid #e5e7eb;border-radius:20px;padding:18px;background:#fff}
.chart-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
.chart-head h3{margin:0}
.chart-head p{margin:4px 0 0;color:#6b7280;font-size:.86rem}
.chart-controls{display:flex;gap:10px;flex-wrap:wrap}
.chart-select{border:1px solid #d1d5db;border-radius:10px;padding:9px 12px;background:#fff;color:#334155;font:inherit;font-size:.86rem}
.chart-meta{display:flex;gap:18px;flex-wrap:wrap}
.chart-meta-item{display:grid;gap:2px}
.chart-meta-label{display:inline-flex;align-items:center;gap:6px;color:#6b7280;font-size:.76rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.chart-dot{width:8px;height:8px;border-radius:999px;display:inline-block}
.chart-dot.fit{background:#3b82f6}
.chart-dot.not-fit{background:#ef4444}
.chart-meta-value{font-size:1rem;font-weight:700;color:#0f172a}
.line-chart{margin-top:18px;height:300px;border-radius:18px;background:#fff;position:relative;overflow:hidden;border:1px solid #edf0f2}
.line-chart svg{width:100%;height:100%;display:block}
.action-grid{display:grid;grid-template-columns:1fr;gap:10px}
.action-card{border:1px solid #e5e7eb;border-radius:18px;padding:16px;background:#fcfdfd;display:grid;gap:8px;min-height:0}
.action-card.critical{background:linear-gradient(180deg,#fff7f7,#ffe8e8);border-color:#fecaca}
.action-card.critical .count{color:#b91c1c}
.action-card.warning{background:linear-gradient(180deg,#fffaf0,#ffefcc);border-color:#fed7aa}
.action-card.warning .count{color:#b45309}
.action-card.success{background:linear-gradient(180deg,#f4fff7,#def7e6);border-color:#bbf7d0}
.action-card.success .count{color:#166534}
.action-card.info{background:linear-gradient(180deg,#f5faff,#e8f3ff);border-color:#bfdbfe}
.action-card.info .count{color:#1d4ed8}
.action-card strong{font-size:1rem;color:#0f172a}
.action-card .count{font-size:1.9rem;font-weight:700;color:#14321f;line-height:1}
.action-card p{margin:0;color:#64748b;font-size:.8rem;line-height:1.5}
.activity-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.activity-list{display:grid;gap:12px;margin-top:14px}
.activity-item{display:flex;justify-content:space-between;gap:12px;padding-bottom:12px;border-bottom:1px solid #edf0f2}
.activity-item:last-child{border-bottom:none;padding-bottom:0}
.activity-item strong{display:block;color:#0f172a}
.activity-item span{display:block;color:#64748b;font-size:.84rem;margin-top:3px}
.tag{padding:4px 9px;border-radius:999px;font-size:.75rem;font-weight:600;white-space:nowrap}
.ok{background:#dcfce7;color:#166534}
.warn{background:#fef3c7;color:#92400e}
.bad{background:#fee2e2;color:#b91c1c}
.info{background:#dbeafe;color:#1d4ed8}
.table-wrap{border:1px solid #e5e7eb;border-radius:20px;background:#fff;overflow:auto}
.table-head{display:flex;justify-content:space-between;align-items:flex-start;padding:18px;border-bottom:1px solid #edf0f2;gap:12px;flex-wrap:wrap}
.table-head h3{margin:0}
.table-head p{margin:6px 0 0;color:#6b7280;font-size:.9rem}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:14px 18px;text-align:left;border-bottom:1px solid #edf0f2;font-size:.92rem;vertical-align:top}
.table th{color:#6b7280;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em}
.table tbody tr:hover{background:#fafcff}
.link-btn{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:10px;padding:8px 12px;background:#fff;color:#374151;font-size:.85rem}
.link-btn:hover{border-color:#389B5B;color:#166534;background:#f0fdf4}
.step-chip{display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;font-size:.78rem;font-weight:700}
.step-chip.declaration{background:#fee2e2;color:#b91c1c}
.step-chip.examination{background:#fef3c7;color:#92400e}
.step-chip.recommendation{background:#dbeafe;color:#1d4ed8}
.step-chip.review{background:#dcfce7;color:#166534}
.empty{padding:18px;color:#6b7280;font-size:.92rem}
@media (max-width:1200px){.stats{grid-template-columns:repeat(3,minmax(0,1fr))}.overview-grid{grid-template-columns:1fr}.activity-grid{grid-template-columns:1fr}}
@media (max-width:760px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:560px){.stats{grid-template-columns:1fr}}
</style>
<div class="dashboard-grid">
    <section class="hero">
        <div>
            <h2>Clinic Dashboard</h2>
        </div>
    </section>

    <section class="stats">
        <article class="stat"><span>Total Companies</span><strong><?php echo number_format((int) ($displayStats['company_total'] ?? 0)); ?></strong></article>
        <article class="stat"><span>Total Patients</span><strong><?php echo number_format((int) ($displayStats['patient_total'] ?? 0)); ?></strong></article>
        <article class="stat"><span>Surveillance Records</span><strong><?php echo number_format((int) ($displayStats['surveillance_total'] ?? 0)); ?></strong></article>
        <article class="stat"><span>Audiometry Records</span><strong><?php echo number_format((int) ($displayStats['audiometry_total'] ?? 0)); ?></strong></article>
        <article class="stat"><span>Need Attention</span><strong><?php echo number_format((int) ($displayStats['pending_total'] ?? 0)); ?></strong></article>
    </section>

    <section class="overview-grid">
        <article class="chart-card">
            <div class="chart-head">
                <div>
                    <h3>Fitness Records</h3>
                </div>
                <div class="chart-controls">
                    <select class="chart-select" id="dashboardChartMode">
                        <option value="month">Monthly View</option>
                        <option value="year">Yearly View</option>
                    </select>
                    <select class="chart-select" id="dashboardChartYear">
                        <option value="2024">2024</option>
                        <option value="2025" selected>2025</option>
                        <option value="2026">2026</option>
                    </select>
                </div>
                <div class="chart-meta">
                    <div class="chart-meta-item">
                        <span class="chart-meta-label"><span class="chart-dot fit"></span>Fit Records</span>
                        <span class="chart-meta-value" id="dashboardFitTotal">126</span>
                    </div>
                    <div class="chart-meta-item">
                        <span class="chart-meta-label"><span class="chart-dot not-fit"></span>Not Fit Records</span>
                        <span class="chart-meta-value" id="dashboardNotFitTotal">34</span>
                    </div>
                </div>
            </div>
            <div class="line-chart" id="dashboardLineChart" aria-hidden="true"></div>
        </article>

        <article class="panel">
            <h3>Action Needed Report</h3>
            <div class="action-grid" style="margin-top:16px;">
                <?php foreach ($displayActionItems as $item): ?>
                    <?php
                    $itemLabel = (string) ($item['label'] ?? '');
                    $cardTone = 'info';
                    if (stripos($itemLabel, 'Pending') !== false) {
                        $cardTone = 'critical';
                    } elseif (stripos($itemLabel, 'Incomplete') !== false) {
                        $cardTone = 'warning';
                    } elseif (stripos($itemLabel, 'Completed') !== false) {
                        $cardTone = 'success';
                    }
                    ?>
                    <article class="action-card <?php echo $esc($cardTone); ?>">
                        <strong><?php echo $esc($item['label'] ?? ''); ?></strong>
                        <div class="count"><?php echo number_format((int) ($item['count'] ?? 0)); ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="table-wrap">
        <div class="table-head">
            <div>
                <h3>Incomplete Records</h3>
                <p>These records still need completion before the workflow is fully done.</p>
            </div>
        </div>
        <table class="table">
            <thead><tr><th>Patient</th><th>Company</th><th>Module</th><th>Current Step</th><th>Last Updated</th><th>Action</th></tr></thead>
            <tbody>
                <?php if (count($incompleteRecords) > 0): ?>
                    <?php foreach ($incompleteRecords as $record): ?>
                        <tr>
                            <td><?php echo $esc($record['patient_name'] ?? '-'); ?></td>
                            <td><?php echo $esc($record['company_name'] ?? '-'); ?></td>
                            <td><span class="tag info"><?php echo $esc($record['module'] ?? 'Surveillance'); ?></span></td>
                            <td>
                                <?php $step = strtolower((string) ($record['current_step'] ?? '')); ?>
                                <span class="step-chip <?php echo $esc($step !== '' ? $step : 'review'); ?>"><?php echo $esc($record['current_step'] ?? '-'); ?></span>
                            </td>
                            <td><?php echo $esc($record['last_updated'] ?? '-'); ?></td>
                            <td><a class="link-btn" href="<?php echo $esc($record['action_url'] ?? '#'); ?>">Open Record</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td class="empty" colspan="6">No incomplete records found for the active clinic.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="activity-grid">
        <article class="panel">
            <h3>Recent Companies</h3>
            <div class="activity-list">
                <?php if ($recentCompanies->count() > 0): ?>
                    <?php foreach ($recentCompanies as $company): ?>
                        <div class="activity-item">
                            <div>
                                <strong><?php echo $esc($company->company_name ?? 'Not set'); ?></strong>
                                <span><?php echo $esc(($company->company_module ?? 'surveillance') === 'audiometry' ? 'Audiometry company' : 'Surveillance company'); ?></span>
                            </div>
                            <span class="tag ok"><?php echo number_format((int) ($company->total_workers ?? 0)); ?> workers</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">No company records added yet.</div>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel">
            <h3>Recent Patients</h3>
            <div class="activity-list">
                <?php if ($recentPatients->count() > 0): ?>
                    <?php foreach ($recentPatients as $patient): ?>
                        <?php $patientName = trim(((string) ($patient->employee_firstName ?? '')) . ' ' . ((string) ($patient->employee_lastName ?? ''))); ?>
                        <?php $identity = trim((string) (($patient->employee_NRIC ?? '') !== '' ? ($patient->employee_NRIC ?? '') : ($patient->employee_passportNo ?? ''))); ?>
                        <div class="activity-item">
                            <div>
                                <strong><?php echo $esc($patientName !== '' ? $patientName : 'Not set'); ?></strong>
                                <span><?php echo $esc($identity !== '' ? $identity : ($patient->employee_telephone ?? '-')); ?></span>
                            </div>
                            <span class="tag ok"><?php echo $esc($patient->employee_telephone ?? '-'); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">No patient records added yet.</div>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel">
            <h3>Recent Surveillance Activity</h3>
            <div class="activity-list">
                <?php if ($recentSurveillance->count() > 0): ?>
                    <?php foreach ($recentSurveillance as $record): ?>
                        <?php $recordName = trim(((string) ($record->employee_firstName ?? '')) . ' ' . ((string) ($record->employee_lastName ?? ''))); ?>
                        <?php $isComplete = !empty($record->employee_signature) && !empty($record->doctor_signature) && !empty($record->surveillance_id); ?>
                        <div class="activity-item">
                            <div>
                                <strong><?php echo $esc($recordName !== '' ? $recordName : 'Not set'); ?></strong>
                                <span><?php echo $esc($record->company_name ?? '-'); ?></span>
                            </div>
                            <span class="tag <?php echo $isComplete ? 'ok' : 'bad'; ?>"><?php echo $isComplete ? 'Completed' : 'In Progress'; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">No surveillance records saved yet.</div>
                <?php endif; ?>
            </div>
        </article>

        <article class="panel">
            <h3>Recent Audiometry Activity</h3>
            <div class="activity-list">
                <?php if ($recentAudiometry->count() > 0): ?>
                    <?php foreach ($recentAudiometry as $record): ?>
                        <?php $recordName = trim(((string) ($record->employee_firstName ?? '')) . ' ' . ((string) ($record->employee_lastName ?? ''))); ?>
                        <div class="activity-item">
                            <div>
                                <strong><?php echo $esc($recordName !== '' ? $recordName : 'Not set'); ?></strong>
                                <span><?php echo $esc($record->company_name ?? '-'); ?></span>
                            </div>
                            <span class="tag ok"><?php echo $esc($record->audioTest_date ?? 'Recorded'); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">No audiometry activity saved yet.</div>
                <?php endif; ?>
            </div>
        </article>
    </section>
</div>
<script>
(function () {
    var chartRoot = document.getElementById('dashboardLineChart');
    var modeSelect = document.getElementById('dashboardChartMode');
    var yearSelect = document.getElementById('dashboardChartYear');
    var fitTotal = document.getElementById('dashboardFitTotal');
    var notFitTotal = document.getElementById('dashboardNotFitTotal');
    if (!chartRoot || !modeSelect || !yearSelect || !fitTotal || !notFitTotal) {
        return;
    }

    var monthlyData = {
        '2024': {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            fit: [8, 12, 10, 15, 13, 18, 16, 14, 19, 17, 20, 22],
            notFit: [3, 4, 3, 5, 4, 6, 5, 4, 6, 5, 7, 6]
        },
        '2025': {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            fit: [10, 14, 13, 18, 17, 21, 19, 22, 20, 24, 23, 26],
            notFit: [4, 5, 4, 6, 5, 7, 6, 8, 6, 7, 8, 9]
        },
        '2026': {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            fit: [12, 16, 15, 19, 21, 23, 22, 24, 26, 25, 27, 29],
            notFit: [5, 5, 6, 7, 6, 8, 7, 8, 9, 8, 9, 10]
        }
    };

    var yearlyData = {
        labels: ['2021', '2022', '2023', '2024', '2025', '2026'],
        fit: [88, 102, 118, 132, 148, 166],
        notFit: [19, 22, 24, 28, 34, 39]
    };

    function sum(values) {
        return values.reduce(function (carry, value) {
            return carry + value;
        }, 0);
    }

    function buildPath(values, width, height, left, top, bottom, maxValue) {
        var step = values.length > 1 ? (width - left * 2) / (values.length - 1) : 0;
        return values.map(function (value, index) {
            var x = left + step * index;
            var y = top + ((maxValue - value) / maxValue) * (height - top - bottom);
            return (index === 0 ? 'M' : 'L') + x + ' ' + y;
        }).join(' ');
    }

    function buildArea(values, width, height, left, top, bottom, maxValue) {
        var step = values.length > 1 ? (width - left * 2) / (values.length - 1) : 0;
        var points = values.map(function (value, index) {
            var x = left + step * index;
            var y = top + ((maxValue - value) / maxValue) * (height - top - bottom);
            return { x: x, y: y };
        });
        var path = points.map(function (point, index) {
            return (index === 0 ? 'M' : 'L') + point.x + ' ' + point.y;
        }).join(' ');
        var last = points[points.length - 1];
        var first = points[0];
        return path + ' L' + last.x + ' ' + (height - bottom) + ' L' + first.x + ' ' + (height - bottom) + ' Z';
    }

    function buildDots(values, color, width, height, left, top, bottom, maxValue) {
        var step = values.length > 1 ? (width - left * 2) / (values.length - 1) : 0;
        return values.map(function (value, index) {
            var x = left + step * index;
            var y = top + ((maxValue - value) / maxValue) * (height - top - bottom);
            return '<circle cx="' + x + '" cy="' + y + '" r="4" fill="' + color + '"></circle>';
        }).join('');
    }

    function renderChart() {
        var mode = modeSelect.value;
        var dataset = mode === 'year' ? yearlyData : monthlyData[yearSelect.value];
        var labels = dataset.labels;
        var fit = dataset.fit;
        var notFit = dataset.notFit;
        var width = 900;
        var height = 300;
        var left = 58;
        var top = 24;
        var bottom = 42;
        var maxValue = Math.max.apply(null, fit.concat(notFit)) + 6;
        var yTicks = 4;
        var xStep = labels.length > 1 ? (width - left * 2) / (labels.length - 1) : 0;
        var horizontalLines = '';
        var verticalLines = '';
        var yLabels = '';
        var xLabels = '';

        for (var i = 0; i <= yTicks; i++) {
            var y = top + ((height - top - bottom) / yTicks) * i;
            var tickValue = Math.round(maxValue - ((maxValue / yTicks) * i));
            horizontalLines += '<line x1="' + left + '" y1="' + y + '" x2="' + (width - left) + '" y2="' + y + '" stroke="#e8edf4" stroke-width="1"></line>';
            yLabels += '<text x="28" y="' + (y + 4) + '" fill="#94a3b8" font-size="12" font-family="Poppins, Segoe UI, sans-serif">' + tickValue + '</text>';
        }

        labels.forEach(function (label, index) {
            var x = left + xStep * index;
            verticalLines += '<line x1="' + x + '" y1="' + top + '" x2="' + x + '" y2="' + (height - bottom) + '" stroke="#f1f5f9" stroke-width="1"></line>';
            xLabels += '<text x="' + x + '" y="' + (height - 14) + '" text-anchor="middle" fill="#94a3b8" font-size="12" font-family="Poppins, Segoe UI, sans-serif">' + label + '</text>';
        });

        fitTotal.textContent = sum(fit);
        notFitTotal.textContent = sum(notFit);

        chartRoot.innerHTML = ''
            + '<svg viewBox="0 0 900 300" preserveAspectRatio="none">'
            + '<defs>'
            + '<linearGradient id="fitAreaFill" x1="0" x2="0" y1="0" y2="1">'
            + '<stop offset="0%" stop-color="#3b82f6" stop-opacity=".18"></stop>'
            + '<stop offset="100%" stop-color="#3b82f6" stop-opacity="0"></stop>'
            + '</linearGradient>'
            + '<linearGradient id="notFitAreaFill" x1="0" x2="0" y1="0" y2="1">'
            + '<stop offset="0%" stop-color="#ef4444" stop-opacity=".14"></stop>'
            + '<stop offset="100%" stop-color="#ef4444" stop-opacity="0"></stop>'
            + '</linearGradient>'
            + '</defs>'
            + '<rect x="0" y="0" width="900" height="300" fill="#ffffff"></rect>'
            + horizontalLines
            + verticalLines
            + yLabels
            + xLabels
            + '<path d="' + buildArea(fit, width, height, left, top, bottom, maxValue) + '" fill="url(#fitAreaFill)"></path>'
            + '<path d="' + buildArea(notFit, width, height, left, top, bottom, maxValue) + '" fill="url(#notFitAreaFill)"></path>'
            + '<path d="' + buildPath(fit, width, height, left, top, bottom, maxValue) + '" fill="none" stroke="#3b82f6" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>'
            + '<path d="' + buildPath(notFit, width, height, left, top, bottom, maxValue) + '" fill="none" stroke="#ef4444" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>'
            + buildDots(fit, '#3b82f6', width, height, left, top, bottom, maxValue)
            + buildDots(notFit, '#ef4444', width, height, left, top, bottom, maxValue)
            + '</svg>';
    }

    modeSelect.addEventListener('change', function () {
        yearSelect.disabled = modeSelect.value === 'year';
        renderChart();
    });

    yearSelect.addEventListener('change', renderChart);
    yearSelect.disabled = false;
    renderChart();
})();
</script>
<?php medis_render_navigation_end(); ?>
</body>
</html>
