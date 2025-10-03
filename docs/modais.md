
Modal Bootstrap

https://getbootstrap.com/docs/4.0/components/modal/

Para passar dados de elementos HTML para um modal no Bootstrap, você pode usar atributos data-* no botão que aciona o modal, como data-id e data-name, e então, no JavaScript (usando jQuery, por exemplo), capturar esses dados e usá-los para preencher o modal. Primeiro, adicione os atributos ao botão e, em seguida, escreva um listener de evento para o clique do botão que recupera esses dados e atualiza o modal antes de exibi-lo. 
1. No HTML (Botão que aciona o Modal)
Adicione os atributos data-* ao elemento que abrirá o modal (geralmente um botão). Eles devem conter os dados que você deseja passar. 
Código

<!-- Botão para acionar o modal -->
<button
  type="button"
  class="btn btn-primary"
  data-bs-toggle="modal"
  data-bs-target="#meuModal"
  data-id="123"
  data-nome="Exemplo de Nome"
  data-email="exemplo@email.com"
>
  Abrir Modal
</button>

<!-- Modal ( estrutura básica ) -->
<div class="modal fade" id="meuModal" tabindex="-1" aria-labelledby="meuModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="meuModalLabel">Detalhes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p><strong>ID:</strong> <span id="idModal"></span></p>
        <p><strong>Nome:</strong> <span id="nomeModal"></span></p>
        <p><strong>Email:</strong> <span id="emailModal"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

data-bs-toggle="modal": Ativa o componente modal. 
data-bs-target="#meuModal": Especifica qual modal deve ser aberto. 
data-id="123", data-nome="Exemplo de Nome", data-email="...": São os atributos que carregam os dados. 

2. No JavaScript (com jQuery)
Use um listener de evento click no botão para capturar os dados dos atributos data-* e colocá-los dentro do modal. 
JavaScript

<script>
$(document).ready(function() {
  // Adiciona um listener de evento para o clique em botões com data-bs-toggle="modal"
  $('button[data-bs-toggle="modal"]').on('click', function() {
    // Obtém os dados dos atributos data-* do botão clicado
    var userId = $(this).data('id');
    var userName = $(this).data('nome');
    var userEmail = $(this).data('email');

    // Preenche os elementos dentro do modal com os dados obtidos
    $('#idModal').text(userId);
    $('#nomeModal').text(userName);
    $('#emailModal').text(userEmail);
  });
});


$('button[data-bs-toggle="modal"]').on('click', function() { ... });: Seleciona todos os botões que ativam um modal e adiciona um listener de clique. 
$(this).data('id'): Recupera o valor do atributo data-id do botão clicado. 
$('#idModal').text(userId);: Localiza o <span> com o ID idModal e define seu texto com o valor de userId. 

<\script>