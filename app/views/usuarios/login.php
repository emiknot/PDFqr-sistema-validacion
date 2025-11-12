<?php
session_start();
include "config/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM QRusuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row["password"])) {
            $_SESSION["usuario"] = $row["usuario"];
            $_SESSION["nombre"] = $row["nombre"];
            $_SESSION["rol"] = $row["rol"];

            header("Location: index.php?url=dashboard");
            exit;
        } else {
            $error = " Contraseña incorrecta";
        }
    } else {
        $error = " Usuario no encontrado";
    }
}
?>

<?php include "app/views/layout/header.php"; ?>

<h3 class="text-center">Subdirección de Recaudación</h3>
<div class="row justify-content-center">
  <div class="col-md-4">
    <div class="card p-4 shadow">
      <h3 class="text-center">Iniciar Sesión</h3>

      <?php if (isset($error)) { ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php } ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input type="text" name="usuario" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
      </form>

      <p class="mt-3 text-center">
        ¿No tienes cuenta?
        <!-- ✅ Enlace corregido que usa el router -->
        <a href="index.php?url=registro">Registrar nuevo usuario</a>
      </p>
    </div>
  </div>
</div>

<?php include "app/views/layout/footer.php"; ?>
