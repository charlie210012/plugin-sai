<?php
/*
Plugin Name: Sistema de Asistencia Inteligente
Plugin URI: http://sai.sidevtech.com/
Description: Plugin que permite crear un asistente virtual en aplicaciones hechas en WordPress.
Version: 1.0
Author: Carlos Andres Arevalo Cortes
Author URI: https://carlosandresarevalo.com/
License: GPL2
*/

// Registra una función para crear el menú de administración
function sai_admin_menu() {
  add_menu_page(
      'sai',      // Título de la página
      'Asistente Virtual',      // Título del menú
      'manage_options',     // Capacidad requerida para acceder
      'sai',      // ID del menú
      'sai_admin_page',  // Función que renderiza la página de administración
      'dashicons-admin-generic',   // Icono del menú (opcional)
      99                      // Posición del menú en el panel
  );
}
add_action( 'admin_menu', 'sai_admin_menu' );

// Función que renderiza la página de administración
function sai_admin_page() {
  global $wpdb;

  // Verifica si se ha enviado el formulario
  if (isset($_POST['submit'])) {
    // Obtén el token enviado por el formulario
    $token = sanitize_text_field($_POST['token']);

    // Guarda o actualiza el token en la tabla de WordPress
    $table_name = $wpdb->prefix . 'config_sai'; // Reemplaza 'config_sai' con el nombre de tu tabla
    $key = 'configKey';
    $existing_token = $wpdb->get_var($wpdb->prepare("SELECT value_data FROM $table_name WHERE key_data = %s", $key));

    if ($existing_token) {
      // Si key_data ya existe, actualiza el registro
      $wpdb->update(
        $table_name,
        array('value_data' => $token),
        array('key_data' => $key),
        array('%s'), // Tipo de dato: cadena de texto (string)
        array('%s') // Tipo de dato: cadena de texto (string)
      );
    } else {
      // Si key_data no existe, inserta un nuevo registro
      $wpdb->insert(
        $table_name,
        array(
          'key_data' => $key,
          'value_data' => $token,
        ),
        array('%s', '%s') // Tipos de datos: cadena de texto (string), cadena de texto (string)
      );
    }

    // Muestra un mensaje de éxito
    echo '<div class="notice notice-success"><p>El token ha sido guardado correctamente.</p></div>';
  }

  // Obtén el token existente de la tabla de WordPress
  $table_name = $wpdb->prefix . 'config_sai'; // Reemplaza 'config_sai' con el nombre de tu tabla
  $existing_token = $wpdb->get_var($wpdb->prepare("SELECT value_data FROM $table_name WHERE key_data = %s", 'configKey'));

  // Muestra el contenido de la página de administración con el formulario
  echo '
    <div class="wrap">
      <h1>Activación de asistente inteligente</h1>
      <p>Ingrese el token de activación:</p>
      <form method="post" action="">
        <input type="text" name="token" placeholder="Token" value="' . esc_attr($existing_token) . '" required>
        <input type="submit" name="submit" value="Guardar">
      </form>
    </div>
  ';
}



function sai_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'config_sai'; // Reemplaza 'nombre_de_la_tabla' con el nombre que desees para tu tabla

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        key_data VARCHAR(255) NOT NULL,
        value_data TEXT NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

function sai_get_data_config($key){
  global $wpdb;
  $table_name = $wpdb->prefix . 'config_sai'; 

  $result = $wpdb->get_results( "SELECT * FROM $table_name WHERE `key_data` = '$key'");

  return $result;
}


function sai_plugin_activation() {
    sai_create_table();
}
register_activation_hook( __FILE__, 'sai_plugin_activation' );

function sai_plugin_deactivation() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'config_sai'; // Reemplaza 'nombre_de_la_tabla' con el nombre que utilizaste para tu tabla

    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query( $sql );
}
register_deactivation_hook( __FILE__, 'sai_plugin_deactivation' );

