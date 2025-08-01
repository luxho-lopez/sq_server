<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!isSuperAdmin()) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $state = $_POST['state'];
    $role = $_POST['role'];

    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
        $error = 'Todos los campos obligatorios deben completarse.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'El correo o el nombre de usuario ya está en uso.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, username, password, state, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$first_name, $last_name, $email, $phone, $username, $hashed_password, $state, $role]);
                $success = 'Usuario creado exitosamente.';
                header('Location: /main/index.php?page=users');
                exit;
            } catch (PDOException $e) {
                $error = 'Error al crear el usuario: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container-header">
<h2>Nuevo Usuario</h2>

<?php if ($error): ?>
    <p style="color: red;"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color: green;"><?php echo $success; ?></p>
<?php endif; ?>
    <a class="delete-button" href="/main/index.php?page=users"><i class="fa-solid fa-arrow-left"></i></a>
</div>

<form method="POST">
    <input type="text" name="first_name" placeholder="Nombre" required>
    <input type="text" name="last_name" placeholder="Apellido" required>
    <input type="email" name="email" placeholder="Correo electrónico" required>
    <input type="text" name="phone" placeholder="Teléfono">
    <input type="text" name="username" placeholder="Nombre de usuario" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <select name="state" required>
        <option value="active">Activo</option>
        <option value="inactive">Inactivo</option>
    </select>
    <select name="role" required>
        <option value="user">Usuario</option>
        <option value="superadmin">Superadministrador</option>
    </select>
    <input type="submit" value="Crear Usuario">
</form>