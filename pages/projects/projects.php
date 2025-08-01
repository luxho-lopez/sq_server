<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('projects', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Activar/Desactivar proyecto
if ($action === 'toggle_state' && $project_id && hasPermission('projects', 'edit')) {
    $stmt = $pdo->prepare("SELECT state FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $current_state = $stmt->fetchColumn();
    if ($current_state) {
        $new_state = $current_state === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE projects SET state = ? WHERE id = ?");
        try {
            $stmt->execute([$new_state, $project_id]);
            $success = "Proyecto " . ($new_state === 'active' ? 'activado' : 'desactivado') . " exitosamente.";
            header('Location: /main/index.php?page=projects');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al cambiar el estado: ' . $e->getMessage();
        }
    } else {
        $error = 'Proyecto no encontrado.';
    }
}

$projects = $pdo->query("SELECT * FROM projects")->fetchAll();
?>

<div class="container-header">
    <h2>Proyectos</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if (hasPermission('projects', 'edit')): ?>
        <a href="/main/index.php?page=new_project" class="add-button">+</a>
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
            <th>Procedimiento</th>
            <th>Entidad</th>
            <th>Descripción</th>
            <th>Keyword</th>
            <th>Acciones</th>
        </tr>
        <?php $row_count = 1; ?>
        <?php foreach ($projects as $project): ?>
            <tr>
                <td data-label="ID"><?php echo $row_count++; ?></td>
                <td data-label="Procedimiento"><?php echo htmlspecialchars($project['process']); ?></td>
                <td data-label="Entidad"><?php echo htmlspecialchars($project['federal_entity']); ?></td>
                <td data-label="Descripción"><?php echo htmlspecialchars($project['description']); ?></td>
                <td data-label="Palabra Clave"><?php echo htmlspecialchars($project['keyword']); ?></td>
                <td data-label="Acciones">
                    <div class="actions">
                        <a href="/main/index.php?page=view_project&project_id=<?php echo $project['id']; ?>" title="Ver">
                            <i class="icon fas fa-eye"></i>
                        </a>
                        <?php if (hasPermission('projects', 'edit')): ?>
                            <a href="/main/index.php?page=edit_project&project_id=<?php echo $project['id']; ?>" title="Editar">
                                <i class="icon fas fa-edit"></i>
                            </a>
                            <a href="/main/index.php?page=projects&action=toggle_state&project_id=<?php echo $project['id']; ?>" title="<?php echo $project['state'] === 'active' ? 'Desactivar' : 'Activar'; ?>" onclick="return confirm('¿Estás seguro de <?php echo $project['state'] === 'active' ? 'desactivar' : 'activar'; ?> este proyecto?');">
                                <i class="icon fas <?php echo $project['state'] === 'active' ? 'fa-toggle-on active-icon' : 'fa-toggle-off'; ?>"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>