<?php

namespace PPSFWOO;

use PPSFWOO\Subscriber;
use PPSFWOO\Exception;

class AjaxActions
{
    // phpcs:disable
	public function admin_ajax_callback()
    {  
        $method = isset($_POST['method']) ? sanitize_text_field(wp_unslash($_POST['method'])): "";

        if(method_exists($this, $method)) {
            
            echo call_user_func([$this, $method]);

        } else {
            
            echo "";

            Exception::log(__CLASS__ . "->$method does not exist.");

        }

        wp_die();
    }
    // phpcs:enable

    public static function subs_id_redirect_nonce($is_ajax = true)
    {
        $nonce_name = "";

        if(!session_id()) session_start();

        if (!isset($_SESSION['ppsfwoo_customer_nonce'])) {

            $nonce_name = $_SESSION['ppsfwoo_customer_nonce'] = wp_generate_password(24, false);

        } else {

            $nonce_name = sanitize_text_field(wp_unslash($_SESSION['ppsfwoo_customer_nonce']));

        }

        if($is_ajax) {

            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $Plan = new Plan(absint($_POST['product_id']));

            if($Plan->id) {

                $PluginMain = PluginMain::get_instance();

                $response = [
                    'client_id' => $PluginMain->client_id,
                    'nonce'     => wp_create_nonce($nonce_name),
                    'plan_id'   => $Plan->id
                ];

            } else {

                $response = ['error' => true];

            }

            return wp_json_encode($response);

        } else {

            return $nonce_name;

        }
    }

    protected function get_sub()
    {
        if(!isset($_POST['nonce'], $_POST['id']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ajax_get_sub')) {

            wp_die('Security check failed.');

        }

        return Subscriber::get(sanitize_text_field(wp_unslash($_POST['id'])));

    }

    protected function log_paypal_buttons_error()
    {
        $logged_error = false;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $message = isset($_POST['message'], $_POST['method']) && $_POST['method'] === __FUNCTION__ ? sanitize_text_field(wp_unslash($_POST['message'])): false;
        
        if($message) {

            Exception::log("PayPal subscription button error: $message");

            $logged_error = true;

        }

        return wp_json_encode(['logged_error' => $logged_error]);
    }
}
