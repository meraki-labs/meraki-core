<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meraki — Cài đặt</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f5; color: #333; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,.1); max-width: 640px; width: 100%; padding: 2rem; }
        .logo { font-size: 1.5rem; font-weight: 700; color: #6366f1; margin-bottom: 1.5rem; }
        .steps { display: flex; gap: .5rem; margin-bottom: 2rem; }
        .step { flex: 1; height: 4px; border-radius: 2px; background: #e5e7eb; }
        .step.done { background: #6366f1; }
        .step.active { background: #a5b4fc; }
        h1 { font-size: 1.25rem; margin-bottom: .5rem; }
        p.subtitle { color: #6b7280; font-size: .9rem; margin-bottom: 1.5rem; }
        .btn { display: inline-block; padding: .625rem 1.25rem; background: #6366f1; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: .9rem; text-decoration: none; }
        .btn:hover { background: #4f46e5; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }
        .alert { padding: .75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: .875rem; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: .875rem; font-weight: 500; margin-bottom: .375rem; }
        input[type=text], input[type=email], input[type=password] { width: 100%; padding: .5rem .75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: .9rem; }
        input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }
        .check-list { list-style: none; }
        .check-list li { display: flex; align-items: flex-start; gap: .5rem; padding: .5rem 0; border-bottom: 1px solid #f3f4f6; font-size: .875rem; }
        .check-list li:last-child { border-bottom: none; }
        .badge { display: inline-block; padding: .125rem .5rem; border-radius: 4px; font-size: .75rem; font-weight: 600; }
        .badge-pass { background: #dcfce7; color: #16a34a; }
        .badge-fail { background: #fee2e2; color: #dc2626; }
        .actions { display: flex; gap: .75rem; margin-top: 1.5rem; }
        .plugin-item { display: flex; align-items: center; gap: .75rem; padding: .625rem 0; border-bottom: 1px solid #f3f4f6; }
        .plugin-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">⚙ Meraki</div>
    <div class="steps">
        @for ($i = 1; $i <= 5; $i++)
            <div class="step {{ isset($currentStep) && $i < $currentStep ? 'done' : ($i == ($currentStep ?? 1) ? 'active' : '') }}"></div>
        @endfor
    </div>
    @yield('content')
</div>
</body>
</html>
