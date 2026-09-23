@php
    /** @var \App\Data\ReaderCardData $card */
    $L = \App\Support\ReaderCard\Layout::class;
    $idSize = $card->sizes['formulyarId'];
@endphp
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    @include('partials.admin.reader-card.styles')
    <style>
        /* Not the card's Roboto Mono: Figma sets the formulyar's ID in Inter. */
        .cover-id { font-weight: 700; letter-spacing: 0.18em; color: #F2F2F2; }
    </style>
</head>
<body>
    {{--
        Formulyar cover, folded down the middle: the right half is the front,
        the left half the back, and the ID runs up the spine edge so the cover
        can be found on a shelf. Figma draws no name on it, only the ID.
    --}}
    <div class="page ground last">
        <div class="abs bar" style="left: 636px; top: 288px; width: 108px; height: 486px;"></div>
        {{-- A 486 x 108 line box turned a quarter clockwise about its centre,
             which is the badge's centre (690, 531). --}}
        <div class="abs" style="left: 447px; top: 477px; width: 486px; height: 108px; transform: rotate(90deg);">
            <div class="abs line center cover-id" style="left: 0; top: {{ $L::centered(54, $idSize) }}px; width: 486px; font-size: {{ $idSize }}px;">{{ $card->idNumber }}</div>
        </div>

        @include('partials.admin.reader-card.cover-header', ['logoTop' => 322])

        <div class="abs line center" style="left: 793px; top: {{ $L::top(763, 48) }}px; width: 643px; font-size: 48px; font-weight: 700; color: #000000;">{{ __('KITOBXON FORMULYARI') }}</div>

        <div class="abs bar" style="left: 874px; top: 844px; width: 486px; height: 108px;"></div>
        <div class="abs line center cover-id" style="left: 874px; top: {{ $L::centered(898, $idSize) }}px; width: 486px; font-size: {{ $idSize }}px;">{{ $card->idNumber }}</div>
    </div>
</body>
</html>
