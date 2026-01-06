-- ============================================
-- SCRIPT: Criar Triggers para Contagem de CPF
-- ============================================
-- Este script cria os triggers para atualização automática
-- da tabela solicitacoes_manuais_contagem_cpf
-- 
-- IMPORTANTE: Este script deve ser executado diretamente no cliente MySQL
-- (phpMyAdmin, MySQL Workbench, linha de comando MySQL) devido ao uso de DELIMITER
-- ============================================

-- Remover triggers se já existirem
DROP TRIGGER IF EXISTS trg_after_insert_solicitacao_manual;
DROP TRIGGER IF EXISTS trg_after_update_solicitacao_manual;
DROP TRIGGER IF EXISTS trg_after_delete_solicitacao_manual;

DELIMITER //

-- Trigger para INSERT
CREATE TRIGGER trg_after_insert_solicitacao_manual
AFTER INSERT ON solicitacoes_manuais
FOR EACH ROW
BEGIN
    DECLARE v_cpf_limpo VARCHAR(11);
    SET v_cpf_limpo = REPLACE(REPLACE(REPLACE(NEW.cpf, '.', ''), '-', ''), ' ', '');

    -- Atualizar ou inserir a contagem geral (categoria_id = NULL)
    INSERT INTO solicitacoes_manuais_contagem_cpf (cpf, imobiliaria_id, categoria_id, quantidade_total, quantidade_12_meses, primeira_solicitacao, ultima_solicitacao)
    VALUES (v_cpf_limpo, NEW.imobiliaria_id, NULL, 1, 
            CASE WHEN NEW.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END,
            NEW.created_at, NEW.created_at)
    ON DUPLICATE KEY UPDATE
        quantidade_total = quantidade_total + 1,
        quantidade_12_meses = quantidade_12_meses + (CASE WHEN NEW.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END),
        ultima_solicitacao = NEW.created_at;

    -- Atualizar ou inserir a contagem por categoria (se categoria_id existir)
    IF NEW.categoria_id IS NOT NULL THEN
        INSERT INTO solicitacoes_manuais_contagem_cpf (cpf, imobiliaria_id, categoria_id, quantidade_total, quantidade_12_meses, primeira_solicitacao, ultima_solicitacao)
        VALUES (v_cpf_limpo, NEW.imobiliaria_id, NEW.categoria_id, 1, 
                CASE WHEN NEW.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END,
                NEW.created_at, NEW.created_at)
        ON DUPLICATE KEY UPDATE
            quantidade_total = quantidade_total + 1,
            quantidade_12_meses = quantidade_12_meses + (CASE WHEN NEW.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END),
            ultima_solicitacao = NEW.created_at;
    END IF;
END;
//

