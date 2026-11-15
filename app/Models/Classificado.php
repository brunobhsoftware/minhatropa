<?php

namespace App\Models;

use App\Models\Concerns\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Anúncio de animal à venda (Comércio → Classificados). Aparece no site enquanto está no ar. */
class Classificado extends Model
{
    use TemUuid;

    protected $table = 'classificado';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    public const ESTADOS = ['no_ar' => 'No ar', 'agendado' => 'Agendado', 'vencido' => 'Prazo encerrado', 'vendido' => 'Vendido', 'encerrado' => 'Retirado'];

    protected $guarded = ['id'];

    protected $casts = ['inicio' => 'date', 'fim' => 'date', 'preco' => 'decimal:2', 'encerrado_em' => 'datetime', 'excluido_em' => 'datetime'];

    /** Dia de hoje no fuso da tela (o período do anúncio é em dias do calendário do Brasil). */
    public static function hoje(): string
    {
        return now('America/Sao_Paulo')->toDateString();
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /** Anúncios abertos: ainda não vendidos nem retirados, e com prazo que não venceu (inclui os agendados). */
    public function scopeAbertos(Builder $q): Builder
    {
        return $q->whereNull('classificado.excluido_em')->where('classificado.situacao', 'ativo')
            ->where(fn ($p) => $p->whereNull('classificado.fim')->orWhere('classificado.fim', '>=', self::hoje()));
    }

    /** Anúncios no ar hoje (o que o site mostra). */
    public function scopeNoAr(Builder $q): Builder
    {
        return $q->abertos()->where('classificado.inicio', '<=', self::hoje());
    }

    public function estado(): string
    {
        $situacao = $this->situacao ?? 'ativo';
        if ($situacao !== 'ativo') {
            return $situacao;
        }
        $hoje = self::hoje();
        if ($this->fim && $this->fim->toDateString() < $hoje) {
            return 'vencido';
        }

        return $this->inicio->toDateString() > $hoje ? 'agendado' : 'no_ar';
    }

    public function estadoTexto(): string
    {
        return self::ESTADOS[$this->estado()];
    }

    public function periodoTexto(): string
    {
        return 'de '.$this->inicio->format('d/m/Y').($this->fim ? ' até '.$this->fim->format('d/m/Y') : ' · sem prazo');
    }
}
