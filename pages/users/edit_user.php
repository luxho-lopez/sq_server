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
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Usuario no encontrado.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
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
                header('Location: /main/index.php?page=users');
                exit;
            } catch (PDOException $e) {
                $error = 'Error al actualizar el usuario: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container-header">
<h2>Editar Usuario</h2>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color: green;"><?php echo $success; ?></p>
<?php endif; ?>

<?php if (!$error && $user): ?>
    <a class="delete-button" href="/main/index.php?page=users"><i class="fa-solid fa-arrow-left"></i></a>
</div>

    <form method="POST">
        <input type="text" name="first_name" placeholder="Nombre" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
        <input type="text" name="last_name" placeholder="Apellido" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
        <input type="email" name="email" placeholder="Correo electrónico" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        <input type="text" name="phone" placeholder="Teléfono" value="<?php echo htmlspecialchars($user['phone']); ?>">
        <input type="text" name="username" placeholder="Nombre de usuario" value="<?php echo htmlspecialchars($user['username']); ?>" required>
        <input type="password" name="password" placeholder="Nueva contraseña (opcional)">
        <select name="state" required>
            <option value="active" <?php echo $user['state'] === 'active' ? 'selected' : ''; ?>>Activo</option>
            <option value="inactive" <?php echo $user['state'] === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
        </select>
        <select name="role" required>
            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Usuario</option>
            <option value="superadmin" <?php echo $user['role'] === 'superadmin' ? 'selected' : ''; ?>>Superadministrador</option>
        </select>
        <input type="submit" value="Actualizar Usuario">
    </form>
<?php endif; ?>