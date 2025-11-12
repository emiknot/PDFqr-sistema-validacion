<?php
return [
    // Usuarios
    "login"      => "app/views/usuarios/login.php",
    "registro"   => "app/views/usuarios/registro.php",
    "logout"     => "app/views/usuarios/logout.php",

    // Dashboard
    "dashboard"  => "app/views/layout/dashboard.php",

    // Documentos
    "documentos" => "app/controllers/DocumentosController.php",
    "nuevo"      => "app/views/documentos/editar.php",
    "editar"     => "app/views/documentos/editar.php",
    "eliminar"   => "app/views/documentos/eliminar.php",
    "entregar_doc" => "app/controllers/EntregarDocController.php",


    // Exportaciones
    "export_csv" => "app/controllers/export/exportar_csv.php",

    // PDFs
    "ver_pdf"    => "app/views/documentos/ver_pdf.php",
    "descargar"  => "app/views/documentos/descargar_pdf.php",

    //Cancelar PDFs
    "cancelar" => "app/controllers/CancelarController.php",
    "recuperar" => "app/controllers/recuperar.php",

    // ver ambos documentos ADEUDO Y MEJORAS
    "ver_pdf_ambos" => "app/views/documentos/ver_pdf_ambos.php",

];
