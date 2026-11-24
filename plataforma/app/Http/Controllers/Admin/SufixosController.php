<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Auditoria;
use App\Support\Sufixo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SufixosController extends Controller
{
    public function index()
    {
        $total = DB::table('sufixo_associacao')->count();
        $ultimos = DB::table('sufixo_associacao')->orderByDesc('id')->limit(20)->get();

        return view('admin.sufixos', ['total' => $total, 'ultimos' => $ultimos]);
    }

    public function importar(Request $request)
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'max:51200'],
        ], [
            'arquivo.required' => 'Envie o arquivo com a lista de sufixos.',
            'arquivo.max' => 'Arquivo muito grande (máximo 50 MB).',
        ]);

        $arquivo = $request->file('arquivo');
        $ext = strtolower($arquivo->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt'], true)) {
            return back()->withErrors(['arquivo' => 'Use um arquivo .csv ou .txt com os dados separados por tabulação ou ponto-e-vírgula.']);
        }

        $conteudo = file_get_contents($arquivo->getRealPath());
        if (! mb_check_encoding($conteudo, 'UTF-8')) {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'Windows-1252');
        }
        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo);
        $linhasTexto = array_filter(array_map('trim', explode("\n", str_replace("\r", '', $conteudo))));

        $cabecalho = null;
        $linhas = [];
        foreach ($linhasTexto as $lt) {
            $cols = str_contains($lt, "\t") ? explode("\t", $lt) : explode(';', $lt);
            $cols = array_map('trim', $cols);

            if (! $cabecalho) {
                $cabecalho = array_map('mb_strtolower', $cols);
                continue;
            }

            $registro = [];
            foreach ($cabecalho as $i => $col) {
                $registro[$col] = $cols[$i] ?? '';
            }

            $mapa = [
                'codigo' => $registro['codigo'] ?? $registro['código'] ?? $registro['cod'] ?? '',
                'sufixo' => $registro['sufixo'] ?? $registro['nome_haras'] ?? $registro['nome do haras'] ?? '',
                'proprietario_nome' => $registro['proprietario'] ?? $registro['proprietário'] ?? $registro['proprietario_nome'] ?? $registro['nome_proprietario'] ?? $registro['nome completo proprietario'] ?? '',
                'proprietario_documento' => $registro['cpf'] ?? $registro['documento'] ?? $registro['proprietario_documento'] ?? '',
                'proprietario_celular' => $registro['celular'] ?? $registro['proprietario_celular'] ?? '',
                'proprietario_telefone' => $registro['telefone'] ?? $registro['proprietario_telefone'] ?? '',
                'uf' => $registro['uf'] ?? $registro['estado'] ?? '',
                'cidade' => $registro['cidade'] ?? $registro['municipio'] ?? $registro['município'] ?? '',
                'cep' => $registro['cep'] ?? '',
            ];

            if ($mapa['codigo'] || $mapa['sufixo']) {
                $linhas[] = $mapa;
            }
        }

        if (! $linhas) {
            return back()->withErrors(['arquivo' => 'Nenhum registro encontrado. Confira o cabeçalho: codigo;sufixo;proprietario;uf;cidade;cep;celular;telefone']);
        }

        $n = Sufixo::importarLote($linhas);
        Auditoria::registrar('sufixos.importar', null, null, ['total' => $n, 'arquivo' => $arquivo->getClientOriginalName()]);

        return back()->with('ok', "{$n} sufixo(s) importado(s) com sucesso.");
    }
}
