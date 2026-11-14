{{--
    Seleção em cascata UF → Município (tabelas do IBGE). Não existe campo de texto livre para cidade/estado.
    Uso: <x-uf-municipio :uf="$v('uf')" :municipio-id="$v('municipio_id')" :cidade="$v('cidade')" obrigatorio />
    prefixo="corresp_" gera corresp_uf e corresp_municipio_id. Sem JavaScript o município não pode ser escolhido.
--}}
@props(['prefixo' => '', 'uf' => null, 'municipioId' => null, 'cidade' => null, 'obrigatorio' => false, 'rotuloUf' => 'UF', 'rotuloMunicipio' => 'Município', 'filtro' => false])
@php
    $uf = old($prefixo.'uf', $uf);
    $municipioId = old($prefixo.'municipio_id', $municipioId);
    $idCampo = 'geo-'.($prefixo ?: 'p').'-'.substr(md5($prefixo.spl_object_id($attributes)), 0, 6);
    $estados = \App\Support\Esquema::temV007() ? \App\Support\Geografia::estados() : collect();
@endphp
<div class="geo" data-geo data-geo-url="{{ url('/geo/municipios') }}" data-geo-municipio="{{ $municipioId }}">
    <label class="campo"><span>{{ $rotuloUf }}{{ $obrigatorio ? ' *' : '' }}</span>
        <select name="{{ $prefixo }}uf" data-geo-uf @if ($obrigatorio) required @endif>
            <option value="">{{ $filtro ? 'Todas as UFs' : 'Selecione' }}</option>
            @foreach ($estados as $e)<option value="{{ $e->sigla_uf }}" @selected($uf === $e->sigla_uf)>{{ $e->sigla_uf }} — {{ $e->nome }}</option>@endforeach
        </select>
    </label>
    <div class="campo geo__municipio"><span id="{{ $idCampo }}-r">{{ $rotuloMunicipio }}{{ $obrigatorio ? ' *' : '' }}</span>
        <input type="text" data-geo-busca role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="{{ $idCampo }}-l" aria-labelledby="{{ $idCampo }}-r"
            autocomplete="off" spellcheck="false" value="{{ $municipioId ? $cidade : '' }}" placeholder="{{ $filtro ? 'Todos os municípios' : 'Selecione a UF primeiro' }}" disabled>
        <input type="hidden" name="{{ $prefixo }}municipio_id" data-geo-id value="{{ $municipioId }}">
        <ul class="geo__lista" id="{{ $idCampo }}-l" role="listbox" hidden></ul>
    </div>
</div>