-- Trigger para UPDATE
CREATE TRIGGER trg_after_update_solicitacao_manual
AFTER UPDATE ON solicitacoes_manuais
FOR EACH ROW
BEGIN
    DECLARE v_old_cpf_limpo VARCHAR(11);
    DECLARE v_new_cpf_limpo VARCHAR(11);
    
    SET v_old_cpf_limpo = REPLACE(REPLACE(REPLACE(OLD.cpf, '.', ''), '-', ''), ' ', '');
    SET v_new_cpf_limpo = REPLACE(REPLACE(REPLACE(NEW.cpf, '.', ''), '-', ''), ' ', '');

    -- Se CPF ou imobiliária mudou, ajustar contagens antigas e novas
    IF v_old_cpf_limpo != v_new_cpf_limpo OR OLD.imobiliaria_id != NEW.imobiliaria_id OR OLD.categoria_id != NEW.categoria_id THEN
        -- Decrementar contagem antiga (geral)
        UPDATE solicitacoes_manuais_contagem_cpf
        SET 
            quantidade_total = GREATEST(0, quantidade_total - 1),
            quantidade_12_meses = GREATEST(0, quantidade_12_meses - (CASE WHEN OLD.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END))
        WHERE cpf = v_old_cpf_limpo AND imobiliaria_id = OLD.imobiliaria_id AND categoria_id IS NULL;

        -- Decrementar contagem antiga (por categoria)
        IF OLD.categoria_id IS NOT NULL THEN
            UPDATE solicitacoes_manuais_contagem_cpf
            SET 
                quantidade_total = GREATEST(0, quantidade_total - 1),
                quantidade_12_meses = GREATEST(0, quantidade_12_meses - (CASE WHEN OLD.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END))
            WHERE cpf = v_old_cpf_limpo AND imobiliaria_id = OLD.imobiliaria_id AND categoria_id = OLD.categoria_id;
        END IF;

        -- Incrementar contagem nova (geral)
        INSERT INTO solicitacoes_manuais_contagem_cpf (cpf, imobiliaria_id, categoria_id, quantidade_total, quantidade_12_meses, primeira_solicitacao, ultima_solicitacao)
        VALUES (v_new_cpf_limpo, NEW.imobiliaria_id, NULL, 1, 
                CASE WHEN NEW.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END,
                NEW.created_at, NEW.created_at)
        ON DUPLICATE KEY UPDATE
            quantidade_total = quantidade_total + 1,
            quantidade_12_meses = quantidade_12_meses + (CASE WHEN NEW.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END),
            ultima_solicitacao = NEW.created_at;

        -- Incrementar contagem nova (por categoria)
        IF NEW.categoria_id IS NOT NULL THEN
            INSERT INTO solicitacoes_manuais_contagem_cpf (cpf, imobiliaria_id, categoria_id, quantidade_total, quantidade_12_meses, primeira_solicitacao, ultima_solicitacao)
            VALUES (v_new_cpf_limpo, NEW.imobiliaria_id, NEW.categoria_id, 1, 
                    CASE WHEN NEW.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END,
                    NEW.created_at, NEW.created_at)
            ON DUPLICATE KEY UPDATE
                quantidade_total = quantidade_total + 1,
                quantidade_12_meses = quantidade_12_meses + (CASE WHEN NEW.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END),
                ultima_solicitacao = NEW.created_at;
        END IF;
    END IF;
END;
//

-- Trigger para DELETE
CREATE TRIGGER trg_after_delete_solicitacao_manual
AFTER DELETE ON solicitacoes_manuais
FOR EACH ROW
BEGIN
    DECLARE v_cpf_limpo VARCHAR(11);
    SET v_cpf_limpo = REPLACE(REPLACE(REPLACE(OLD.cpf, '.', ''), '-', ''), ' ', '');

    -- Decrementar contagem geral
    UPDATE solicitacoes_manuais_contagem_cpf
    SET 
        quantidade_total = GREATEST(0, quantidade_total - 1),
        quantidade_12_meses = GREATEST(0, quantidade_12_meses - (CASE WHEN OLD.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END))
    WHERE cpf = v_cpf_limpo AND imobiliaria_id = OLD.imobiliaria_id AND categoria_id IS NULL;

    -- Decrementar contagem por categoria
    IF OLD.categoria_id IS NOT NULL THEN
        UPDATE solicitacoes_manuais_contagem_cpf
        SET 
            quantidade_total = GREATEST(0, quantidade_total - 1),
            quantidade_12_meses = GREATEST(0, quantidade_12_meses - (CASE WHEN OLD.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END))
        WHERE cpf = v_cpf_limpo AND imobiliaria_id = OLD.imobiliaria_id AND categoria_id = OLD.categoria_id;
    END IF;
END;
//

DELIMITER ;

-- Verificar se os triggers foram criados
SELECT 
    TRIGGER_NAME,
    EVENT_MANIPULATION,
    EVENT_OBJECT_TABLE,
    ACTION_STATEMENT
FROM INFORMATION_SCHEMA.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
AND TRIGGER_NAME LIKE 'trg_after_%solicitacao_manual%'
ORDER BY TRIGGER_NAME;

