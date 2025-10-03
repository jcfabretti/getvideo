ffmpeg -i input.webm -c:v copy -c:a aac -b:a 192k output.mp4

## ffmpeg: O comando para iniciar o FFmpeg.

-i input.webm: O arquivo de entrada, que é o vídeo que foi baixado.

-c:v copy: Copia o stream de vídeo sem re-codificar, o que economiza tempo e não perde qualidade.

-c:a aac: Re-codifica o áudio para o codec AAC, que é um dos mais compatíveis.

-b:a 192k: Define a taxa de bits (bitrate) do áudio para 192kbps.

output.mp4: O novo arquivo de saída, já "consertado" e pronto para ser baixado pelo usuário.