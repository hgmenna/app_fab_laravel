<!doctype html><html lang="es">
<head>@include('partials.mobile-pwa-head')<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cuenta FAB</title><link rel="stylesheet" href="{{ asset('fab-mobile/app.css') }}"></head>
<body>@include('partials.mobile-header')<main class="wrap registration-screen">
    <a class="back" href="{{ route('mobile-app') }}">‹ Calendario</a>
    @if ($user)
        <h1>Mi cuenta</h1><p>{{ $user->name }}</p>
        <form method="post" action="{{ route('mobile-account.logout') }}">@csrf<button type="submit" class="icon-button" aria-label="Cerrar sesión" title="Cerrar sesión"><x-fab-icon name="signout" /></button></form>
    @else
        <h1>Ingresar a FAB</h1>
        @if (!empty($localPreview))
            <p class="muted">Vista previa. El ingreso se habilitará al publicar la app en el servidor de FAB.</p>
        @endif
        @if ($errors->any())<p class="registration-errors" role="alert">{{ $errors->first() }}</p>@endif
        <form class="account-form" method="post" action="{{ route('mobile-account.login') }}">
            @csrf
            <label>Usuario<input name="name" required autocomplete="username" value="{{ old('name') }}" @disabled(!empty($localPreview))></label>
            <label>Contraseña<input name="password" type="password" required autocomplete="current-password" @disabled(!empty($localPreview))></label>
            <button type="submit" @disabled(!empty($localPreview)) class="icon-button" aria-label="Ingresar" title="Ingresar"><x-fab-icon name="signin" /></button>
        </form>
    @endif
</main>@include('partials.mobile-footer')
</body></html>
