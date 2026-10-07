@props(['name' => 'grid'])
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('grid') <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/> @break
@case('box') <path d="m12 3 9 5v8l-9 5-9-5V8l9-5Zm0 9 9-4M12 12 3 8m9 4v9M7.5 5.5l9 5"/> @break
@case('leaf') <path d="M20 4C8 2 2 9 6 16s16 3 14-12ZM4 21l11-11M9 16v-5m0 5h5"/> @break
@case('sales') <path d="M4 4h2l2 12h10l3-8H7M10 20h.01M18 20h.01"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/> @break
@case('users') <circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5"/> @break
@case('wallet') <rect x="3" y="6" width="18" height="14" rx="2"/><path d="m5 6 12-3v3m4 6h-6v4h6M16 14h.01"/> @break
@case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
@case('settings') <path d="M4 6h16M4 12h16M4 18h16"/><circle cx="9" cy="6" r="2" fill="currentColor"/><circle cx="16" cy="12" r="2" fill="currentColor"/><circle cx="8" cy="18" r="2" fill="currentColor"/> @break
@case('search') <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/> @break
@case('plus') <path d="M12 5v14M5 12h14"/> @break
@case('arrow') <path d="M5 12h14m-5-5 5 5-5 5"/> @break
@case('up') <path d="m5 16 6-6 4 4 6-9m-6 0h6v6"/> @break
@case('down') <path d="m5 8 6 6 4-4 6 9m-6 0h6v-6"/> @break
@case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
@case('close') <path d="m6 6 12 12M6 18 18 6"/> @break
@case('trash') <path d="M3 6h18M9 6V4h6v2M5 6l1 14h12l1-14M10 10v6M14 10v6"/> @break
@case('logout') <path d="M9 4H4v16h5m5-12 4 4-4 4m-6-4h13"/> @break
@case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 11h18"/> @break
@case('check') <path d="m5 12 4 4L19 6"/> @break
@default <circle cx="12" cy="12" r="8"/><path d="M12 8v4m0 4h.01"/>
@endswitch
</svg>
