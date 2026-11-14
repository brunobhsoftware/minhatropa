@if (session('ok'))<div class="alerta alerta--ok" role="status"><p>{{ session('ok') }}</p></div>@endif
@if (session('aviso'))<div class="alerta alerta--aviso" role="status"><p>{{ session('aviso') }}</p></div>@endif
@if ($errors->any())
<div class="alerta alerta--erro" role="alert">
    @if ($errors->count() === 1)<p>{{ $errors->first() }}</p>@else<p>Confira os campos:</p><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
</div>
@endif
