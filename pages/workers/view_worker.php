<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('workers', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;
$error = '';

if (!$worker_id) {
    $error = 'ID de Empleado inválido.';
} else {
    $stmt = $pdo->prepare("SELECT w.*, p.description AS project_description
                            FROM workers w
                            LEFT JOIN projects p ON w.project_id = p.id
                            WHERE w.id = ?");
    $stmt->execute([$worker_id]);
    $worker = $stmt->fetch();

    if (!$worker) {
        $error = 'Empleado no encontrado.';
    } else {
        $filesStmt = $pdo->prepare("SELECT * FROM worker_files WHERE worker_id = ? ORDER BY file_type ASC, uploaded_at DESC");
        $filesStmt->execute([$worker_id]);
        $files = $filesStmt->fetchAll();
    }
}
?>

<div class="container-header">
    <h2>Detalles del Empleado</h2>
    <a class="delete-button" href="/main/index.php?page=workers"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Codigo</th>
                <td data-label="Codigo del empleado"><?php echo htmlspecialchars($worker['worker_code']); ?></td>
            </tr>
            <tr>
                <th>Nombre</th>
                <td data-label="Nombre"><?php echo htmlspecialchars($worker['name']); ?></td>
            </tr>
            <tr>
                <th>Apellido</th>
                <td data-label="Apellido"><?php echo htmlspecialchars($worker['last_name']); ?></td>
            </tr>
            <tr>
                <th>Direccion</th>
                <td data-label="Direccion"><?php echo htmlspecialchars($worker['address']); ?></td>
            </tr>
            <tr>
                <th>Ciudad</th>
                <td data-label="Ciudad"><?php echo htmlspecialchars($worker['city']); ?></td>
            </tr>
            <tr>
                <th>Estado</th>
                <td data-label="Estado"><?php echo htmlspecialchars($worker['estado']); ?></td>
            </tr>
            <tr>
                <th>Codigo Postal</th>
                <td data-label="Codigo Postal"><?php echo htmlspecialchars($worker['postal_code']); ?></td>
            </tr>
            <tr>
                <th>Pais</th>
                <td data-label="Pais"><?php echo htmlspecialchars($worker['country']); ?></td>
            </tr>
            <tr>
                <th>Telefono</th>
                <td data-label="Telefono">
                    <?php if (!empty($worker['phone'])): ?>
                        <?php echo htmlspecialchars($worker['phone']); ?></a>
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Correo</th>
                <td data-label="Correo">
                    <?php if (!empty($worker['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($worker['email']); ?>"><?php echo htmlspecialchars($worker['email']); ?></a>
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Proyecto asignado</th>
                <td data-label="Proyecto asignado"><?php echo $worker['project_description']; ?></td>
            </tr>
            <tr>
                <th>Fecha de Alta</th>
                <td data-label="Fecha"><?php echo $worker['created_at']; ?></td>
            </tr>
            <tr>
                <th>Ultima Actualizacion</th>
                <td data-label="Fecha"><?php echo $worker['updated_at']; ?></td>
            </tr>
            <tr>
                <th>Estado</th>
                <td data-label="Estado"><?php echo $worker['state'] === 'active' ? 'Activo' : 'Inactivo'; ?></td>
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