@php
    /** @var \App\Data\ReaderCardData $card */
    $L = \App\Support\ReaderCard\Layout::class;
    $reader = $card->reader;
    $palette = $card->palette;
    $isStudent = $reader->type?->is_student ?? true;

    $placeLabel = $isStudent ? __('O‘qish joyi') : __('Ish joyi');
    $unitLabel = $isStudent ? __('Mutaxassisligi') : __('Bo‘limi');
    $groupLabel = $isStudent ? __('Guruhi') : __('Lavozimi');

    // The printed rules, broken exactly where the Figma frame breaks them.
    // dompdf applies no kerning, so its lines run ~2% wider than Figma's and
    // would wrap in different places if left to flow. Admin documents are
    // Uzbek-only by project decision, so these are not translation keys.
    $rules = [
        ['ARMda kitobxon guvohnomasiz kitob berilmaydi.'],
        ['O‘quv zalida adabiyotlardan foydalanish, kitobxon', 'guvohnomasini garovga berish orqali amalga oshiriladi.'],
        ['O‘quv zalidan adabiyotlarni olib chiqish qat’iyan', 'taqiqlanadi.'],
        ['Elektron o‘quv zalidan foydalanish uchun kitobxon', 'guvohnomasini taqdim etgandan so‘ng bo‘sh', 'kompyuterdan foydalanishga ruxsat etiladi.'],
        ['Har o‘quv yili boshida kitobxonlar qayta ro‘yxatdan', 'o‘tkaziladi.'],
        ['Har o‘quv yili so‘ngida ARMdan qarzdorligi yo‘qligi', 'to‘g‘risida kutubxona muhri va kutubxonachi imzosi orqali', 'tasdiqlash zarur.'],
        ['ARMda kitobxonlarga berilgan adabiyotlar soni va muddati,', 'kitobxonlarga xizmat ko‘rsatishning boshqa tartibi “Axborot', 'resurs markazidan foydalanish qoidalari” orqali amalga', 'oshiriladi.'],
    ];

    // Right column of the inside: cap top of each year's first line, and the
    // rule under each block — measured, not derived, because the first block
    // sits closer to the title rule than the rest sit to each other.
    $yearTops = [151, 351, 532, 713, 894];
    $yearRules = [313, 494, 675, 856, 1036];

    // Name fields: label cap top, value baseline, divider — 116px apart.
    $fields = [
        ['label' => __('Familyasi'), 'value' => $card->surname, 'size' => $card->sizes['surname'], 'label_cap' => 280, 'baseline' => 336, 'rule' => 342],
        ['label' => __('Ismi'), 'value' => $card->firstName, 'size' => $card->sizes['firstName'], 'label_cap' => 396, 'baseline' => 451, 'rule' => 458],
        ['label' => __('Sharifi'), 'value' => $card->patronymic, 'size' => $card->sizes['patronymic'], 'label_cap' => 511, 'baseline' => 567, 'rule' => 574],
    ];
