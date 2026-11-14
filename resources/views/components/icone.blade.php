@props(['nome', 'tamanho' => 20])
@php
$p = [
    'painel' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
    'haras' => '<path d="M3 21V9l9-6 9 6v12"/><path d="M9 21v-6h6v6"/><path d="M3 21h18"/>',
    'cavalo' => '<path d="M4 20c0-6 3-10 8-12l2-4 1 3c3 1 5 4 5 7l-2 1-3-2-2 3"/><path d="M8 20c1-3 3-5 5-6"/>',
    'usuarios' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
    'mensagem' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    'banco' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"/><path d="M3 12c0 1.7 4 3 9 3s9-1.3 9-3"/>',
    'historico' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
    'chave' => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/>',
    'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'mais' => '<path d="M12 5v14M5 12h14"/>',
    'sair' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    'site' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20"/>',
    'editar' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
    'busca' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    'check' => '<path d="M20 6 9 17l-5-5"/>',
    'seta' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
    'cadeado' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    'saude' => '<path d="M12 21s-7-4.4-9.3-9A5.4 5.4 0 0 1 12 6.6 5.4 5.4 0 0 1 21.3 12c-2.3 4.6-9.3 9-9.3 9z"/><path d="M12 10v5M9.5 12.5h5"/>',
    'manejo' => '<path d="M9 3h6l1 4H8z"/><path d="M8 7v11a3 3 0 0 0 3 3h2a3 3 0 0 0 3-3V7"/><path d="M8 12h8"/>',
    'nutricao' => '<path d="M12 21c4-2 7-6 7-11V5l-7-2-7 2v5c0 5 3 9 7 11z"/><path d="M12 8v8M9 11c1 1.5 2 2 3 2s2-.5 3-2"/>',
    'plano' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/>',
];
@endphp
<svg {{ $attributes->merge(['class' => 'icone']) }} width="{{ $tamanho }}" height="{{ $tamanho }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $p[$nome] ?? '' !!}</svg>
