<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('containers', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$container_id = isset($_GET['container_id']) ? (int)$_GET['container_id'] : 0;

// Activar/Desactivar almacen
if ($action === 'toggle_state' && $container_id && hasPermission('containers', 'edit')) {
    $stmt = $pdo->prepare("SELECT state FROM containers WHERE id = ?");
    $stmt->execute([$container_id]);
    $current_state = $stmt->fetchColumn();
    if ($current_state) {
        $new_state = $current_state === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE containers SET state = ? WHERE id = ?");
        try {
            $stmt->execute([$new_state, $container_id]);
            $success = "Almacen " . ($new_state === 'active' ? 'activado' : 'desactivado') . " exitosamente.";
            header('Location: /main/index.php?page=containers');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al cambiar el estado: ' . $e->getMessage();
        }
    } else {
        $error = 'Almacen no encontrado.';
    }
}

$containers = $pdo->query("SELECT c.*, u.username
                        FROM containers c
                        LEFT JOIN users u ON c.user_id = u.id
                        ")->fetchAll();
?>

<div class="container-header">
    <h2>Almacen</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if (hasPermission('containers', 'edit')): ?>
        <a href="/main/index.php?page=new_container" class="add-button">+</a>
    <?php endif; ?>
</div>


<div class="table-controls">
    <div>
        <label>
            Mostrar registros
            <select id="rowsPerPage" onchange="changeRowsPerPage(this)">
                <option value="10">10</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">Todos</option>
            </select>
        </label>
    </div>
    <div>
        <input type="text" id="tableSearch" placeholder="Buscar..." onkeyup="filterTable()" />
    </div>
</div>

<div class="table-wrapper">
    <table id="dataTable">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Ubicacion</th>
            <th>Responsable</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
        <?php $row_count = 1; ?>
        <?php foreach ($containers as $container): ?>
            <tr>
                <td data-label="ID"><?php echo $row_count++; ?></td>
                <td data-label="Nombre"><?php echo htmlspecialchars($container['name']); ?></td>
                <td data-label="Ubicacion"><?php echo htmlspecialchars($container['location']); ?></td>
                <td data-label="Responsable"><?php echo htmlspecialchars($container['username']); ?></td>
                <td data-label="Estado"><?php echo htmlspecialchars($container['state']); ?></td>
                <td data-label="Acciones">
                    <div class="actions">
                        <a href="/main/index.php?page=view_container&container_id=<?php echo $container['id']; ?>" title="Ver">
                            <i class="icon fas fa-eye"></i>
                        </a>
                        <?php if (hasPermission('containers', 'edit')): ?>
                            <a href="/main/index.php?page=edit_container&container_id=<?php echo $container['id']; ?>" title="Editar">
                                <i class="icon fas fa-edit"></i>
                            </a>
                            <a href="/main/index.php?page=containers&action=toggle_state&container_id=<?php echo $container['id']; ?>" title="<?php echo $container['state'] === 'active' ? 'Desactivar' : 'Activar'; ?>" onclick="return confirm('¿Estás seguro de <?php echo $container['state'] === 'active' ? 'desactivar' : 'activar'; ?> este proyecto?');">
                                <i class="icon fas <?php echo $container['state'] === 'active' ? 'fa-toggle-on active-icon' : 'fa-toggle-off'; ?>"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>