@endphp
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    @include('partials.admin.reader-card.styles')
    <style>
        .rules { position: absolute; left: 50px; width: 690px; border-collapse: collapse; font-size: 22px; line-height: {{ $L::lineHeight(31) }}px; color: #303030; }
        .rules td { padding: 0 0 16px 0; vertical-align: top; white-space: nowrap; }
        .rules tr.last td { padding-bottom: 0; }
        .rules td.num { width: 34px; padding-left: 7px; }

        .label { font-size: 20px; font-weight: 300; letter-spacing: 0.06em; color: #4F4F4F; }
        .value { font-weight: 500; letter-spacing: 0.06em; color: #333333; }
        .sign-label { font-size: 16px; font-weight: 300; letter-spacing: 0.06em; color: #4F4F4F; }
        .rule { position: absolute; height: 1px; }
        .blank { position: absolute; height: 1px; background-color: #4F4F4F; }
    </style>
</head>
<body>
    {{-- ==================== Page 1: outside ==================== --}}
    <div class="page ground">
        <div class="abs bar" style="left: 52px; top: 50px; width: 642px; height: 64px;"></div>
        <div class="abs line center bar-text" style="left: 52px; top: {{ $L::top(69, 36) }}px; width: 642px;">{{ __('ESLATMA!') }}</div>

        <table class="rules" style="top: {{ $L::top(170, 22, $L::lineHeight(31)) }}px;">
            @foreach ($rules as $i => $lines)
                <tr @class(['last' => $loop->last])>
                    <td class="num">{{ $i + 1 }}.</td>
                    <td>{!! collect($lines)->map(fn ($line) => e($line))->implode('<br>') !!}</td>
                </tr>
            @endforeach
        </table>

        {{-- Warning box: a tinted strip with the darker "!" square on its left. --}}
        <div class="abs" style="left: 50px; top: 912px; width: 644px; height: 94px; background-color: {{ $palette->soft }}; border-radius: 6px;"></div>
        <div class="abs" style="left: 50px; top: 912px; width: 84px; height: 94px; background-color: {{ $palette->strong }}; border-radius: 6px 0 0 6px;"></div>
        <div class="abs line center" style="left: 50px; top: {{ $L::top(941, 48) }}px; width: 84px; font-size: 48px; font-weight: 700; color: #FFFFFF;">!</div>
        <div class="abs" style="left: 156px; top: {{ $L::top(934, 20, $L::lineHeight(32)) }}px; font-size: 20px; line-height: {{ $L::lineHeight(32) }}px; color: {{ $palette->ink }}; white-space: nowrap;">
            {{ __('KITOBXON GUVOHNOMASIDAN BOSHQA SHAXS') }}<br>
            {{ __('FOYDALANISHI QAT’IYAN TAQIQLANADI.') }}
        </div>

        @include('partials.admin.reader-card.cover-header', ['logoTop' => 363])

        <div class="abs line center" style="left: 793px; top: {{ $L::top(841, 48) }}px; width: 643px; font-size: 48px; font-weight: 700; color: #000000;">{{ __('KITOBXON GUVOHNOMASI') }}</div>
        <div class="abs line center" style="left: 793px; top: {{ $L::top(975, 20) }}px; width: 643px; font-size: 20px; color: #000000;">{{ __('AXBOROT RESURS MARKAZIDAN FOYDALANISH UCHUN') }}</div>
    </div>

    {{-- ==================== Page 2: inside ==================== --}}
    <div class="page plain last">
        <div class="abs bar" style="left: 50px; top: 50px; width: 644px; height: 64px;"></div>
        <div class="abs line center bar-text" style="left: 50px; top: {{ $L::top(69, 36) }}px; width: 644px;">{{ __('KITOBXON GUVOHNOMASI') }}</div>

        @if ($card->photoPath)
            {{-- 300 x 392 frame; dompdf has no object-fit, so the photo is scaled to it. --}}
            <img class="abs" src="{{ $card->photoPath }}" alt="" style="left: 50px; top: 175px; width: 300px; height: 392px;">
        @else
            <div class="abs" style="left: 50px; top: 175px; width: 300px; height: 392px; background-color: #E0E0E0;"></div>
            <div class="abs line center" style="left: 50px; top: {{ $L::centered(371, 20) }}px; width: 300px; font-size: 20px; color: #828282;">{{ __('Rasm yo‘q') }}</div>
        @endif

        <div class="abs bar" style="left: 394px; top: 176px; width: 300px; height: 62px;"></div>
        <div class="abs line center id-text" style="left: 394px; top: {{ $L::centered(207, $card->sizes['cardId'], 'mono') }}px; width: 300px; font-size: {{ $card->sizes['cardId'] }}px; letter-spacing: 0.06em;">{{ $card->idNumber }}</div>

        @foreach ($fields as $field)
            <div class="abs line label" style="left: 394px; top: {{ $L::top($field['label_cap'], 20) }}px;">{{ $field['label'] }}:</div>
            <div class="abs line value" style="left: 394px; top: {{ $L::onBaseline($field['baseline'], $field['size']) }}px; font-size: {{ $field['size'] }}px;">{{ $field['value'] ?: '—' }}</div>
            <div class="rule" style="left: 394px; top: {{ $field['rule'] }}px; width: 300px; background-color: #E0E0E0;"></div>
        @endforeach

        {{--
            Affiliation box: laid out in flow, not pinned, because a specialty
            name runs to 63 characters and wraps onto three lines — the box
            grows into the free space above the signature lines instead of
            spilling over them. Line boxes are 24px (labels, sub-values) and 36px
            (the place), so with dompdf's baseline at 0.8 of the box the padding
            and margins below land each line's capitals where Figma has them:
            659, 691, 749 and 779, box bottom at 818.
        --}}
        @php($box24 = $L::lineHeight(24))
        @php($box36 = $L::lineHeight(36))
        <div class="abs" style="left: 50px; top: 635px; width: 644px; min-height: 183px; padding: 19px 22px 20px 22px; background-color: #F5F5F5; border-radius: 8px;">
            <div class="label" style="line-height: {{ $box24 }}px;">{{ $placeLabel }}:</div>
            <div class="value" style="margin-top: 6px; font-size: {{ $card->sizes['place'] }}px; line-height: {{ $box36 }}px;">{{ $reader->affiliationPlace?->name ?: '—' }}</div>
            <table style="width: 100%; margin-top: 24px; border-collapse: collapse;">
                <tr>
                    <td style="width: 314px; padding: 0 16px 0 0; vertical-align: top;">
                        <div class="label" style="line-height: {{ $box24 }}px;">{{ $unitLabel }}:</div>
                        <div class="value" style="margin-top: 6px; font-size: 20px; line-height: {{ $box24 }}px;">{{ $reader->affiliationUnit?->name ?: '—' }}</div>
                    </td>
                    <td style="padding: 0; vertical-align: top;">
                        <div class="label" style="line-height: {{ $box24 }}px;">{{ $groupLabel }}:</div>
                        <div class="value" style="margin-top: 6px; font-size: 20px; line-height: {{ $box24 }}px;">{{ $reader->affiliationGroup?->name ?: '—' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="blank" style="left: 50px; top: 965px; width: 297px;"></div>
        <div class="blank" style="left: 398px; top: 965px; width: 296px;"></div>
        @if ($reader->issued_date)
            <div class="abs line value" style="left: 398px; top: {{ $L::onBaseline(956, 20) }}px; font-size: 20px;">{{ $reader->issued_date->format('d.m.Y') }}</div>
        @endif
        <div class="abs line sign-label" style="left: 50px; top: {{ $L::top(984, 16) }}px;">{{ __('Kitobxon imzosi') }}:</div>
        <div class="abs line sign-label" style="left: 398px; top: {{ $L::top(984, 16) }}px;">{{ __('Berilgan sana') }}:</div>

        {{-- Right column: record of use, one block per academic year. --}}
        <div class="abs center" style="left: 795px; top: {{ $L::top(55, 24, $L::lineHeight(29)) }}px; width: 641px; font-size: 24px; line-height: {{ $L::lineHeight(29) }}px; font-weight: 700; letter-spacing: 0.1em; color: #000000; white-space: nowrap;">
            {{ __('AXBOROT RESURS MARKAZIDAN') }}<br>
            {{ __('FOYDALANGANLIGI TO‘G‘RISIDA MA’LUMOT') }}
        </div>
        <div class="rule" style="left: 795px; top: 133px; width: 641px; background-color: #E6E6E6;"></div>

        @foreach ($yearTops as $i => $y)
            <div class="abs line" style="left: 799px; top: {{ $L::top($y, 20) }}px; font-size: 20px;">{{ $i + 1 }}. 20__ / 20__ &nbsp;{{ __('o‘quv yili') }}</div>
            <div class="abs line" style="left: 795px; top: {{ $L::top($y + 52, 20) }}px; font-size: 20px;">{{ __('Registratsiya') }} &#8470;</div>
            <div class="blank" style="left: 952px; top: {{ $y + 67 }}px; width: 141px;"></div>
            <div class="abs line" style="left: 795px; top: {{ $L::top($y + 91, 20) }}px; font-size: 20px;">{{ __('O‘qigan kitoblari') }}</div>
            <div class="blank" style="left: 956px; top: {{ $y + 106 }}px; width: 137px;"></div>
            <div class="rule" style="left: 795px; top: {{ $yearRules[$i] }}px; width: 641px; background-color: #E6E6E6;"></div>
        @endforeach
    </div>
</body>
</html>
