<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    use TemUuid;

    protected $table = 'usuario';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    /** A tabela não tem remember_token: "lembrar de mim" fica desligado. */
    protected $rememberTokenName = '';

    protected $fillable = ['nome', 'email', 'telefone', 'senha_hash', 'admin_plataforma', 'ativo'];

    protected $hidden = ['senha_hash'];

    protected function casts(): array
    {
        return [
            'bloqueado_ate' => 'datetime',
            'ultimo_login_em' => 'datetime',
            'ativo' => 'boolean',
            'admin_plataforma' => 'boolean',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'senha_hash';
    }

    public function getAuthPassword(): ?string
    {
        return $this->senha_hash;
    }

    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'empresa_usuario')->withPivot(['id', 'papel', 'status']);
    }

    public function primeiroNome(): string
    {
        return strtok($this->nome, ' ') ?: $this->nome;
    }
}
