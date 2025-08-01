<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('projects', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$error = '';
$success = '';

// 1. Obtener datos del proyecto
if (!$project_id) {
    $error = 'ID de proyecto inválido.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project) {
        $error = 'Proyecto no encontrado.';
    } else {
        $filesStmt = $pdo->prepare("SELECT * FROM project_files WHERE project_id = ? ORDER BY file_type ASC, uploaded_at DESC");
        $filesStmt->execute([$project_id]);
        $files = $filesStmt->fetchAll();
    }
}

// 2. Procesar formulario de edición y subida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $process = trim($_POST['process']);
    $company = trim($_POST['company']);
    $contracting_area = trim($_POST['contracting_area']);
    $federal_entity = trim($_POST['federal_entity']);
    $description = trim($_POST['description']);
    $keyword = trim($_POST['keyword']);

    if (empty($process) || empty($company) || empty($contracting_area) || empty($federal_entity) || empty($keyword)) {
        $error = 'Los campos de proceso, compañia, area y entidad son obligatorios.';
    } else {
        $stmt = $pdo->prepare("UPDATE projects SET process = ?, company = ?, contracting_area = ?, federal_entity = ?, description = ?, keyword = ? WHERE id = ?");
        try {
            $stmt->execute([$process, $company, $contracting_area, $federal_entity, $description, $keyword, $project_id]);

            // Subir archivos
            if (!empty($_FILES['files']['name'][0])) {
                $uploadDir = '/main/assets/uploads/projects/' . $project_id . '/';
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
                if (!is_dir($fullPath)) mkdir($fullPath, 0777, true);

                foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
                    if ($_FILES['files']['error'][$index] === UPLOAD_ERR_OK) {
                        $originalName = basename($_FILES['files']['name'][$index]);
                        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                        $safeName = uniqid() . '.' . $ext;
                        $targetPath = $fullPath . $safeName;

                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $fileType = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'image' : 'document';
                            $pdo->prepare("INSERT INTO project_files (project_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)")
                                ->execute([$project_id, $originalName, $uploadDir . $safeName, $fileType]);
                        }
                    }
                }
            }

            header("Location: /main/index.php?page=edit_project&project_id=$project_id&success=proyecto_actualizado");
            exit;
        } catch (PDOException $e) {
            $error = 'Error al actualizar el proyecto: ' . $e->getMessage();
        }
    }
}

// 3. Manejar mensajes de retroalimentación
if (isset($_GET['error'])) {
    $errors = [
        'parametros_invalidos' => 'Parámetros inválidos.',
        'no_se_pudo_eliminar_archivo' => 'No se pudo eliminar el archivo físico.',
        'archivo_no_encontrado' => 'El archivo no fue encontrado.',
        'error_base_datos' => 'Error en la base de datos.'
    ];
    $error = $errors[$_GET['error']] ?? 'Error desconocido.';
}

if (isset($_GET['success'])) {
    $success = $_GET['success'] === 'archivo_eliminado' ? 'Archivo eliminado exitosamente.' : 'Proyecto actualizado exitosamente.';
}
?>

<div class="container-header">
    <h2>Editar Proyecto</h2> 
    <a class="delete-button" href="/main/index.php?page=projects"><i class="fa-solid fa-arrow-left"></i></a>

    <?php if ($error): ?>
        <div style="color: #e74c3c; padding: 10px; border-radius: 5px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif ($success): ?>
        <div style="color: #2ecc71; padding: 10px; border-radius: 5px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!$error && $project): ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="process" placeholder="Numero de procedimiento" value="<?php echo htmlspecialchars($project['process']); ?>" required>
        <input type="text" name="company" placeholder="Compañia" value="<?php echo htmlspecialchars($project['company']); ?>" required>
        <input type="text" name="contracting_area" placeholder="Area" value="<?php echo htmlspecialchars($project['contracting_area']); ?>" required>
        <input type="text" name="federal_entity" placeholder="Entidad" value="<?php echo htmlspecialchars($project['federal_entity']); ?>" required>
        <textarea name="description" rows="5" placeholder="Descripción"><?php echo htmlspecialchars($project['description']); ?></textarea>
        <input type="text" name="keyword" placeholder="Palabra Clave" value="<?php echo htmlspecialchars($project['keyword']); ?>" required>

        <label for="files">Archivos nuevos:</label>
        <input type="file" name="files[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">

        <input type="submit" value="Actualizar Proyecto">
    </form>

    <?php if (!empty($files)): ?>
        <h3>Archivos actuales</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 16px;">
            <?php foreach ($files as $file): ?>
                <div style="text-align: center; width: 5cm;">
                    <?php if ($file['file_type'] === 'image'): ?>
                        <a href="<?php echo $file['file_path']; ?>" target="_blank">
                            <img src="<?php echo $file['file_path']; ?>" style="width: 5cm; height: 5cm; object-fit: cover; border: 1px solid #ccc;">
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $file['file_path']; ?>" target="_blank">
                            <i class="fas fa-file-alt" style="font-size: 5cm; color: #777;"></i>
                        </a>
                    <?php endif; ?>
                    <div title="<?php echo htmlspecialchars($file['file_name']); ?>">
                        <?php echo strlen($file['file_name']) > 15 ? substr($file['file_name'], 0, 12) . '…' : $file['file_name']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>