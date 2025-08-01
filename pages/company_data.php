<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';

$user_id = $_SESSION['user_id'] ?? 0;
$error = '';
$success = '';

// Verificar si el usuario es superadministrador
$is_superadmin = false; // Cambia esto según tu lógica para determinar si el usuario es superadministrador
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user && $user['role'] === 'superadmin') {
    $is_superadmin = true;
}

if (!$user_id) {
    header('Location: /main/index.php');
    exit;
}

// Obtener datos de la compañía
$stmt = $pdo->prepare("SELECT * FROM company_data WHERE id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    $error = 'Compañía no encontrada.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_superadmin) {
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    $country = $_POST['country'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $updates = [];
    $params = [];

    if (!empty($name)) {
        $updates[] = "name = ?";
        $params[] = $name;
    }

    if (!empty($address)) {
        $updates[] = "address = ?";
        $params[] = $address;
    }

    if (!empty($city)) {
        $updates[] = "city = ?";
        $params[] = $city;
    }

    if (!empty($state)) {
        $updates[] = "state = ?";
        $params[] = $state;
    }

    if (!empty($postal_code)) {
        $updates[] = "postal_code = ?";
        $params[] = $postal_code;
    }

    if (!empty($country)) {
        $updates[] = "country = ?";
        $params[] = $country;
    }

    if (!empty($phone)) {
        $updates[] = "phone = ?";
        $params[] = $phone;
    }

    if (!empty($email)) {
        $updates[] = "email = ?";
        $params[] = $email;
    }

    if (!empty($_FILES['logotipo']['name'])) {
        $target_dir = '/main/assets/uploads/profiles/';
        $filename = uniqid('profile_') . '_' . basename($_FILES['logotipo']['name']);
        $target_path = $target_dir . $filename;

        $absolute_path = $_SERVER['DOCUMENT_ROOT'] . $target_path;
        if (move_uploaded_file($_FILES['logotipo']['tmp_name'], $absolute_path)) {
            $updates[] = "logotipo = ?";
            $params[] = $target_path;
        } else {
            $error = 'Error al subir la imagen.';
        }
    }

    if (!$error && !empty($updates)) {
        $params[] = $user_id;
        $sql = "UPDATE company_data SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $success = 'Perfil de la compañía actualizado correctamente.';
        header("Location: /main/index.php?page=company_data&success=1");
        exit;
    }
}

if (isset($_GET['success'])) {
    $success = 'Perfil de la compañía actualizado correctamente.';
}
?>

<h2>Perfil de la Compañía</h2>

<?php if ($error): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php elseif ($success): ?>
    <p style="color:green;"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <table>
        <tr>
            <th>Nombre</th>
            <td>
                <?php if ($is_superadmin): ?>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($company['name']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($company['name']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Dirección</th>
            <td>
                <?php if ($is_superadmin): ?>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($company['address']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($company['address']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Ciudad</th>
            <td>
                <?php if ($is_superadmin): ?>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($company['city']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($company['city']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Estado</th>
            <td>
                <?php if ($is_superadmin): ?>
                    <input type="text" name="state" value="<?php echo htmlspecialchars($company['state']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($company['state']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Código Postal</th>
            <td>
                <?php if ($is_superadmin): ?>
                    <input type="text" name="postal_code" value="<?php echo htmlspecialchars($company['postal_code']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($company['postal_code']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>País</th>
            <td>
                <?php if ($is_superadmin): ?>
                    <input type="text" name="country" value="<?php echo htmlspecialchars($company['country']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($company['country']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Teléfono</th>
            <td>
                <?php if ($is_superadmin): ?>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($company['phone'] ?: ''); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($company['phone'] ?: '-'); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Email</th>
            <td>
                <?php if ($is_superadmin): ?>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($company['email']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($company['email']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Página Web</th>
            <td>
                <?php if ($is_superadmin): ?>
                    <input type="text" name="website" value="<?php echo htmlspecialchars($company['website']); ?>">
                <?php else: ?>
                    <?php echo htmlspecialchars($company['website']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Foto de Perfil</th>
            <td>
                <?php if (!empty($company['logotipo'])): ?>
                    <img src="<?php echo $company['logotipo']; ?>" alt="Perfil" style="height: 100px; object-fit: cover;"><br>
                <?php endif; ?>
                <?php if ($is_superadmin): ?>
                    <input type="file" name="logotipo" accept="image/*">
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <br>
    <?php if ($is_superadmin): ?>
        <button type="submit">Actualizar Perfil</button>
    <?php endif; ?>
</form>
