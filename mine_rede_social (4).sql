-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 17/10/2025 às 07:38
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `mine_rede_social`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `postagem_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comentarios`
--

INSERT INTO `comentarios` (`id`, `postagem_id`, `usuario_id`, `conteudo`, `criado_em`) VALUES
(10, 14, 6, 'que lindo', '2025-10-10 15:31:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `curtidas`
--

CREATE TABLE `curtidas` (
  `id` int(11) NOT NULL,
  `postagem_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `curtidas`
--

INSERT INTO `curtidas` (`id`, `postagem_id`, `usuario_id`, `criado_em`) VALUES
(14, 13, 8, '2025-10-10 17:08:00'),
(16, 13, 6, '2025-10-16 03:02:43'),
(17, 14, 7, '2025-10-16 19:22:09'),
(19, 13, 7, '2025-10-16 21:10:19'),
(20, 14, 8, '2025-10-17 01:41:50'),
(23, 14, 6, '2025-10-17 02:18:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `id_remetente` int(11) NOT NULL,
  `id_destinatario` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `data_hora` datetime DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `mensagens`
--

INSERT INTO `mensagens` (`id`, `id_remetente`, `id_destinatario`, `conteudo`, `data_hora`, `lida`) VALUES
(6, 7, 6, 'sophiaaaa', '2025-10-10 11:47:48', 1),
(7, 8, 6, 'oii', '2025-10-10 12:29:29', 1),
(8, 6, 8, 'olaaa', '2025-10-10 12:30:27', 1),
(12, 7, 9, 'oii', '2025-10-16 16:54:08', 0),
(15, 6, 7, 'tudo bem?', '2025-10-16 18:05:54', 1),
(16, 6, 7, '?você vai amanhã para escola?', '2025-10-16 18:06:21', 1),
(17, 6, 7, 'estou querendo faltar', '2025-10-16 18:06:33', 1),
(18, 6, 7, '#sextouuuuu', '2025-10-16 18:06:45', 1),
(19, 7, 6, 'oie', '2025-10-16 18:08:06', 1),
(20, 7, 6, 'acho que não vou amanhã kkkkkkk', '2025-10-16 18:08:35', 1),
(21, 7, 6, '#sextouu né', '2025-10-16 18:09:05', 1),
(23, 6, 7, 'arrasamos', '2025-10-16 23:19:01', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens_reacoes`
--

CREATE TABLE `mensagens_reacoes` (
  `id` int(11) NOT NULL,
  `id_mensagem` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `reacao` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_hora` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `tipo` enum('mensagem','comentario','curtida') NOT NULL,
  `referencia_id` int(11) NOT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_hora` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `usuario_id`, `remetente_id`, `tipo`, `referencia_id`, `lida`, `data_hora`) VALUES
(5, 8, 6, 'comentario', 14, 1, '2025-10-10 12:31:29'),
(6, 8, 6, 'comentario', 14, 1, '2025-10-16 00:02:35'),
(7, 8, 6, 'curtida', 13, 1, '2025-10-16 00:02:43'),
(8, 8, 7, 'curtida', 14, 1, '2025-10-16 16:22:09'),
(9, 8, 7, 'curtida', 13, 1, '2025-10-16 16:22:14'),
(10, 8, 7, 'comentario', 14, 1, '2025-10-16 16:22:33'),
(11, 8, 7, 'curtida', 13, 1, '2025-10-16 18:10:19'),
(12, 8, 6, 'curtida', 14, 1, '2025-10-16 23:18:00'),
(13, 8, 6, 'comentario', 14, 1, '2025-10-16 23:18:22'),
(14, 7, 6, 'curtida', 17, 1, '2025-10-17 01:41:12'),
(15, 7, 6, 'curtida', 17, 1, '2025-10-17 01:45:08'),
(16, 7, 6, 'curtida', 17, 1, '2025-10-17 01:53:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `postagens`
--

CREATE TABLE `postagens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `conteudo` text NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `postagens`
--

INSERT INTO `postagens` (`id`, `usuario_id`, `conteudo`, `imagem`, `criado_em`) VALUES
(13, 8, 'as sophias são as melhores', NULL, '2025-10-10 15:28:43'),
(14, 8, 'que perfume lindo', 'postagens/68e926bb26e40.jpg', '2025-10-10 15:31:07');

-- --------------------------------------------------------

--
-- Estrutura para tabela `seguidores`
--

CREATE TABLE `seguidores` (
  `id` int(11) NOT NULL,
  `seguidor_id` int(11) NOT NULL,
  `seguido_id` int(11) NOT NULL,
  `data_seguimento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `seguidores`
--

INSERT INTO `seguidores` (`id`, `seguidor_id`, `seguido_id`, `data_seguimento`) VALUES
(2, 7, 6, '2025-10-17 04:14:09'),
(6, 6, 6, '2025-10-17 04:22:56'),
(7, 6, 7, '2025-10-17 04:23:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT 'default.png',
  `status_online` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acesso` datetime DEFAULT current_timestamp(),
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `foto_perfil`, `status_online`, `criado_em`, `ultimo_acesso`, `bio`) VALUES
(6, 'sophia', 'sophia@gmail.com', '$2y$10$zNrhtopF9TIn.BK0e9/RIug2uGK1ZSnEmYMJKc6z7mwbi/PZREooi', 'perfil/68e9193b6af8e.jpg', 0, '2025-10-10 14:33:31', '2025-10-17 02:37:49', ':)'),
(7, 'sophia morgado', 'morgado@gmail.com', '$2y$10$zJaSTL/MTfz92/kRpvRjiemSpiXSEQOxljQiL0w76.KYz0UWrgpCm', 'perfil/68e91c75980a6.jpg', 0, '2025-10-10 14:47:17', '2025-10-17 02:18:43', NULL),
(8, 'luis', 'luis@gmail.com', '$2y$10$1FAd03zTj7FKtxgh8H5XfORniaW44FGkQ9futZ1BDWOo7mYCeWD5e', 'perfil/68e9257c4f68f.png', 0, '2025-10-10 15:25:48', '2025-10-16 23:39:21', NULL),
(9, 'thomas', 'thomas@gmail.com', '$2y$10$raiJiZPPu.fQCKkN/RS9SuwYh5UsAe8W/i1nx9ZdqgWd/kakgB9aq', 'perfil/default.png', 0, '2025-10-10 17:37:02', '2025-10-10 14:37:45', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `postagem_id` (`postagem_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `curtidas`
--
ALTER TABLE `curtidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `postagem_id` (`postagem_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_remetente` (`id_remetente`),
  ADD KEY `id_destinatario` (`id_destinatario`);

--
-- Índices de tabela `mensagens_reacoes`
--
ALTER TABLE `mensagens_reacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_mensagem` (`id_mensagem`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `remetente_id` (`remetente_id`);

--
-- Índices de tabela `postagens`
--
ALTER TABLE `postagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `seguidores`
--
ALTER TABLE `seguidores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seguidor_id` (`seguidor_id`),
  ADD KEY `seguido_id` (`seguido_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `curtidas`
--
ALTER TABLE `curtidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `mensagens_reacoes`
--
ALTER TABLE `mensagens_reacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `postagens`
--
ALTER TABLE `postagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `seguidores`
--
ALTER TABLE `seguidores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`postagem_id`) REFERENCES `postagens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `curtidas`
--
ALTER TABLE `curtidas`
  ADD CONSTRAINT `curtidas_ibfk_1` FOREIGN KEY (`postagem_id`) REFERENCES `postagens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `curtidas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`id_remetente`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `mensagens_ibfk_2` FOREIGN KEY (`id_destinatario`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `mensagens_reacoes`
--
ALTER TABLE `mensagens_reacoes`
  ADD CONSTRAINT `mensagens_reacoes_ibfk_1` FOREIGN KEY (`id_mensagem`) REFERENCES `mensagens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensagens_reacoes_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificacoes_ibfk_2` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `postagens`
--
ALTER TABLE `postagens`
  ADD CONSTRAINT `postagens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `seguidores`
--
ALTER TABLE `seguidores`
  ADD CONSTRAINT `seguidores_ibfk_1` FOREIGN KEY (`seguidor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seguidores_ibfk_2` FOREIGN KEY (`seguido_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
