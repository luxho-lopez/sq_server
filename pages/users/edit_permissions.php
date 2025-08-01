<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!isSuperAdmin()) {
    header('Location: /main/index.php');
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$error = '';
$success = '';

if (!$user_id) {
    $error = 'ID de usuario inválido.';
} else {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Usuario no encontrado.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $page_query = $pdo->query("SELECT id, key_name, display_name FROM pages");
    $pages = $page_query->fetchAll(PDO::FETCH_ASSOC);
    try {
        $pdo->beginTransaction();
        foreach ($pages as $page) {
            $can_view = isset($_POST["can_view_{$page['key_name']}"]) ? 1 : 0;
            $can_edit = isset($_POST["can_edit_{$page['key_name']}"]) ? 1 : 0;

            // Verificar si el permiso ya existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM permissions WHERE user_id = ? AND page_id = ?");
            $stmt->execute([$user_id, $page['id']]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                // Actualizar permiso existente
                $stmt = $pdo->prepare("UPDATE permissions SET can_view = ?, can_edit = ? WHERE user_id = ? AND page_id = ?");
                $stmt->execute([$can_view, $can_edit, $user_id, $page['id']]);
            } else {
                // Insertar nuevo permiso
                $stmt = $pdo->prepare("INSERT INTO permissions (user_id, page_id, can_view, can_edit) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $page['id'], $can_view, $can_edit]);
            }
        }
        $pdo->commit();
        $success = 'Permisos actualizados correctamente.';
        header('Location: /main/index.php?page=users');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error al actualizar permisos: ' . $e->getMessage();
    }
}

$permissions = [];
if (!$error) {
    $stmt = $pdo->prepare("SELECT page_id, can_view, can_edit FROM permissions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $permissions = array_column($permissions, null, 'page_id');
}
?>

<h2>Editar Permisos: <?php echo $error ? 'Usuario' : htmlspecialchars($user['username']); ?></h2>

<?php if ($error): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<?php if (!$error): ?>
    <form method="POST">
        <div class="table-wrapper">
            <table>
                <tr>
                    <th>Página</th>
                    <th>Ver</th>
                    <th>Modificar</th>
                </tr>
                <?php
                $page_query = $pdo->query("SELECT id, key_name, display_name FROM pages");
                $pages = $page_query->fetchAll(PDO::FETCH_ASSOC);
                foreach ($pages as $page):
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($page['display_name']); ?></td>
                        <td>
                            <input type="checkbox" name="can_view_<?php echo htmlspecialchars($page['key_name']); ?>"
                                <?php echo isset($permissions[$page['id']]) && $permissions[$page['id']]['can_view'] ? 'checked' : ''; ?>>
                        </td>
                        <td>
                            <input type="checkbox" name="can_edit_<?php echo htmlspecialchars($page['key_name']); ?>"
                                <?php echo isset($permissions[$page['id']]) && $permissions[$page['id']]['can_edit'] ? 'checked' : ''; ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <input type="submit" value="Guardar Permisos">
    </form>
<?php endif; ?>
