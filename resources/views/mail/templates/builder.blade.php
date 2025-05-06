@foreach($components as $component)
    @php
        // Komponentin stilini hazırlayırıq
        $style = collect($component['style'] ?? [])
            ->map(fn($value, $key) => "{$key}: {$value}")
            ->implode('; ');

        // Komponentin content-ini hazırlayırıq
        $content = $component['content'] ?? '';
    @endphp

    {{-- Komponentin tipinə görə render edirik --}}
    @switch($component['type'])
        @case('header')
            <div class="header-component" style="{{ $style }}">
                <h1 style="margin: 0; font-size: 24px; font-weight: 600;">
                    {!! $content !!}
                </h1>
            </div>
            @break

        @case('text')
            <div class="text-component" style="{{ $style }}">
                <p style="margin: 0;">
                    {!! $content !!}
                </p>
            </div>
            @break

        @case('image')
            <div class="image-component" style="{{ $style }}">
                <img src="{{ $component['src'] ?? '' }}"
                     alt="{{ $component['alt'] ?? '' }}"
                     style="width: 100%; height: auto;">
            </div>
            @break

        @case('button')
            <div class="button-component" style="text-align: center; margin: 24px 0;">
                <a href="{{ $component['url'] ?? '#' }}"
                   style="display: inline-block; padding: 12px 24px;
                          background-color: #007bff; color: white;
                          text-decoration: none; border-radius: 4px;
                          {{ $style }}">
                    {!! $content !!}
                </a>
            </div>
            @break

        @case('divider')
            <hr style="border: 0; border-top: 1px solid #e9ecef; margin: 24px 0; {{ $style }}">
            @break

        @case('spacer')
            <div style="height: {{ $component['height'] ?? '24px' }}"></div>
            @break

        @default
            <div style="{{ $style }}">
                {!! $content !!}
            </div>
    @endswitch
@endforeach