function sai_shortcode() {
    wp_enqueue_style( 'shortcode-style', plugin_dir_url( __FILE__ ) . 'css/shortcode.css', array(), '1.0' );
    $token_results = sai_get_data_config('configKey');
    $token = '';
    if (!empty($token_results)) {
        $token = $token_results[0]->value_data;
    }
    
    ob_start();
    ?>
    <div id="sai">
        <div v-if="showMessage" class="floating-message" @click="handleButtonClick">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/sai.png'; ?>" style="width: 100%; height: 100%; border-radius: 50%;" alt="Logo de nuestro asistente virtual" srcset="">     
        </div>
        <div v-if="chatOpen" class="chat-container">
            <div class="chat-header">
                <span>Sistema de Asistencia Inteligente (SAI)</span>
                <button class="close-button" @click="closeChat">×</button>
            </div>
            <div class="chat-messages">
                <div
                v-for="message in messages"
                class="chat-message"
                :key="message.id"
                :class="{'chat-message-bot': message.sender === 'bot', 'chat-message-user': message.sender === 'user'}"
                >
                <img :src="message.sender == 'bot' ? imageSai : imageUser" alt="">
                <p class="chat-message-text">{{ message.content }}</p>
                </div>
                <div class="chat-message" v-show="consult">
                <img :src="imageSai" alt="">
                <v-progress-circular
                    indeterminate
                    size="24"
                    color="primary"
                ></v-progress-circular>
                <p class="chat-message-text">Procesando .....</p>
                </div>
            </div>
            <div class="chat-input-container">
                <input v-model="inputMessage" class="chat-input" placeholder="Escribe un mensaje" @keyup.enter="sendMessage">
                <button class="chat-send-button" @click="sendMessage">Enviar</button>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>

    <script>

    const format = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric', hour12: false, timeZone: 'America/Bogota' };

    new Vue({
        el: '#sai',
        data: {
            showMessage: true,
            imageUser: '<?php echo plugin_dir_url( __FILE__ ) . "assets/SaiCloud.svg"; ?>',
            imageSai: '<?php echo plugin_dir_url( __FILE__ ) . "assets/sai.png"; ?>',
            chatOpen: false,
            inputMessage:'',
            messages: [],
            consult: false,
        },
        methods:{
            handleButtonClick(){
                this.chatOpen = !this.chatOpen;
            },
            closeChat() {
                this.chatOpen = false;
            },
            sendMessage() {
              const message = this.inputMessage.trim();
                if (message !== '') {
                    this.messages.push({ id: Date.now(), sender: 'user', content: message , activeComponent:false, component: '' });
                    // Lógica para procesar la respuesta del chatbot
                    this.inputMessage = '';

                    this.$nextTick(() => {
                    const chatBody = document.querySelector('.chatbot-messages');
                    chatBody.scrollTop = chatBody.scrollHeight;
                    });

                    this.sendServices(message);
                }
            },
            sendServices(message){
              this.consult = true;
              const token = '<?php echo $token; ?>';
              const urlBase = 'https://sai.sidevtech.com/';
              if(token !== ''){
                axios.post(urlBase + 'api/sai/send', {
                message: message,
                }, {headers: {
                    Authorization: 'Bearer '+ token,
                  },})
                  .then(response => {
                  console.log(response);

                  this.consult = false;
                  // Agregar la respuesta del servidor al chat
                  this.addMessage({
                    id:1,
                    sender: 'bot',
                    content: response.data.message,
                    link: '',
                    activeComponent: response.data.activeComponent ?? false,
                    component: response.data.component ?? null,
                    timestamp: new Date().toLocaleTimeString('es-ES',format)
                  });

                  this.$nextTick(() => {
                    const chatBody = document.querySelector('.chatbot-messages');
                    chatBody.scrollTop = chatBody.scrollHeight;
                  });
                })
                .catch(error => {
                  console.log(error);
                  this.addMessage({
                    id:1,
                    sender: 'bot',
                    content: "El token de activación que ingresaste no es valido.",
                    link: '',
                    activeComponent:false,
                    component:null,
                    timestamp: new Date().toLocaleTimeString('es-ES',format)
                  });
                  this.consult = false;
                });
              }else{
                this.addMessage({
                    id:1,
                    sender: 'bot',
                    content: "El asistente no esta activo, ingresa un token de activación valido",
                    link: '',
                    activeComponent:false,
                    component:null,
                    timestamp: new Date().toLocaleTimeString('es-ES',format)
                });

                this.consult = false;
              }
             
            },
            addMessage(message) {
                this.messages.push(message);
                // Desplazarse al final del chat para mostrar el último mensaje
                this.$nextTick(() => {
                    const chatBody = document.querySelector('.chat-messages');
                    chatBody.scrollTop = chatBody.scrollHeight;
                });
            },
        }
    });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode( 'sai', 'sai_shortcode' );

// Encola los archivos de Bootstrap
function sai_admin_enqueue_scripts() {
    // Encola el archivo CSS de Bootstrap
    wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css', array(), '5.0.2' );

    // Encola el archivo de script de Bootstrap (requiere jQuery)
    wp_enqueue_script( 'jquery' ); // Asegúrate de que jQuery esté registrado y encolado antes que el archivo de Bootstrap
    wp_enqueue_script( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js', array( 'jquery' ), '5.0.2', true );

    // // Encola el archivo de script que contiene tu componente Vue.js
    // wp_enqueue_script( 'sai-script', plugin_dir_url( __FILE__ ) . 'js/sai.js', array( 'vue' ), '1.0', true );

    // // Encola los estilos CSS necesarios para tu componente Vue.js
    // wp_enqueue_style( 'sai-style', plugin_dir_url( __FILE__ ) . 'css/sai.css', array( 'bootstrap' ), '1.0' );
}
add_action( 'admin_enqueue_scripts', 'sai_admin_enqueue_scripts' );
