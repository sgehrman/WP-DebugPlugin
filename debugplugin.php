<?php
/*
Plugin Name: WP Debug Plugin
Version: v1.0
Plugin URI: http://distantfutu.re
Author: Steve Gehrman
Author URI: http://distantfutu.re
Description: Random Debug stuff
 */

define('CREATION_SECRET_KEY', 'www');
define('YOUR_VERIFICATION_SECRET_KEY', '56b458f3613364.08018088');
define('YOUR_LICENSE_SERVER_URL', 'http://127.0.0.1/cocoatech');

add_action('admin_menu', 'slm_sample_license_menu');

function slm_sample_license_menu()
{
    add_options_page('License Manager Menu', 'License Manager', 'manage_options', 'license_management_menu_id', 'license_management_page');
}

function license_option_key()
{
    // there's no globals in PHP? so using a fucking function instead.
    return 'license_key';
}

function license_management_page()
{
    echo '<div class="wrap">';
    echo '<h2>License Manager</h2>';

    $lic = new LicenseManager(YOUR_LICENSE_SERVER_URL, YOUR_VERIFICATION_SECRET_KEY, CREATION_SECRET_KEY);

    // License activate button was clicked
    if (isset($_REQUEST['activate_license'])) {
        $licenseKey = $_REQUEST['input_license_key'];

        if ($lic->active($licenseKey)) {
            echo 'You license Activated successfuly';
        } else {
            echo $lic->err;
        }
    } else if (isset($_REQUEST['deactivate_license'])) {
        // License deactivate button was clicked
        $licenseKey = $_REQUEST['input_license_key'];

        if ($lic->deactivate($licenseKey)) {
            echo 'You license Deactivated successfuly';
        } else {
            echo $lic->err;
        }
    }

    if ($lic->is_licensed()) {
        echo 'Thank You for Purchasing!';

        ?>
        <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th style="width:100px;"><label for="input_license_key">License Key</label></th>
                    <td ><input class="regular-text" type="text" id="input_license_key" name="input_license_key"  value="<?echo get_option(license_option_key()); ?>" ></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="deactivate_license" value="Deactivate" class="button-primary" />
            </p>
        </form>
        <?
    } else {
        ?>
        <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th style="width:100px;"><label for="input_license_key">License Key</label></th>
                    <td ><input class="regular-text" type="text" id="input_license_key" name="input_license_key"  value="<?echo get_option(license_option_key()); ?>" ></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="activate_license" value="Activate" class="button-primary" />
            </p>
        </form>
        <?
    }

    echo '</div>';
}

function logg($data)
{
    if (is_array($data)) {
        $output = "<script>alert( 'Logg: " . implode(',', $data) . "' );</script>";
    } else {
        $output = "<script>alert( 'Logg: " . $data . "' );</script>";
    }

    echo $output;
}

class LicenseManager
{
    public $server;
    public $api_key;
    public $creation_secret_key;
    private $product_id = 'My_product_name_OR_ID';
    public $err;

    public function __construct($server, $api_key, $creation_secret_key)
    {
        $this->server              = $server;
        $this->api_key             = $api_key;
        $this->creation_secret_key = $creation_secret_key;
    }

    public function is_licensed()
    {
        $lic = get_option(license_option_key());
        if (!empty($lic)) {
            return true;
        }

        return false;
    }

    public function check($licenseKey)
    {
        $api_params = array(
            'slm_action'  => 'slm_check',
            'secret_key'  => $this->api_key,
            'license_key' => $licenseKey,
        );

        $query    = add_query_arg($api_params, $this->server);
        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        if (is_wp_error($response)) {
            echo "Unexpected Error! The query returned with an error.";
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response));
        }

        if ($license_data->result == 'success') {

            return true;
        } else {
            $this->err = $license_data->message;

        }

        return false;
    }

    public function create()
    {
        $api_params = array(
            'slm_action'          => 'slm_create_new',
            'secret_key'          => $this->creation_secret_key,
            'first_name'          => 'elvis',
            'last_name'           => 'presley',
            'email'               => 'elvis@king.com',
            'company_name'        => 'XYZ',
            'txn_id'              => 'ABC0987654321',
            'max_allowed_domains' => '10',
            'date_created'        => date('Y - m - d'),
            'date_expiry'         => '2017 - 01 - 01',
        );

        $query    = add_query_arg($api_params, $this->server);
        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        if (is_wp_error($response)) {
            echo "Unexpected Error! The query returned with an error.";
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response));
        }

        if ($license_data->result == 'success') {

            return true;
        } else {
            $this->err = $license_data->message;
        }

        return false;
    }

    public function active($licenseKey)
    {
        $api_params = array(
            'slm_action'         => 'slm_activate',
            'secret_key'         => $this->api_key,
            'license_key'        => $licenseKey,
            'registered_domain=' => $_SERVER['SERVER_NAME'], // get_bloginfo('siteurl'),
            'item_reference='    => urlencode($this->product_id),
        );

        $query    = add_query_arg($api_params, $this->server);

        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        if (is_wp_error($response)) {
            echo "Unexpected Error! The query returned with an error.";
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response));

            if ($license_data->result == 'success') {
                update_option(license_option_key(), $licenseKey);
                return true;
            } else {
                $this->err = $license_data->message;

            }
        }
        return false;
    }

    public function deactivate($licenseKey)
    {
        $api_params = array(
            'slm_action'         => 'slm_deactivate',
            'secret_key'         => $this->api_key,
            'license_key'        => $licenseKey,
            'registered_domain=' => $_SERVER['SERVER_NAME'], // get_bloginfo('siteurl'),
            'item_reference='    => urlencode($this->product_id),
        );

        $query    = add_query_arg($api_params, $this->server);
        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        if (is_wp_error($response)) {
            echo "Unexpected Error! The query returned with an error.";
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response));

            if ($license_data->result == 'success') {
                update_option(license_option_key(), '');
                return true;
            } else {
                $this->err = $license_data->message;

            }
        }
        return false;
    }

}

?>