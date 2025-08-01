<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('suppliers', 'edit')) {
    header('Location: /main/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $estado = trim($_POST['estado']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $website = trim($_POST['website']);
    $state = trim($_POST['state']);

    if (empty($name) || empty($contact_person) || empty($address) || empty($city) || empty($estado) || empty($postal_code) || empty($country)) {
        $error = 'Todos los datos son obligatorios.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, address, city, estado, postal_code, country, phone, email, website, state) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$name, $contact_person, $address, $city, $estado, $postal_code, $country, $phone, $email, $website, $state]);
            $projectId = $pdo->lastInsertId();
            $success = 'Proyecto creado exitosamente.';
            header('Location: /main/index.php?page=suppliers');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al crear el registro: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-header">
    <h2>Nuevo Proveedor</h2>
    <a class="delete-button" href="/main/index.php?page=suppliers"><i class="fa-solid fa-arrow-left"></i></a>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
</div>

<form method="POST" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Nombre" required>
    <input type="text" name="contact_person" placeholder="Persona de Contacto" required>
    <input type="text" name="address" placeholder="Dirección" required>
    <input type="text" name="city" placeholder="Ciudad" required>
    <input type="text" name="estado" placeholder="Estado" required>
    <input type="text" name="postal_code" placeholder="Codigo Postal" required>
    <input type="text" name="country" placeholder="Pais" required>
    <input type="tel" name="phone" placeholder="Telefono" required>
    <input type="email" name="email" placeholder="Correo" required>
    <input type="text" name="website" placeholder="Sitio web" required>
    <select name="state" required>
        <option value="active">Activo</option>
        <option value="inactive">Inactivo</option>
    </select><br>

    <input type="submit" value="Crear Registro">
</form>
