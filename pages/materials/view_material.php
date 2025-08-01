<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('materials', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$material_id = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
$error = '';
$stock = 0;

if (!$material_id) {
    $error = 'ID de material inválido.';
} else {
    $stmt = $pdo->prepare("
        SELECT m.*, c.name AS category_name, mu.name AS unit_name, mu.abbreviation AS unit_abbreviation
        FROM materials m
        LEFT JOIN material_categories c ON m.category_id = c.id
        LEFT JOIN measurement_units mu ON m.unit_id = mu.id
        WHERE m.id = ?
    ");
    $stmt->execute([$material_id]);
    $material = $stmt->fetch();

    if (!$material) {
        $error = 'Material no encontrado.';
    } else {
        // Obtener stock según tipo
        if ($material['has_serial']) {
            $stockStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_serials WHERE material_id = ? AND state = 'active'");
            $stockStmt->execute([$material_id]);
            $stock = (int) $stockStmt->fetchColumn();
        } else {
            $stockStmt = $pdo->prepare("SELECT quantity FROM inventory_items WHERE material_id = ?");
            $stockStmt->execute([$material_id]);
            $stock = (int) $stockStmt->fetchColumn();
        }

        // Obtener archivos
        $filesStmt = $pdo->prepare("SELECT * FROM material_files WHERE material_id = ? ORDER BY file_type ASC, uploaded_at DESC");
        $filesStmt->execute([$material_id]);
        $files = $filesStmt->fetchAll();
    }
}
?>

<div class="container-header">
    <h2>Detalles del Material</h2>
    <a class="delete-button" href="/main/index.php?page=materials"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Código</th>
                <td data-label="Código"><?php echo $material['material_code']; ?></td>
            </tr>
            <tr>
                <th>Nombre</th>
                <td data-label="Nombre"><?php echo htmlspecialchars($material['name']); ?></td>
            </tr>
            <tr>
                <th>Descripción</th>
                <td data-label="Descripción"><?php echo nl2br(htmlspecialchars($material['description'])); ?></td>
            </tr>
            <tr>
                <th>Stock</th>
                <td data-label="Stock"><?php echo $stock; ?></td>
            </tr>
            <tr>
                <th>Categoría</th>
                <td data-label="Categoría"><?php echo htmlspecialchars($material['category_name'] ?? 'Sin categoría'); ?></td>
            </tr>
            <tr>
                <th>Medida</th>
                <td data-label="Medida"><?php echo htmlspecialchars($material['unit_name']) . " ({$material['unit_abbreviation']})"; ?></td>
            </tr>
            <tr>
                <th>Tiene N° de Serie</th>
                <td data-label="Serie"><?php echo $material['has_serial'] ? 'Sí' : 'No'; ?></td>
            </tr>
            <tr>
                <th>Estado</th>
                <td data-label="Estado"><?php echo $material['state'] === 'active' ? 'Activo' : 'Inactivo'; ?></td>
            </tr>
            <tr>
                <th>Fecha de Creación</th>
                <td data-label="Fecha"><?php echo $material['created_at']; ?></td>
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
