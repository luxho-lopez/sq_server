<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!isSuperAdmin()) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';
$action = $_GET['action'] ?? '';
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Obtener páginas disponibles desde la base de datos (id como clave)
$page_query = $pdo->query("SELECT id, key_name, display_name FROM pages");
$pages = [];
foreach ($page_query->fetchAll(PDO::FETCH_ASSOC) as $page) {
    $pages[$page['id']] = [
        'key_name' => $page['key_name'],
        'display_name' => $page['display_name']
    ];
}

// Procesar actualización de permisos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_permissions' && $user_id) {
    try {
        $pdo->beginTransaction();
        foreach ($pages as $page_id => $page_info) {
            $key = $page_info['key_name'];
            $can_view = isset($_POST["can_view_{$user_id}_{$key}"]) ? 1 : 0;
            $can_edit = isset($_POST["can_edit_{$user_id}_{$key}"]) ? 1 : 0;

            // Usar INSERT ON DUPLICATE KEY para no duplicar registros
            $stmt = $pdo->prepare("
                INSERT INTO permissions (user_id, page_id, can_view, can_edit)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_edit = VALUES(can_edit)
            ");
            $stmt->execute([$user_id, $page_id, $can_view, $can_edit]);
        }
        $pdo->commit();
        $success = 'Permisos actualizados correctamente.';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error al actualizar permisos: ' . $e->getMessage();
    }
}

// Obtener solo usuarios con permisos de administrador
$users = $pdo->query("SELECT id, username FROM users WHERE role IN ('user')");

// Obtener usuarios
// $users = $pdo->query("SELECT id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);

// Obtener permisos actuales
$permissions = [];
$stmt = $pdo->prepare("SELECT user_id, page_id, can_view, can_edit FROM permissions");
$stmt->execute();
$all_permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($all_permissions as $perm) {
    $permissions[$perm['user_id']][$perm['page_id']] = [
        'can_view' => $perm['can_view'],
        'can_edit' => $perm['can_edit']
    ];
}
?>

<h2>Administrar Permisos</h2>

<?php if ($error): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<?php foreach ($users as $user): ?>
    <h3>Permisos del Usuario: <?php echo htmlspecialchars($user['username']); ?></h3>
    <form method="POST" action="/main/index.php?page=admin_permissions&action=update_permissions&user_id=<?php echo $user['id']; ?>">
        <div class="table-wrapper">
            <table>
                <tr>
                    <th>Página</th>
                    <th>Ver</th>
                    <th>Modificar</th>
                </tr>
                <?php foreach ($pages as $page_id => $page_info): ?>
                    <?php
                        $key = $page_info['key_name'];
                        $display = $page_info['display_name'];
                        $perm = $permissions[$user['id']][$page_id] ?? ['can_view' => 0, 'can_edit' => 0];
                    ?>
                    <tr>
                        <td data-label="Página"><?php echo htmlspecialchars($display); ?></td>
                        <td data-label="Ver">
                            <input type="checkbox" name="can_view_<?php echo $user['id']; ?>_<?php echo $key; ?>" 
                                <?php echo $perm['can_view'] ? 'checked' : ''; ?>>
                        </td>
                        <td data-label="Modificar">
                            <input type="checkbox" name="can_edit_<?php echo $user['id']; ?>_<?php echo $key; ?>" 
                                <?php echo $perm['can_edit'] ? 'checked' : ''; ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <input type="submit" value="Guardar Permisos">
    </form>
<?php endforeach; ?>
