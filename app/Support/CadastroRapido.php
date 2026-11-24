<?php

namespace App\Support;

class CadastroRapido
{
    /**
     * Lê uma string colada (formato da ficha de animal da ABCCMM ou similar) e extrai os dados.
     * Retorna um array com as chaves: animal, pai, mae, criador, proprietario.
     */
    public static function interpretar(string $texto): array
    {
        $linhas = array_map('trim', explode("\n", str_replace("\r", '', $texto)));
        $plano = implode("\t", $linhas);
        $campos = array_map('trim', preg_split('/\t+/', $plano));

        $dados = [
            'animal' => [],
            'pai' => [],
            'mae' => [],
            'criador' => [],
            'proprietario' => [],
        ];

        $dados['animal'] = self::extrairAnimal($campos, $texto);
        $dados['pai'] = self::extrairGenitor($campos, $texto, 'pai');
        $dados['mae'] = self::extrairGenitor($campos, $texto, 'mãe');
        $dados['criador'] = self::extrairPessoa($campos, $texto, 'criador');
        $dados['proprietario'] = self::extrairPessoa($campos, $texto, 'proprietário');

        return $dados;
    }

    private static function extrairAnimal(array $campos, string $texto): array
    {
        $dados = [];

        // Sexo
        if (preg_match('/Sexo:\s*(Macho|Fêmea|Femea)/iu', $texto, $m)) {
            $dados['sexo'] = mb_strtolower($m[1]) === 'macho' ? 'macho' : 'femea';
        }

        // Nascimento
        if (preg_match('/Nasc\.?:\s*(\d{2}\/\d{2}\/\d{4})/u', $texto, $m)) {
            $dados['data_nascimento'] = self::parseData($m[1]);
        }

        // Idade
        if (preg_match('/Idade:\s*(.+?)(?:\t|ID Animal)/u', $texto, $m)) {
            $dados['idade_texto'] = trim($m[1]);
        }

        // ID Animal
        if (preg_match('/ID Animal:\s*(\d+)/u', $texto, $m)) {
            $dados['id_associacao'] = $m[1];
        }

        // Pelagem
        if (preg_match('/Pelagem:\s*([A-ZÁÉÍÓÚÂÊÃÕÇ\-\s]+)/iu', $texto, $m)) {
            $dados['pelagem'] = self::normalizarPelagem(trim($m[1]));
            $dados['pelagem_texto'] = trim($m[1]);
        }

        // Registrado
        if (preg_match('/Registrado:\s*(Sim|Não)/iu', $texto, $m)) {
            $dados['registro_definitivo'] = mb_strtolower($m[1]) === 'sim';
        }

        // Vivo
        if (preg_match('/Vivo:\s*(Sim|Não)/iu', $texto, $m)) {
            $dados['vivo'] = mb_strtolower($m[1]) === 'sim';
        }

        // Bloqueado
        if (preg_match('/Bloqueado:\s*(Sim|Não)/iu', $texto, $m)) {
            $dados['bloqueado'] = mb_strtolower($m[1]) === 'não';
        }

        return $dados;
    }

    private static function extrairGenitor(array $campos, string $texto, string $tipo): array
    {
        $dados = [];
        $rotulo = $tipo === 'pai' ? 'Dados do pai' : 'Dados da mãe';

        if (! preg_match('/' . preg_quote($rotulo, '/') . '\s*\n(.+?)(?=Dados d[ao]|$)/si', $texto, $bloco)) {
            return $dados;
        }
        $b = $bloco[1];

        if (preg_match('/Nome:\s*([^\t\n]+)/u', $b, $m)) {
            $dados['nome'] = Texto::maiusculas(trim($m[1]));
        }
        if (preg_match('/Registro:\s*(\S+)/u', $b, $m)) {
            $dados['registro'] = trim($m[1]);
        }

        return $dados;
    }

    private static function extrairPessoa(array $campos, string $texto, string $tipo): array
    {
        $dados = [];
        $rotulo = $tipo === 'criador' ? 'Dados do criador' : 'Dados do proprietário';

        if (! preg_match('/' . preg_quote($rotulo, '/') . '\s*\n(.+?)(?=Dados d[ao]|$)/si', $texto, $bloco)) {
            return $dados;
        }
        $b = $bloco[1];

        if (preg_match('/Associado:\s*(\S+)\s*-\s*(.+?)(?:\n|\t)/u', $b, $m)) {
            $dados['codigo'] = trim($m[1]);
            $dados['nome'] = Texto::maiusculas(trim($m[2]));
        }
        if (preg_match('/Categoria:\s*([^\t\n]+)/u', $b, $m)) {
            $dados['categoria'] = trim($m[1]);
        }
        if (preg_match('/Propriedade:\s*([^\t\n]+?)(?:\s+Cidade:)/u', $b, $m)) {
            $dados['propriedade'] = trim($m[1]);
        }
        if (preg_match('/Cidade:\s*([^\t\n]+)/u', $b, $m)) {
            $partes = explode('-', trim($m[1]));
            $dados['cidade'] = trim($partes[0]);
            if (isset($partes[1])) {
                $dados['uf'] = strtoupper(trim($partes[1]));
            }
        }

        return $dados;
    }

    private static function parseData(string $data): ?string
    {
        $p = explode('/', $data);
        if (count($p) !== 3) {
            return null;
        }

        return "{$p[2]}-{$p[1]}-{$p[0]}";
    }

    public static function normalizarPelagem(string $pelagem): string
    {
        $mapa = [
            'tordilha' => 'tordilha',
            'castanha' => 'castanha',
            'castanha-escura' => 'castanha_escura',
            'alazã' => 'alaza',
            'alazã-queimada' => 'alaza_queimada',
            'baia' => 'baia',
            'baia-amarilha' => 'baia_amarilha',
            'baia-cereja' => 'baia_cereja',
            'baia-escura' => 'baia_escura',
            'branca' => 'branca',
            'lobuna' => 'lobuna',
            'preta' => 'preta',
            'rosilha' => 'rosilha',
            'ruça' => 'ruca',
            'zaina' => 'zaina',
        ];

        $p = mb_strtolower(trim($pelagem));

        return $mapa[$p] ?? 'outra';
    }

    public static function calcularIdade(?string $dataNascimento): string
    {
        if (! $dataNascimento) {
            return '—';
        }
        try {
            $nasc = new \DateTime($dataNascimento);
        } catch (\Throwable) {
            return '—';
        }
        $diff = $nasc->diff(new \DateTime());
        $partes = [];
        if ($diff->y > 0) {
            $partes[] = $diff->y . ' ano' . ($diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            $partes[] = $diff->m . ' ' . ($diff->m === 1 ? 'mês' : 'meses');
        }
        if ($diff->d > 0 && $diff->y === 0) {
            $partes[] = $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
        }

        return implode(' e ', $partes) ?: 'recém-nascido';
    }
}
