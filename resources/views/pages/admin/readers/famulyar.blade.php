<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        @page { margin: 0; }
        body { color: #1f2937; margin: 0; }

        .frame { margin: 16px; border: 3px solid #465fff; border-radius: 6px; height: 480px; position: relative; }

        .id-box { width: 220px; margin: 34px auto 0; border: 2px solid #465fff; border-radius: 4px; padding: 10px 12px; text-align: center; }
        .id-box .label { font-size: 9px; color: #6b7280; letter-spacing: 1px; text-transform: uppercase; margin: 0 0 3px; }
        .id-box .value { font-size: 16px; font-weight: bold; color: #465fff; margin: 0; }

        .logo-wrap { width: 150px; height: 150px; margin: 40px auto; border: 3px solid #465fff; border-radius: 50%; text-align: center; }
        .logo-wrap img { width: 110px; height: 110px; margin-top: 18px; object-fit: contain; }

        .name-box { width: 380px; margin: 0 auto; border: 2px solid #465fff; border-radius: 4px; padding: 12px 16px; text-align: center; }
        .name-box .label { font-size: 9px; color: #6b7280; letter-spacing: 1px; text-transform: uppercase; margin: 0 0 3px; }
        .name-box .value { font-size: 15px; font-weight: bold; color: #111827; margin: 0; }
    </style>
</head>
<body>
    <div class="frame">
        <div class="id-box">
            <p class="label">{{ __('ID') }}</p>
            <p class="value">{{ $reader->id_number ?: '—' }}</p>
        </div>

        <div class="logo-wrap">
            @if ($logo)
                <img src="{{ $logo }}" alt="">
            @endif
        </div>

        <div class="name-box">
            <p class="label">{{ __('Ism, familiya') }}</p>
            <p class="value">{{ $reader->full_name }}</p>
        </div>
    </div>
</body>
</html>
