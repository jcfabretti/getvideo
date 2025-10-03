@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <form id="videoForm" class="mb-2">
        @csrf
        <div class="input-group">
            <input type="url" name="url" id="videoUrl" class="form-control"
                placeholder="Cole o link do vídeo" value="{{ old('url') }}" required>
            <button class="btn btn-danger" type="submit" id="downloadBtn">
                <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSpin"></span>
                <span id="btnText">Buscar formatos</span>
            </button>
            <input type="hidden" name="cookiePath" id="cookiePath">
        </div>
    </form>

    <div id="errorMessage" class="alert alert-danger d-none mt-2"></div>
    
    <div class="ad-box mt-3">
        <span>Espaço para Propaganda</span>
    </div>

    <div id="formatsContainer" class="formats-box d-none">
        <h6>Selecione o formato:</h6>
        <ul id="formatsList" class="list-group"></ul>
    </div>
</div>

<script>
    // Todo o seu JavaScript para o download pode ficar aqui
    // Ele será carregado dentro do layout principal
    const form = document.getElementById('videoForm');
    const formatsContainer = document.getElementById('formatsContainer');
    const formatsList = document.getElementById('formatsList');
    const btnText = document.getElementById('btnText');
    const btnSpin = document.getElementById('btnSpin');
    const btn = document.getElementById('downloadBtn');
    const errorMessage = document.getElementById('errorMessage');
    const urlInput = document.getElementById('videoUrl');

    let videoCookiePath = null;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        errorMessage.classList.add('d-none');
        errorMessage.textContent = "";
        formatsContainer.classList.add('d-none');
        formatsList.innerHTML = "";
        videoCookiePath = null;

        btn.disabled = true;
        btnSpin.classList.remove('d-none');
        btnText.textContent = 'Carregando...';

        try {
            const fd = new FormData(form);
            const res = await axios.post("{{ route('videos.formats') }}", fd);
            btn.disabled = false;
            btnSpin.classList.add('d-none');
            btnText.textContent = 'Buscar formatos';

            if (res.data?.success && Array.isArray(res.data.formats)) {
                if (res.data.formats.length === 0) {
                    showError("Nenhum formato encontrado para esse link.");
                    return;
                }

                const firstFormatWithCookie = res.data.formats.find(f => f.cookie_path);
                if (firstFormatWithCookie) {
                    videoCookiePath = firstFormatWithCookie.cookie_path;
                }

                formatsContainer.classList.remove('d-none');
                res.data.formats.forEach(f => {
                    const li = document.createElement('li');
                    li.className = "list-group-item d-flex justify-content-between align-items-center";
                    li.innerHTML = `
                        <span>${f.label} <small class="text-muted ms-2">(${f.id})</small></span>
                        <button class="btn btn-sm btn-outline-primary">Baixar</button>
                    `;
                    li.querySelector('button').onclick = () => postDownload(f.id, videoCookiePath);
                    formatsList.appendChild(li);
                });
            } else {
                console.error(res.data);
                showError(res.data?.message || 'Não foi possível listar formatos.');
            }
        } catch (err) {
            btn.disabled = false;
            btnSpin.classList.add('d-none');
            btnText.textContent = 'Buscar formatos';
            console.error(err);
            const msg = (err.response?.data?.message) || (err.response?.data?.error) || err.message || 'Falha na requisição';
            if (/logado/i.test(msg)) {
                showError("Para download deste Site é preciso estar logado.");
                const loginUrl = urlInput.value.replace(/\/$/, "") + "/login";
                window.open(loginUrl, "_blank");
            } else {
                showError("Erro: " + msg);
            }
        }
    });

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.classList.remove('d-none');
    }

    function postDownload(formatId, cookiePath) {
        const url = urlInput.value;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('videos.download') }}";
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = "{{ csrf_token() }}";
        const urlInputHidden = document.createElement('input');
        urlInputHidden.type = 'hidden';
        urlInputHidden.name = 'url';
        urlInputHidden.value = url;
        const fmtInput = document.createElement('input');
        fmtInput.type = 'hidden';
        fmtInput.name = 'format';
        fmtInput.value = formatId;
        if (cookiePath) {
            const cookieInputHidden = document.createElement('input');
            cookieInputHidden.type = 'hidden';
            cookieInputHidden.name = 'cookie_path';
            cookieInputHidden.value = cookiePath;
            form.appendChild(cookieInputHidden);
        }
        form.appendChild(tokenInput);
        form.appendChild(urlInputHidden);
        form.appendChild(fmtInput);
        document.body.appendChild(form);
        form.submit();
        form.remove();
    }
</script>
@endsection
