<?php
/**
 * View: Avaliação NPS via Token
 */
$title = 'Avaliação - Assistência 360°';
ob_start();
?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
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
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <!-- Informações da Solicitação -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="text-sm space-y-1">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Nº da Solicitação:</span>
                            <span class="font-semibold text-gray-900">
                                <?php 
                                $numeroSolicitacao = $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacao['id']);
                                echo htmlspecialchars($numeroSolicitacao);
                                ?>
                            </span>
                        </div>
                        <?php if ($solicitacao['categoria_nome']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Serviço:</span>
                            <span class="font-semibold text-gray-900"><?= htmlspecialchars($solicitacao['categoria_nome']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
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
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        Enviar Avaliação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function enviarAvaliacao(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.textContent = 'Enviando...';
    
    fetch('<?= url('avaliacao') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Obrigado pela sua avaliação!');
            // Mostrar mensagem de sucesso
            document.querySelector('.bg-white').innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-check-circle text-green-600 text-5xl mb-6"></i>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Obrigado!</h2>
                    <p class="text-gray-600">Sua avaliação foi registrada com sucesso.</p>
                </div>
            `;
        } else {
            alert('Erro ao enviar avaliação: ' + (data.message || 'Erro desconhecido'));
            submitButton.disabled = false;
            submitButton.textContent = 'Enviar Avaliação';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao enviar avaliação. Tente novamente.');
        submitButton.disabled = false;
        submitButton.textContent = 'Enviar Avaliação';
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/public.php';
?>

