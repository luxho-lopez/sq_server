<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('invoices', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
$error = '';
$success = '';

// 1. Obtener datos de la factura
if (!$invoice_id) {
    $error = 'ID de proveedor inválido.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        $error = 'Proveedor no encontrado.';
    } else {
        $filesStmt = $pdo->prepare("SELECT * FROM invoice_files WHERE invoice_id = ? ORDER BY file_type ASC, uploaded_at DESC");
        $filesStmt->execute([$invoice_id]);
        $files = $filesStmt->fetchAll();
    }
}

// 2. Procesar formulario de edición y subida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $invoice_code = trim($_POST['invoice_code']);
    $supplier_id = trim($_POST['supplier_id']);

    if (empty($invoice_code)) {
        $error = 'El codigo de factura es obligatorio.';
    } else {
        $stmt = $pdo->prepare("UPDATE invoices SET invoice_code = ?, supplier_id = ? WHERE id = ?");
        try {
            $stmt->execute([$invoice_code, $supplier_id, $invoice_id]);

            // Subir archivos
            if (!empty($_FILES['files']['name'][0])) {
                $uploadDir = '/main/assets/uploads/invoices/' . $invoice_id . '/';
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
                            $pdo->prepare("INSERT INTO invoice_files (invoice_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)")
                                ->execute([$invoice_id, $originalName, $uploadDir . $safeName, $fileType]);
                        }
                    }
                }
            }

            header("Location: /main/index.php?page=edit_invoice&invoice_id=$invoice_id&success=factura_actualizada");
            exit;
        } catch (PDOException $e) {
            $error = 'Error al actualizar la factura: ' . $e->getMessage();
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
    $success = $_GET['success'] === 'archivo_eliminado' ? 'Archivo eliminado exitosamente.' : 'Factura actualizada exitosamente.';
}
?>

<div class="container-header">
    <h2>Editar Factura</h2> 
    <a class="delete-button" href="/main/index.php?page=invoices"><i class="fa-solid fa-arrow-left"></i></a>

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

<?php if (!$error && $invoice): ?>
    <form method="POST" enctype="multipart/form-data">
        <label for="invoice_code">Codigo de factura:</label>
        <input type="text" name="invoice_code" placeholder="Codigo de factura" value="<?php echo htmlspecialchars($invoice['invoice_code']); ?>" required>
        
        <label for="supplier_id">Proveedor:</label>
        <select name="supplier_id" id="supplier_id" required>
            <option value="">Seleccione un proveedor</option>
            <?php
            $suppliers = $pdo->query("SELECT id, name FROM suppliers WHERE state = 'active' ORDER BY name")->fetchAll();
            foreach ($suppliers as $supplier) {
                echo '<option value="' . $supplier['id'] . '"' . ($supplier['id'] == $invoice['supplier_id'] ? ' selected' : '') . '>' . htmlspecialchars($supplier['name']) . '</option>';
            }
            ?>
        </select>
        <br>
        <label for="files">Archivos nuevos:</label>
        <input type="file" name="files[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
        <br>
        <input type="submit" value="Actualizar Factura">
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