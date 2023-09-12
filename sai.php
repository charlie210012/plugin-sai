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
    $title = sanitize_text_field($_POST['title']);
    $token = sanitize_text_field($_POST['token']);
    $id = sanitize_text_field($_POST['id']);

    $image_base64 = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $image_data = file_get_contents($_FILES['image']['tmp_name']);
      if ($image_data !== false) {
        $image_base64 = base64_encode($image_data);
      }
    }

    $value = [
      "id" => $id,
      "token" => $token,
      "title" => $title,
      "image_base64" => $image_base64,
    ];

    // Guarda o actualiza el token en la tabla de WordPress
    $table_name = $wpdb->prefix . 'config_sai'; // Reemplaza 'config_sai' con el nombre de tu tabla
    $key = 'configKey';
    $existing_token = $wpdb->get_var($wpdb->prepare("SELECT value_data FROM $table_name WHERE key_data = %s", $key));

    if ($existing_token) {
      // Si key_data ya existe, actualiza el registro
      $wpdb->update(
        $table_name,
        array('value_data' => json_encode($value)),
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
          'value_data' => json_encode($value),
        ),
        array('%s', '%s') // Tipos de datos: cadena de texto (string), cadena de texto (string)
      );
    }

    // Muestra un mensaje de éxito
    echo '<div class="notice notice-success"><p>La información ha sido guardado correctamente.</p></div>';
  }

  // Obtén el token existente de la tabla de WordPress
  $table_name = $wpdb->prefix . 'config_sai'; // Reemplaza 'config_sai' con el nombre de tu tabla
  $existing_token = $wpdb->get_var($wpdb->prepare("SELECT value_data FROM $table_name WHERE key_data = %s", 'configKey'));
  $token = json_decode($existing_token)->token ?? null; //
  $id = json_decode($existing_token)->id ?? null; 
  $title = json_decode($existing_token)->title ?? null;
  $image_base64 = json_decode($existing_token)->image_base64 ?? null;


  // Muestra el contenido de la página de administración con el formulario
  echo '
    <div class="container mt-5">
      <div class="row">
      <h2 class="text-left">Activación de Asistente Inteligente</h2>
          <div class="col-md-6">
              <form method="post" action="" enctype="multipart/form-data">
                  <div class="d-flex justify-content-center align-items-center">
                    <div class="form-group justify-content-center align-items-center rounded-circle overflow-hidden" style="width: 100px; height: 100px;">
                        <img class="img-fluid" src="data:image/png;base64, '.esc_attr($image_base64).'" alt="Imagen Circular">
                    </div>
                  </div>
                  
          
                  <div class="form-group">
                      <label for="image">Cargar Imagen:</label>
                      <input type="file" class="form-control-file" name="image" accept="image/*">
                  </div>
                  <div class="form-group">
                      <label for="title">Título:</label>
                      <input type="text" class="form-control" name="title" value="' . esc_attr($title) . '" placeholder="Título" required>
                  </div>
                  <div class="form-group">
                      <label for="id">ID DEL CLIENTE:</label>
                      <input type="text" class="form-control" name="id" value="' . esc_attr($id) . '" placeholder="ID del usuario" required>
                  </div>
                  <div class="form-group">
                      <label for="token">TOKEN DEL CLIENTE:</label>
                      <input type="text" class="form-control" name="token" placeholder="Token" value="' . esc_attr($token) . '" required>
                  </div>
                  <br/>
                  <div class="form-group text-center">
                      <button type="submit" class="btn btn-primary" name="submit">Guardar</button>
                  </div>
              </form>
          </div>
          <div class="col-md-6">
            <div class="card">
                <h2>¡Bienvenido al Tutorial!</h2>
                <p>En este tutorial, aprenderás cómo usar nuestro plugin de forma efectiva.</p>
                
                <ul>
                    <li><a target="_blank" href="https://plugin-sai.sidevtech.com/">Link : Plataforma</a></li>
                    <li><a href="#">Sección 1: Crear usuario</a></li>
                    <li><a href="#">Sección 1: Obtener credenciales</a></li>
                    <li><a href="#">Sección 2: Configurar principios</a></li>
                    <li><a href="#">Sección 3: Usar el shortcode</a></li>
                </ul>
                
                <p>¡Comencemos!</p>
            </div>
          </div>
      </div>
  </div>
  ';
}



