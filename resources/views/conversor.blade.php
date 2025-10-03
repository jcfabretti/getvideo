@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Conversor de Vídeos FFmpeg</h1>

    <div class="mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="folderInput" class="col-form-label">Selecionar Pasta Local:</label>
            </div>
            <div class="col-auto">
                <input type="file" id="folderInput" class="form-control" webkitdirectory directory>
            </div>
        </div>
    </div>

    <div id="loading" class="text-center d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
        <p class="mt-2">Lendo arquivos da pasta...</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div id="videoGallery" class="video-container">
        </div>
</div>

<script>
    // Armazenará os objetos de arquivo lidos do input
    let videoFiles = [];

    const folderInput = document.getElementById('folderInput');
    const videoGallery = document.getElementById('videoGallery');
    const loadingSpinner = document.getElementById('loading');

    // Evento para ler a pasta quando for selecionada
    folderInput.addEventListener('change', (e) => {
        const files = e.target.files;
        videoFiles = []; // Limpa a lista anterior
        videoGallery.innerHTML = '';
        loadingSpinner.classList.remove('d-none');

        if (files.length > 0) {
            Array.from(files).forEach(file => {
                // Filtra apenas arquivos MP4
                if (file.type.startsWith('video/mp4')) {
                    videoFiles.push(file);
                }
            });
            displayVideos();
        } else {
            loadingSpinner.classList.add('d-none');
            videoGallery.innerHTML = '<p>Nenhum vídeo encontrado na pasta selecionada.</p>';
        }
    });

    function displayVideos() {
        loadingSpinner.classList.add('d-none');
        if (videoFiles.length === 0) {
            videoGallery.innerHTML = '<p>Nenhum vídeo .mp4 encontrado na pasta.</p>';
            return;
        }

        videoFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const thumbnail = 'https://via.placeholder.com/200x112.png?text=Vídeo'; // Placeholder para o thumbnail

                const videoItem = document.createElement('div');
                videoItem.className = 'video-item';
                videoItem.id = `video-item-${index}`;
                videoItem.innerHTML = `
                    <img src="${thumbnail}" alt="Thumbnail do vídeo" class="video-thumbnail">
                    <p class="text-truncate">${file.name}</p>
                    <button
                        class="btn btn-success btn-sm convert-btn"
                        data-index="${index}"
                        onclick="converterVideo(this)"
                    >
                        Converter
                    </button>
                    <div class="spinner-border spinner-border-sm text-success mt-2 d-none" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <div class="mt-2 status-text d-none text-success">Convertido!</div>
                `;
                videoGallery.appendChild(videoItem);
            };
            reader.readAsDataURL(file);
        });
    }

    function converterVideo(button) {
        const fileIndex = button.getAttribute('data-index');
        const fileToUpload = videoFiles[fileIndex];

        const videoItem = button.closest('.video-item');
        const spinner = videoItem.querySelector('.spinner-border');
        const statusText = videoItem.querySelector('.status-text');

        button.classList.add('d-none');
        spinner.classList.remove('d-none');

        const formData = new FormData();
        formData.append('video_file', fileToUpload);

        fetch("{{ route('videos.converter') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                spinner.classList.add('d-none');
                statusText.classList.remove('d-none');
                videoItem.classList.add('border-success');
                console.log(data.message);
            } else {
                spinner.classList.add('d-none');
                button.classList.remove('d-none');
                alert('Erro na conversão: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            spinner.classList.add('d-none');
            button.classList.remove('d-none');
            alert('Ocorreu um erro na requisição.');
        });
    }
</script>
@endsection