@php
    /** @var string $content */
    /** @var string $toc_html */
    /** @var string $toc_position */
    /** @var array $front_matter */
@endphp

@if($toc_position === 'top' && $toc_html)
    <nav class="fp-toc-top">{!! $toc_html !!}</nav>
    <div>{!! $content !!}</div>
@elseif($toc_position === 'left' && $toc_html)
    <div class="fp-toc-wrapper">
        <nav class="fp-toc-sidebar">{!! $toc_html !!}</nav>
        <div class="fp-toc-content">{!! $content !!}</div>
    </div>
@elseif($toc_position === 'right' && $toc_html)
    <div class="fp-toc-wrapper">
        <div class="fp-toc-content">{!! $content !!}</div>
        <nav class="fp-toc-sidebar">{!! $toc_html !!}</nav>
    </div>
@else
    <div>{!! $content !!}</div>
@endif
