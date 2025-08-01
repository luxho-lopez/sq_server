<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('invoices', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';

// Obtener los proveedores para el dropdown
$suppliers = [];
$stmt = $pdo->query("SELECT id, name FROM suppliers WHERE state = 'active' ORDER BY name ASC");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_code = trim($_POST['invoice_code']);
   
    // Validación básica
    if (empty($invoice_code)) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        // Verificar si el invoice_code ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE invoice_code = ?");
        $stmt->execute([$invoice_code]);
        if ($stmt->fetchColumn() > 0) {
            $error = "El código de invoice '$invoice_code' ya existe.";
        } else {
            // Insertar invoice
            $stmt = $pdo->prepare("INSERT INTO invoices (invoice_code, supplier_id) VALUES (?, ?)");
            try {
                $stmt->execute([$invoice_code, $_POST['supplier_id']]);
                $invoiceId = $pdo->lastInsertId();

                // Subida de archivos
                if (!empty($_FILES['files']['name'][0])) {
                    $uploadBase = $_SERVER['DOCUMENT_ROOT'] . "/main/assets/uploads/invoices/$invoiceId/";
                    if (!is_dir($uploadBase)) {
                        mkdir($uploadBase, 0777, true);
                    }

                    foreach ($_FILES['files']['tmp_name'] as $i => $tmpName) {
                        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                            $originalName = basename($_FILES['files']['name'][$i]);
                            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                            $fileType = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'image' : 'document';
                            $uniqueName = uniqid() . '.' . $ext;
                            $destination = $uploadBase . $uniqueName;
                            $relativePath = "/main/assets/uploads/invoices/$invoiceId/$uniqueName";

                            move_uploaded_file($tmpName, $destination);

                            $insertFile = $pdo->prepare("INSERT INTO invoice_files (invoice_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)");
                            $insertFile->execute([$invoiceId, $originalName, $relativePath, $fileType]);
                        }
                    }
                }

                header('Location: /main/index.php?page=invoices');
                exit;
            } catch (PDOException $e) {
                $error = 'Error al crear el invoice: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container-header">
    <h2>Nueva Factura</h2>
    <a class="delete-button" href="/main/index.php?page=invoices"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <label>Código de factura:</label>
    <input type="text" name="invoice_code" placeholder="Código único de la factura" required>

    <label>Proveedor:</label>
    <select name="supplier_id" required>
        <option value="">Seleccione un proveedor</option>
        <?php foreach ($suppliers as $supplier): ?>
            <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
        <?php endforeach; ?>
    </select><br>

    <label>Archivos (imágenes o documentos):</label>
    <input type="file" name="files[]" multiple>

    <input type="submit" value="Crear invoice">
</form>