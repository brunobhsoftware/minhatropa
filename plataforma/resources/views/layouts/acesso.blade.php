@extends('layouts.base')
@section('classe_body', 'pagina-acesso')
@section('corpo')
<main class="acesso">
    <div class="acesso__caixa @yield('largura')">
        <a href="{{ route('inicio') }}" class="acesso__marca"><img src="{{ asset('img/logo-minhatropa.png') }}" alt="MinhaTropa — Gestão inteligente para Haras" width="260" height="58"></a>
        @include('layouts.alertas')
        @yield('conteudo')
    </div>
    <p class="acesso__rodape"><a href="{{ route('inicio') }}">← Voltar ao site</a></p>
</main>
@endsection
