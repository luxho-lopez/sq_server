<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('invoices', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
$error = '';

if (!$invoice_id) {
    $error = 'ID de Factura inválido.';
} else {
    $stmt = $pdo->prepare("SELECT *, suppliers.name AS supplier_name 
                        FROM invoices
                        INNER JOIN suppliers ON invoices.supplier_id = suppliers.id
                        WHERE invoices.id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        $error = 'Factura no encontrada.';
    } else {
        $filesStmt = $pdo->prepare("SELECT * FROM invoice_files WHERE invoice_id = ? ORDER BY file_type ASC, uploaded_at DESC");
        $filesStmt->execute([$invoice_id]);
        $files = $filesStmt->fetchAll();

        // Se obtienen los materiales asociados a la factura
        $stmt = $pdo->prepare("SELECT im.*, m.material_code, m.name AS material_name, mu.abbreviation AS unit_abbreviation,
                            COALESCE(
                                (
                                    SELECT mf.file_path 
                                    FROM material_files mf 
                                    WHERE mf.material_id = m.id AND mf.file_type = 'image' 
                                    ORDER BY mf.uploaded_at DESC 
                                    LIMIT 1
                                ),
                                '/main/assets/uploads/materials/default_material.png'
                            ) AS image_path
                            FROM inventory_movements im
                            INNER JOIN materials m ON im.material_id = m.id
                            LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                            WHERE invoice_id = ? AND im.type = 'entry'
                            ORDER BY im.id ASC");
        $stmt->execute([$invoice_id]);
        $materials = $stmt->fetchAll();
    }
}
?>

<div class="container-header">
    <h2>Detalles de Factura</h2>
    <a class="delete-button" href="/main/index.php?page=invoices"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Fecha</th>
                <td data-label="Fecha"><?php echo htmlspecialchars($invoice['created_at']); ?></td>
            </tr>
            <tr>
                <th>Código de Factura</th>
                <td data-label="Código de Factura"><?php echo htmlspecialchars($invoice['invoice_code']); ?></td>
            </tr>
            <tr>
                <th>Proveedor</th>
                <td data-label="Proveedor"><?php echo htmlspecialchars($invoice['supplier_name']); ?></td>
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

    <div class="table-wrapper">
        <h3>Materiales Asociados</h3>
        <table id="dataTable">
            <tr>
                <th>#</th>
                <th>Files</th>
                <th>Código</th>
                <th>Material</th>
                <th>Serie</th>
                <th>Cantidad</th>
                <th>Costo</th>
            </tr>
            <?php $row_count = 1; ?>
            <?php foreach ($materials as $material): ?>
                <tr>
                    <td data-label="#"><?php echo $row_count++; ?></td>
                    <td data-label="Files">
                        <img src="<?php echo htmlspecialchars($material['image_path']); ?>" alt="Imagen del material" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" />
                    </td>
                    <td data-label="Código"><?php echo htmlspecialchars($material['material_code']); ?></td>
                    <td data-label="Material"><?php echo htmlspecialchars($material['material_name']); ?></td>
                    <td data-label="Serie"><?php echo htmlspecialchars($material['serial_number']); ?></td>
                    <td data-label="Cantidad"><?php echo htmlspecialchars($material['quantity']); ?> <?php echo htmlspecialchars($material['unit_abbreviation']); ?></td>
                    <td data-label="Costo"><?php echo htmlspecialchars($material['cost']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

<?php endif; ?>