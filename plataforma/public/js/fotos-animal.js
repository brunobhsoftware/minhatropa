// Fotos do animal: ao escolher a imagem, o navegador recorta o centro em retrato 3:4, reduz para o tamanho padrão
// e compacta em JPEG antes de enviar. Assim a foto do celular (de vários MB) vira um arquivo pequeno já na origem.
// O servidor refaz o mesmo tratamento ao receber (App\Support\FotosAnimal).
(function () {
    var caixa = document.getElementById('fotosAnimal'); if (!caixa) return;
    var L = parseInt(caixa.dataset.largura, 10) || 900, A = parseInt(caixa.dataset.altura, 10) || 1200;
    var kb = function (b) { return b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; };

    function carregar(arquivo) {
        // createImageBitmap já aplica a rotação gravada pela câmera; o <img> fica de reserva.
        if (window.createImageBitmap) {
            return createImageBitmap(arquivo, { imageOrientation: 'from-image' }).catch(function () { return viaImg(arquivo); });
        }
        return viaImg(arquivo);
    }
    function viaImg(arquivo) {
        return new Promise(function (ok, falha) {
            var url = URL.createObjectURL(arquivo), img = new Image();
            img.onload = function () { URL.revokeObjectURL(url); ok(img); };
            img.onerror = function () { URL.revokeObjectURL(url); falha(new Error('imagem')); };
            img.src = url;
        });
    }
    function tratar(arquivo) {
        return carregar(arquivo).then(function (img) {
            var w = img.width, h = img.height, p = L / A, cw, ch;
            if (w / h > p) { ch = h; cw = Math.round(h * p); } else { cw = w; ch = Math.round(w / p); }
            var tela = document.createElement('canvas'); tela.width = L; tela.height = A;
            var c = tela.getContext('2d'); c.fillStyle = '#fff'; c.fillRect(0, 0, L, A);
            c.imageSmoothingQuality = 'high';
            c.drawImage(img, Math.round((w - cw) / 2), Math.round((h - ch) / 2), cw, ch, 0, 0, L, A);
            return new Promise(function (ok, falha) {
                tela.toBlob(function (b) { b ? ok(b) : falha(new Error('compactar')); }, 'image/jpeg', 0.82);
            });
        });
    }

    caixa.querySelectorAll('[data-foto]').forEach(function (bloco) {
        var campo = bloco.querySelector('[data-foto-arquivo]'), previa = bloco.querySelector('[data-foto-previa]'),
            vazio = bloco.querySelector('[data-foto-vazio]'), aviso = bloco.querySelector('[data-foto-aviso]'),
            remover = bloco.querySelector('[data-foto-remover]');
        campo.addEventListener('change', function () {
            var arquivo = campo.files && campo.files[0]; if (!arquivo) return;
            if (!/^image\/(jpeg|png|webp)$/.test(arquivo.type)) { campo.value = ''; aviso.textContent = 'Escolha uma imagem JPG, PNG ou WebP.'; return; }
            bloco.dataset.pronto = '0'; aviso.textContent = 'Ajustando a foto…';
            tratar(arquivo).then(function (blob) {
                try {
                    var dt = new DataTransfer();
                    dt.items.add(new File([blob], 'foto-' + bloco.dataset.foto + '.jpg', { type: 'image/jpeg' }));
                    campo.files = dt.files;
                    aviso.textContent = 'Pronta: ' + kb(blob.size) + ' (original ' + kb(arquivo.size) + ').';
                } catch (e) {
                    aviso.textContent = 'A foto vai ser ajustada ao salvar.'; // navegador antigo: o servidor trata
                }
                previa.src = URL.createObjectURL(blob); previa.hidden = false; if (vazio) vazio.hidden = true;
                if (remover) { remover.checked = false; bloco.classList.remove('foto-animal--sai'); }
                bloco.dataset.pronto = '1';
            }).catch(function () {
                campo.value = ''; bloco.dataset.pronto = '1';
                aviso.textContent = 'Não foi possível ler essa imagem. Tente outra foto.';
            });
        });
        if (remover) remover.addEventListener('change', function () { bloco.classList.toggle('foto-animal--sai', remover.checked); });
    });

    // Não deixa enviar enquanto alguma foto ainda está sendo ajustada.
    var form = caixa.closest('form');
    if (form) form.addEventListener('submit', function (e) {
        if (caixa.querySelector('[data-pronto="0"]')) { e.preventDefault(); setTimeout(function () { form.requestSubmit ? form.requestSubmit() : form.submit(); }, 400); }
    });
})();
