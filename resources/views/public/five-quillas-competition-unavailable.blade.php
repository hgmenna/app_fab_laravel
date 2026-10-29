<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="15">
    <meta name="robots" content="noindex">
    <title>{{ $competition->tournament?->name }} · Resultados</title>
    <style>html,body{margin:0;min-height:100%;background:#071421}body{min-height:100vh}.availability-screen{min-height:100vh!important}</style>
</head>
<body>
@include('public.partials.competition-availability')
</body>
</html>
