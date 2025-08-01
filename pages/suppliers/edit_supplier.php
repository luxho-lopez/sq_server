<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('suppliers', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$error = '';
$success = '';

// 1. Obtener datos del proveedor
if (!$supplier_id) {
    $error = 'ID de proveedor inválido.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();

    if (!$supplier) {
        $error = 'Proveedor no encontrado.';
    } else {
        $filesStmt = $pdo->prepare("SELECT * FROM supplier_files WHERE supplier_id = ? ORDER BY file_type ASC, uploaded_at DESC");
        $filesStmt->execute([$supplier_id]);
        $files = $filesStmt->fetchAll();
    }
}

// 2. Procesar formulario de edición y subida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $estado = trim($_POST['estado']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);

    if (empty($name) || empty($contact_person) || empty($address) || empty($city)) {
        $error = 'Los campos de proceso, compañia, area y entidad son obligatorios.';
    } else {
        $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact_person = ?, address = ?, city = ?, estado = ?, postal_code = ?, country = ?, phone = ?, email = ?, website = ? WHERE id = ?");
        try {
            $stmt->execute([$name, $contact_person, $address, $city, $estado, $postal_code, $country, $phone, $email, $website, $supplier_id]);

            // Subir archivos
            if (!empty($_FILES['files']['name'][0])) {
                $uploadDir = '/main/assets/uploads/suppliers/' . $supplier_id . '/';
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
                            $pdo->prepare("INSERT INTO supplier_files (supplier_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)")
                                ->execute([$supplier_id, $originalName, $uploadDir . $safeName, $fileType]);
                        }
                    }
                }
            }

            header("Location: /main/index.php?page=edit_supplier&supplier_id=$supplier_id&success=proveedor_actualizado");
            exit;
        } catch (PDOException $e) {
            $error = 'Error al actualizar el proveedor: ' . $e->getMessage();
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
    $success = $_GET['success'] === 'archivo_eliminado' ? 'Archivo eliminado exitosamente.' : 'Proveedor actualizado exitosamente.';
}
?>

<div class="container-header">
    <h2>Editar Proveedor</h2> 
    <a class="delete-button" href="/main/index.php?page=suppliers"><i class="fa-solid fa-arrow-left"></i></a>

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

<?php if (!$error && $supplier): ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Nombre" value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
        <input type="text" name="contact_person" placeholder="Persona de contacto" value="<?php echo htmlspecialchars($supplier['contact_person']); ?>" required>
        <input type="text" name="address" placeholder="Direccion" value="<?php echo htmlspecialchars($supplier['address']); ?>" required>
        <input type="text" name="city" placeholder="Ciudad" value="<?php echo htmlspecialchars($supplier['city']); ?>" required>
        <input type="text" name="estado" placeholder="Estado" value="<?php echo htmlspecialchars($supplier['estado']); ?>" required>
        <input type="text" name="postal_code" placeholder="Codigo Postal" value="<?php echo htmlspecialchars($supplier['postal_code']); ?>" required>
        <input type="text" name="country" placeholder="Pais" value="<?php echo htmlspecialchars($supplier['country']); ?>" required>
        <input type="tel" name="phone" placeholder="Telefono" value="<?php echo htmlspecialchars($supplier['phone']); ?>" required>
        <input type="email" name="email" placeholder="Correo" value="<?php echo htmlspecialchars($supplier['email']); ?>" required>
        <input type="text" name="website" placeholder="Sitio web" value="<?php echo htmlspecialchars($supplier['website']); ?>" required>
        <br>
        <label for="files">Archivos nuevos:</label>
        <input type="file" name="files[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">

        <input type="submit" value="Actualizar Proveedor">
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