@props(['title' => 'Beranda', 'header' => null])

@include('layouts.app', ['title' => $title, 'header' => $header ?? $title, 'slot' => $slot])