function sai_create_table() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'config_sai';

  $charset_collate = $wpdb->get_charset_collate();
  $sql = "CREATE TABLE $table_name (
      id INT NOT NULL AUTO_INCREMENT,
      key_data VARCHAR(255) NOT NULL,
      value_data JSON NOT NULL,
      PRIMARY KEY (id)
  ) $charset_collate;";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );
}


function sai_save_auth(){
  global $wpdb;
  $table_name = $wpdb->prefix . 'config_sai'; 
  $token = [
    "authAccess" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIzIiwianRpIjoiNjU5NDdlODk2ZDc5NmJmY2RkYzQ4MDFhMjhhMTcwMWZhZjEzNDIwZjZjZDFhMjhiYTM3Yjc0MjI4NDA4ZDdlZjQ3NmY0M2RmYzM1NjRhNTIiLCJpYXQiOjE2ODk3MjM1MDMuMzg0NDYsIm5iZiI6MTY4OTcyMzUwMy4zODQ0NjMsImV4cCI6NDg0NTM5NzEwMy4zODE3ODMsInN1YiI6IiIsInNjb3BlcyI6W119.SQq_cW0cBxZ0bG02nixzLwxjFPInZe2pqU8avWN7fhUYmT4NLHxF0i370rikkfy-3CdskXdkzjiJRCERyhk4w-y-i3zAIphxfsD4k9a8Cd-kp9aoXg3v4m9VZDx2eOCp0IoRgMWw9ga7gt7h29pvAWEmJsd6V0dK3z0VzwC3FL4VEDd2cqbhiiPPLoz34QSu3v5dRFyC9gzmwlw-x0dPJlv2KxXNLqsXLlvPejMj0NvxL44Ib65PL3hP03bB6uK9AdsdPP5cWm2wFu3SYMQQnb2OXlR7bZwLZmx_bLOfXfEUrEGLcQUzgxeo2dT_1gMf7dOAQXb7I7XZ6bw_rgU0sYqA8AjfP-9DWg_ZD07NlmxmJXujEz-8LYI0BnAvVD98phwrcCnXqZE4Huu354J7PSEcSWuivC2oOEow4XnftsDsaV7qwPEYXHQT2UrUmP4KtZDT8VQCS8tVM822WdMYs4drmtAOn11Ngd98yGFwoHH3vnyuB7Tn1rEm6_LG63buDflCt80MoGE5v0R62MGfdHw58BKLx5Is1jWWsBrZGx8rW-cRQJuENhxBSORnl7x2MlP3CsTWGlRV2uZT-nWNh8R23oLfTNTAeU4hCii53s-pBAYBWUI9f7LiDmPNAu4m_wgSucrT76-PiLyx2CtoAzUjDyXHx5G9-NFTC_x9PHs"
  ];
  $wpdb->insert(
    $table_name,
    array(
      'key_data' => 'auth',
      'value_data' => json_encode($token),
    ),
    array('%s', '%s') // Tipos de datos: cadena de texto (string), cadena de texto (string)
  );
}

function sai_get_data_config($key){
  global $wpdb;
  $table_name = $wpdb->prefix . 'config_sai'; 

  $result = $wpdb->get_results( "SELECT * FROM $table_name WHERE `key_data` = '$key'");

  return $result;
}


