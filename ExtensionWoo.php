<?php

class WC_Ext_Payment_For_Vzla_Payments extends WC_Payment_Gateway {

  private static $instance;

  public function __construct() {
    add_action('admin_menu', array( $this, 'agregar_pagina_configuracion' ) );
    add_action('admin_init', array( $this, 'registrar_opciones_pagos_venezuela') );
    // add_action('admin_init', array( $this, 'registrar_opciones_pagos_venezuela_woocommerce') );

    add_action('wp_before_admin_bar_render', array( $this, 'add_custom_admin_bar_link') );
    add_filter('woocommerce_gateway_description', array( $this, 'modificar_descripcion_cod' ), 10, 2);
    add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'reemplazar_instrucciones_cod' ), 10, 2 );
    add_action( 'woocommerce_email_order_meta', array( $this, 'modificar_instrucciones_correo_cod' ), 10, 4 );
  }

  public static function get_instance()
  {
      if (!self::$instance) {
          self::$instance = new self();
      }
      return self::$instance;
  }

  public function cal_tasa_hoy($precio=null, $backEnd=false) {
    if($backEnd) return "%%Valor Producto x Tasa del día%%";
    if(!$precio) return 0;
    $tasa_dia = get_option( 'tasa_dia_config', 1 );

    $toPay = $tasa_dia * $precio;
    $toPay = number_format( floatval( $toPay ), 2, ',', '.' ) . ' Bs.';

    return $toPay;
  }

  public function generar_alerta_al_cliente($alert, $price=0)
  {
    if( !$alert ) return '';

    $alertReplaced = nl2br( $this->replace_shortcodes_for_options( $alert, $price ));

    return '<p style="padding:1rem; border-radius:6px; text-align:center; margin: 1rem 0rem; font-size: 16px; font-weight: bold; border: 1px solid #dce324; background-color: rgba(252, 255, 166, .6)">'.
      $alertReplaced
    .'</p>';
  }

  public function agregar_pagina_configuracion() {
      add_submenu_page(
          'tools.php',
          'Pagos Venezuela',
          'Pagos Venezuela',
          'manage_options',
          'pagos-venezuela',
          array( $this, 'mostrar_pagina_configuracion' )
      );
  }

  public function registrar_opciones_pagos_venezuela() {
    add_settings_section('seccion_pagos_venezuela', 'Configuración de pagos', array( $this, 'mostrar_seccion_pagos_venezuela' ), 'pagos-venezuela');

    add_settings_field('campo_tasa_dia', 'Tasa del día', array( $this, 'mostrar_campo_tasa_dia' ), 'pagos-venezuela', 'seccion_pagos_venezuela');
    add_settings_field('campo_pago_movil_config', 'Pago Móvil', array( $this, 'mostrar_campo_pago_movil_config' ), 'pagos-venezuela', 'seccion_pagos_venezuela');
    add_settings_field('campo_pago_zelle_config', 'Pago Zelle', array( $this, 'mostrar_campo_pago_zelle_config' ), 'pagos-venezuela', 'seccion_pagos_venezuela');
    add_settings_field('campo_pago_banesco_config', 'Pago Banesco', array( $this, 'mostrar_campo_pago_banesco_config' ), 'pagos-venezuela', 'seccion_pagos_venezuela');
    add_settings_field('campo_otros_metodos_pago_config', 'Otros métodos de pago', array( $this, 'mostrar_campo_otros_metodos_pago_config' ), 'pagos-venezuela', 'seccion_pagos_venezuela');

    register_setting('opciones_pagos_venezuela', 'tasa_dia_config');
    register_setting('opciones_pagos_venezuela', 'pago_movil_config');
    register_setting('opciones_pagos_venezuela', 'pago_zelle_config');
    register_setting('opciones_pagos_venezuela', 'pago_banesco_config');
    register_setting('opciones_pagos_venezuela', 'otros_metodos_pago_config');

    // WooCommerce section

    add_settings_section(
        'seccion_pagos_venezuela_woocommerce',
        'Campos WooCommerce',
        array( $this, 'mostrar_seccion_campos_woocommerce' ),
        'pagos-venezuela'
    );

    add_settings_field(
        'campo_descripcion_woocommerce',
        'Descripción',
        array( $this, 'mostrar_campo_descripcion' ),
        'pagos-venezuela',
        'seccion_pagos_venezuela_woocommerce'
    );

    add_settings_field(
        'campo_instrucciones_woocommerce',
        'Instrucciones',
        array( $this, 'mostrar_campo_instrucciones' ),
        'pagos-venezuela',
        'seccion_pagos_venezuela_woocommerce'
    );

    add_settings_field(
        'campo_alerta_para_cliente',
        'Alerta',
        array( $this, 'mostrar_campo_alerta_al_cliente' ),
        'pagos-venezuela',
        'seccion_pagos_venezuela_woocommerce'
    );

    register_setting('opciones_pagos_venezuela', 'descripcion_woocommerce');
    register_setting('opciones_pagos_venezuela', 'instrucciones_woocommerce');
    register_setting('opciones_pagos_venezuela', 'alerta_para_cliente');
  }

  public function mostrar_seccion_pagos_venezuela() {
    echo '<hr>';
    echo '<p>Ingrese la configuración de pagos para Venezuela. <br> Toda la información aquí ingresada se usará en el ecommerce de su sitio web.</p>';
  }

  public function mostrar_campo_tasa_dia() {
      $tasa_dia_config = esc_attr(get_option('tasa_dia_config'));
      echo '<input type="number" step=".01" id="tasa_dia_config" placeholder="10.30" name="tasa_dia_config" value="' . $tasa_dia_config . '" />';
      echo '<p>Bs. por $ '. $tasa_dia_config .'</p>';
  }

  public function mostrar_campo_pago_movil_config() {
      $pago_movil_config = esc_attr(get_option('pago_movil_config'));
      echo '<textarea cols="50" rows="5" id="pago_movil_config" name="pago_movil_config">' . $pago_movil_config . '</textarea>';
  }

  public function mostrar_campo_pago_zelle_config() {
      $pago_zelle_config = esc_attr(get_option('pago_zelle_config'));
      echo '<textarea cols="50" rows="5" id="pago_zelle_config" name="pago_zelle_config">' . $pago_zelle_config . '</textarea>';
  }

  public function mostrar_campo_pago_banesco_config() {
      $pago_banesco_config = esc_attr(get_option('pago_banesco_config'));
      echo '<textarea cols="50" rows="5" id="pago_banesco_config" name="pago_banesco_config">' . $pago_banesco_config . '</textarea>';
  }

  public function mostrar_campo_otros_metodos_pago_config() {
      $otros_metodos_pago_config = esc_attr(get_option('otros_metodos_pago_config'));
      echo '<textarea cols="50" rows="5" id="otros_metodos_pago_config" name="otros_metodos_pago_config">' . $otros_metodos_pago_config . '</textarea>';
  }

  public function mostrar_seccion_campos_woocommerce() {
    echo '<hr>';
    echo 'Ingrese la descripción e instrucciones para el método de pago contrareembolso de WooCommerce.';
  }

  public function mostrar_campo_descripcion() {
      $descripcion_woocommerce = esc_attr(get_option('descripcion_woocommerce'));
      echo '<textarea id="descripcion_woocommerce" name="descripcion_woocommerce" cols="50" rows="5">' . $descripcion_woocommerce . '</textarea>';
      echo '<p>Utiliza [valor_bs_con_tasa_hoy], [pago_movil], [zelle], [banesco_verde], [otro], para mostrar las configuraciones de esos campos a tus usuarios.</p>';
  }

  public function mostrar_campo_instrucciones() {
      $instrucciones_woocommerce = esc_attr(get_option('instrucciones_woocommerce'));
      echo '<textarea id="instrucciones_woocommerce" name="instrucciones_woocommerce" cols="50" rows="10">' . $instrucciones_woocommerce . '</textarea>';
      echo '<p>Utiliza [valor_bs_con_tasa_hoy], [pago_movil], [zelle], [banesco_verde], [otro], para mostrar las configuraciones de esos campos a tus usuarios.</p>';
  }

  public function mostrar_campo_alerta_al_cliente() {
      $alerta_para_cliente = esc_attr(get_option('alerta_para_cliente'));
      echo '<textarea id="alerta_para_cliente" name="alerta_para_cliente" cols="50" rows="4">' . $alerta_para_cliente . '</textarea>';
  }

  public function mostrar_pagina_configuracion()
  {
    $WCdescriptionCustomCOD = get_option( 'descripcion_woocommerce' );
    $WCInstructionCustomCOD = get_option( 'instrucciones_woocommerce' );
    $WCAlertClient = get_option( 'alerta_para_cliente', '' );

    $calTasaHoy = $this->cal_tasa_hoy(null, true);

    ?>
    <div class="wrap">
        <h1>Pagos Venezuela</h1>
        <form method="post" action="options.php">
            <?php
              settings_fields('opciones_pagos_venezuela');
              do_settings_sections('pagos-venezuela');
            ?>
            <hr>
            <!--php
              settings_fields('opciones_pagos_venezuela_woocommerce');
              do_settings_sections('pagos-venezuela-woocommerce');?>
            <hr-->
            <h2>Texto que verán los usuarios:</h2>

          <table class="form-table">
            <tbody>
              <tr>
          			<th scope="row">
                  Description
                  <p style="font-size:8pt; color:#6b6b6b">No podrás ver el precio aquí, tienes que ir al checkout directamente.</p>

                </th>
          			<td>
          				<p class="description">
                    <?php echo nl2br( $this->replace_shortcodes_for_options( $WCdescriptionCustomCOD, $calTasaHoy )) ?>
          				</p>
          			</td>
          		</tr>
              <tr>
          			<th scope="row">
                  Instrucciones
                  <p style="font-size:8pt; color:#6b6b6b">No podrás ver el precio aquí, tienes que ir al checkout directamente.</p>

                </th>
          			<td>
          				<p class="description">
                    <?php
                      echo nl2br( $this->replace_shortcodes_for_options( $WCInstructionCustomCOD, $calTasaHoy ));
                      echo $this->generar_alerta_al_cliente( $WCAlertClient, $calTasaHoy );
                      ?>
          				</p>
          			</td>
          		</tr>
            </tbody>
          </table>

            <?php
            submit_button();
            ?>
        </form>
    </div>
    <?php
  }

  public function replace_shortcodes_for_options($string, $valor="0.00")
  {
    if( !$string ) return '';

    $pago_movil_config = esc_attr(get_option('pago_movil_config', ''));
    $pago_zelle_config = esc_attr(get_option('pago_zelle_config', ''));
    $pago_banesco_config = esc_attr(get_option('pago_banesco_config', ''));
    $otros_metodos_pago_config = esc_attr(get_option('otros_metodos_pago_config', ''));

    $string = str_replace('[valor_bs_con_tasa_hoy]', $valor, $string);
    $string = str_replace('[pago_movil]', '<b>Pago Móvil: </b><br>' . $pago_movil_config, $string);
    $string = str_replace('[zelle]', '<b>Zelle: </b><br>' . $pago_zelle_config, $string);
    $string = str_replace('[banesco_verde]', '<b>Banesco Verde: </b><br>' . $pago_banesco_config, $string);
    $string = str_replace('[otro]', '<b>Otros Métodos de Pago: </b><br>' . $otros_metodos_pago_config, $string);

    return $string;
  }

  public function modificar_descripcion_cod($description, $payment_id)
  {
    $WCdescriptionCustomCOD = get_option( 'descripcion_woocommerce' );

    $totalCheckout = WC()->cart->get_cart_contents_total();
    $price = $this->cal_tasa_hoy( $totalCheckout );

    if ($payment_id === 'cod') {
        $description = nl2br( $this->replace_shortcodes_for_options( $WCdescriptionCustomCOD, $price ));
    }
    return $description;
  }

  function reemplazar_instrucciones_cod( $texto, $pedido_id ) {
    $WCInstructionCustomCOD = get_option( 'instrucciones_woocommerce', '' );
    $WCAlertClient = get_option( 'alerta_para_cliente', '' );
    $fecha_hoy = date( 'd/m/Y' );
    $tasa_dia = get_option( 'tasa_dia_config', 1 );

    // Obtener el objeto de pedido correspondiente al ID del pedido
    $pedido = wc_get_order( $pedido_id );

    // Obtener el precio total del pedido
    $precio_total = $pedido->get_total();
    $price = $this->cal_tasa_hoy( $precio_total );

    // Obtener el método de pago actual
    $metodo_pago = $pedido->get_payment_method();

    // Obtener las instrucciones antiguas del texto
    $instrucciones_antiguas = 'Cash on delivery:';

    // Reemplazar las instrucciones antiguas con las nuevas instrucciones
    $instrucciones_propias = nl2br( $this->replace_shortcodes_for_options( $WCInstructionCustomCOD, $price ) );

    if($WCAlertClient) $instrucciones_propias .= $this->generar_alerta_al_cliente( $WCAlertClient, $price );

    $texto = $instrucciones_propias;

    if ( $metodo_pago == 'cod' ) {
      $texto = str_replace( $instrucciones_antiguas, $instrucciones_propias, $texto );

      // Agregar una nota al pedido
      $pedido->add_order_note(
          'Este pedido se hizo el día: '. $fecha_hoy .' y se selecciono el método de pago de Pago Móvil (Tasa del día '. $tasa_dia .' Bs. x $, total: '. $price .'), Zelle o Banesco Efectivo, por favor verificar transacción para liberar los contenidos.'
      );
    }

    return $texto;
  }

  public function add_custom_admin_bar_link()
  {
      global $wp_admin_bar;

      // Agregamos el link a la barra de administración
      $wp_admin_bar->add_menu(array(
          'id' => 'custom-admin-bar-link',
          'title' => 'Pagos Venezuela',
          'href' => admin_url( 'tools.php?page=pagos-venezuela' )
      ));
  }

  public function modificar_instrucciones_correo_cod( $order, $sent_to_admin, $plain_text, $email )
  {
    $WCInstructionCustomCOD = get_option( 'instrucciones_woocommerce' );
    $WCAlertClient = get_option( 'alerta_para_cliente', '' );

    // Obtener el precio total del pedido
    $precio_total = $order->get_total();
    $price = $this->cal_tasa_hoy( $precio_total );


    if ( 'cod' == $order->get_payment_method() ) {
        $instrucciones_propias = nl2br( $this->replace_shortcodes_for_options( $WCInstructionCustomCOD, $price ) );
        if($WCAlertClient) $instrucciones_propias .= $this->generar_alerta_al_cliente( $WCAlertClient, $price );

        echo '<div>' . $instrucciones_propias . '</div>';
    }
  }

}
