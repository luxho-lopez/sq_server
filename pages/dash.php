<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/main/includes/auth.php';
if (!hasPermission('dash', 'view')) {
    header('Location: /main/index.php');
    exit;
}
?>
<div class="container">
        <h1>Bienvenido al Dashboard</h1>
        <p>Esta es la página de inicio de tu Dashboard. Aquí puedes ver un resumen de la información más relevante.</p>
        <div class="card-wrapper">
            <div class="material-card">
                <h3>Proyectos Activos</h3>
                <p>Detalles sobre los proyectos recientes.</p>
                <div class="actions">
                    <a href="/main/index.php?page=projects" class="view-button">Ver Proyectos</a>
                </div>
            </div>
            <div class="material-card">
                <h3>Materiales Usados</h3>
                <p>Detalles sobre los materiales usados recientemente.</p>
                <div class="actions">
                    <a href="/main/index.php?page=materials" class="view-button">Ver Materiales</a>
                </div>
            </div>
            <div class="material-card">
                <h3>Inventario Actual</h3>
                <p>Detalles sobre el inventario actual.</p>
                <div class="actions">
                    <a href="/main/index.php?page=inventory" class="view-button">Ver Inventario</a>
                </div>
            </div>
        </div>
    </div>