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
        $this->has_fields = false;
        $this->method_title = __('Mipagoseguro-compra protegida', 'mipagoseguro_woocommerce');
        $this->method_description = __('Ofrece compra protegida y acepta tarjetas de crédito, PSE y Efecty.', 'mipagoseguro_woocommerce');
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
                        'default' => __('Compra protegida Mipagoseguro (Tarjeta crédito/debito, PSE, Nequi y Efecty)', 'mipagoseguro_woocommerce'),                        
                    ),
                    'mps_description' => array(
                        'title' => __('<span class="epayco-required">Descripción</span>', 'mipagoseguro_woocommerce'),
                        'type' => 'textarea',
                        'description' => __('Corresponde a la descripción que verá el usuaro durante el checkout', 'mipagoseguro_woocommerce'),
                        'default' => __('Checkout ePayco (Tarjetas de crédito,debito,efectivo)', 'mipagoseguro_woocommerce'),                        
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
                        'default' => 'no',
                    ),
                    'mps_endorder_state' => array(
                        'title' => __('Estado Final del Pedido', 'mipagoseguro_woocommerce'),
                        'type' => 'select',
                        'description' => __('Seleccione el estado del pedido que se aplicará a la hora de aceptar y confirmar el pago de la orden', 'mipagoseguro_woocommerce'),
                        'options' => array('processing'=>"Procesando","completed"=>"Completado"),
                    ),
                    'mps_url_response' => array(
                        'title' => __('Página de confirmación de pedido.', 'mipagoseguro_woocommerce'),
                        'type' => 'select',
                        'description' => __('Url de la tienda donde será direccionado el cliente una vez el pago sea realizado.', 'mipagoseguro_woocommerce'),
                        'options'       => $this->get_pages(__('Seleccionar pagina', 'payco-woocommerce')),
                    ),
                    'epayco_url_confirmation' => array(
                        'title' => __('Página de Confirmación', 'mipagoseguro_woocommerce'),
                        'type' => 'select',
                        'description' => __('Url de la tienda donde ePayco confirma el pago', 'mipagoseguro_woocommerce'),
                        'options'       => $this->get_pages(__('Seleccionar pagina', 'payco-woocommerce')),
                    ),
                    'epayco_reduce_stock_pending' => array(
                        'title' => __('Reducir el stock en transacciones pendientes', 'epayco_woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar', 'epayco_woocommerce'),
                        'description' => __('Habilite para reducir el stock en transacciones pendientes', 'epayco_woocommerce'),
                        'default' => 'yes',
                    ),
                    'epayco_lang' => array(
                        'title' => __('Idioma del Checkout', 'epayco_woocommerce'),
                        'type' => 'select',
                        'description' => __('Seleccione el idioma del checkout', 'epayco_woocommerce'),
                        'options' => array('es'=>"Español","en"=>"Inglés"),
                    ),
                );
            }
}
?>