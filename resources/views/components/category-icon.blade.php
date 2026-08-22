@props(['icon'])

@php
    // Les catégories stockent des slugs façon Heroicons (voir ServiceCategorySeeder) ;
    // on les fait correspondre aux ligatures Material Symbols utilisées par la charte.
    $map = [
        'wrench' => 'plumbing',
        'bolt' => 'bolt',
        'building-office' => 'foundation',
        'hammer' => 'carpenter',
        'paint-brush' => 'format_paint',
        'squares-2x2' => 'grid_view',
        'fire' => 'local_fire_department',
        'sparkles' => 'auto_awesome',
        'leaf' => 'yard',
        'shield-check' => 'shield',
        'lightning-bolt' => 'electric_bolt',
        'sun' => 'ac_unit',
        'truck' => 'local_shipping',
        'beaker' => 'water_drop',
        'car' => 'directions_car',
        'scissors' => 'content_cut',
        'needle' => 'checkroom',
        'hand' => 'spa',
        'computer-desktop' => 'computer',
        'device-mobile' => 'smartphone',
        'tv' => 'tv',
        'cake' => 'cake',
        'shopping-bag' => 'shopping_bag',
        'camera' => 'photo_camera',
        'musical-note' => 'music_note',
        'book-open' => 'menu_book',
        'user-group' => 'diversity_3',
        'home' => 'home_repair_service',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'material-symbols-outlined']) }}>{{ $map[$icon] ?? 'handyman' }}</span>
