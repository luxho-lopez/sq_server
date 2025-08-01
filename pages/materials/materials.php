<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('materials', 'view')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$material_id = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;

// Activar/Desactivar material
if ($action === 'toggle_state' && $material_id && hasPermission('materials', 'edit')) {
    $stmt = $pdo->prepare("SELECT state FROM materials WHERE id = ?");
    $stmt->execute([$material_id]);
    $current_state = $stmt->fetchColumn();
    if ($current_state) {
        $new_state = $current_state === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE materials SET state = ? WHERE id = ?");
        try {
            $stmt->execute([$new_state, $material_id]);
            $success = "Material " . ($new_state === 'active' ? 'activado' : 'desactivado') . " exitosamente.";
            header('Location: /main/index.php?page=materials');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al cambiar el estado: ' . $e->getMessage();
        }
    } else {
        $error = 'Material no encontrado.';
    }
}

$query = "
    SELECT 
        m.*, 
        mc.name AS category_name,
        mu.name AS unit_name, mu.abbreviation AS unit_abbreviation,
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
    FROM materials m
    INNER JOIN material_categories mc ON m.category_id = mc.id
    INNER JOIN measurement_units mu ON m.unit_id = mu.id
    ORDER BY m.name ASC
";

$materials = $pdo->query($query)->fetchAll();
?>

<div class="container-header">
    <h2>Materiales</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if (hasPermission('materials', 'edit')): ?>
        <a href="/main/index.php?page=new_material" class="add-button">+</a>
    <?php endif; ?>
</div>

<div class="table-controls">
    <div class="controls">
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

<div class="table-wrapper" id="tableView">
    <table id="dataTable">
        <tr>
            <th style="width: 20px">ID</th>
            <th style="width: 50px">Files</th>
            <th>Codigo</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Categoria</th>
            <th>Medida</th>
            <th>Acciones</th>
        </tr>
        <?php $row_count = 1; ?>
        <?php foreach ($materials as $material): ?>
            <tr>
                <td data-label="ID"><?php echo $row_count++; ?></td>
                <td data-label="Files">
                    <img src="<?php echo htmlspecialchars($material['image_path']); ?>" alt="Imagen del material" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" />
                </td>
                <td data-label="Codigo"><?php echo htmlspecialchars($material['material_code']); ?></td>
                <td data-label="Nombre"><?php echo htmlspecialchars($material['name']); ?></td>
                <td data-label="Descripción"><?php echo $material['description']; ?></td>
                <td data-label="Categoria"><?php echo $material['category_name']; ?></td>
                <td data-label="Medida"><?php echo $material['unit_abbreviation']; ?></td>
                <td data-label="Acciones">
                    <div class="actions">
                        <a href="/main/index.php?page=view_material&material_id=<?php echo $material['id']; ?>" title="Ver">
                            <i class="icon fas fa-eye"></i>
                        </a>
                        <?php if (hasPermission('materials', 'edit')): ?>
                            <a href="/main/index.php?page=add_entry&material_id=<?php echo $material['id']; ?>" title="Add Entry">
                                <i class="icon fa-solid fa-square-plus"></i>
                            </a>
                            <a href="/main/index.php?page=edit_material&material_id=<?php echo $material['id']; ?>" title="Editar">
                                <i class="icon fas fa-edit"></i>
                            </a>
                            <a href="/main/index.php?page=materials&action=toggle_state&material_id=<?php echo $material['id']; ?>" title="<?php echo $material['state'] === 'active' ? 'Desactivar' : 'Activar'; ?>" onclick="return confirm('¿Estás seguro de <?php echo $material['state'] === 'active' ? 'desactivar' : 'activar'; ?> este material?');">
                                <i class="icon fas <?php echo $material['state'] === 'active' ? 'fa-toggle-on active-icon' : 'fa-toggle-off'; ?>"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="card-wrapper" id="cardView" style="display: none;">
    <?php foreach ($materials as $material): ?>
        <div class="material-card" data-filter-text="<?php echo strtolower($material['name'] . ' ' . $material['description'] . ' ' . $material['category_name'] . ' ' . $material['unit_abbreviation']); ?>">
            <img src="<?php echo htmlspecialchars($material['image_path']); ?>" alt="Imagen del material" style="width: 100%; height: 180px; object-fit: cover; border-radius: 6px; margin-bottom: 10px;" />
            <h3><?php echo htmlspecialchars($material['name']); ?></h3>
            <p><strong>Descripción:</strong> <?php echo $material['description']; ?></p>
            <p><strong>Categoría:</strong> <?php echo $material['category_name']; ?></p>
            <p><strong>Medida:</strong> <?php echo $material['unit_abbreviation']; ?></p>
            <div class="actions">
                <a href="/main/index.php?page=view_material&material_id=<?php echo $material['id']; ?>" title="Ver">
                    <i class="icon fas fa-eye"></i>
                </a>
                <?php if (hasPermission('materials', 'edit')): ?>
                    <a href="/main/index.php?page=edit_material&material_id=<?php echo $material['id']; ?>" title="Editar">
                        <i class="icon fas fa-edit"></i>
                    </a>
                    <a href="/main/index.php?page=materials&action=toggle_state&material_id=<?php echo $material['id']; ?>" title="<?php echo $material['state'] === 'active' ? 'Desactivar' : 'Activar'; ?>" onclick="return confirm('¿Estás seguro de <?php echo $material['state'] === 'active' ? 'desactivar' : 'activar'; ?> este material?');">
                        <i class="icon fas <?php echo $material['state'] === 'active' ? 'fa-toggle-on active-icon' : 'fa-toggle-off'; ?>"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>