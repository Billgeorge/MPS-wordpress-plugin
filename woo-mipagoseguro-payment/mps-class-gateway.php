<?php
/**
 * Copyright © Lyra Network and contributors.
 * This file is part of PayZen plugin for WooCommerce. See COPYING.md for license details.
 *
 * @author    Mipagoseguro (https://www.mipagoseguro.co/)
 * @author    Jorge Espinosa
 * @copyright Mipagoseguro
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
 */

if (! defined('ABSPATH')) {
    exit;
}

class MPS_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'mipagoseguro';
        $this->icon = 'https://369969691f476073508a-60bf0867add971908d4f26a64519c2aa.ssl.cf5.rackcdn.com/logos/logo_epayco_200px.png';
        $this->has_fields = false;
        $this->method_title = __('Mipagoseguro-compra protegida', 'mipagoseguro_woocommerce');
        $this->method_description = __('Ofrece compra protegida y acepta tarjetas de crédito y PSE.', 'mipagoseguro_woocommerce');
        $this->order_button_text = __('Pagar', 'mipagoseguro_woocommerce');
        $this->init_form_fields();
        $this->init_settings();

        //form variables
        $this->title = $this->get_option('mps_title');
        $this->mps_customerid = $this->get_option('mps_customerid');
        $this->mps_secretkey = $this->get_option('mps_secretkey');
        $this->mps_publickey = $this->get_option('mps_publickey');
        $this->mps_description = $this->get_option('mps_description');
        $this->mps_testmode = $this->get_option('mps_testmode');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action( 'woocommerce_api_mps', array( $this, 'process_mps_response' ) );
    }

    public function init_form_fields()
            {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Habilitar/Deshabilitar', 'mipagoseguro_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Mostrar Mipagoseguro en Checkout', 'mipagoseguro_woocommerce'),
                        'default' => 'yes'
                    ),
                    'mps_title' => array(
                        'title' => __('<span class="mps-required">Título</span>', 'mipagoseguro_woocommerce'),
                        'type' => 'text',
                        'description' => __('Título que el usuario ve en el checkout, usa uno que resalte la compra protegida de Mipagoseguro.', 'mipagoseguro_woocommerce'),
                        'default' => __('Compra protegida Mipagoseguro', 'mipagoseguro_woocommerce'),                        
                    ),
                    'mps_description' => array(
                        'title' => __('<span class="epayco-required">Descripción</span>', 'mipagoseguro_woocommerce'),
                        'type' => 'textarea',
                        'description' => __('Corresponde a la descripción que verá el usuario durante el checkout', 'mipagoseguro_woocommerce'),
                        'default' => __('Servicio de compra protegida: Tu dinero no llega al vendedor, hasta que no recibas tu producto.', 'mipagoseguro_woocommerce'),                        
                    ),
                    'mps_customerid' => array(
                        'title' => __('<span class="epayco-required">ID_CLIENTE</span>', 'mipagoseguro_woocommerce'),
                        'type' => 'text',
                        'description' => __('ID de tu cuenta en Mipagoseguro, búscalo en la sección editar perfil en tu cuenta', 'mipagoseguro_woocommerce'),
                        'default' => '',                        
                        'placeholder' => '',
                    ),
                    'mps_secretkey' => array(
                        'title' => __('<span class="epayco-required">MPS_KEY</span>', 'mipagoseguro_woocommerce'),
                        'type' => 'text',
                        'description' => __('Llave para asegurar información enviada y recibida a Mipagoseguro. Ingresa a Mipagoseguro y en la sección perfil encuentras esta información.', 'mipagoseguro_woocommerce'),
                        'default' => '',
                        'placeholder' => ''
                    ),
                    'mps_publickey' => array(
                        'title' => __('<span class="epayco-required">MPS_PUBLIC_KEY</span>', 'mipagoseguro_woocommerce'),
                        'type' => 'text',
                        'description' => __('LLave para asegurar información de cliente y Mipagoseguro. Ingresa a Mipagoseguro y en la sección perfil encuentras esta información.', 'mipagoseguro_woocommerce'),
                        'default' => '',
                        'placeholder' => ''
                    ),
                    'mps_testmode' => array(
                        'title' => __('Modo pruebas', 'mipagoseguro_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar/Deshabilitar el modo de pruebas', 'mipagoseguro_woocommerce'),
                        'description' => __('Habilite para lanzar transacciones de prueba.', 'mipagoseguro_woocommerce'),
                        'default' => 'false',
                    )
                );
            }

            function get_pages($title = false, $indent = true) {
                $wp_pages = get_pages('sort_column=menu_order');
                $page_list = array();
                if ($title) $page_list[] = $title;
                foreach ($wp_pages as $page) {
                    $prefix = '';
                    // show indented child pages?
                    if ($indent) {
                        $has_parent = $page->post_parent;
                        while($has_parent) {
                            $prefix .=  ' - ';
                            $next_page = get_page($has_parent);
                            $has_parent = $next_page->post_parent;
                        }
                    }
                    // add to page list array array
                    $page_list[$page->ID] = $prefix . $page->post_title;
                }
                return $page_list;
            }

            public function process_mps_response(){
                $order = wc_get_order( $_GET['id'] );
	            $order->payment_complete();
	            $order->reduce_order_stock();
            }

            public function process_payment( $order_id ) {

                if (!function_exists('write_log')) {

                    function write_log($log) {
                        if (true === WP_DEBUG) {
                            if (is_array($log) || is_object($log)) {
                                error_log(print_r($log, true));
                            } else {
                                error_log($log);
                            }
                        }
                    }
                
                }                
 
                global $woocommerce;

                $order = wc_get_order( $order_id );
                
                $bodyToken = [
                    'grant_type'=>'client_credentials'                    
                ];
                write_log($bodyToken);
                $optionsToken = [
                    'body'        => $bodyToken,
                    'headers'     => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Authorization' =>'Basic ' . base64_encode( $this->get_option('mps_publickey') . ':' . $this->get_option('mps_secretkey') )
                    
                    ],
                    'timeout'     => 60,
                    'redirection' => 5,
                    'blocking'    => true,
                    'httpversion' => '1.0',
                    'sslverify'   => false,
                    'data_format' => 'body',
                ];
                write_log($optionsToken);
                $responseToken = wp_remote_post( 'http://localhost:8080/oauth/token', $optionsToken );
                write_log($responseToken);
                
                if( !is_wp_error( $responseToken ) ) {
                    $responseBody = wp_remote_retrieve_body( $responseToken );
                    write_log('body token response');
                    write_log($responseBody);
                    $responseBodyJson = json_decode($responseBody, true);
                    $token = $responseBodyJson['access_token'];
                    write_log($token);

                    $items = $order->get_items();
                    $productsName='';
                    foreach ( $items as $item ) {
                        $productsName = $productsName . ' ' .  $item->get_name();
                    }

                    $message = get_woocommerce_currency() . '+' . $productsName . '+' . 
                    $order->get_billing_email() . '+' . $order->get_billing_first_name() . '+' . $order->get_billing_last_name() . '+' . 
                    $this->get_option('mps_customerid') . '+' . $order->get_billing_phone() . '+' . $order_id . '+' . 
                    'true' . '+' . $order->get_total() . '+' . $this->get_option('mps_publickey');

                    $signature = base64_encode (hash_hmac ('sha256', $message, $this->get_option('mps_publickey'), true));
                    $body = [
                        'orderId'=>$order_id,
                        'total'=>$order->get_total(),                        
                        'testMode'=>'true',
                        'firstName'=>$order->get_billing_first_name(),
                        'lastName'=>$order->get_billing_last_name(),
                        'numberContact'=>$order->get_billing_phone(),
                        'email'=>$order->get_billing_email(),
                        'currency'=>get_woocommerce_currency(),
                        'merchantId'=>$this->get_option('mps_customerid'),
                        'description'=>$productsName,
                        'signature'=> $signature
                    ];
                     
                    $body = wp_json_encode( $body );
                     
                    $options = [
                        'body'        => $body,
                        'headers'     => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                        ],
                        'timeout'     => 60,
                        'redirection' => 5,
                        'blocking'    => true,
                        'httpversion' => '1.0',
                        'sslverify'   => false,
                        'data_format' => 'body',
                    ];
                    write_log($options);
                    $response = wp_remote_post( 'http://localhost:8084/mps/woo', $options );
                    write_log($response);
                    if( !is_wp_error( $response ) ) {
                        $body = wp_remote_retrieve_body( $response );
                        return array(
                            'result'    => 'success',
                            'redirect'  => $body
                        );
                    }else {
                        wc_add_notice(  'Connection error.', 'error' );
                        return;
                    }
                }else{
                    wc_add_notice(  'Error procesando pago.', 'error' );
                    return;
                }
                
            }
            
}
?>