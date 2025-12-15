-- ==========================================
-- Tabela para armazenar os resultados de matching
-- Sistema de Recomendação de Vagas de Emprego
-- ==========================================

CREATE TABLE IF NOT EXISTS tb_matches (
    id_match SERIAL PRIMARY KEY,
    id_usuario INTEGER NOT NULL REFERENCES tb_usuarios(id_usuario) ON DELETE CASCADE,
    id_vaga BIGINT NOT NULL REFERENCES tb_vagas(id_vaga) ON DELETE CASCADE,

    -- Métricas individuais (escala 0-100)
    skill_match_score DECIMAL(5,2) CHECK (skill_match_score >= 0 AND skill_match_score <= 100),
    experience_score DECIMAL(5,2) CHECK (experience_score >= 0 AND experience_score <= 100),
    preference_score DECIMAL(5,2) CHECK (preference_score >= 0 AND preference_score <= 100),
    interest_score DECIMAL(5,2) CHECK (interest_score >= 0 AND interest_score <= 100),
    hire_similarity_score DECIMAL(5,2) CHECK (hire_similarity_score >= 0 AND hire_similarity_score <= 100),

    -- Score final (escala 0-100)
    match_final DECIMAL(5,2) CHECK (match_final >= 0 AND match_final <= 100),

    -- Categoria do match (C1-C5)
    categoria VARCHAR(10) CHECK (categoria IN ('C1', 'C2', 'C3', 'C4', 'C5')),

    -- Metadados
    data_calculo TIMESTAMP DEFAULT NOW(),
    visualizado BOOLEAN DEFAULT FALSE,
    data_visualizacao TIMESTAMP,
    candidatura_enviada BOOLEAN DEFAULT FALSE,
    data_candidatura TIMESTAMP,

    -- Garantir que cada usuário tenha apenas um match por vaga
    UNIQUE(id_usuario, id_vaga)
);

-- Índices para otimizar consultas
CREATE INDEX IF NOT EXISTS idx_matches_usuario ON tb_matches(id_usuario);
CREATE INDEX IF NOT EXISTS idx_matches_vaga ON tb_matches(id_vaga);
CREATE INDEX IF NOT EXISTS idx_matches_final ON tb_matches(match_final DESC);
CREATE INDEX IF NOT EXISTS idx_matches_categoria ON tb_matches(categoria);
CREATE INDEX IF NOT EXISTS idx_matches_data_calculo ON tb_matches(data_calculo DESC);

-- Índice composto para consultas de recomendações
CREATE INDEX IF NOT EXISTS idx_matches_usuario_score ON tb_matches(id_usuario, match_final DESC);

-- Comentários descritivos
COMMENT ON TABLE tb_matches IS 'Armazena os resultados de matching entre usuários e vagas';
COMMENT ON COLUMN tb_matches.skill_match_score IS 'Score de compatibilidade de habilidades técnicas (0-100)';
COMMENT ON COLUMN tb_matches.experience_score IS 'Score de compatibilidade de experiência profissional (0-100)';
COMMENT ON COLUMN tb_matches.preference_score IS 'Score de alinhamento com preferências do usuário (0-100)';
COMMENT ON COLUMN tb_matches.interest_score IS 'Score de compatibilidade vocacional (0-100)';
COMMENT ON COLUMN tb_matches.hire_similarity_score IS 'Score de similaridade com contratações bem-sucedidas (0-100)';
COMMENT ON COLUMN tb_matches.match_final IS 'Score final calculado pela fórmula de regressão linear múltipla (0-100)';
COMMENT ON COLUMN tb_matches.categoria IS 'Categoria do match: C1=Incompatível, C2=Baixo, C3=Moderado, C4=Alto, C5=Excelente';

-- Visualização para facilitar consultas
CREATE OR REPLACE VIEW vw_matches_resumo AS
SELECT
    m.*,
    u.nome AS nome_usuario,
    u.email AS email_usuario,
    v.titulo AS titulo_vaga,
    v.empresa AS empresa_vaga,
    v.cidade AS cidade_vaga,
    v.estado AS estado_vaga,
    v.modelo_trabalho,
    v.nivel_senioridade,
    v.url_original,
    CASE
        WHEN m.categoria = 'C5' THEN 'Excelente'
        WHEN m.categoria = 'C4' THEN 'Alto'
        WHEN m.categoria = 'C3' THEN 'Moderado'
        WHEN m.categoria = 'C2' THEN 'Baixo'
        WHEN m.categoria = 'C1' THEN 'Incompatível'
    END AS categoria_nome
FROM tb_matches m
INNER JOIN tb_usuarios u ON m.id_usuario = u.id_usuario
INNER JOIN tb_vagas v ON m.id_vaga = v.id_vaga
ORDER BY m.match_final DESC;

COMMENT ON VIEW vw_matches_resumo IS 'Visualização consolidada dos matches com informações de usuários e vagas';
