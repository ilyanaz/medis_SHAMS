<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Company</title>
    <style>
        :root{--surface:#f3f4f6;--panel:#fff;--line:#e5e7eb;--text:#111827;--muted:#8b8b95;--danger:#ff3347}
        *{box-sizing:border-box}
        body{margin:0;font-family:"Poppins","Segoe UI",Tahoma,Geneva,Verdana,sans-serif;background:var(--surface);color:var(--text)}
        .shell{position:relative;min-height:100vh;overflow:hidden}
        .page-bg{min-height:100vh;padding:26px 22px;background:linear-gradient(180deg,#f8fafc 0,#eef2f7 100%)}
        .list-shell{max-width:1500px;margin:0 auto;border:1px solid var(--line);border-radius:28px;background:#fff;padding:28px 26px 22px;box-shadow:0 18px 48px rgba(15,23,42,.06)}
        .list-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
        .list-head h2{margin:0;font-size:2rem}
        .list-head p{margin:8px 0 0;color:#6b7280}
        .top-btn{display:inline-flex;align-items:center;justify-content:center;border-radius:14px;padding:12px 18px;background:#389B5B;color:#fff;text-decoration:none;font-weight:600}
        .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:20px}
        .search{width:min(320px,100%);border:1px solid #d1d5db;border-radius:14px;padding:12px 14px;background:#fff;color:#6b7280}
        .ghost-filters{display:flex;gap:10px}
        .ghost-btn{border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151}
        .table-wrap{margin-top:18px;border-top:1px solid #edf0f2}
        .row{display:grid;grid-template-columns:2.1fr 1.7fr 1.6fr 1fr 120px;gap:16px;align-items:center;padding:18px 10px;border-bottom:1px solid #edf0f2}
        .row.head{padding-top:16px;padding-bottom:16px;color:#6b7280;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em}
        .name{font-weight:600}
        .sub{margin-top:4px;color:#8b8b95;font-size:.92rem}
        .icons{display:flex;gap:14px;color:#94a3b8}
        .pager{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:16px;color:#6b7280;font-size:.9rem}
        .page-bg.is-dimmed{filter:blur(1.6px);pointer-events:none;user-select:none}
        .overlay{position:absolute;inset:0;display:grid;place-items:center;padding:24px;background:rgba(125,132,146,.72)}
        .modal{position:relative;width:min(460px,100%);background:var(--panel);border-radius:22px;box-shadow:0 24px 56px rgba(15,23,42,.18);padding:62px 24px 22px;text-align:center}
        .icon-wrap{position:absolute;left:50%;top:0;transform:translate(-50%,-42%);width:96px;height:96px;border-radius:999px;background:radial-gradient(circle at 50% 38%,#fff 0,#fff 42%,#f6f7fb 100%);display:grid;place-items:center;box-shadow:0 14px 30px rgba(15,23,42,.08)}
        .trash-icon{width:42px;height:42px;color:var(--danger)}
        .trash-icon svg{width:100%;height:100%;stroke:currentColor;fill:none;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
        .close-btn{position:absolute;top:16px;right:16px;width:34px;height:34px;border:none;background:transparent;color:#c0c2ca;cursor:pointer}
        .close-btn svg{width:100%;height:100%;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round}
        h1{margin:0;font-size:1.45rem;line-height:1.25}
        .muted{margin:16px auto 0;max-width:330px;color:var(--muted);font-size:.94rem;line-height:1.5;font-weight:600}
        .meta{margin:16px auto 0;display:grid;gap:6px;max-width:320px;color:#6b7280;font-size:.88rem}
        .meta strong{color:#111827}
        .actions{margin-top:22px;display:flex;justify-content:flex-end;gap:14px;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;border:none;background:transparent;color:#444;font:inherit;font-size:.98rem;font-weight:600;cursor:pointer;padding:10px 8px}
        .btn-danger{min-width:124px;border-radius:14px;background:var(--danger);color:#fff;padding:12px 20px;box-shadow:0 12px 26px rgba(255,51,71,.22)}
        @media (max-width:760px){
            .page-bg{padding:18px}
            .list-shell{padding:18px}
            .row{grid-template-columns:1fr}
            .row.head{display:none}
        }
        @media (max-width:640px){
            .modal{padding:64px 18px 18px}
            h1{font-size:1.25rem}
            .muted{font-size:.92rem}
            .actions{justify-content:center}
            .btn-danger{width:100%}
        }
    </style>
</head>
<body>
<?php
$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$companyRecord = $companyRecord ?? null;
$csrfToken = function_exists('csrf_token') ? (string) csrf_token() : '';
$returnTo = (string) request()->query('return_to', function_exists('route') ? route('panel.company_list') : '#');
$backUrl = $returnTo !== '' ? $returnTo : (function_exists('route') ? route('panel.company_list') : '#');
$formAction = ($companyRecord && function_exists('route')) ? route('panel.company.destroy', ['company' => $companyRecord->company_id]) : '#';
?>
<div class="shell">
    <div class="page-bg is-dimmed" aria-hidden="true">
        <div class="list-shell">
            <div class="list-head">
                <div>
                    <h2>Company List</h2>
                    <p>Review companies added under the selected clinic and manage their setup records.</p>
                </div>
                <a class="top-btn" href="javascript:void(0)">+ Add Company</a>
            </div>
            <div class="toolbar">
                <input class="search" type="text" value="" placeholder="Search record" readonly>
                <div class="ghost-filters">
                    <div class="ghost-btn">Surveillance</div>
                    <div class="ghost-btn">Audiometry</div>
                </div>
            </div>
            <div class="table-wrap">
                <div class="row head">
                    <div>Name</div>
                    <div>MYKPP Registration Number</div>
                    <div>Contact</div>
                    <div>Total Workers</div>
                    <div>Action</div>
                </div>
                <div class="row">
                    <div>
                        <div class="name"><?php echo $esc($companyRecord->company_name ?? 'Company'); ?></div>
                        <div class="sub"><?php echo $esc($companyRecord->company_email ?? 'No email provided'); ?></div>
                    </div>
                    <div><?php echo $esc($companyRecord->mykpp_registration_no ?? '-'); ?></div>
                    <div><?php echo $esc($companyRecord->company_telephone ?? '-'); ?></div>
                    <div><?php echo $esc(number_format((int) ($companyRecord->total_workers ?? 0))); ?></div>
                    <div class="icons">View Edit Delete</div>
                </div>
            </div>
            <div class="pager">
                <span>Showing 1-1 of 1 records</span>
                <span>Next</span>
            </div>
        </div>
    </div>

    <div class="overlay">
        <form class="modal" method="POST" action="<?php echo $esc($formAction); ?>">
            <input type="hidden" name="_token" value="<?php echo $esc($csrfToken); ?>">
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="return_to" value="<?php echo $esc($returnTo); ?>">

            <div class="icon-wrap" aria-hidden="true">
                <div class="trash-icon">
                    <svg viewBox="0 0 24 24"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>
                </div>
            </div>

            <a class="close-btn" href="<?php echo $esc($backUrl); ?>" aria-label="Close delete dialog">
                <svg viewBox="0 0 24 24"><path d="M6 6l12 12"></path><path d="M18 6L6 18"></path></svg>
            </a>

            <h1>You are about to delete a company</h1>
            <p class="muted">This will remove the selected company record from the current clinic display. Are you sure?</p>

            <div class="meta">
                <div><strong>Company:</strong> <?php echo $esc($companyRecord->company_name ?? '-'); ?></div>
                <div><strong>Reference:</strong> #COM<?php echo $esc($companyRecord->company_id ?? ''); ?></div>
            </div>

            <div class="actions">
                <a class="btn" href="<?php echo $esc($backUrl); ?>">Cancel</a>
                <button class="btn btn-danger" type="submit">Delete</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
