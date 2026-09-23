{{--
    Right half of both covers (the card's outside and the formulyar): university
    name, centre name, logo. Identical in the two Figma panels except for how
    far down the logo sits, hence $logoTop.
--}}
@php($L = \App\Support\ReaderCard\Layout::class)

<div class="abs center" style="left: 793px; top: {{ $L::top(57, 32, $L::lineHeight(38)) }}px; width: 643px; font-size: 32px; line-height: {{ $L::lineHeight(38) }}px; font-weight: 700; color: #000000; white-space: nowrap;">
    {{ __('SAMARQAND DAVLAT VETERINARIYA') }}<br>
    {{ __('MEDITSINASI, CHORVACHILIK VA') }}<br>
    {{ __('BIOTEXNOLOGIYALAR UNIVERSITETI') }}<br>
    {{ __('NUKUS FILIALI') }}
</div>

<div class="abs line center" style="left: 793px; top: {{ $L::top(241, 20) }}px; width: 643px; font-size: 20px; color: #000000;">
    {{ __('AXBOROT RESURS MARKAZI') }}
</div>

<img class="abs" src="{{ $card->logoPath }}" alt="" style="left: 957px; top: {{ $logoTop }}px; width: 311px; height: 328px;">
