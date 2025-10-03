<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Video Downloader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #e0e0e0;
        }

        .topbar {
            background: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #ccc;
        }

        .logo-text {
            font-weight: bold;
            font-size: 1.2rem;
            margin-left: 10px;
        }

        .ad-box {
            background: #fff;
            border: 1px solid #ccc;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
        }

        .formats-box {
            background: #fff;
            border: 1px solid #ccc;
            margin-top: 20px;
            padding: 10px;
        }

        .format-item {
            cursor: pointer;
        }

        /* Estilos adicionais para o menu */
        .topbar .nav-link {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="topbar">
        <div class="d-flex align-items-center">
            <a href="{{ env('app.url') }}" style="text-decoration: none;color: inherit;">
                <img src="{{ asset('/logotipo.png') }}" alt="Logo" height="30">
                <span class="logo-text">Video Downloader</span>
            </a>
            
            <ul class="nav ms-4">
                <li class="nav-item">
                    <a class="nav-link text-dark" href="{{ route('videos.index') }}">Download Vídeo</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark" href="{{ route('videos.listar') }}">Converter Vídeo</a>
                </li>
            </ul>
        </div>
        <select id="lang" class="form-select w-auto">
            <option>Português</option>
            <option>English</option>
            <option>Español</option>
        </select>
    </div>

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    </body>
</html>