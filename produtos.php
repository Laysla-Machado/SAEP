<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = '';

// Exclusão
if (isset($_GET['excluir'])) {
    try {
        $conn->prepare("DELETE FROM produtos WHERE id = ?")->execute([(int)$_GET['excluir']]);
        $mensagem = 'Produto Excluído com Sucesso.'; $tipoMensagem = 'sucesso';
    } catch (PDOException $e) { $mensagem = 'Erro ao excluir: ' . $e->getMessage(); $tipoMensagem = 'erro'; }
}

// Cadastro / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    
        $data = [
        'codigo'              => trim($_POST['codigo'] ?? ''),
        'nome'                => trim($_POST['nome'] ?? ''),
        'fabricante'          => trim($_POST['fabricante'] ?? '') ?: null,
        'preco'               => floatval($_POST['preco'] ?? 0),
        'estoque_atual'       => (int)($_POST['estoque_atual'] ?? 0),
        'estoque_minimo'      => (int)($_POST['estoque_minimo'] ?? 0),
        'ativo'               => isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1,
        'categoria'           => trim($_POST['categoria'] ?? '') ?: null,
        'material_fabricacao' => trim($_POST['material_fabricacao'] ?? '') ?: null,
    ];

    if (!$data['codigo'] || !$data['nome'] || $data['preco'] <= 0) {
        $mensagem = 'Preencha todos os campos obrigatórios!'; $tipoMensagem = 'erro';
    } else {
        try {
            if ($id > 0) {
                $sql = "UPDATE PRODUTOS SET 
                            codigo = :codigo, 
                            nome = :nome, 
                            fabricante = :fabricante, 
                            preco = :preco, 
                            estoque_atual = :estoque_atual, 
                            estoque_minimo = :estoque_minimo, 
                            ativo = :ativo,  
                            categoria = :categoria, 
                            material_fabricacao = :material_fabricacao 
                        WHERE id = :id";
                $data['id'] = $id;
            } else {
                $sql = "INSERT INTO PRODUTOS (codigo, nome, fabricante, preco, estoque_atual, estoque_minimo, ativo, categoria, material_fabricacao) 
                        VALUES (:codigo, :nome, :fabricante, :preco, :estoque_atual, :estoque_minimo, :ativo, :categoria, :material_fabricacao)";
            }
            $conn->prepare($sql)->execute($data);
            $mensagem = $id > 0 ? 'Produto atualizado!' : 'Produto cadastrado!'; $tipoMensagem = 'sucesso';
        } catch (PDOException $e) { $mensagem = 'Erro: ' . $e->getMessage(); $tipoMensagem = 'erro'; }
    }
    
}

// Buscar produto para edição
$produtoEditar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([(int)$_GET['editar']]);
    $produtoEditar = $stmt->fetch();
}

// Listar
$busca = trim($_GET['busca'] ?? '');
$sql = "SELECT * FROM PRODUTOS WHERE 1=1" . ($busca ? " AND (nome LIKE :b OR codigo LIKE :b OR categoria LIKE :b OR fabricante LIKE :b)" : "") . " ORDER BY nome ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($busca ? ['b' => "%$busca%"] : []);
$produtos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Produtos - Sistema Industrial</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Cadastro de Produtos - Sistema Industrial</h1>
    <p><a href="index.php">⬅️Voltar</a></p>
    <hr>

    <?php if ($mensagem): ?>
        <p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>"><?= htmlspecialchars($mensagem) ?></p>
        <hr>
    <?php endif; ?>

    <h2><?= $produtoEditar ? 'Editar' : 'Novo' ?> Produto</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $produtoEditar['id'] ?? 0 ?>">
        <label>Código: * <input type="text" name="codigo" value="<?= htmlspecialchars($produtoEditar['codigo'] ?? '') ?>" required></label><br>
        <label>Nome: * <input type="text" name="nome" size="40" value="<?= htmlspecialchars($produtoEditar['nome'] ?? '') ?>" required></label><br>
        <label>Fabricante: <input type="text" name="fabricante" value="<?= htmlspecialchars($produtoEditar['fabricante'] ?? '') ?>"></label><br>
        <label>Preço: * <input type="number" step="0.01" name="preco" value="<?= htmlspecialchars($produtoEditar['preco'] ?? '') ?>" required></label><br>
        <label>Estoque Atual: <input type="number" name="estoque_atual" value="<?= htmlspecialchars($produtoEditar['estoque_atual'] ?? 0) ?>"></label><br>
        <label>Estoque Mínimo: <input type="number" name="estoque_minimo" value="<?= htmlspecialchars($produtoEditar['estoque_minimo'] ?? 0) ?>"></label><br>
        <label>Ativo: <input type="checkbox" name="ativo" value="  1" <?= ($produtoEditar['ativo'] ?? 1) ? 'checked' : '' ?>></label><br>
        <label>Categoria: <input type="text" name="categoria" size="40" value="<?= htmlspecialchars($produtoEditar['categoria'] ?? '') ?>"></label><br>
        <label>Material de Fabricação: <input type="text" name="material_fabricacao" value="<?= htmlspecialchars($produtoEditar['material_fabricacao'] ?? '') ?>"></label><br>

        
        

        <button type="submit">Salvar</button>
        <?php if ($produtoEditar): ?><a href="produtos.php"><button type="button">Cancelar</button></a><?php endif; ?>
    </form>

    <hr>

    <h2>Lista de Produtos</h2>
    <form method="GET">
        <label>Buscar <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome, Código ou Categoria..."></label>
        <button type="submit">Buscar</button>
        <?php if ($busca): ?><a href="produtos.php">Limpar</a><?php endif; ?>
    </form>
    <br>

    <?php if ($produtos): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Fabricante</th>
                <th>Preço</th>
                <th>Estoque Atual</th>
                <th>Estoque Mínimo</th>
                <th>Ativo</th>
                <th>Categoria</th>
                <th>Material de Fabricação</th>
            </tr>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['codigo']) ?></td>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['fabricante'] ?? '') ?></td>
                    <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                    <td><?= $p['estoque_atual'] ?></td>
                    <td><?= $p['estoque_minimo'] ?></td>
                    <td><?= $p['ativo'] ? 'Sim' : 'Não' ?></td>
                    <td><?= htmlspecialchars($p['categoria'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['material_fabricacao'] ?? '') ?></td>
                    <td>
                        <a href="produtos.php?editar=<?= $p['id'] ?>">Editar</a>
                        <a href="produtos.php?excluir=<?= $p['id'] ?>" onclick="return confirm('Deseja realmente excluir este produto?')">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nenhum produto cadastrado.</p>
    <?php endif; ?>
</body>
</html>