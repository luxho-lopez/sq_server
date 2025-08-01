<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('projects', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $process = trim($_POST['process']);
    $company = trim($_POST['company']);
    $contracting_area = trim($_POST['contracting_area']);
    $federal_entity = trim($_POST['federal_entity']);
    $description = trim($_POST['description']);
    $keyword = trim($_POST['keyword']);
    $state = $_POST['state'];

    if (empty($process) || empty($company) || empty($contracting_area) || empty($federal_entity) || empty($keyword)) {
        $error = 'Todos los datos son obligatorios.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO projects (process, company, contracting_area, federal_entity, description, keyword, state) VALUES (?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$process, $company, $contracting_area, $federal_entity, $description, $keyword, $state]);
            $projectId = $pdo->lastInsertId();
            $success = 'Proyecto creado exitosamente.';
            header('Location: /main/index.php?page=projects');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al crear el proyecto: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-header">
    <h2>Nuevo Proyecto</h2>
    <a class="delete-button" href="/main/index.php?page=projects"><i class="fa-solid fa-arrow-left"></i></a>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
</div>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="process" placeholder="Numero de procedimiento" required>
    <input type="text" name="company" placeholder="Compañia" required>
    <input type="text" name="contracting_area" placeholder="Area" required>
    <input type="text" name="federal_entity" placeholder="Entidad" required>
    <textarea name="description" placeholder="Descripción"></textarea>
    <input type="text" name="keyword" placeholder="Palabra Clave" required>
    <select name="state" required>
        <option value="active">Activo</option>
        <option value="inactive">Inactivo</option>
    </select><br>

    <input type="submit" value="Crear Proyecto">
</form>
