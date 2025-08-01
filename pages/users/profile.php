<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';

$user_id = $_SESSION['user_id'] ?? 0;
$error = '';
$success = '';

if (!$user_id) {
    header('Location: /main/index.php');
    exit;
}

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $error = 'Usuario no encontrado.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $updates = [];
    $params = [];

    if (!empty($username)) {
        $updates[] = "username = ?";
        $params[] = $username;
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $updates[] = "password = ?";
        $params[] = $hashed_password;
    }

    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = '/main/assets/uploads/profiles/';
        $filename = uniqid('profile_') . '_' . basename($_FILES['profile_picture']['name']);
        $target_path = $target_dir . $filename;

        $absolute_path = $_SERVER['DOCUMENT_ROOT'] . $target_path;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $absolute_path)) {
            $updates[] = "profile_picture = ?";
            $params[] = $target_path;
        } else {
            $error = 'Error al subir la imagen.';
        }
    }

    if (!$error && !empty($updates)) {
        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $success = 'Perfil actualizado correctamente.';
        header("Location: /main/index.php?page=profile&success=1");
        exit;
    }
}

if (isset($_GET['success'])) {
    $success = 'Perfil actualizado correctamente.';
}
?>

<h2>Mi Perfil</h2>

<?php if ($error): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php elseif ($success): ?>
    <p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <table>
        <tr>
            <th>Nombre</th>
            <td><?php echo htmlspecialchars($user['first_name']); ?></td>
        </tr>
        <tr>
            <th>Apellido</th>
            <td><?php echo htmlspecialchars($user['last_name']); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
        </tr>
        <tr>
            <th>Teléfono</th>
            <td><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
        </tr>
        <tr>
            <th>Usuario</th>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
        </tr>
        <tr>
            <th>Nueva Contraseña</th>
            <td><input type="password" name="password" placeholder="Dejar en blanco si no cambia"></td>
        </tr>
        <tr>
            <th>Foto de Perfil</th>
            <td>
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="<?php echo $user['profile_picture']; ?>" alt="Perfil" style="width: 100px; height: 100px; object-fit: cover;"><br>
                <?php endif; ?>
                <input type="file" name="profile_picture" accept="image/*">
            </td>
        </tr>
    </table>
    <br>
    <button type="submit">Actualizar Perfil</button>
</form>
