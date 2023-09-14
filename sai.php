<?php
/*
Plugin Name: Sistema de Asistencia Inteligente
Plugin URI: http://plugin-sai.sidevtech.com/
Description: Plugin que permite crear un asistente virtual en aplicaciones hechas en WordPress.
Version: 1.0
Author: Carlos Andres Arevalo Cortes
Author URI: https://carlosandresarevalo.com/
License: GPL2
*/

// Definir el directorio base del plugin
define('SAI_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Cargar archivos de funciones
require_once(SAI_PLUGIN_DIR . 'includes/sai-functions.php');

// Registrar el menú de administración
add_action('admin_menu', 'sai_admin_menu');

// Registrar activación y desactivación del plugin
register_activation_hook(__FILE__, 'sai_plugin_activation');
register_deactivation_hook(__FILE__, 'sai_plugin_deactivation');

// Agregar shortcode de SAI
add_shortcode('sai', 'sai_shortcode');

// Encolar estilos y scripts
add_action('admin_enqueue_scripts', 'sai_admin_enqueue_scripts');
