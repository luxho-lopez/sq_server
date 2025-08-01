<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('suppliers', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0;

// Activar/Desactivar proyecto
if ($action === 'toggle_state' && $supplier_id && hasPermission('suppliers', 'edit')) {
    $stmt = $pdo->prepare("SELECT state FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $current_state = $stmt->fetchColumn();
    if ($current_state) {
        $new_state = $current_state === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE suppliers SET state = ? WHERE id = ?");
        try {
            $stmt->execute([$new_state, $supplier_id]);
            $success = "Proyecto " . ($new_state === 'active' ? 'activado' : 'desactivado') . " exitosamente.";
            header('Location: /main/index.php?page=suppliers');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al cambiar el estado: ' . $e->getMessage();
        }
    } else {
        $error = 'Proyecto no encontrado.';
    }
}

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
?>

<div class="container-header">
    <h2>Proveedores</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if (hasPermission('suppliers', 'edit')): ?>
        <a href="/main/index.php?page=new_supplier" class="add-button">+</a>
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
            <th>Contacto</th>
            <th>Telefono</th>
            <th>Correo</th>
            <th>Sitio web</th>
            <th>Acciones</th>
        </tr>
        <?php $row_count = 1; ?>
        <?php foreach ($suppliers as $supplier): ?>
            <tr>
                <td data-label="ID"><?php echo $row_count++; ?></td>
                <td data-label="Nombre"><?php echo htmlspecialchars($supplier['name']); ?></td>
                <td data-label="Persona de contacto"><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                <td data-label="Telefono"><?php echo htmlspecialchars($supplier['phone']); ?></td>
                <td data-label="Correo">
                    <?php if (!empty($supplier['email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>"><?php echo htmlspecialchars($supplier['email']); ?></a>
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                </td>
                <td data-label="Sitio web">
                    <?php if (!empty($supplier['website'])): ?>
                        <a href="https://<?php echo htmlspecialchars($supplier['website']); ?>" target="_blank"><?php echo htmlspecialchars($supplier['website']); ?></a>
                    <?php else: ?>
                        No disponible
                    <?php endif; ?>
                </td>
                <td data-label="Acciones">
                    <div class="actions">
                        <a href="/main/index.php?page=view_supplier&supplier_id=<?php echo $supplier['id']; ?>" title="Ver">
                            <i class="icon fas fa-eye"></i>
                        </a>
                        <?php if (hasPermission('suppliers', 'edit')): ?>
                            <a href="/main/index.php?page=edit_supplier&supplier_id=<?php echo $supplier['id']; ?>" title="Editar">
                                <i class="icon fas fa-edit"></i>
                            </a>
                            <a href="/main/index.php?page=suppliers&action=toggle_state&supplier_id=<?php echo $supplier['id']; ?>" title="<?php echo $supplier['state'] === 'active' ? 'Desactivar' : 'Activar'; ?>" onclick="return confirm('¿Estás seguro de <?php echo $supplier['state'] === 'active' ? 'desactivar' : 'activar'; ?> este proyecto?');">
                                <i class="icon fas <?php echo $supplier['state'] === 'active' ? 'fa-toggle-on active-icon' : 'fa-toggle-off'; ?>"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>