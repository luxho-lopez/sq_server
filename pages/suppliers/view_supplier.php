<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('suppliers', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;
$error = '';

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
?>

<div class="container-header">
    <h2>Detalles del Proveedor</h2>
    <a class="delete-button" href="/main/index.php?page=suppliers"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Nombre</th>
                <td data-label="Nombre"><?php echo htmlspecialchars($supplier['name']); ?></td>
            </tr>
            <tr>
                <th>Persona de Contacto</th>
                <td data-label="Persona de Contacto"><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
            </tr>
            <tr>
                <th>Direccion</th>
                <td data-label="Direccion"><?php echo htmlspecialchars($supplier['address']); ?></td>
            </tr>
            <tr>
                <th>Ciudad</th>
                <td data-label="Ciudad"><?php echo htmlspecialchars($supplier['city']); ?></td>
            </tr>
            <tr>
                <th>Estado</th>
                <td data-label="Estado"><?php echo htmlspecialchars($supplier['estado']); ?></td>
            </tr>
            <tr>
                <th>Codigo Postal</th>
                <td data-label="Codigo Postal"><?php echo htmlspecialchars($supplier['postal_code']); ?></td>
            </tr>
            <tr>
                <th>Pais</th>
                <td data-label="Pais"><?php echo htmlspecialchars($supplier['country']); ?></td>
            </tr>
            <tr>
                <th>Telefono</th>
                <td data-label="Telefono"><?php echo htmlspecialchars($supplier['phone']); ?></td>
            </tr>
            <tr>
                <th>Correo</th>
                <td data-label="Correo">
                    <?php if (!empty($supplier['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>"><?php echo htmlspecialchars($supplier['email']); ?></a>
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Sitio web</th>
                <td data-label="Sitio web">
                    <?php if (!empty($supplier['website'])): ?>
                        <a href="https://<?php echo htmlspecialchars($supplier['website']); ?>" target="_blank"><?php echo htmlspecialchars($supplier['website']); ?></a>
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Fecha de Creación</th>
                <td data-label="Fecha"><?php echo $supplier['created_at']; ?></td>
            </tr>
            <tr>
                <th>Estado</th>
                <td data-label="Estado"><?php echo $supplier['state'] === 'active' ? 'Activo' : 'Inactivo'; ?></td>
            </tr>
        </table>
    </div>

    <?php if (!empty($files)): ?>
        <h3>Archivos Asociados</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 16px;">
            <?php foreach ($files as $file): ?>
                <div style="width: 5cm; text-align: center; font-size: 12px;">
                    <?php if ($file['file_type'] === 'image'): ?>
                        <a href="<?php echo $file['file_path']; ?>" target="_blank">
                            <img src="<?php echo $file['file_path']; ?>" alt="imagen" style="width: 5cm; height: 5cm; object-fit: cover; border: 1px solid #ccc;">
                        </a>
                    <?php else: ?>
                        <a href="<?php echo $file['file_path']; ?>" target="_blank" style="text-decoration: none;">
                            <i class="fas fa-file-alt" style="font-size: 5cm; color: #555;"></i>
                        </a>
                    <?php endif; ?>
                    <div title="<?php echo htmlspecialchars($file['file_name']); ?>">
                        <?php echo strlen($file['file_name']) > 15 ? substr($file['file_name'], 0, 12) . '…' : $file['file_name']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No hay archivos asociados.</p>
    <?php endif; ?>

<?php endif; ?>