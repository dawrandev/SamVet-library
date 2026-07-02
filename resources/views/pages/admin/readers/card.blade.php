@php
    $isStudent = $reader->type->isStudent();

    // Rasmdagi barcha ma'lumot — juftlik ro'yxati sifatida
    $rows = array_filter([
        __('ID raqami') => $reader->id_number,
        __('Ro‘yxat raqami') => $reader->registration_number,
        __('Berilgan sana') => $reader->issued_date?->format('d.m.Y'),
        ($isStudent ? __('O‘qish joyi') : __('Ish joyi')) => $reader->affiliation_place,
        ($isStudent ? __('Mutaxassisligi') : __('Bo‘limi')) => $reader->affiliation_unit,
        ($isStudent ? __('Guruhi') : __('Lavozimi')) => $reader->affiliation_group,
        __('Millati') => $reader->nationality,
        __('Tug‘ilgan sana') => $reader->birth_date?->format('d.m.Y'),
        __('Jinsi') => $reader->gender?->label(),
        __('Pasport seriyasi') => $reader->passport,
        __('JSHSHIR') => $reader->pinfl,
        __('Tuman') => $reader->district,
        __('Manzil') => $reader->address,
        __('Telefon') => $reader->phone,
        __('A‘zolik yili') => $reader->member_year,
    ], fn ($v) => filled($v));
@endphp
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        @page { margin: 18px 22px; }
        body { color: #1f2937; font-size: 11px; margin: 0; }

        .header { text-align: center; border-bottom: 2px solid #465fff; padding-bottom: 8px; margin-bottom: 12px; }
        .header .uni { font-size: 13px; font-weight: bold; color: #465fff; }
        .header .sub { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .title { text-align: center; font-size: 15px; font-weight: bold; letter-spacing: 1px; margin: 6px 0 14px; }

        .top { width: 100%; }
        .top td { vertical-align: top; }
        .photo-cell { width: 110px; }
        .photo { width: 100px; height: 120px; border: 1px solid #d1d5db; border-radius: 6px; object-fit: cover; }
        .photo-empty { width: 100px; height: 120px; border: 1px solid #d1d5db; border-radius: 6px; background: #f3f4f6; text-align: center; }
        .photo-empty span { font-size: 46px; color: #9ca3af; line-height: 120px; }

        .name { font-size: 15px; font-weight: bold; margin: 0 0 6px; }
        .badge { display: inline-block; background: #465fff; color: #fff; font-size: 10px; font-weight: bold; padding: 3px 10px; border-radius: 10px; }
        .badge-status { background: #e5e7eb; color: #374151; margin-left: 4px; }

        table.details { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.details td { padding: 5px 6px; border-bottom: 1px solid #f0f0f0; font-size: 11px; }
        table.details td.label { color: #6b7280; width: 42%; }
        table.details td.value { color: #111827; font-weight: bold; }

        .note { margin-top: 14px; color: #ef4444; font-size: 10px; font-weight: bold; text-align: center; }
        .footer { margin-top: 26px; width: 100%; font-size: 10px; color: #6b7280; }
        .footer td { padding-top: 20px; }
        .sign-line { border-top: 1px solid #9ca3af; padding-top: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="uni">{{ __('Samarqand veterinariya universiteti') }}</div>
        <div class="sub">{{ __('Elektron kutubxona') }}</div>
    </div>

    <div class="title">{{ __('KITOBXON GUVOHNOMASI') }}</div>

    <table class="top">
        <tr>
            <td class="photo-cell">
                @if ($photo)
                    <img class="photo" src="{{ $photo }}" alt="">
                @else
                    <div class="photo-empty"><span>&#128100;</span></div>
                @endif
            </td>
            <td>
                <p class="name">{{ $reader->full_name }}</p>
                <span class="badge">{{ $reader->type->label() }}</span>
                <span class="badge badge-status">{{ $reader->status->label() }}</span>
            </td>
        </tr>
    </table>

    <table class="details">
        @foreach ($rows as $label => $value)
            <tr>
                <td class="label">{{ $label }}</td>
                <td class="value">{{ $value }}</td>
            </tr>
        @endforeach
    </table>

    <table class="footer">
        <tr>
            <td width="50%">{{ __('Berilgan sana') }}: {{ $reader->issued_date?->format('d.m.Y') ?: '—' }}</td>
            <td width="50%" style="text-align: right;">
                <div class="sign-line" style="display: inline-block; width: 150px; text-align: center;">{{ __('Kutubxonachi imzosi') }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
