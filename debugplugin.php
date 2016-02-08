<?
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

    $site_url = network_site_url('/');

    $lic = new LicenseManager($site_url, YOUR_VERIFICATION_SECRET_KEY, CREATION_SECRET_KEY);

    handleButtonClicks($lic);

    echo activateForm();
    echo deactivateForm();
    echo checkForm();

    echo createForm();
    dumpShit();

    echo '</div>';
}

function handleButtonClicks($lic)
{
    // License activate button was clicked
    if (isset($_REQUEST['activate_license'])) {
        $licenseKey = $_REQUEST['input_license_key'];

        if ($lic->active($licenseKey)) {
            echo 'You license Activated successfully';
        } else {
            echo $lic->err;
        }
    } else if (isset($_REQUEST['deactivate_license'])) {
        // License deactivate button was clicked
        $licenseKey = $_REQUEST['input_license_key'];

        if ($lic->deactivate($licenseKey)) {
            echo 'You license Deactivated successfully';
        } else {
            echo $lic->err;
        }
    } else if (isset($_REQUEST['create_license'])) {
        // License deactivate button was clicked
        $firstName   = $_REQUEST['input_first_name'];
        $lastName    = $_REQUEST['input_last_name'];
        $email       = $_REQUEST['input_email'];
        $company     = $_REQUEST['input_company'];
        $numInstalls = $_REQUEST['input_installs'];

        if ($lic->create($firstName, $lastName, $email, $company, $numInstalls)) {
            echo 'You License creasted successfully';
        } else {
            echo $lic->err;
        }
    } else if (isset($_REQUEST['check_license'])) {
        // License deactivate button was clicked
        $licenseKey = $_REQUEST['input_license_key'];

        if ($lic->check($licenseKey)) {
            echo 'You license Checked successfully';
        } else {
            echo $lic->err;
        }
    }
}

function logg($data)
{
    $result = recursiveLogg($data, "");

    echo "<script>alert( 'Loga: " . $result . "' );</script>";
}

function recursiveLogg($data, $appendTo)
{
    if (is_string($data)) {
        $output = $data;
    } else if (is_array($data)) {
        $output = 'ARY: ';

        foreach ($data as $value) {
            $value = recursiveLogg($value, '');

            $output = $output . "$value, ";
        }
    } else if (is_object($data)) {
        $output = 'OBJ: ';
        foreach ($data as $key => $value) {
            $value = recursiveLogg($value, '');

            $output = $output . "$key => $value, ";
        }
    } else {
        $output = gettype($data);
    }

    return $appendTo . $output;
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

            logg($license_data);

            return true;
        } else {
            $this->err = $license_data->message;
        }

        return false;
    }

    public function create($firstName, $lastName, $email, $company, $numInstalls)
    {
        $api_params = array(
            'slm_action'          => 'slm_create_new',
            'secret_key'          => $this->creation_secret_key,
            'first_name'          => $firstName,
            'last_name'           => $lastName,
            'email'               => $email,
            'company_name'        => $company,
            'txn_id'              => 'ABC0987654321',
            'max_allowed_domains' => $numInstalls,
            'date_created'        => date('Y-m-d'),
            'date_expiry'         => '',
        );

        $query    = add_query_arg($api_params, $this->server);
        $response = wp_remote_get($query, array('timeout' => 20, 'sslverify' => false));

        if (is_wp_error($response)) {
            echo "Unexpected Error! The query returned with an error.";
        } else {
            $license_data = json_decode(wp_remote_retrieve_body($response));

            if ($license_data->result == 'success') {
                update_option(license_option_key(), $license_data->key);
                return true;
            } else {
                $this->err = $license_data->message;
            }
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

        $query = add_query_arg($api_params, $this->server);

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

// -------------------------------------------------------------------------------
// html

function activateForm()
{
    $licenseKey = get_option(license_option_key());

    $html = <<<HTML

   <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th style="width:100px;"><label for="input_license_key">License Key</label></th>
                    <td ><input class="regular-text" type="text" id="input_license_key" name="input_license_key"  value=$licenseKey ></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="activate_license" value="Activate" class="button-primary" />
            </p>
        </form>
        <hr/>
HTML;
    return $html;
}

function deactivateForm()
{
    $licenseKey = get_option(license_option_key());

    $html = <<<HTML

  <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th style="width:100px;"><label for="input_license_key">License Key</label></th>
                    <td ><input class="regular-text" type="text" id="input_license_key" name="input_license_key"  value=$licenseKey ></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="deactivate_license" value="Deactivate" class="button-primary" />
            </p>
        </form>
        <hr/>
HTML;
    return $html;
}

function createForm()
{
    $firstName = 'Xxook';
    $lastName  = 'Quuook';
    $email     = 'Xxook@aol.com';

    $html = <<<HTML

  <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th style="width:100px;"><label for="input_first_name">First Name</label></th>
                    <td ><input class="regular-text" type="text" id="input_first_name" name="input_first_name"  value=$firstName ></td>
                </tr>
                <tr>
                    <th style="width:100px;"><label for="input_last_name">Last Name</label></th>
                    <td ><input class="regular-text" type="text" id="input_last_name" name="input_last_name"  value=$lastName ></td>
                </tr>
                <tr>
                    <th style="width:100px;"><label for="input_email">Email</label></th>
                    <td ><input class="regular-text" type="text" id="input_email" name="input_email"  value=$email ></td>
                </tr>
                <tr>
                    <th style="width:100px;"><label for="input_company">Company</label></th>
                    <td ><input class="regular-text" type="text" id="input_company" name="input_company"  value='' ></td>
                </tr>
                <tr>
                    <th style="width:100px;"><label for="input_installs">Install Count</label></th>
                    <td ><input class="regular-text" type="number" id="input_installs" name="input_installs"  value='4' ></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="create_license" value="Create License" class="button-primary" />
            </p>
        </form>

        <hr/>
HTML;
    return $html;
}

function checkForm()
{
    $licenseKey = get_option(license_option_key());

    $html = <<<HTML

  <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th style="width:100px;"><label for="input_license_key">License Key</label></th>
                    <td ><input class="regular-text" type="text" id="input_license_key" name="input_license_key"  value=$licenseKey ></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="check_license" value="Check" class="button-primary" />
            </p>
        </form>
        <hr/>
HTML;
    return $html;
}

function dumpShit()
{
    $site_title       = get_bloginfo('name');
    $site_url         = network_site_url('/');
    $site_description = get_bloginfo('description');

    echo 'The Network Home URL is: ' . $site_url . '\n';
    echo 'The Network Home Name is: ' . $site_title . '\n';
    echo 'The Network Home Tagline is: ' . $site_description . '\n';
}
