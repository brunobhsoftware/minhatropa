{{-- Topo dos cadastros: a pessoa escolhe se cadastra um Haras / Fazenda ou se cadastra como profissional. --}}
<nav class="escolha-cadastro" aria-label="O que você quer cadastrar?">
    <a href="{{ route('cadastro') }}" class="{{ $atual === 'propriedade' ? 'ativo' : '' }}" @if ($atual === 'propriedade') aria-current="page" @endif><b>Haras / Fazenda</b><small>Cadastrar a propriedade</small></a>
    <a href="{{ route('cadastro.profissional') }}" class="{{ $atual === 'profissional' ? 'ativo' : '' }}" @if ($atual === 'profissional') aria-current="page" @endif><b>Profissional</b><small>Veterinário, gerente, treinador, tratador…</small></a>
</nav>
