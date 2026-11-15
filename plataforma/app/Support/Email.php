<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envio de e-mails do MinhaTropa.
 *
 * Duas contas SMTP da Hostinger (config/mail.php):
 *   - atendimento() → atendimento@minhatropa.com.br: cadastro, senha, operações com o usuário.
 *   - aviso()       → noreply@minhatropa.com.br: avisos que não esperam resposta.
 *
 * Toda mensagem passa pela tabela mensagem_envio: grava destinatário, assunto e o corpo renderizado,
 * depois o resultado (enviado / erro). O envio acontece depois da resposta HTTP (dispatch afterResponse).
 */
class Email
{
    public static function atendimento(string $tipo, string $para, string $assunto, string $html, string $texto, array $refs = []): void
    {
        self::enfileirar('atendimento', $tipo, $para, $assunto, $html, $texto, $refs);
    }

    public static function aviso(string $tipo, string $para, string $assunto, string $html, string $texto, array $refs = []): void
    {
        self::enfileirar('noreply', $tipo, $para, $assunto, $html, $texto, $refs);
    }

    public static function layout(string $titulo, string $blocos, ?string $botaoTexto = null, ?string $botaoUrl = null, ?string $rodape = null): string
    {
        return view('emails.layout', compact('titulo', 'blocos', 'botaoTexto', 'botaoUrl', 'rodape'))->render();
    }

    private static function enfileirar(string $conta, string $tipo, string $para, string $assunto, string $html, string $texto, array $refs): void
    {
        $id = DB::table('mensagem_envio')->insertGetId([
            'empresa_id' => $refs['empresa_id'] ?? null,
            'tipo' => mb_substr($tipo, 0, 40),
            'canal' => 'email',
            'destinatario' => mb_substr($para, 0, 150),
            'assunto' => mb_substr($assunto, 0, 150),
            'corpo_renderizado' => $html,
            'status' => 'pendente',
            'prioridade' => $refs['prioridade'] ?? 1,
        ]);

        if (! empty($refs['ocultar'])) {
            DB::table('mensagem_envio')->where('id', $id)->update([
                'corpo_renderizado' => str_replace($refs['ocultar'], '[removido por segurança]', $html),
            ]);
        }

        $anexos = array_map(fn ($a) => ['nome' => $a['nome'], 'mime' => $a['mime'], 'b64' => base64_encode($a['conteudo'])], $refs['anexos'] ?? []);
        $cc = array_values(array_filter((array) ($refs['cc'] ?? []), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL) && mb_strtolower($e) !== mb_strtolower($para)));
        dispatch(fn () => self::enviar($id, $conta, $para, $assunto, $html, $texto, $anexos, $cc))->afterResponse();
    }

    private static function enviar(int $id, string $conta, string $para, string $assunto, string $html, string $texto, array $anexos = [], array $cc = []): void
    {
        $mailer = config("mail.mailers.$conta.password") ? $conta : 'log';
        $remetente = config("mail.mailers.$conta.username") ?: "$conta@minhatropa.com.br";

        DB::table('mensagem_envio')->where('id', $id)->update(['status' => 'enviando', 'tentativas' => DB::raw('tentativas + 1')]);
        try {
            $enviada = Mail::mailer($mailer)->html($html, function ($m) use ($para, $assunto, $remetente, $texto, $conta, $anexos, $cc) {
                $m->to($para)->from($remetente, 'MinhaTropa')->subject($assunto)->text($texto);
                if ($cc) {
                    $m->cc($cc);
                }
                foreach ($anexos as $a) {
                    $m->attachData(base64_decode($a['b64']), $a['nome'], ['mime' => $a['mime']]);
                }
                if ($conta === 'noreply') {
                    $m->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
                }
            });
            DB::table('mensagem_envio')->where('id', $id)->update([
                'status' => 'enviado', 'enviado_em' => now(), 'erro' => null,
                'provedor_msg_id' => $enviada?->getMessageId() ? mb_substr($enviada->getMessageId(), 0, 150) : null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Falha no envio de e-mail', ['mensagem_envio_id' => $id, 'erro' => $e->getMessage()]);
            DB::table('mensagem_envio')->where('id', $id)->update(['status' => 'erro', 'erro' => mb_substr($e->getMessage(), 0, 500)]);
        }
    }
}
