<?php

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
  
  // Función que renderiza la página de administración
  function sai_admin_page() {
    global $wpdb;
  
    // Verifica si se ha enviado el formulario
    if (isset($_POST['submit'])) {
        // Obtén el token enviado por el formulario
        $title = sanitize_text_field($_POST['title']);
        $token = sanitize_text_field($_POST['token']);
        $id = sanitize_text_field($_POST['id']);
  
        $image_filename = ''; // Nombre de archivo para la imagen
  
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_tmp_name = $_FILES['image']['tmp_name'];
            $image_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
  
            // Genera un nombre único para el archivo basado en su contenido
            $image_hash = md5(file_get_contents($image_tmp_name));
            $image_filename = $image_hash . '.' . $image_extension;
  
            $image_destination = plugin_dir_path(__FILE__) . '../assets/upload/' . $image_filename;
  
            // Mueve el archivo al directorio "images"
            if (move_uploaded_file($image_tmp_name, $image_destination)) {
                // El archivo se ha movido con éxito, ahora puedes guardarlo en la base de datos
                $value = [
                    "id" => $id,
                    "token" => $token,
                    "title" => $title,
                    "image_filename" => $image_filename, // Almacenar el nombre del archivo en lugar de Base64
                ];
  
                // Guarda o actualiza el token en la tabla de WordPress
                $table_name = $wpdb->prefix . 'config_sai';
                $key = 'configKey';
                $existing_token = $wpdb->get_var($wpdb->prepare("SELECT value_data FROM $table_name WHERE key_data = %s", $key));
  
                if ($existing_token) {
                    // Si key_data ya existe, actualiza el registro
                    $wpdb->update(
                        $table_name,
                        array('value_data' => json_encode($value)),
                        array('key_data' => $key),
                        array('%s'),
                        array('%s')
                    );
                } else {
                    // Si key_data no existe, inserta un nuevo registro
                    $wpdb->insert(
                        $table_name,
                        array(
                            'key_data' => $key,
                            'value_data' => json_encode($value),
                        ),
                        array('%s', '%s')
                    );
                }
  
                // Muestra un mensaje de éxito
                echo '<div class="notice notice-success"><p>La información ha sido guardada correctamente.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error al mover el archivo.</p></div>';
            }
        }
    }
  
    // Obtén el token existente de la tabla de WordPress
    $table_name = $wpdb->prefix . 'config_sai';
    $existing_token = $wpdb->get_var($wpdb->prepare("SELECT value_data FROM $table_name WHERE key_data = %s", 'configKey'));
    $token = json_decode($existing_token)->token ?? null;
    $id = json_decode($existing_token)->id ?? null;
    $title = json_decode($existing_token)->title ?? null;
    $image_filename = json_decode($existing_token)->image_filename ?? null;
  
    // Muestra el contenido de la página de administración con el formulario
    echo '
      <div class="container mt-5">
        <div class="row">
        <h2 class="text-left">Activación de Asistente Inteligente</h2>
            <div class="col-md-6">
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="d-flex justify-content-center align-items-center">
                      <div class="form-group justify-content-center align-items-center rounded-circle overflow-hidden" style="width: 100px; height: 100px;">
                          <img class="img-fluid" src="' . plugin_dir_url(__FILE__) . '../assets/upload/' . esc_attr($image_filename) . '" alt="Imagen Circular">
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
  
  function sai_plugin_deactivation() {
      global $wpdb;
      $table_name = $wpdb->prefix . 'config_sai'; // Reemplaza 'nombre_de_la_tabla' con el nombre que utilizaste para tu tabla
  
      $sql = "DROP TABLE IF EXISTS $table_name;";
      $wpdb->query( $sql );
  }
  
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
          $productDiscount = get_post_meta($productId, '_sale_price', true);

          if (empty($productDiscount)) {
            $productDiscount = $productPrice;
          }

          // Guardar los datos del producto en el objeto
          $productData[] = array(
            'id' => $productId,
            'name' => $productName,
            'price' => $productPrice,
            'discount' => $productDiscount,
            'excerpt' => $product->post_excerpt,
            'permalink' => $product->guid,
            // Agrega más propiedades según tus necesidades
        );
      }

      $productsTotal = json_encode($productData, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS);

    }else{
        $productsTotal = null;
    }
  
  
    wp_enqueue_style( 'shortcode-style', plugin_dir_url( __FILE__ ) . '../css/shortcode.css', array(), '1.0' );
    $token_results = sai_get_data_config('configKey');
    $auth = sai_get_data_config('auth');
    $value = json_decode($token_results[0]->value_data);
    $image_base64 = !empty($value->image_filename) ? plugin_dir_url(__FILE__) . '../assets/upload/' . $value->image_filename : plugin_dir_url(__FILE__) . '../assets/sai.png';
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
          <img :src="image_base64" style="width: 100%; height: 100%; border-radius: 50%;" alt="Logo de nuestro asistente virtual" srcset="">
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
                <img :src="message.sender == 'bot' ? image_base64 : imageUser" alt="">
                <p class="chat-message-text" v-html="decodeEntities(message.content)"></p>
              </div>
              <div class="chat-message" v-show="consult">
                <img :src="image_base64" alt="">
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
          el: '#sai', // Elemento HTML al que se vinculará la instancia de Vue
          data: {
              showMessage: true,
              imageUser: '<?php echo plugin_dir_url( __FILE__ ) . "../assets/SaiCloud.svg"; ?>',
              image_base64: '<?php echo esc_url($image_base64); ?>',
              chatOpen: false,
              inputMessage: '',
              messages: [],
              consult: false,
          },
          mounted() {
              // Agrega eventos al cargar el componente
              window.addEventListener('scroll', this.handleScroll);
              window.addEventListener('resize', this.handleResize);
          },
          beforeUnmount() {
              // Elimina eventos antes de desmontar el componente
              window.removeEventListener('scroll', this.handleScroll);
              window.removeEventListener('resize', this.handleResize);
          },
          methods: {
              // Métodos para manejar eventos y acciones
              decodeEntities(value) {
                  const textarea = document.createElement('textarea');
                  textarea.innerHTML = value;
                  return textarea.value;
              },
              handleScroll() {
                  // Lógica para manejar el evento de desplazamiento
                  const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                  this.containerStyle.bottom = scrollTop > 0 ? '20px' : '100px';
              },
              handleResize() {
                  // Lógica para manejar el evento de cambio de tamaño de ventana
                  const screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
                  if (screenWidth < 600) {
                      this.containerStyle.width = '60px';
                      this.containerStyle.height = '60px';
                  } else {
                      this.containerStyle.width = '80px';
                      this.containerStyle.height = '80px';
                  }
              },
              handleButtonClick() {
                  // Lógica para manejar el clic en el botón
                  this.chatOpen = !this.chatOpen;
              },
              closeChat() {
                  // Lógica para cerrar el chat
                  this.chatOpen = false;
              },
              sendMessage() {
                  // Lógica para enviar un mensaje
                  const message = this.inputMessage.trim();
                  if (message !== '') {
                      this.messages.push({
                          id: Date.now(),
                          sender: 'user',
                          content: message,
                          activeComponent: false,
                          component: ''
                      });
                      // Lógica para procesar la respuesta del chatbot
                      this.inputMessage = '';
                      this.$nextTick(() => {
                          const chatBody = document.querySelector('.chat-messages');
                          chatBody.scrollTop = chatBody.scrollHeight;
                      });
                      this.sendServices(message);
                  }
              },
              sendServices(message) {
                  // Lógica para enviar servicios
                  this.consult = true;
                  const token = '<?php echo $token; ?>';
                  const id = '<?php echo $id; ?>';
                  const auth = '<?php echo $bearerToken; ?>';
                  const products = '<?php echo $productsTotal; ?>';
                  const urlBase = 'https://sai-wordpress.sidevtech.com/';
                  if (token !== '') {
                      axios.post(urlBase + 'api/sai/send/' + id, {
                              message: message,
                              products: products,
                              token: token
                          }, {
                              headers: {
                                  Authorization: 'Bearer ' + auth
                              }
                          })
                          .then(response => {
                              this.consult = false;
                              // Agregar la respuesta del servidor al chat
                              this.addMessage({
                                  id: 1,
                                  sender: 'bot',
                                  content: response.data.message,
                                  link: '',
                                  activeComponent: response.data.activeComponent ?? false,
                                  component: response.data.component ?? null,
                                  timestamp: new Date().toLocaleTimeString('es-ES', format)
                              });
                          })
                          .catch(error => {
                              console.log(error);
                              this.addMessage({
                                  id: 1,
                                  sender: 'bot',
                                  content: "El token de activación que ingresaste no es válido.",
                                  link: '',
                                  activeComponent: false,
                                  component: null,
                                  timestamp: new Date().toLocaleTimeString('es-ES', format)
                              });
                              this.consult = false;
                          });
                  } else {
                      this.addMessage({
                          id: 1,
                          sender: 'bot',
                          content: "El asistente no está activo, ingresa un token de activación válido",
                          link: '',
                          activeComponent: false,
                          component: null,
                          timestamp: new Date().toLocaleTimeString('es-ES', format)
                      });
                      this.consult = false;
                  }
              },
              addMessage(message) {
                  // Lógica para agregar mensajes al chat
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

  ?>