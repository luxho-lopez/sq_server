<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';

if (!isSuperAdmin()) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Editar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $user_id) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $state = $_POST['state'];
    $role = $_POST['role'];

    if (empty($first_name) || empty($last_name) || empty($email) || empty($username)) {
        $error = 'Todos los campos obligatorios deben completarse.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (email = ? OR username = ?) AND id != ?");
        $stmt->execute([$email, $username, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'El correo o el nombre de usuario ya está en uso.';
        } else {
            $query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, username = ?, state = ?, role = ?";
            $params = [$first_name, $last_name, $email, $phone, $username, $state, $role];
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query .= ", password = ?";
                $params[] = $hashed_password;
            }
            $query .= " WHERE id = ?";
            $params[] = $user_id;
            $stmt = $pdo->prepare($query);
            try {
                $stmt->execute($params);
                $success = 'Usuario actualizado exitosamente.';
            } catch (PDOException $e) {
                $error = 'Error al actualizar el usuario: ' . $e->getMessage();
            }
        }
    }
}

// Borrar usuario
if ($action === 'delete' && $user_id) {
    if ($user_id == $_SESSION['user_id']) {
        $error = 'No puedes eliminar tu propia cuenta.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        try {
            $stmt->execute([$user_id]);
            $success = 'Usuario eliminado exitosamente.';
            header('Location: /main/index.php?page=users');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al eliminar el usuario: ' . $e->getMessage();
        }
    }
}

// Activar/Desactivar usuario
if ($action === 'toggle_state' && $user_id) {
    if ($user_id == $_SESSION['user_id']) {
        $error = 'No puedes cambiar el estado de tu propia cuenta.';
    } else {
        $stmt = $pdo->prepare("SELECT state FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_state = $stmt->fetchColumn();
        if ($current_state) {
            $new_state = $current_state === 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE users SET state = ? WHERE id = ?");
            try {
                $stmt->execute([$new_state, $user_id]);
                $success = "Usuario " . ($new_state === 'active' ? 'activado' : 'desactivado') . " exitosamente.";
                header('Location: /main/index.php?page=users');
                exit;
            } catch (PDOException $e) {
                $error = 'Error al cambiar el estado: ' . $e->getMessage();
            }
        } else {
            $error = 'Usuario no encontrado.';
        }
    }
}

// Obtener datos del usuario para editar
$edit_user = null;
if ($action === 'edit' && $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $edit_user = $stmt->fetch();
}

// Obtener todos los usuarios
$users = $pdo->query("SELECT * FROM users")->fetchAll();
?>

<div class="container-header">
<h2>Gestionar Usuarios</h2>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color: green;"><?php echo $success; ?></p>
<?php endif; ?>

<a href="/main/index.php?page=new_user" class="add-button">+</a>
</div>
<!-- Control de tabla -->

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

<!-- Lista de usuarios -->

<div class="table-wrapper">
    <table id="dataTable">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Usuario</th>
            <th>Estado</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td data-label="ID"><?php echo $user['id']; ?></td>
                <td data-label="Nombre"><?php echo htmlspecialchars($user['first_name']); ?></td>
                <td data-label="Apellido"><?php echo htmlspecialchars($user['last_name']); ?></td>
                <td data-label="Correo"><?php echo htmlspecialchars($user['email']); ?></td>
                <td data-label="Teléfono"><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
                <td data-label="Usuario"><?php echo htmlspecialchars($user['username']); ?></td>
                <td data-label="Estado"><?php echo $user['state'] === 'active' ? 'Activo' : 'Inactivo'; ?></td>
                <td data-label="Rol"><?php echo $user['role'] === 'superadmin' ? 'Superadministrador' : 'Usuario'; ?></td>
                <td data-label="Acciones">
                    <div class="actions">
                        <a href="/main/index.php?page=view_user&user_id=<?php echo $user['id']; ?>" title="Ver">
                            <i class="icon fas fa-eye"></i>
                        </a>
                        <a href="/main/index.php?page=edit_user&user_id=<?php echo $user['id']; ?>" title="Editar">
                            <i class="icon fas fa-edit"></i>
                        </a>
                        <?php if($user['role'] !== 'superadmin') { ?>
                        <a href="/main/index.php?page=edit_permissions&user_id=<?php echo $user['id']; ?>" title="Permisos">
                            <i class="icon fas fa-user-shield"></i>
                        <?php } ?>
                        </a>
                        <a href="/main/index.php?page=users&action=toggle_state&user_id=<?php echo $user['id']; ?>" title="<?php echo $user['state'] === 'active' ? 'Desactivar' : 'Activar'; ?>" onclick="return confirm('¿Estás seguro de <?php echo $user['state'] === 'active' ? 'desactivar' : 'activar'; ?> este usuario?');">
                            <i class="icon fas <?php echo $user['state'] === 'active' ? 'fa-toggle-on active-icon' : 'fa-toggle-off'; ?>"></i>
                        </a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>