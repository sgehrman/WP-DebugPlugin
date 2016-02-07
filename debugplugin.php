<?php
/*
Plugin Name: WP Debug Plugin
Version: v1.0
Plugin URI: http://distantfutu.re
Author: Steve Gehrman
Author URI: http://distantfutu.re
Description: Random Debug stuff
 */

define('YOUR_SPECIAL_SECRET_KEY', '56b458f3613364.08018088');
define('YOUR_LICENSE_SERVER_URL', 'http://127.0.0.1/cocoatech');

add_action('admin_menu', 'slm_sample_license_menu');

function slm_sample_license_menu()
{
    add_options_page('License Manager Menu', 'License Manager', 'manage_options', 'license_management_menu_id', 'license_management_page');
}

$savedLicenseKey = 'saved_license_key';

function license_management_page()
{
    echo '<div class="wrap">';
    echo '<h2>License Manager</h2>';

    // License activate button was clicked
    if (isset($_REQUEST['activate_license'])) {
        $license_key = $_REQUEST['input_license_key'];

        // Send query to the license manager server
        $lic = new LicenseManager($license_key, YOUR_LICENSE_SERVER_URL, YOUR_SPECIAL_SECRET_KEY);
        if ($lic->active()) {
            echo 'You license Activated successfuly';
        } else {
            echo $lic->err;
        }
    }

    // License deactivate button was clicked
    if (isset($_REQUEST['deactivate_license'])) {
        $license_key = $_REQUEST['input_license_key'];

        // Send query to the license manager server
        $lic = new LicenseManager($license_key, YOUR_LICENSE_SERVER_URL, YOUR_SPECIAL_SECRET_KEY);
        if ($lic->active()) {
            echo 'You license Activated successfuly';
        } else {
            echo $lic->err;
        }
    }

    $lic = new LicenseManager($license_key, YOUR_LICENSE_SERVER_URL, YOUR_SPECIAL_SECRET_KEY);
    if ($lic->is_licensed()) {
        echo 'Thank You for Purchasing!';

        ?>
        <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th style="width:100px;"><label for="input_license_key">License Key</label></th>
                    <td ><input class="regular-text" type="text" id="input_license_key" name="input_license_key"  value="<?php echo get_option($savedLicenseKey); ?>" ></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="activate_license" value="Deactivate" class="button-primary" />
            </p>
        </form>
        <?
    } else {
        ?>
        <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th style="width:100px;"><label for="input_license_key">License Key</label></th>
                    <td ><input class="regular-text" type="text" id="input_license_key" name="input_license_key"  value="<?php echo get_option($savedLicenseKey); ?>" ></td>
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
        $output = "<script>alert( 'Debug Objects: " . implode(',', $data) . "' );</script>";
    } else {
        $output = "<script>alert( 'Debug Objects: " . $data . "' );</script>";
    }

    echo $output;
}

class LicenseManager
{
    public $license;
    public $server;
    public $api_key;
    private $product_id = 'My_product_name_OR_ID';
    public $err;

    public function __construct($license, $server, $api_key)
    {
        $this->server  = $server;
        $this->api_key = $api_key;
        $this->license = $license;
    }

    public function is_licensed()
    {
        $lic = get_option($savedLicenseKey);
        if (!empty($lic)) {
            return true;
        }

        return false;
    }

    public function active()
    {
        $url      = $this->server . '/?secret_key=' . $this->api_key . '&slm_action=slm_activate&license_key=' . $this->license . '&registered_domain=' . get_bloginfo('siteurl') . '&item_reference=' . $this->product_id;
        $response = wp_remote_get($url, array('timeout' => 20, 'sslverify' => false));

        if (is_array($response)) {
            $json         = $response['body']; // use the content
            $json         = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', utf8_encode($json));
            $license_data = json_decode($json);
        }
        if ($license_data->result == 'success') {
            update_option($savedLicenseKey, $this->lic);
            return true;
        } else {
            $this->err = $license_data->message;

            return false;
        }

    }

    public function deactivate()
    {

    }

}

?>