function sai_plugin_activation() {
    sai_create_table();
    sai_save_auth();
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
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
     
      $args = array(
          'post_type' => 'product',
          'posts_per_page' => -1,
      );
      
      $products = get_posts($args);

      // Procesar los productos y guardarlos en un objeto
      $productData = array();
      foreach ($products as $product) {
          $productId = $product->ID;
          $productName = $product->post_title;
          $productPrice = get_post_meta($productId, '_regular_price', true);

          // Guardar los datos del producto en el objeto
          $productData[] = array(
            'id' => $productId,
            'name' => $productName,
            'price' => $productPrice,
            'excerpt' => $product->post_excerpt,
            'permalink' => $product->guid,
            // Agrega más propiedades según tus necesidades
        );
      }

      $productsTotal = json_encode($productData, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS);

    }else{
      $productsTotal = null;
    }


    wp_enqueue_style( 'shortcode-style', plugin_dir_url( __FILE__ ) . 'css/shortcode.css', array(), '1.0' );
    $token_results = sai_get_data_config('configKey');
    $auth = sai_get_data_config('auth');
    $value = json_decode($token_results[0]->value_data);
    $valueAuth = json_decode($auth[0]->value_data);
    $token = '';
    if (!empty($token_results)) {
        $token = $value->token;
        $id = $value->id;
        $bearerToken = $valueAuth->authAccess;
        
    }
    
    ob_start();
    ?>
    <div id="sai">
        <div v-if="showMessage" class="floating-message" @click="handleButtonClick">
          <img :src="imageSrc" style="width: 100%; height: 100%; border-radius: 50%;" alt="Logo de nuestro asistente virtual" srcset="">
        </div>
        <div v-if="chatOpen" class="chat-container">
          <div class="chat-header">
              <span><?php echo $value->title; ?></span>
              <button class="close-button" @click="closeChat">Cerrar</button>
          </div>
          <div class="chat-messages">
              <div
                v-for="message in messages"
                class="chat-message"
                :key="message.id"
                :class="{'chat-message-bot': message.sender === 'bot', 'chat-message-user': message.sender === 'user'}"
                >
                <img :src="message.sender == 'bot' ? imageSrc : imageUser" alt="">
                <p class="chat-message-text" v-html="decodeEntities(message.content)"></p>
              </div>
              <div class="chat-message" v-show="consult">
                <img :src="imageSrc" alt="">
                <div class="progress-circular">
                  <div class="progress-circular-inner"></div>
                </div>
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
        image_base64: 'data:image/png;base64, <?php echo $value->image_base64 ?>',
        chatOpen: false,
        inputMessage:'',
        messages: [],
        consult: false,
    },
    computed: {
      imageSrc: function() {
        // Si $image_base64 existe, utiliza la imagen en base64; de lo contrario, utiliza la imagen por defecto
        return this.image_base64 ? this.image_base64 : '<?php echo plugin_dir_url( __FILE__ ) . 'assets/sai.png'; ?>';
      }
    },
    mounted() {
      window.addEventListener('scroll', this.handleScroll);
      window.addEventListener('resize', this.handleResize);
    },
    beforeUnmount() {
      window.removeEventListener('scroll', this.handleScroll);
      window.removeEventListener('resize', this.handleResize);
    },
    methods:{
      decodeEntities(value) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = value;
        return textarea.value;
      },
      handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        this.containerStyle.bottom = scrollTop > 0 ? '20px' : '100px';
      },
      handleResize() {
        const screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
        if (screenWidth < 600) {
          this.containerStyle.width = '60px';
          this.containerStyle.height = '60px';
        } else {
          this.containerStyle.width = '80px';
          this.containerStyle.height = '80px';
        }
      },
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
                  const chatBody = document.querySelector('.chat-messages');
                  chatBody.scrollTop = chatBody.scrollHeight;
                });
                this.sendServices(message);
            }
        },
        sendServices(message){
          this.consult = true;
          const token = '<?php echo $token; ?>';
          const id = '<?php echo $id; ?>';
          const auth = '<?php echo $bearerToken; ?>';
          const products = '<?php echo $productsTotal; ?>';
          const urlBase = 'https://sai-wordpress.sidevtech.com/';
          if(token !== ''){
            axios.post(urlBase + 'api/sai/send/'+ id, {
            message: message,
            products: products,
            token: token
            }, {headers: {
                Authorization: 'Bearer '+ auth,
              },})
              .then(response => {
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
