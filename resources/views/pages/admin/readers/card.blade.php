@php
    $isStudent = $reader->type->isStudent();

    // "Familyasi Ismi Sharifi" — split from the single stored full_name (the DB
    // has no separate surname/first-name/patronymic columns). First word is the
    // surname, second the first name, everything else (e.g. "Xabibulla o'g'li")
    // is the patronymic — matches standard Uzbek naming order.
    $nameParts = preg_split('/\s+/', trim((string) $reader->full_name), 3);
    $surname = $nameParts[0] ?? '';
    $firstName = $nameParts[1] ?? '';
    $patronymic = $nameParts[2] ?? '';

    $placeLabel = $isStudent ? __('O‘qish joyi') : __('Ish joyi');
    $unitLabel = $isStudent ? __('Mutaxassisligi') : __('Bo‘limi');
    $groupLabel = $isStudent ? __('Guruhi') : __('Lavozimi');

    $yearsCount = 5;
@endphp
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
        @page { margin: 14px 16px; }
        body { color: #1f2937; font-size: 9.5px; margin: 0; }

        table.card { width: 100%; border-collapse: collapse; }
        table.card > tr > td { vertical-align: top; padding: 0; }
        td.left-col { width: 52%; padding-right: 14px; border-right: 1px solid #e5e7eb; }
        td.right-col { width: 48%; padding-left: 14px; }

        .badge { display: inline-block; color: #fff; font-size: 12px; font-weight: bold; letter-spacing: 0.5px; padding: 5px 12px; border-radius: 4px; margin-bottom: 10px; }

        table.id-row { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.id-row td { vertical-align: top; }
        .photo-cell { width: 78px; padding-right: 10px; }
        .photo { width: 70px; height: 88px; border: 1px solid #d1d5db; border-radius: 4px; object-fit: cover; }
        .photo-empty { width: 70px; height: 88px; border: 1px solid #d1d5db; border-radius: 4px; background: #f3f4f6; text-align: center; }
        .photo-empty span { display: block; padding-top: 36px; font-size: 8px; color: #9ca3af; }
        .id-number { font-size: 10px; color: #374151; }
        .id-number b { display: block; font-size: 13px; color: #111827; margin-top: 2px; }

        .name-block p { margin: 0 0 6px; font-size: 10px; color: #6b7280; }
        .name-block p b { display: block; font-size: 12px; color: #111827; font-weight: bold; margin-top: 1px; }

        .affiliation-box { background: #f9fafb; border: 1px solid #eef0f3; border-radius: 4px; padding: 8px 10px; margin: 8px 0 12px; }
        .affiliation-box p { margin: 0 0 6px; font-size: 10px; color: #6b7280; }
        .affiliation-box p b { display: block; font-size: 11px; color: #111827; font-weight: bold; margin-top: 1px; }
        .affiliation-box p:last-child { margin-bottom: 0; }
        table.affiliation-sub { width: 100%; border-collapse: collapse; }
        table.affiliation-sub td { width: 50%; vertical-align: top; padding: 0; }

        table.footer-row { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.footer-row td { font-size: 9.5px; color: #374151; vertical-align: bottom; }
        .sign-line { display: inline-block; border-top: 1px solid #9ca3af; padding-top: 3px; width: 110px; }

        .right-title { font-size: 10px; font-weight: bold; text-align: center; color: #111827; line-height: 1.4; margin-bottom: 8px; }
        .right-rule { border: none; border-top: 1px solid #d1d5db; margin: 0 0 10px; }
        .year-row p { margin: 0 0 5px; font-size: 10px; color: #111827; }
        .year-row p.year-line { font-weight: bold; }
        .year-rule { border: none; border-top: 1px solid #eef0f3; margin: 0 0 10px; }
    </style>
</head>
<body>
    <table class="card">
        <tr>
            <td class="left-col">
                <div class="badge" style="background: {{ $reader->type->certificateColor() }};">{{ __('KITOBXON GUVOHNOMASI') }}</div>

                <table class="id-row">
                    <tr>
                        <td class="photo-cell">
                            @if ($photo)
                                <img class="photo" src="{{ $photo }}" alt="">
                            @else
                                <div class="photo-empty"><span>{{ __('Rasm yo‘q') }}</span></div>
                            @endif
                        </td>
                        <td class="id-number">
                            {{ __('ID raqam') }}:
                            <b>{{ $reader->id_number ?: '—' }}</b>
                        </td>
                    </tr>
                </table>

                <div class="name-block">
                    <p>{{ __('Familyasi') }}: <b>{{ $surname ?: '—' }}</b></p>
                    <p>{{ __('Ismi') }}: <b>{{ $firstName ?: '—' }}</b></p>
                    <p>{{ __('Sharifi') }}: <b>{{ $patronymic ?: '—' }}</b></p>
                </div>

                <div class="affiliation-box">
                    <p>{{ $placeLabel }}: <b>{{ $reader->affiliationPlace?->name ?: '—' }}</b></p>
                    <table class="affiliation-sub">
                        <tr>
                            <td>{{ $unitLabel }}: <b>{{ $reader->affiliationUnit?->name ?: '—' }}</b></td>
                            <td>{{ $groupLabel }}: <b>{{ $reader->affiliationGroup?->name ?: '—' }}</b></td>
                        </tr>
                    </table>
                </div>

                <table class="footer-row">
                    <tr>
                        <td width="55%">
                            {{ __('Kitobxon imzosi') }}:
                            <span class="sign-line">&nbsp;</span>
                        </td>
                        <td width="45%">
                            {{ __('Berilgan sana') }}: {{ $reader->issued_date?->format('d.m.Y') ?: '—' }}
                        </td>
                    </tr>
                </table>
            </td>

            <td class="right-col">
                <div class="right-title">{{ __('AXBOROT RESURS MARKAZIDAN FOYDALANGANLIGI TO‘G‘RISIDA MA’LUMOT') }}</div>
                <hr class="right-rule">

                @for ($i = 1; $i <= $yearsCount; $i++)
                    <div class="year-row">
                        <p class="year-line">{{ $i }}. 20__&#8203;/20__ {{ __('o‘quv yili') }}</p>
                        <p>{{ __('Registratsiya') }} &#8470;: __________</p>
                    </div>
                    <hr class="year-rule">
                @endfor
            </td>
        </tr>
    </table>
</body>
</html>
