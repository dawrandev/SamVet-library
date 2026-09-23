{{--
    Shared by the reader card and the formulyar. Every length is a Figma pixel:
    the service sets the PDF's DPI so the page is exactly the 1486 x 1050 panel
    (see ReaderCardService). The page box stops short of that — A5 at 180 dpi
    is 1048.8px tall, and a box even 0.2px taller than the page spills a blank one.
--}}
<style>
    @font-face { font-family: 'Inter'; font-weight: 300; src: url('{{ $card->fontDir }}/Inter-Light.ttf') format('truetype'); }
    @font-face { font-family: 'Inter'; font-weight: 400; src: url('{{ $card->fontDir }}/Inter-Regular.ttf') format('truetype'); }
    @font-face { font-family: 'Inter'; font-weight: 500; src: url('{{ $card->fontDir }}/Inter-Medium.ttf') format('truetype'); }
    @font-face { font-family: 'Inter'; font-weight: 700; src: url('{{ $card->fontDir }}/Inter-Bold.ttf') format('truetype'); }
    @font-face { font-family: 'Roboto Mono'; font-weight: 500; src: url('{{ $card->fontDir }}/RobotoMono-Medium.ttf') format('truetype'); }

    @page { margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Inter', sans-serif; color: #333333; }

    .page { position: relative; width: 1484px; height: 1048px; overflow: hidden; page-break-after: always; }
    .page.last { page-break-after: auto; }
    /* The pattern: an opaque #F2F2F2 stencil with the strokes cut out, so the
       background colour underneath shows through them in this type's tint. */
    .ground { background-color: {{ $card->palette->pattern }}; background-image: url('{{ $card->stencilPath }}'); background-repeat: repeat; }
    .plain { background-color: #FFFFFF; }

    .abs { position: absolute; }
    /* Single lines: line-height equal to the size, so Layout::top() applies. */
    .line { white-space: nowrap; line-height: 1; }
    .center { text-align: center; }

    .bar { background-color: {{ $card->palette->accent }}; border-radius: 4px; }
    .bar-text { color: #F2F2F2; font-weight: 700; font-size: 36px; letter-spacing: 0.16em; }
    .id-text { font-family: 'Roboto Mono', monospace; font-weight: 500; color: #F2F2F7; }
</style>
