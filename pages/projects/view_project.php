<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('projects', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$error = '';

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
?>

<div class="container-header">
    <h2>Detalles del Proyecto</h2>
    <a class="delete-button" href="/main/index.php?page=projects"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Procedimiento</th>
                <td data-label="Procedimiento"><?php echo htmlspecialchars($project['process']); ?></td>
            </tr>
            <tr>
                <th>Compañia</th>
                <td data-label="Compañia"><?php echo htmlspecialchars($project['company']); ?></td>
            </tr>
            <tr>
                <th>Area</th>
                <td data-label="Area"><?php echo htmlspecialchars($project['contracting_area']); ?></td>
            </tr>
            <tr>
                <th>Entidad</th>
                <td data-label="Entidad"><?php echo htmlspecialchars($project['federal_entity']); ?></td>
            </tr>
            <tr>
                <th>Descripción</th>
                <td data-label="Descripción"><?php echo htmlspecialchars($project['description'] ?: '-'); ?></td>
            </tr>
            <tr>
                <th>Palabra Clave</th>
                <td data-label="Palabra Clave"><?php echo htmlspecialchars($project['keyword']); ?></td>
            </tr>
            <tr>
                <th>Fecha de Creación</th>
                <td data-label="Fecha"><?php echo $project['created_at']; ?></td>
            </tr>
            <tr>
                <th>Estado</th>
                <td data-label="Estado"><?php echo $project['state'] === 'active' ? 'Activo' : 'Inactivo'; ?></td>
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