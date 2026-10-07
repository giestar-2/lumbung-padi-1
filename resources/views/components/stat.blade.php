@props(['label', 'value', 'note' => '', 'icon' => 'wallet', 'featured' => false])
<article {{ $attributes->class(['panel stat-card']) }}>
    <div class="flex min-h-10 items-center justify-between gap-2"><h2 class="stat-label">{{ $label }}</h2><span class="stat-icon"><x-icon :name="$icon"/></span></div>
    <p class="stat-value">{{ $value }}</p>
    @if($note)<p class="metric-note">{{ $note }}</p>@endif
</article>
