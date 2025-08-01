<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('materials', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$material_id = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
$error = '';
$success = '';

// Obtener categorías para el selector
$catStmt = $pdo->query("SELECT id, name FROM material_categories ORDER BY name ASC");
$categories = $catStmt->fetchAll();

// Obtener unidades de medida para el selector
$unitStmt = $pdo->query("SELECT id, name, abbreviation FROM measurement_units ORDER BY name ASC");
$units = $unitStmt->fetchAll();

// 1. Obtener datos del material
if (!$material_id) {
    $error = 'ID de material inválido.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM materials WHERE id = ?");
    $stmt->execute([$material_id]);
    $material = $stmt->fetch();

    if (!$material) {
        $error = 'Material no encontrado.';
    } else {
        $filesStmt = $pdo->prepare("SELECT * FROM material_files WHERE material_id = ? ORDER BY file_type ASC, uploaded_at DESC");
        $filesStmt->execute([$material_id]);
        $files = $filesStmt->fetchAll();
    }
}

// 2. Procesar formulario de edición y subida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $material_code = trim($_POST['material_code']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'] ?? null;
    $unit_id = $_POST['unit_id'] ?? 1; // Si no se selecciona, usar ID 1
    $has_serial = isset($_POST['has_serial']) ? 1 : 0;

    if (empty($name)) {
        $error = 'El nombre del material es obligatorio.';
    } else {
        $stmt = $pdo->prepare("UPDATE materials SET material_code = ?, name = ?, description = ?, category_id = ?, unit_id = ?, has_serial = ? WHERE id = ?");
        try {
            $stmt->execute([$material_code, $name, $description, $category_id, $unit_id, $has_serial, $material_id]);

            // Subir archivos
            if (!empty($_FILES['files']['name'][0])) {
                $uploadDir = '/main/assets/uploads/materials/' . $material_id . '/';
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
                            $pdo->prepare("INSERT INTO material_files (material_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)")
                                ->execute([$material_id, $originalName, $uploadDir . $safeName, $fileType]);
                        }
                    }
                }
            }

            header("Location: /main/index.php?page=edit_material&material_id=$material_id&success=material_actualizado");
            exit;
        } catch (PDOException $e) {
            $error = 'Error al actualizar el material: ' . $e->getMessage();
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
    $success = $_GET['success'] === 'archivo_eliminado' ? 'Archivo eliminado exitosamente.' : 'Material actualizado exitosamente.';
}
?>

<div class="container-header">
    <h2>Editar Material</h2> 
    <a class="delete-button" href="/main/index.php?page=materials"><i class="fa-solid fa-arrow-left"></i></a>

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

<?php if (!$error && $material): ?>
    <form method="POST" enctype="multipart/form-data">
        
        <input type="text" name="material_code" placeholder="Código unico del material" value="<?php echo htmlspecialchars($material['material_code']); ?>" required>

        <input type="text" name="name" placeholder="Nombre del Material" value="<?php echo htmlspecialchars($material['name']); ?>" required>
        
        <textarea name="description" placeholder="Descripción"><?php echo htmlspecialchars($material['description']); ?></textarea>

        <select name="category_id" required>
            <option value="">-- Categoría --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo $material['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="unit_id" required>
            <?php foreach ($units as $unit): ?>
                <option value="<?php echo $unit['id']; ?>" <?php echo $material['unit_id'] == $unit['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($unit['name']) . " ({$unit['abbreviation']})"; ?>
                </option>
            <?php endforeach; ?>
        </select><br>
        
        <label><input type="checkbox" name="has_serial" <?php echo $material['has_serial'] ? 'checked' : ''; ?>> ¿Tiene número de serie?</label>

        <label for="files">Archivos nuevos:</label>
        <input type="file" name="files[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">

        <input type="submit" value="Actualizar Material">
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