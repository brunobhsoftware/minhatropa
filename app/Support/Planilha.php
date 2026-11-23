<?php

namespace App\Support;

use ZipArchive;

/**
 * Leitura e escrita de planilhas .xlsx sem dependência externa (só ZipArchive + XML nativo do PHP).
 *
 * O projeto não usa Composer no ambiente de nuvem (o proxy bloqueia downloads) e evita bibliotecas
 * pesadas, então esta classe cuida do mínimo que a importação/exportação da tropa precisa:
 *  - escrever um .xlsx de uma única aba, com cabeçalho em negrito e todas as células como texto
 *    (nada de número vira notação científica, "0001" continua "0001", datas ficam como digitadas);
 *  - ler um .xlsx (ou .csv) de volta para uma matriz de linhas (cada linha = vetor de strings por coluna).
 *
 * Só o que a tropa usa: uma aba, valores de texto. Fórmulas são lidas pelo resultado (quando houver).
 */
class Planilha
{
    /**
     * Gera um .xlsx num arquivo temporário e devolve o caminho dele.
     * $colunas: rótulos do cabeçalho (1ª linha). $linhas: matriz de linhas, cada uma um vetor de valores na ordem das colunas.
     */
    public static function escrever(array $colunas, array $linhas, string $nomeAba = 'Planilha'): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive;
        if ($zip->open($caminho, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não foi possível criar a planilha.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::relsRaiz());
        $zip->addFromString('xl/workbook.xml', self::workbook($nomeAba));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($colunas, $linhas));

        $zip->close();

