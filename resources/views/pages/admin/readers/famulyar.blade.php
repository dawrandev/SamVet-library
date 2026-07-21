<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        @page { margin: 0; }
        body { color: #1f2937; margin: 0; }

        .frame { margin: 16px; border: 3px solid #465fff; border-radius: 10px; height: 420px; position: relative; padding-top: 32px; }

        .id { text-align: center; }
        .id .label { font-size: 9px; color: #9ca3af; letter-spacing: 2px; text-transform: uppercase; margin: 0 0 4px; line-height: 1; }
        .id .value { font-size: 16px; font-weight: bold; color: #465fff; letter-spacing: 0.5px; margin: 0; line-height: 1; }

        .logo-wrap { width: 140px; height: 140px; margin: 26px auto; border: 3px solid #465fff; border-radius: 50%; text-align: center; }
        .logo-wrap img { width: 104px; height: 104px; margin-top: 18px; object-fit: contain; }

        .name { text-align: center; padding: 0 40px; }
        .name .rule { width: 60px; height: 2px; background: #465fff; margin: 0 auto 12px; border-radius: 2px; }
        .name .value { font-size: 18px; font-weight: bold; color: #111827; letter-spacing: 0.3px; margin: 0; line-height: 1.3; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="id">
            <p class="label">{{ __('ID') }}</p>
            <p class="value">{{ $reader->id_number ?: '—' }}</p>
        </div>

        <div class="logo-wrap">
            @if ($logo)
                <img src="{{ $logo }}" alt="">
            @endif
        </div>

        <div class="name">
            <div class="rule"></div>
            <p class="value">{{ $reader->full_name }}</p>
        </div>
    </div>
</body>
</html>
