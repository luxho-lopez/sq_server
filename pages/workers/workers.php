<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('workers', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;

// Activar/Desactivar proyecto
if ($action === 'toggle_state' && $worker_id && hasPermission('workers', 'edit')) {
    $stmt = $pdo->prepare("SELECT state FROM workers WHERE id = ?");
    $stmt->execute([$worker_id]);
    $current_state = $stmt->fetchColumn();
    if ($current_state) {
        $new_state = $current_state === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE workers SET state = ? WHERE id = ?");
        try {
            $stmt->execute([$new_state, $worker_id]);
            $success = "Proyecto " . ($new_state === 'active' ? 'activado' : 'desactivado') . " exitosamente.";
            header('Location: /main/index.php?page=workers');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al cambiar el estado: ' . $e->getMessage();
        }
    } else {
        $error = 'Proyecto no encontrado.';
    }
}

$workers = $pdo->query("SELECT w.*, p.keyword
                        FROM workers w
                        LEFT JOIN projects p ON w.project_id = p.id
                        ORDER BY w.name")->fetchAll();
?>

<div class="container-header">
    <h2>Empleados</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if (hasPermission('workers', 'edit')): ?>
        <a href="/main/index.php?page=new_worker" class="add-button">+</a>
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
            <th>Codigo</th>
            <th>Nombre</th>
            <th>Telefono</th>
            <th>Correo</th>
            <th>Proyecto</th>
            <th>Acciones</th>
        </tr>
        <?php $row_count = 1; ?>
        <?php foreach ($workers as $worker): ?>
            <tr>
                <td data-label="ID"><?php echo $row_count++; ?></td>
                <td data-label="Codigo de empleado"><?php echo htmlspecialchars($worker['worker_code']); ?></td>
                <td data-label="Nombre"><?php echo htmlspecialchars($worker['name']); ?> <?php echo htmlspecialchars($worker['last_name']); ?></td>
                <td data-label="Telefono">
                    <?php if (!empty($worker['phone'])): ?>
                        <?php echo htmlspecialchars($worker['phone']); ?>
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                </td>
                <td data-label="Correo">
                    <?php if (!empty($worker['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($worker['email']); ?>"><?php echo htmlspecialchars($worker['email']); ?></a>
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                </td>
                <td data-label="Proyecto"><?php echo htmlspecialchars($worker['keyword']); ?></td>
                <td data-label="Acciones">
                    <div class="actions">
                        <a href="/main/index.php?page=view_worker&worker_id=<?php echo $worker['id']; ?>" title="Ver">
                            <i class="icon fas fa-eye"></i>
                        </a>
                        <?php if (hasPermission('workers', 'edit')): ?>
                            <a href="/main/index.php?page=edit_worker&worker_id=<?php echo $worker['id']; ?>" title="Editar">
                                <i class="icon fas fa-edit"></i>
                            </a>
                            <a href="/main/index.php?page=workers&action=toggle_state&worker_id=<?php echo $worker['id']; ?>" title="<?php echo $worker['state'] === 'active' ? 'Desactivar' : 'Activar'; ?>" onclick="return confirm('¿Estás seguro de <?php echo $worker['state'] === 'active' ? 'desactivar' : 'activar'; ?> este proyecto?');">
                                <i class="icon fas <?php echo $worker['state'] === 'active' ? 'fa-toggle-on active-icon' : 'fa-toggle-off'; ?>"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>