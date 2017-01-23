<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function wpcable_settings_fields() {
  
 
  // create array for all the fields we want to export
  $wpcable_settings_fields = array(
    'email' => __('email', 'wpcable'),
    'password' => __('password', 'wpcable'),
  );
  
  return $wpcable_settings_fields;
}
add_action('admin_menu', 'wpcable_xls_options', 100);

function wpcable_xls_options() {
    add_submenu_page( 'options-general.php', 'Codeable settings', 'Codeable settings', 'manage_options', 'codeable_settings', 'codeable_settings_callback' ); 
    add_action( 'admin_init', 'codeable_register_settings' );
}

// register settings
function codeable_register_settings(){
  
  $wpcable_settings_fields = wpcable_settings_fields();
  
  register_setting( 'wpcable_group', 'wpcable' ); 
  foreach ($wpcable_settings_fields as $key => $field) {
    register_setting( 'wpcable_group', 'wpcable_'.$key ); 
  }
  
  register_setting( 'wpcable_group', 'wpcable_what_to_check' );
  register_setting( 'wpcable_group', 'wpcable_skip_zero_months' );
}

function codeable_settings_callback() {
  global $wpdb;


  if (!is_ssl()) {
    ?>

    <div class="update-nag notice">
      <p><?php echo __('Please consider installing this plugin on a secure website', 'wpcable'); ?></p>
    </div>

  <?php }

  set_time_limit(300);
  
  $wpcable_settings_fields = wpcable_settings_fields();
  
  $wpcable_what_to_check    = get_option('wpcable_what_to_check');
  $wpcable_skip_zero_months = get_option('wpcable_skip_zero_months');
  
  // truncate all data
  if (isset($_GET['flushdata'])) {
    
    $tables = array(
      $wpdb->prefix .'codeable_transcactions' => __('Transcactions', 'wpcable'),
      $wpdb->prefix . 'codeable_clients' => __('Clients', 'wpcable'),
      $wpdb->prefix . 'codeable_amounts' => __('Amounts', 'wpcable')
    );
    
    foreach ($tables as $db_table => $db_label) {
    
      if ($wpdb->query('TRUNCATE '. $db_table) === true) {
        ?>
        <div class="updated notice">
          <p><?php echo $db_label .' ('. $db_table .') '. __( 'table truncated!', 'wpcable' ); ?></p>
        </div>
        <?php
      } else {
        ?>
        <div class="error notice">
          <p><?php echo $db_label .' ('. $db_table .') '. __( 'table could not be truncated!', 'wpcable' ); ?></p>
        </div>
        <?php
      }
    }
    
  }
  
  
  if (isset($_GET['fetchdata'])) {
    
    $wpcable_transcactions = new wpcable_transcactions;
    $total = $wpcable_transcactions->store_transactions();
    ?>
    <div class="updated notice">
      <p><?php echo $total .' '. __( 'new entries', 'wpcable' ); ?></p>
    </div>
    <?php
  }

?>
    <div class="wrap wpcable_wrap">
      <h2><?php _e('Codeable settings', 'wpcable'); ?></h2>
      
      <table class="form-table">
        <tbody>

      <form method="post" action="options.php">
        <?php settings_fields( 'wpcable_group' ); ?>
        <?php do_settings_sections( 'wpcable_group' ); ?>
        
        <?php
                    
          foreach ($wpcable_settings_fields as $key => $label) {

            echo '
              <tr>
                <th scope="row">
                  <label class="wpcable_label" for="wpcable_'. $key .'">'. $label .'</label> 
                </th>
                <td>
                <input id="wpcable_'. $key .'" type="'. ($key == 'password' ? 'password' : 'text') .'" name="wpcable_'.$key.'" value="'. get_option('wpcable_'.$key) .'" />
                </td>
                
              </tr>';
          }
          
          echo '
              <tr>
                <th scope="row">
                  <label class="wpcable_label" for="wpcable_what_to_check">'. __('Scan method', 'wpcable') .'</label> 
                </th>
                <td>
                  <select id="wpcable_what_to_check" name="wpcable_what_to_check">
                    <option value="0" '. ($wpcable_what_to_check == 0 ? 'selected="selected"': '') .' >'. __('Stop if the transcaction id is found (use this if you want ot update your data of first time fetch)') .'</option>
                    <option value="1" '. ($wpcable_what_to_check == 1 ? 'selected="selected"': '') .'>'. __('Check everything (use this if you got a time out while fetching)') .'</option>
                  </select>
                </td>
                
              </tr>';
        ?>

        </tbody>
      </table>
        
        <?php
         
          echo '<div class="action-buttons">';
          submit_button(__('Save Changes', 'wpcable')); 
          echo '</div>';
        
        if (get_option('wpcable_email') != '' && get_option('wpcable_password') != '')  { ?>
          
          <p><small><?php echo __('Be sure that you set PHP timeout to 120 or more on your first fetch or if you have deleted the cached data', 'wpcable'); ?></small></p>
        
          <a href="options-general.php?page=codeable_settings&fetchdata=true" class="button button-large button-action"><?php echo __('Fetch remote data', 'wooxls'); ?></a> 
          <a href="options-general.php?page=codeable_settings&flushdata=true" class="button button-large button-danger"><?php echo __('Delete cached data', 'wooxls'); ?></a> 
        <?php } ?>

    </div>
    <style>
    
      .button.button-action { background: #5CB85C; border-color: #4CAE4C; color: #ffffff; font-weight: bold; -webkit-box-shadow: none; 	-moz-box-shadow: none; box-shadow: none; }
      .button.button-action:hover { background: #449D44; border-color: #398439; color: #ffffff;  }
      .button.button-danger { background: #D9534F; border-color: #D43F3A; color: #ffffff; -webkit-box-shadow: none; 	-moz-box-shadow: none; box-shadow: none; }
      .button.button-danger:hover { background: #C9302C; border-color: #AC2925; color: #ffffff; }
      .button.button-warning { padding-left: 25px; padding-right: 25px; font-weight: bold; background: #F0AD4E; border-color: #EEA236; color: #ffffff; -webkit-box-shadow: none; 	-moz-box-shadow: none; box-shadow: none; }
      .button.button-warning:hover { background: #EC971F; border-color: #D58512; color: #ffffff; }      
    </style>
<?php
}

?>
