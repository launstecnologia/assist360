<?php
/**
 * View: Avaliação NPS
 */
$title = 'Avaliação - Assistência 360°';
$currentPage = 'locatario-avaliacao';
ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                <i class="fas fa-star mr-2 text-yellow-500"></i>
                Avalie nosso atendimento
            </h1>
            <p class="text-gray-600">
                Sua opinião é muito importante para nós!
            </p>
        </div>
        
        <form id="formAvaliacao" onsubmit="enviarAvaliacao(event)">
            <input type="hidden" name="solicitacao_id" value="<?= $solicitacao['id'] ?>">
            
            <!-- Pergunta NPS -->
            <div class="mb-8">
                <label class="block text-lg font-medium text-gray-900 mb-4 text-center">
                    Em uma escala de 0 a 10, qual a probabilidade de você recomendar nosso serviço?
                </label>
                <div class="flex justify-center gap-2 flex-wrap">
                    <?php for ($i = 0; $i <= 10; $i++): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="nps_score" value="<?= $i ?>" required class="hidden peer">
                        <div class="w-12 h-12 flex items-center justify-center rounded-lg border-2 border-gray-300 peer-checked:border-green-500 peer-checked:bg-green-500 peer-checked:text-white text-gray-700 font-semibold hover:border-green-400 transition-colors">
                            <?= $i ?>
                        </div>
                    </label>
                    <?php endfor; ?>
                </div>
                <div class="flex justify-between mt-2 text-sm text-gray-500">
                    <span>Pouco provável</span>
                    <span>Muito provável</span>
                </div>
            </div>
            
            <!-- Comentários -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Comentários (opcional)
                </label>
                <textarea name="comentario" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Deixe seu comentário sobre o atendimento..."></textarea>
            </div>
            
            <!-- Botões -->
            <div class="flex justify-end gap-3">
                <a href="<?= url($instancia . '/solicitacoes') ?>" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Pular
                </a>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Enviar Avaliação
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function enviarAvaliacao(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('<?= url($instancia) ?>/solicitacoes/<?= $solicitacao['id'] ?>/avaliacao', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Obrigado pela sua avaliação!');
            window.location.href = '<?= url($instancia . '/solicitacoes') ?>';
        } else {
            alert('Erro ao enviar avaliação: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao enviar avaliação. Tente novamente.');
    });
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/locatario.php';
?>