        return $caminho;
    }

    /**
     * Lê um arquivo de planilha (.xlsx ou .csv) e devolve a matriz de linhas.
     * Cada linha é um vetor 0-indexado de strings; linhas totalmente vazias são descartadas no fim.
     */
    public static function ler(string $caminho): array
    {
        $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'txt') {
            return self::lerCsv($caminho);
        }

        return self::lerXlsx($caminho);
    }

    // ----------------------------------------------------------------------- leitura XLSX

    private static function lerXlsx(string $caminho): array
    {
        $zip = new ZipArchive;
        if ($zip->open($caminho) !== true) {
            throw new \RuntimeException('Arquivo .xlsx inválido ou danificado.');
        }

        $compartilhadas = self::stringsCompartilhadas($zip);
        $alvo = self::caminhoPrimeiraAba($zip);
        $xml = $zip->getFromName($alvo);
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('A planilha não tem nenhuma aba legível.');
        }

        $doc = self::carregarXml($xml);
        $linhas = [];
        foreach ($doc->getElementsByTagName('row') as $row) {
            $indiceLinha = ((int) $row->getAttribute('r')) - 1;
            if ($indiceLinha < 0) {
                $indiceLinha = count($linhas);
            }
            $celulas = [];
            $max = -1;
            foreach ($row->getElementsByTagName('c') as $c) {
                $col = self::colunaDaRef($c->getAttribute('r'));
                $celulas[$col] = self::valorDaCelula($c, $compartilhadas);
                $max = max($max, $col);
            }
            $linha = [];
            for ($i = 0; $i <= $max; $i++) {
                $linha[$i] = $celulas[$i] ?? '';
            }
            $linhas[$indiceLinha] = $linha;
        }

        ksort($linhas);

        return self::normalizar(array_values($linhas));
    }

    private static function stringsCompartilhadas(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $doc = self::carregarXml($xml);
        $lista = [];
        foreach ($doc->getElementsByTagName('si') as $si) {
            $texto = '';
            foreach ($si->getElementsByTagName('t') as $t) {
                $texto .= $t->nodeValue;
            }
            $lista[] = $texto;
        }

        return $lista;
    }

    /** Descobre o arquivo da primeira aba pelos rels do workbook (cai no padrão sheet1.xml). */
    private static function caminhoPrimeiraAba(ZipArchive $zip): string
    {
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $wb = $zip->getFromName('xl/workbook.xml');
        if ($rels !== false && $wb !== false) {
            $docWb = self::carregarXml($wb);
            $primeiro = $docWb->getElementsByTagName('sheet')->item(0);
            $rid = $primeiro?->getAttribute('r:id') ?: $primeiro?->getAttribute('id');
            if ($rid) {
                $docRels = self::carregarXml($rels);
                foreach ($docRels->getElementsByTagName('Relationship') as $r) {
                    if ($r->getAttribute('Id') === $rid) {
                        $alvo = ltrim($r->getAttribute('Target'), '/');

                        return str_starts_with($alvo, 'xl/') ? $alvo : 'xl/'.$alvo;
                    }
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function valorDaCelula(\DOMElement $c, array $compartilhadas): string
    {
        $tipo = $c->getAttribute('t');
        if ($tipo === 'inlineStr') {
            $texto = '';
            foreach ($c->getElementsByTagName('t') as $t) {
                $texto .= $t->nodeValue;
            }

            return trim($texto);
        }

        $v = $c->getElementsByTagName('v')->item(0);
        $bruto = $v ? $v->nodeValue : '';
        if ($tipo === 's') {
            $i = (int) $bruto;

            return trim($compartilhadas[$i] ?? '');
        }

        return trim((string) $bruto);
    }

    /** Converte a parte de letras de uma referência ("B2") no índice 0-indexado da coluna (B = 1). */
    private static function colunaDaRef(string $ref): int
    {
        if (! preg_match('/^([A-Z]+)/', strtoupper($ref), $m)) {
            return 0;
        }
        $letras = $m[1];
        $n = 0;
        $len = strlen($letras);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($letras[$i]) - 64);
        }

        return $n - 1;
    }

    // ----------------------------------------------------------------------- leitura CSV

    private static function lerCsv(string $caminho): array
    {
        $conteudo = file_get_contents($caminho);
        if ($conteudo === false) {
            throw new \RuntimeException('Não foi possível ler o arquivo CSV.');
        }
        // Excel em pt-BR costuma gravar em Windows-1252: converte para UTF-8 se não for UTF-8 válido.
        if (! mb_check_encoding($conteudo, 'UTF-8')) {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'Windows-1252');
        }
        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo); // remove BOM
        $primeiraLinha = strtok($conteudo, "\n");
        $delim = (substr_count($primeiraLinha, ';') >= substr_count($primeiraLinha, ',')) ? ';' : ',';

        $linhas = [];
        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $conteudo);
        rewind($fp);
        while (($campos = fgetcsv($fp, 0, $delim)) !== false) {
            $linhas[] = array_map(fn ($c) => trim((string) $c), $campos);
        }
        fclose($fp);

        return self::normalizar($linhas);
    }

    // ----------------------------------------------------------------------- escrita XLSX

    private static function sheet(array $colunas, array $linhas): string
    {
        $colunas = array_values($colunas);
        $sd = '';

        // Cabeçalho (estilo 1 = negrito sobre fundo escuro).
        $sd .= '<row r="1">';
        foreach ($colunas as $i => $titulo) {
            $sd .= self::celula(self::letraColuna($i).'1', (string) $titulo, 1);
        }
        $sd .= '</row>';

        // Dados (estilo 2 = texto).
        $r = 2;
        foreach ($linhas as $linha) {
            $sd .= '<row r="'.$r.'">';
            $i = 0;
            foreach ($colunas as $idx => $_) {
                $valor = $linha[$idx] ?? ($linha[$i] ?? '');
                $sd .= self::celula(self::letraColuna($i).$r, (string) $valor, 2);
                $i++;
            }
            $sd .= '</row>';
            $r++;
        }

        $colsXml = '<cols>';
        foreach ($colunas as $i => $_) {
            $colsXml .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="20" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A2" sqref="A2"/></sheetView></sheetViews>'
            .$colsXml
            .'<sheetData>'.$sd.'</sheetData>'
            .'</worksheet>';
    }

    private static function celula(string $ref, string $valor, int $estilo): string
    {
        $valor = self::limparTexto($valor);

        return '<c r="'.$ref.'" s="'.$estilo.'" t="inlineStr"><is><t xml:space="preserve">'
            .htmlspecialchars($valor, ENT_QUOTES | ENT_XML1, 'UTF-8')
            .'</t></is></c>';
    }

    /** Remove caracteres de controle que o OOXML não aceita (tudo abaixo de 0x20, menos tab/CR/LF). */
    private static function limparTexto(string $s): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s) ?? $s;
    }

    public static function letraColuna(int $i): string
    {
        $s = '';
        $i++;
        while ($i > 0) {
            $i--;
            $s = chr(65 + ($i % 26)).$s;
            $i = intdiv($i, 26);
        }

        return $s;
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function relsRaiz(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(string $nomeAba): string
    {
        $nome = htmlspecialchars(self::nomeAbaValido($nomeAba), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$nome.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    /** Nome de aba do Excel: até 31 caracteres e sem : \ / ? * [ ] */
    private static function nomeAbaValido(string $nome): string
    {
        $nome = preg_replace('/[:\\\\\/?*\[\]]/', ' ', $nome) ?: 'Planilha';

        return mb_substr(trim($nome), 0, 31) ?: 'Planilha';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function styles(): string
    {
        // Estilo 0: normal. Estilo 1: cabeçalho (negrito, texto branco, fundo azul do kit). Estilo 2: texto (@).
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="@"/></numFmts>'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF101828"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="164" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private static function carregarXml(string $xml): \DOMDocument
    {
        $doc = new \DOMDocument;
        $anterior = libxml_use_internal_errors(true);
        $doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        return $doc;
    }

    /** Descarta linhas totalmente vazias no fim e garante vetores 0-indexados. */
    private static function normalizar(array $linhas): array
    {
        $linhas = array_map('array_values', $linhas);
        while (! empty($linhas)) {
            $ultima = end($linhas);
            if (count(array_filter($ultima, fn ($v) => trim((string) $v) !== '')) > 0) {
                break;
            }
            array_pop($linhas);
        }

        return $linhas;
    }
}
