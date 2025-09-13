<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Memebresia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div id="app">
    <membership-payment></membership-payment>
</div>
</body>
</html>

