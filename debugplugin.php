<?php
/*
  Plugin Name: WP Debug Plugin
  Version: v1.0
  Plugin URI: http://distantfutu.re
  Author: Steve Gehrman
  Author URI: http://distantfutu.re
  Description: Random Debug stuff
 */


// This is the secret key for API authentication. You configured it in the settings menu of the license manager plugin.
define('YOUR_SPECIAL_SECRET_KEY', '56b458f3613364.08018088'); //Rename this constant name so it is specific to your plugin or theme.

// This is the URL where API query request will be sent to. This should be the URL of the site where you have installed the main license manager plugin. Get this value from the integration help page.
define('YOUR_LICENSE_SERVER_URL', 'http://127.0.0.1/cocoatech'); //Rename this constant name so it is specific to your plugin or theme.

// This is a value that will be recorded in the license manager data so you can identify licenses for this item/product.
define('YOUR_ITEM_REFERENCE', '????'); //Rename this constant name so it is specific to your plugin or theme.

add_action('admin_menu', 'slm_sample_license_menu');

function slm_sample_license_menu() {
    add_options_page('Sample License Activation Menu', 'Sample License', 'manage_options', 'youlice_classesence', 'sample_license_management_page');
}

function sample_license_management_page() {
    echo '<div class="wrap">';
    echo '<h2>Sample License Management</h2>';

    /*** License activate button was clicked ***/
    if (isset($_REQUEST['activate_license'])) {
        $license_key = $_REQUEST['sample_license_key'];
        // Send query to the license manager server
        $lic    = new youlice_class($license_key , YOUR_LICENSE_SERVER_URL , YOUR_SPECIAL_SECRET_KEY );
        if($lic->active()){
            echo 'You license Activated successfuly';
        }else{
            echo $lic->err;
        }

    }

    $lic = new youlice_class($license_key , YOUR_LICENSE_SERVER_URL , YOUR_SPECIAL_SECRET_KEY );
    if($lic->is_licensed()){
        echo 'Thank You Phurchasing!';
    }else{
        ?>
        <form action="" method="post">
            <table class="form-table">
                <tr>
                    <th style="width:100px;"><label for="sample_license_key">License Key</label></th>
                    <td ><input class="regular-text" type="text" id="sample_license_key" name="sample_license_key"  value="<?php echo get_option('sample_license_key'); ?>" ></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="activate_license" value="Activate" class="button-primary" />
            </p>
        </form>
        <?php
    }

    
    echo '</div>';
}


class youlice_class{
    public $lic;
    public $server;
    public $api_key;
    private $wp_option 	= 'product_1450';
    private $product_id = 'My_product_name_OR_ID';
    public $err;
    public function __construct($lic=false , $server , $api_key){
        if($this->is_licensed())
            $this->lic      =   get_option($this->wp_option);
        else
            $this->lic      =   $lic;

        $this->server   =   $server;
        $this->api_key  =   $api_key;
    }
    /**
     * check for current product if licensed
     * @return boolean 
     */
    public function is_licensed(){
        $lic = get_option($this->wp_option);
        if(!empty( $lic ))
            return true;
        return false;
    }

    /**
     * send query to server and try to active lisence
     * @return boolean
     */
    public function active(){
        $url = YOUR_LICENSE_SERVER_URL . '/?secret_key=' . YOUR_SPECIAL_SECRET_KEY . '&slm_action=slm_activate&license_key=' . $this->lic . '&registered_domain=' . get_bloginfo('siteurl').'&item_reference='.$this->product_id;
        $response = wp_remote_get($url, array('timeout' => 20, 'sslverify' => false));

        if(is_array($response)){
            $json = $response['body']; // use the content
            $json = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', utf8_encode($json));
            $license_data = json_decode($json);
        }
        if($license_data->result == 'success'){
            update_option( $this->wp_option, $this->lic );
            return true;
        }else{
            $this->err = $license_data->message;
            return false;
        }
    }

    /**
     * send query to server and try to deactive lisence
     * @return boolean
     */
    public function deactive(){

    }

}

?>