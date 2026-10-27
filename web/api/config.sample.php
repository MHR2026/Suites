<?php
// Configuración para enviar el formulario a través del correo de IONOS.
//
// 1. Copia este fichero con el nombre  mhr-form-config.php
// 2. Súbelo a la carpeta principal de la web (junto a index.html) o, mejor, a la carpeta
//    que la contiene (un nivel por encima), para que no se borre al subir versiones nuevas.
// 3. Pon la contraseña del buzón info@sonsampoli.com.
// 4. Comprueba que funciona abriendo /api/propietarios.php?check=1

return [
    'host' => 'smtp.ionos.es',
    'port' => 587,
    'user' => 'info@sonsampoli.com',
    'password' => 'CONTRASEÑA_DEL_BUZÓN',
];
