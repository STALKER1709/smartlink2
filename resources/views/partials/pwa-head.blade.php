{{-- Installation sur l'écran d'accueil.

     iOS ne lit pas le manifeste : la couleur de la barre, le nom sous l'icône
     et l'icône elle-même y passent par les balises `apple-*`. Les deux jeux
     sont donc nécessaires, ils ne se remplacent pas. --}}
<link rel="manifest" href="{{ route('manifest') }}">
<meta name="theme-color" content="#005f48">

<link rel="icon" href="{{ asset('images/icone-192.png') }}" sizes="192x192" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('images/icone-apple-180.png') }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'SmartLink') }}">
