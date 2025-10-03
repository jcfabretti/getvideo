<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // Adicionado: Necessário para usar Log::error
use Illuminate\Support\Str;        // Adicionado: Necessário para usar Str::slug
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class VideoController extends Controller
{
    // --- DECLARAÇÃO DAS PROPRIEDADES ---
    // Mantenha as declarações aqui para tipagem correta
    private string $ytDlpPath;
    private string $ffmpegPath;
    private string $userAgent;

    public function __construct()
    {
        // Define o caminho completo para os executáveis dentro da estrutura do Laravel (storage/app/bin)
        // Isso garante que o código funcione em qualquer SO, desde que o binário correto esteja na pasta.
        $this->ytDlpPath = storage_path('app/bin/yt-dlp');
        $this->ffmpegPath = storage_path('app/bin/ffmpeg');
        $this->userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        // Lembrete: Para compatibilidade Windows/Linux, você deve garantir que o binário
        // yt-dlp não tenha a extensão .exe no servidor Linux, ou usar uma lógica
        // condicional aqui, como:
        /*
        $ext = (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
        $this->ytDlpPath = storage_path('app/bin/yt-dlp' . $ext);
        */
    }

    public function index()
    {
        return view('index');
    }

    /** Força TEMP/TMP para o yt-dlp (necessário no Windows quando rodando pelo PHP). */
    private function forceTempDir(): string
    {
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        // Define as variáveis de ambiente
        putenv("TEMP=$tempPath");
        putenv("TMP=$tempPath");
        $_ENV["TEMP"]   = $tempPath;
        $_ENV["TMP"]    = $tempPath;
        $_SERVER["TEMP"] = $tempPath;
        $_SERVER["TMP"]  = $tempPath;
        return $tempPath;
    }

    /** Lista formatos: retorna [{ id, desc }] */
    public function listFormats(Request $request)
    {
        $request->validate(['url' => 'required|url']);
        $url = $request->string('url');
        // dd($url); // Linha de debug removida

        try {
            $this->forceTempDir();
            
            // Usando a propriedade da classe para o User-Agent
            $command = [
                $this->ytDlpPath,
                '--user-agent', $this->userAgent,
                '-F',
                $url->toString() // Convertido para string
            ];
            
            // Usando a Facade Process do Laravel (que usa o componente Symfony)
            // Nota: O seu código original usava new Process(), que também funciona,
            // mas a Facade Illuminate\Support\Facades\Process é mais idiomática no Laravel.
            // Mantive a sua implementação com Symfony\Component\Process\Process.
            $process = new Process($command);
            $process->setTimeout(120);
            $process->run();

            // Se o processo inicial for bem-sucedido, retorna a resposta
            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $final = $this->parseFormatsOutput($output);
                
                // Adiciona o campo cookie_path como nulo para todas as saídas
                $final = array_map(function($item) {
                    $item['cookie_path'] = null;
                    return $item;
                }, $final);

                return response()->json(['success' => true, 'formats' => $final]);
            }
            
            // Se falhar, lança a exceção para ser capturada e analisada abaixo
            throw new ProcessFailedException($process);

        } catch (ProcessFailedException $e) {
            $stderr = $e->getProcess()->getErrorOutput();
            $host = parse_url($url, PHP_URL_HOST);

            if ((str_contains($stderr, 'NsfwViewerHasNoStatedAge') || str_contains($stderr, 'requires authentication')) && 
                (str_contains($host, 'x.com') || str_contains($host, 'twitter.com'))) {

                $cookieFile = storage_path('app/x.com_cookies.txt');
                logger()->info("Vídeo privado ou requer autenticação. Tentando com cookies em: $cookieFile");
                
                if (!file_exists($cookieFile)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este vídeo do X requer login. Por favor, exporte seus cookies para o arquivo ' . basename($cookieFile) . ' e tente novamente.'
                    ], 401);
                }

                // Tenta uma segunda vez com o parâmetro de cookies
                $processWithCookies = new Process([
                    $this->ytDlpPath,
                    '--cookies',
                    $cookieFile,
                    '-F',
                    $url->toString()
                ]);
                $processWithCookies->setTimeout(120);
                $processWithCookies->run();

                if ($processWithCookies->isSuccessful()) {
                    $output = $processWithCookies->getOutput();
                    $final = $this->parseFormatsOutput($output);
                    
                    // Adiciona o caminho do cookie à saída
                    $final = array_map(function($item) use ($cookieFile) {
                        $item['cookie_path'] = $cookieFile;
                        return $item;
                    }, $final);

                    return response()->json(['success' => true, 'formats' => $final]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Falha na autenticação com cookies. Verifique se o arquivo está correto.'
                    ], 500);
                }
            }
            
            Log::error("yt-dlp error: " . $e->getMessage()); // Usando a Facade Log
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar formatos: ' . $e->getMessage(),
            ], 500);

        } catch (\Throwable $e) {
            Log::error('listFormats error: ' . $e->getMessage()); // Usando a Facade Log
            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro inesperado: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** Download pelo formato selecionado (POST -> retorna arquivo) */
    public function downloadVideo(Request $request)
    {
        $request->validate([
            'url'         => 'required|url',
            'format'      => 'required|string',
            'cookie_path' => 'nullable|string'
        ]);

        $url = $request->string('url');
        $format = $request->string('format');
        $cookiePath = $request->string('cookie_path');

        // Cria um nome base único para o arquivo, facilitando a busca após o download.
        $safeFileName = Str::slug(parse_url($url, PHP_URL_HOST) . '-' . now()->timestamp);
        
        $outputDirectory = storage_path('app/public/downloads'); // Pasta dedicada para downloads
        if (!Storage::disk('public')->exists('downloads')) {
            Storage::disk('public')->makeDirectory('downloads');
        }

        $command = []; // Inicializa o comando aqui

        try {
            $this->forceTempDir();

            $command = [$this->ytDlpPath];

            // Adicionar User-Agent (Usando a propriedade da classe)
            $command[] = '--user-agent';
            $command[] = $this->userAgent;

            // Adicionar cookies SOMENTE se fornecido
            if ($cookiePath->isNotEmpty()) {
                $command[] = '--cookies';
                $command[] = $cookiePath->toString();
            }

            // Adicionar os parâmetros de formato e URL
            $command[] = '-f';
            $command[] = $format->toString();

            // Usar o placeholder do yt-dlp para nomear o arquivo de forma previsível e com a extensão correta.
            // O nome base é o $safeFileName que geramos.
            $command[] = '-o';
            $command[] = $outputDirectory . '/' . $safeFileName . '.%(ext)s'; 
            
            $command[] = $url->toString();

            // dd($command); // Linha de debug

            $process = new Process($command);
            $process->setTimeout(3600); // 1 hora de timeout
            $process->setWorkingDirectory(dirname($this->ytDlpPath)); // Define o diretório de trabalho do yt-dlp

            $process->run();

            if (!$process->isSuccessful()) {
                Log::error("yt-dlp stdout: ".$process->getOutput());
                Log::error("yt-dlp stderr: ".$process->getErrorOutput());
                throw new ProcessFailedException($process);
            }

            // --- Lógica para encontrar o nome real do arquivo baixado ---
            $filesInOutput = Storage::disk('public')->files('downloads');
            $downloadedFile = null;
            $finalFileName = null;
            
            // Procura por qualquer arquivo na pasta que comece com o nome base que geramos.
            foreach ($filesInOutput as $f) {
                $fileNameWithoutExt = pathinfo(basename($f), PATHINFO_FILENAME);
                
                if (str_starts_with($fileNameWithoutExt, $safeFileName)) { 
                    $downloadedFile = storage_path('app/public/' . $f);
                    $finalFileName = basename($f); // Nome do arquivo com a extensão real
                    break;
                }
            }

            if (!$downloadedFile || !file_exists($downloadedFile)) {
                 Log::error('Arquivo yt-dlp não encontrado após download: ' . $outputDirectory);
                 throw new \RuntimeException('Arquivo de vídeo não foi encontrado após o download.');
            }

            // Retorna o arquivo e o exclui após o envio.
            return response()->download($downloadedFile, $finalFileName)->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            // Se ProcessFailedException for lançada, o Log::error já teria sido chamado dentro do if.
            Log::error('downloadVideo error: '.$e->getMessage().' | URL: '.$url.' | Command: '.implode(' ', $command));
            
            $message = 'Erro no download. ' . $e->getMessage();
            if (str_contains($e->getMessage(), 'NsfwViewerHasNoStatedAge') || str_contains($e->getMessage(), 'requires authentication')) {
                $message = 'Não foi possível fazer o download. O vídeo pode ser privado, restrito ou requer autenticação.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }
    }


    /** Analisa a saída do yt-dlp -F para extrair formatos. */
    private function parseFormatsOutput(string $rawOutput, ?string $cookiePath = null): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($rawOutput));
        $out = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (stripos($line, 'format code') !== false) continue; // cabeçalho do yt-dlp
            if (preg_match('/^\[[^\]]+\]/', $line)) continue;      // [twitter], [info], etc.

            // primeira "coluna" = id; resto = detalhes
            if (!preg_match('/^(\S+)\s+(.+)$/u', $line, $m)) continue;
            $id      = $m[1];
            $details = $m[2];

            // ÁUDIO?
            if (stripos($details, 'audio only') !== false) {
                // captura último "NNNk" encontrado (32k, 64k, 128k, etc.)
                if (preg_match_all('/\b(\d{2,4})k\b/i', $details, $all)) {
                    $bitrate = end($all[1]) . 'k';
                    $label = "Download audio {$bitrate}";
                } else {
                    $label = "Download audio";
                }
            } else {
                // VÍDEO: pega resolução 320x568, 480x852, 720x1280, etc.
                if (preg_match('/\b(\d{3,4}x\d{3,4})\b/', $details, $r)) {
                    $label = "Download video {$r[1]}";
                } else {
                    $label = "Download video";
                }
            }

            $out[] = ['id' => $id, 'label' => $label, 'cookie_path' => $cookiePath];
        }

        return $out;
    }
}