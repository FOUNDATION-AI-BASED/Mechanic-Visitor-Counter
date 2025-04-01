<?php
/*
Plugin Name: Mechanic Visitor Counter
Plugin URI: https://www.adityasubawa.com/mechanic-visitor-counter/
Description: Mechanic Visitor Counter is a widgets which will display the Visitor counter and traffic statistics on WordPress.
Version: 3.3.4
Author: Aditya Subawa
Author URI: https://www.adityasubawa.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: mechanic-visitor-counter
*/

if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

// Define plugin version constant
define( 'MECHANIC_VISITOR_COUNTER_VERSION', '3.3.4' );

// Load local language
add_action('plugins_loaded', 'statsmechanic_load_textdomain');
function statsmechanic_load_textdomain() {
    load_plugin_textdomain( 'mechanic-visitor-counter', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
}

global $wpdb;
// Define table name safely
define('BMW_TABLE_NAME', $wpdb->prefix . 'mech_statistik');
define('BMW_PATH', plugin_dir_path( __FILE__ ));

require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); // Needed for dbDelta

function install(){
    global $wpdb;
    $table_name = BMW_TABLE_NAME;
    $charset_collate = $wpdb->get_charset_collate();

    // Check if table exists using WordPress way
    if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) != $table_name ) {
        $sql = "CREATE TABLE $table_name (
            ip varchar(100) NOT NULL default '',  -- Increased length for IPv6
            tanggal date NOT NULL,
            hits int(10) UNSIGNED NOT NULL default 1, -- Use UNSIGNED
            online varchar(255) NOT NULL,
            PRIMARY KEY  (ip, tanggal)
        ) $charset_collate;";

        dbDelta( $sql ); // Use dbDelta for table creation/updates
    }
}

function uninstall(){
    global $wpdb;
    $table_name = BMW_TABLE_NAME;
    $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );
}

// This function seems unused for style selection now, keeping for potential legacy use
function acak($path, $exclude = ".|..|.svn|.DS_Store", $recursive = true) {
    $path = trailingslashit($path);
    $folder_handle = @opendir($path);
    if ( ! $folder_handle ) {
        error_log("Could not open directory: " . $path);
        return array();
    }
    $exclude_array = explode("|", $exclude);
    $result = array();
    $done = array();
    while(false !== ($filename = readdir($folder_handle))) {
        if(!in_array(strtolower($filename), $exclude_array)) {
            $full_path = $path . $filename;
            if(is_dir($full_path)) {
                if($recursive) {
                    $recursive_result = acak($full_path, $exclude, true);
                    if (is_array($recursive_result)) {
                        $result = array_merge($result, $recursive_result);
                    }
                }
            } else {
                if ($filename === '0.gif') {
                    if (!isset($done[$path])) {
                        $result[] = $path;
                        $done[$path] = 1;
                    }
                }
            }
        }
    }
    closedir($folder_handle);
    return $result;
}
register_activation_hook(__FILE__, 'install');
register_deactivation_hook(__FILE__, 'uninstall');

class Wp_StatsMechanic extends WP_Widget{

    function __construct(){
        $params = array(
            'description' => __('Display Visitor Counter and Statistics Traffic', 'mechanic-visitor-counter'),
            'name' => __('Mechanic - Visitor Counter', 'mechanic-visitor-counter')
        );

        parent::__construct(
            'Wp_StatsMechanic',
            $params['name'],
            $params
        );
    }

    public function form($instance)  {
        $defaults = array(
            'title' => '',
            'font_color' => '',
            'count_start' => 0,
            'hits_start' => 0,
            'count_length' => '4',
            'today_view' => 'on',
            'yesterday_view' => 'on',
            'month_view' => 'on',
            'year_view' => 'on',
            'total_view' => 'on',
            'hits_view' => 'on',
            'totalhits_view' => 'on',
            'online_view' => 'on',
            'ip_display' => false,
            'server_time' => false,
            'statsmechanic_align' => 'Left',
        );
        $instance = wp_parse_args( (array) $instance, $defaults );

        $title = sanitize_text_field($instance['title']);
        $font_color = sanitize_hex_color($instance['font_color']);
        $count_start = absint($instance['count_start']);
        $hits_start = absint($instance['hits_start']);
        $count_length = esc_attr($instance['count_length']);
        $statsmechanic_align = esc_attr($instance['statsmechanic_align']);
?>
<p><label for="<?php echo esc_attr( $this->get_field_id('title') ); ?>"><?php esc_html_e('Title:', 'mechanic-visitor-counter'); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('font_color') ); ?>"><?php esc_html_e('Font Color:', 'mechanic-visitor-counter'); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id('font_color') ); ?>" name="<?php echo esc_attr( $this->get_field_name('font_color') ); ?>" type="text" value="<?php echo esc_attr( $font_color ); ?>" /></label></p>
<p><small><?php esc_html_e('To change the font color, fill the field with the HTML color code. example: #333', 'mechanic-visitor-counter'); ?> </small></p>
<p><small><a href="https://www.adityasubawa.com/color-picker/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Click here', 'mechanic-visitor-counter'); ?></a> <?php esc_html_e('to select another color variation.', 'mechanic-visitor-counter'); ?></small></p>
<hr>
<p><strong><?php esc_html_e('Widget Options', 'mechanic-visitor-counter'); ?></strong></p>

<p><label for="<?php echo esc_attr( $this->get_field_id('count_start') ); ?>"><?php esc_html_e('Counter Start:', 'mechanic-visitor-counter'); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id('count_start') ); ?>" name="<?php echo esc_attr( $this->get_field_name('count_start') ); ?>" type="number" value="<?php echo esc_attr( $count_start ); ?>" /></label></p>
<p><small><?php esc_html_e('Fill in with numbers to start the initial calculation of the counter, if the empty counter will start from 1', 'mechanic-visitor-counter'); ?></small></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('hits_start') ); ?>"><?php esc_html_e('Hits Start:', 'mechanic-visitor-counter'); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id('hits_start') ); ?>" name="<?php echo esc_attr( $this->get_field_name('hits_start') ); ?>" type="number" value="<?php echo esc_attr( $hits_start ); ?>" /></label></p>
<p><small><?php esc_html_e('Fill in the numbers to start the initial calculation of the hits, if the empty hits will start from 1', 'mechanic-visitor-counter'); ?></small></p>

<p><label for="<?php echo esc_attr( $this->get_field_id('count_length') ); ?>"><?php esc_html_e('Image Counter Length:', 'mechanic-visitor-counter'); ?><select class="select" id="<?php echo esc_attr( $this->get_field_id('count_length') ); ?>" name="<?php echo esc_attr( $this->get_field_name('count_length') ); ?>">
          <?php foreach ( array('4', '5', '6', '7') as $length ) : ?>
          <option value="<?php echo esc_attr( $length ); ?>" <?php selected( $count_length, $length ); ?>><?php echo esc_html( $length ); ?></option>
          <?php endforeach; ?>
         </select></label></p>
<p><small><?php esc_html_e('Define your Image counter length, the default length is 4', 'mechanic-visitor-counter'); ?></small></p>
<hr>
<p><strong><?php esc_html_e('Display Options', 'mechanic-visitor-counter'); ?></strong></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('today_view') ); ?>"><input type="checkbox" class="checkbox" <?php checked( $instance['today_view'], 'on' ); ?> id="<?php echo esc_attr( $this->get_field_id('today_view') ); ?>" name="<?php echo esc_attr( $this->get_field_name('today_view') ); ?>" /> <?php esc_html_e('Visit Today', 'mechanic-visitor-counter'); ?></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('yesterday_view') ); ?>"><input type="checkbox" class="checkbox" <?php checked( $instance['yesterday_view'], 'on' ); ?> id="<?php echo esc_attr( $this->get_field_id('yesterday_view') ); ?>" name="<?php echo esc_attr( $this->get_field_name('yesterday_view') ); ?>" /> <?php esc_html_e('Visit Yesterday', 'mechanic-visitor-counter'); ?></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('month_view') ); ?>"><input type="checkbox" class="checkbox" <?php checked( $instance['month_view'], 'on' ); ?> id="<?php echo esc_attr( $this->get_field_id('month_view') ); ?>" name="<?php echo esc_attr( $this->get_field_name('month_view') ); ?>" /> <?php esc_html_e('This Month', 'mechanic-visitor-counter'); ?></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('year_view') ); ?>"><input type="checkbox" class="checkbox" <?php checked( $instance['year_view'], 'on' ); ?> id="<?php echo esc_attr( $this->get_field_id('year_view') ); ?>" name="<?php echo esc_attr( $this->get_field_name('year_view') ); ?>" /> <?php esc_html_e('This Year', 'mechanic-visitor-counter'); ?></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('total_view') ); ?>"><input type="checkbox" class="checkbox" <?php checked( $instance['total_view'], 'on' ); ?> id="<?php echo esc_attr( $this->get_field_id('total_view') ); ?>" name="<?php echo esc_attr( $this->get_field_name('total_view') ); ?>" /> <?php esc_html_e('Total Visit', 'mechanic-visitor-counter'); ?></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('hits_view') ); ?>"><input type="checkbox" class="checkbox" <?php checked( $instance['hits_view'], 'on' ); ?> id="<?php echo esc_attr( $this->get_field_id('hits_view') ); ?>" name="<?php echo esc_attr( $this->get_field_name('hits_view') ); ?>" /> <?php esc_html_e('Hits Today', 'mechanic-visitor-counter'); ?></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('totalhits_view') ); ?>"><input type="checkbox" class="checkbox" <?php checked( $instance['totalhits_view'], 'on' ); ?> id="<?php echo esc_attr( $this->get_field_id('totalhits_view') ); ?>" name="<?php echo esc_attr( $this->get_field_name('totalhits_view') ); ?>" /> <?php esc_html_e('Total Hits', 'mechanic-visitor-counter'); ?></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('online_view') ); ?>"><input type="checkbox" class="checkbox" <?php checked( $instance['online_view'], 'on' ); ?> id="<?php echo esc_attr( $this->get_field_id('online_view') ); ?>" name="<?php echo esc_attr( $this->get_field_name('online_view') ); ?>" /> <?php esc_html_e('Whos Online', 'mechanic-visitor-counter'); ?></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('ip_display') ); ?>"><input type="checkbox" class="checkbox" <?php checked( $instance['ip_display'], 'on' ); ?> id="<?php echo esc_attr( $this->get_field_id('ip_display') ); ?>" name="<?php echo esc_attr( $this->get_field_name('ip_display') ); ?>" /> <?php esc_html_e('IP address', 'mechanic-visitor-counter'); ?></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('server_time') ); ?>"><input type="checkbox" class="checkbox" <?php checked( $instance['server_time'], 'on' ); ?> id="<?php echo esc_attr( $this->get_field_id('server_time') ); ?>" name="<?php echo esc_attr( $this->get_field_name('server_time') ); ?>" /> <?php esc_html_e('Server Time', 'mechanic-visitor-counter'); ?></label></p>
<p><label for="<?php echo esc_attr( $this->get_field_id('statsmechanic_align') ); ?>"><?php esc_html_e('Widget align:', 'mechanic-visitor-counter'); ?>
        <select class="select" id="<?php echo esc_attr( $this->get_field_id('statsmechanic_align') ); ?>" name="<?php echo esc_attr( $this->get_field_name('statsmechanic_align') ); ?>">
          <?php foreach ( array( 'Left', 'Center', 'Right' ) as $align_option ) : ?>
          <option value="<?php echo esc_attr( $align_option ); ?>" <?php selected( $statsmechanic_align, $align_option ); ?>><?php echo esc_html( $align_option ); ?></option>
          <?php endforeach; ?>
         </select></label></p>
<hr>
<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ZMEZEYTRBZP5N&lc=ID&item_name=Aditya%20Subawa&item_number=426267&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url( 'https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif' ); ?>" alt="<?php esc_attr_e('Donate', 'mechanic-visitor-counter'); ?>" /></a></p>
<?php
    }

    public function widget($args, $instance){
        global $wpdb;
        $table_name = BMW_TABLE_NAME;

        $defaults = array(
            'title' => '',
            'font_color' => '',
            'count_start' => 0,
            'hits_start' => 0,
            'count_length' => '4',
            'today_view' => 'on',
            'yesterday_view' => 'on',
            'month_view' => 'on',
            'year_view' => 'on',
            'total_view' => 'on',
            'hits_view' => 'on',
            'totalhits_view' => 'on',
            'online_view' => 'on',
            'ip_display' => false,
            'server_time' => false,
            'statsmechanic_align' => 'Left',
        );
        $instance = wp_parse_args( (array) $instance, $defaults );

        echo wp_kses_post( $args['before_widget'] );

        $title = apply_filters('widget_title', $instance['title']);
        if (!empty($title)) {
            echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );
        }

        $ipaddress_display = $instance['ip_display'] === 'on';
        $stime_display = $instance['server_time'] === 'on';
        $fontcolor = sanitize_hex_color($instance['font_color']);
        $count_length = absint($instance['count_length']);
        $style = get_option('statsmechanic_style', 'led');
        $align = esc_attr($instance['statsmechanic_align']);
        $todayview = $instance['today_view'] === 'on';
        $yesview = $instance['yesterday_view'] === 'on';
        $monthview = $instance['month_view'] === 'on';
        $yearview = $instance['year_view'] === 'on';
        $totalview = $instance['total_view'] === 'on';
        $hitsview = $instance['hits_view'] === 'on';
        $totalhitsview = $instance['totalhits_view'] === 'on';
        $onlineview = $instance['online_view'] === 'on';
        $count_start = absint($instance['count_start']);
        $hits_start = absint($instance['hits_start']);

        $ip = '';
        if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $remote_addr = wp_unslash( $_SERVER['REMOTE_ADDR'] );
            if ( filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
                $ip = $remote_addr;
            }
        }

        if ( ! empty($ip) ) {
            $current_date_mysql = current_time('mysql', true);
            $tanggal = substr($current_date_mysql, 0, 10);
            $waktu = current_time('timestamp', true);

            $check_sql = $wpdb->prepare(
                "SELECT ip FROM %s WHERE ip = %s AND tanggal = %s",
                $table_name,
                $ip,
                $tanggal
            );
            $exists = $wpdb->get_var($check_sql);

            if ( null === $exists ) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'ip' => $ip,
                        'tanggal' => $tanggal,
                        'hits' => 1,
                        'online' => $waktu
                    ),
                    array(
                        '%s',
                        '%s',
                        '%d',
                        '%s'
                    )
                );
            } else {
                $wpdb->query( $wpdb->prepare(
                    "UPDATE %s SET hits = hits + 1, online = %s WHERE ip = %s AND tanggal = %s",
                    $table_name,
                    $waktu,
                    $ip,
                    $tanggal
                ) );
            }
        }

        $current_date_mysql = current_time('mysql', true);
        $tanggal = substr($current_date_mysql, 0, 10);
        $blan = substr($current_date_mysql, 0, 7);
        $thn = substr($current_date_mysql, 0, 4);

        $yesterday_timestamp = strtotime('-1 day', current_time('timestamp', true));
        $yesterday_date = gmdate('Y-m-d', $yesterday_timestamp);

        $kemarin1 = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT ip) FROM %s WHERE tanggal = %s",
            $table_name,
            $yesterday_date
        ) );
        $kemarin1 = absint($kemarin1);

        $bulan1 = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT ip) FROM %s WHERE tanggal LIKE %s",
            $table_name,
            $wpdb->esc_like( $blan ) . '%'
        ) );
        $bulan1 = absint($bulan1);

        $tahunini1 = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT ip) FROM %s WHERE tanggal LIKE %s",
            $table_name,
            $wpdb->esc_like( $thn ) . '%'
        ) );
        $tahunini1 = absint($tahunini1);

        $pengunjung = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT ip) FROM %s WHERE tanggal = %s",
            $table_name,
            $tanggal
        ) );
        $pengunjung = absint($pengunjung);

        $totalpengunjung = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT ip) FROM %s",
            $table_name
        ) );
        $totalpengunjung = absint($totalpengunjung);

        $hits = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(hits) FROM %s WHERE tanggal = %s",
            $table_name,
            $tanggal
        ) );
        $hits = absint($hits);

        $totalhits = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(hits) FROM %s",
            $table_name
        ) );
        $totalhits = absint($totalhits);

        $bataswaktu = current_time('timestamp', true) - 300;
        $pengunjungonline = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT ip) FROM %s WHERE online > %s",
            $table_name,
            $bataswaktu
        ) );
        $pengunjungonline = absint($pengunjungonline);

        $ext = ".gif";
        $style_dir = BMW_PATH . "styles/image/" . basename($style);

        if (!is_dir($style_dir)) {
            $style = 'led';
            $style_dir = BMW_PATH . "styles/image/led";
        }

        $new_count_length = in_array($count_length, array(4, 5, 6, 7)) ? $count_length : 4;
        $counter_base = $totalpengunjung + $count_start;
        $counter_display_num = sprintf("%0" . $new_count_length . "d", $counter_base);

        $tothitsgbr = "";
        $arr = str_split($counter_display_num);
        foreach ($arr as $value) {
            $image_path = "styles/image/$style/$value$ext";
            if (file_exists(BMW_PATH . $image_path)) {
                $image_url = plugins_url( $image_path, __FILE__ );
                $tothitsgbr .= "<img src='" . esc_url( $image_url ) . "' alt='" . esc_attr( $value ) . "' class='mvc-digit-img'>";
            } else {
                $tothitsgbr .= esc_html($value);
            }
        }

        $imgvisit     = "<img src='" . esc_url( plugins_url( 'counter/mvcvisit.png', __FILE__ ) ) . "' alt='' class='mvc-icon'>";
        $yesterday    = "<img src='" . esc_url( plugins_url( 'counter/mvcyesterday.png', __FILE__ ) ) . "' alt='' class='mvc-icon'>";
        $month        = "<img src='" . esc_url( plugins_url( 'counter/mvcmonth.png', __FILE__ ) ) . "' alt='' class='mvc-icon'>";
        $year         = "<img src='" . esc_url( plugins_url( 'counter/mvcyear.png', __FILE__ ) ) . "' alt='' class='mvc-icon'>";
        $imgtotal     = "<img src='" . esc_url( plugins_url( 'counter/mvctotal.png', __FILE__ ) ) . "' alt='' class='mvc-icon'>";
        $imghits      = "<img src='" . esc_url( plugins_url( 'counter/mvctoday.png', __FILE__ ) ) . "' alt='' class='mvc-icon'>";
        $imgtotalhits = "<img src='" . esc_url( plugins_url( 'counter/mvctotalhits.png', __FILE__ ) ) . "' alt='' class='mvc-icon'>";
        $imgonline    = "<img src='" . esc_url( plugins_url( 'counter/mvconline.png', __FILE__ ) ) . "' alt='' class='mvc-icon'>";

        wp_enqueue_style(
            'statsmechanic-default-style',
            plugins_url( 'styles/css/default.css', __FILE__ ),
            array(),
            MECHANIC_VISITOR_COUNTER_VERSION
        );

        $style_attr_array = array();
        if ( ! empty( $align ) ) {
            $valid_align = array('left', 'center', 'right');
            $align_lower = strtolower($align);
            if (in_array($align_lower, $valid_align)) {
                $style_attr_array[] = 'text-align:' . $align_lower;
            }
        }
        if ( ! empty( $fontcolor ) ) {
            $style_attr_array[] = 'color:' . $fontcolor;
        }
        $style_attr_array[] = 'font-size:inherit';
        $style_attr = implode(';', $style_attr_array);
        $escaped_style_attr = esc_attr( $style_attr );

        ?>
        <div id='mvcwid-<?php echo esc_attr($this->id); ?>' class='mvcwid' style='<?php echo esc_attr($escaped_style_attr); ?>'>
            <div class="mvccount"><?php echo $tothitsgbr; ?></div>
            <div class="mvctable">
                <table width='100%'>
                    <?php if ($todayview) : ?>
                    <tr><td style='<?php echo esc_attr($escaped_style_attr); ?>'><?php echo $imgvisit; ?> <?php esc_html_e('Visit Today :', 'mechanic-visitor-counter'); ?> <?php echo esc_html( number_format_i18n( $pengunjung ) ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($yesview) : ?>
                    <tr><td style='<?php echo esc_attr($escaped_style_attr); ?>'><?php echo $yesterday; ?> <?php esc_html_e('Visit Yesterday :', 'mechanic-visitor-counter'); ?> <?php echo esc_html( number_format_i18n( $kemarin1 ) ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($monthview) : ?>
                    <tr><td style='<?php echo esc_attr($escaped_style_attr); ?>'><?php echo $month; ?> <?php esc_html_e('This Month :', 'mechanic-visitor-counter'); ?> <?php echo esc_html( number_format_i18n( $bulan1 ) ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($yearview) : ?>
                    <tr><td style='<?php echo esc_attr($escaped_style_attr); ?>'><?php echo $year; ?> <?php esc_html_e('This Year :', 'mechanic-visitor-counter'); ?> <?php echo esc_html( number_format_i18n( $tahunini1 ) ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($totalview) : ?>
                    <tr><td style='<?php echo esc_attr($escaped_style_attr); ?>'><?php echo $imgtotal; ?> <?php esc_html_e('Total Visit :', 'mechanic-visitor-counter'); ?> <?php echo esc_html( number_format_i18n( $totalpengunjung + $count_start ) ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($hitsview) : ?>
                    <tr><td style='<?php echo esc_attr($escaped_style_attr); ?>'><?php echo $imghits; ?> <?php esc_html_e('Hits Today :', 'mechanic-visitor-counter'); ?> <?php echo esc_html( number_format_i18n( $hits ) ); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($totalhitsview) : ?>
                    <tr><td style='<?php echo esc_attr($escaped_style_attr); ?>'><?php echo $imgtotalhits; ?> <?php esc_html_e('Total Hits :', 'mechanic-visitor-counter'); ?> <?php
                        $display_total_hits = $totalhits + $hits_start;
                        echo esc_html( number_format_i18n( $display_total_hits ) );
                    ?></td></tr>
                    <?php endif; ?>
                    <?php if ($onlineview) : ?>
                    <tr><td style='<?php echo esc_attr($escaped_style_attr); ?>'><?php echo $imgonline; ?> <?php esc_html_e("Who's Online :", 'mechanic-visitor-counter'); ?> <?php echo esc_html( number_format_i18n( $pengunjungonline ) ); ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
            <?php if ($ipaddress_display && !empty($ip)) : ?>
            <div class="mvcip"><?php esc_html_e('Your IP Address:', 'mechanic-visitor-counter'); ?> <?php echo esc_html( $ip ); ?></div>
            <?php endif; ?>
            <?php if ($stime_display) : ?>
            <div class="mvcserver"><?php esc_html_e('Server Time:', 'mechanic-visitor-counter'); ?> <?php echo esc_html( $tanggal ); ?></div>
            <?php endif; ?>
        </div>
        <?php
        echo wp_kses_post( $args['after_widget'] );
    }

    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['font_color'] = sanitize_hex_color( $new_instance['font_color'] );
        $instance['count_start'] = isset( $new_instance['count_start'] ) ? absint( $new_instance['count_start'] ) : 0;
        $instance['hits_start'] = isset( $new_instance['hits_start'] ) ? absint( $new_instance['hits_start'] ) : 0;
        $allowed_lengths = array( '4', '5', '6', '7' );
        $instance['count_length'] = isset( $new_instance['count_length'] ) && in_array( $new_instance['count_length'], $allowed_lengths, true ) ? $new_instance['count_length'] : '4';
        $instance['today_view'] = isset( $new_instance['today_view'] ) ? 'on' : false;
        $instance['yesterday_view'] = isset( $new_instance['yesterday_view'] ) ? 'on' : false;
        $instance['month_view'] = isset( $new_instance['month_view'] ) ? 'on' : false;
        $instance['year_view'] = isset( $new_instance['year_view'] ) ? 'on' : false;
        $instance['total_view'] = isset( $new_instance['total_view'] ) ? 'on' : false;
        $instance['hits_view'] = isset( $new_instance['hits_view'] ) ? 'on' : false;
        $instance['totalhits_view'] = isset( $new_instance['totalhits_view'] ) ? 'on' : false;
        $instance['online_view'] = isset( $new_instance['online_view'] ) ? 'on' : false;
        $instance['ip_display'] = isset( $new_instance['ip_display'] ) ? 'on' : false;
        $instance['server_time'] = isset( $new_instance['server_time'] ) ? 'on' : false;
        $allowed_align = array( 'Left', 'Center', 'Right' );
        $instance['statsmechanic_align'] = isset( $new_instance['statsmechanic_align'] ) && in_array( $new_instance['statsmechanic_align'], $allowed_align, true ) ? $new_instance['statsmechanic_align'] : 'Left';

        return $instance;
    }
}

add_action('widgets_init', 'register_wp_statsmechanic');
function register_wp_statsmechanic() {
    register_widget('Wp_StatsMechanic');
}

function mvc_shortcode( $atts ) {
    global $wp_widget_factory;

    $atts = shortcode_atts(
        array(
            'title'      => '',
            'font_color' => '',
        ),
        $atts,
        'mechanic_visitor'
    );

    $instance_args = array(
        'title' => sanitize_text_field( $atts['title'] ),
        'font_color' => sanitize_hex_color( $atts['font_color'] ),
    );

    $widget_name = 'Wp_StatsMechanic';

    if ( ! isset( $wp_widget_factory->widgets[ $widget_name ] ) || ! is_a( $wp_widget_factory->widgets[ $widget_name ], 'WP_Widget' ) ) {
        return '<!-- Mechanic Visitor Counter Widget not found -->';
    }

    ob_start();

    $widget_id = 'mvc-shortcode-' . wp_rand( 1000, 9999 );
    $widget_args = array(
        'widget_id'     => $widget_id,
        'before_widget' => '',
        'after_widget'  => '',
        'before_title'  => '',
        'after_title'   => '',
    );

    the_widget( $widget_name, $instance_args, $widget_args );

    $output = ob_get_clean();

    return $output;
}
add_shortcode('mechanic_visitor', 'mvc_shortcode');

function statsmechanic_sanitize_style( $input ) {
    $sanitized = preg_replace( '/[^a-zA-Z0-9_-]/', '', basename( $input ) );
    $style_dir = BMW_PATH . 'styles/image/' . $sanitized;
    if ( is_dir( $style_dir ) ) {
        return $sanitized;
    }
    return 'led';
}

add_action('admin_menu', 'statsmechanic_menu');
function statsmechanic_menu() {
    register_setting(
        'plugin_statsmechanic_options',
        'statsmechanic_style',
        'statsmechanic_sanitize_style'
    );

    add_options_page(
        __('Mechanic Visitor Counter Options', 'mechanic-visitor-counter'),
        __('Visitor Counter', 'mechanic-visitor-counter'),
        'manage_options',
        'mechanic-visitor-counter-options',
        'statsmechanic_options_page_html'
    );
}

function statsmechanic_options_page_html() {
    if (!current_user_can('manage_options')) {
        wp_die( esc_html__('You do not have sufficient permissions to access this page.', 'mechanic-visitor-counter') );
    }

    $current_style = get_option('statsmechanic_style', 'led');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form method="post" action="options.php">
            <?php settings_fields('plugin_statsmechanic_options'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="statsmechanic-style-select"><?php esc_html_e('Select Counter Style', 'mechanic-visitor-counter'); ?></label></th>
                        <td>
                            <select name="statsmechanic_style" id="statsmechanic-style-select">
                                <?php
                                $styles_dir = BMW_PATH . 'styles/image/';
                                $available_styles = array();
                                if (is_dir($styles_dir)) {
                                    $dirs = scandir($styles_dir);
                                    foreach ($dirs as $dir) {
                                        if ($dir !== '.' && $dir !== '..' && is_dir($styles_dir . $dir) && file_exists($styles_dir . $dir . '/0.gif')) {
                                            $style_name = basename($dir);
                                            $available_styles[$style_name] = ucfirst($style_name);
                                        }
                                    }
                                }
                                if (!isset($available_styles['led']) && is_dir($styles_dir . 'led')) {
                                    $available_styles['led'] = 'Led';
                                }
                                ksort($available_styles);
                                foreach ($available_styles as $style_value => $style_label) {
                                    echo '<option value="' . esc_attr($style_value) . '" ' . selected($current_style, $style_value, false) . '>' . esc_html($style_label) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button( esc_html__('Save Changes', 'mechanic-visitor-counter') ); ?>
        </form>
        <hr>
        <div class="mvc_preview_wrap">
            <h2><?php esc_html_e('Preview Counter Style', 'mechanic-visitor-counter'); ?></h2>
            <?php
            $preview_number = '1234567890';
            $ext = ".gif";
            $preview_style_dir = BMW_PATH . 'styles/image/' . basename($current_style);
            ?>
            <div class="mvc_preview_image" style="line-height: 0;">
                <?php
                if (is_dir($preview_style_dir)) {
                    $arr = str_split($preview_number);
                    foreach ($arr as $value) {
                        $image_path = "styles/image/{$current_style}/{$value}{$ext}";
                        if (file_exists(BMW_PATH . $image_path)) {
                            $image_url = plugins_url($image_path, __FILE__);
                            echo "<img src='" . esc_url($image_url) . "' alt='" . esc_attr($value) . "' style='vertical-align: middle;'>";
                        } else {
                            echo esc_html($value);
                        }
                    }
                } else {
                    echo '<p>' . esc_html__('Selected style directory not found.', 'mechanic-visitor-counter') . '</p>';
                }
                ?>
            </div>
        </div>
        <hr>
        <div class="mvc_plugin_info">
             <h2><?php esc_html_e('Plugin Information', 'mechanic-visitor-counter'); ?></h2>
             <p><?php esc_html_e('Mechanic Visitor Counter is a simple plugin to display visitor statistics on your WordPress site. You can customize the appearance through the widget settings and select different counter styles here.', 'mechanic-visitor-counter'); ?></p>
             <p><?php
                 // translators: %s is the URL to the plugin homepage
                 printf(
                     wp_kses_post(__('For more information, visit the <a href="%s" target="_blank" rel="noopener noreferrer">plugin homepage</a>.', 'mechanic-visitor-counter')),
                     esc_url('https://www.adityasubawa.com/mechanic-visitor-counter/')
                 );
             ?></p>
             <p><?php esc_html_e('If you find this plugin useful, please consider making a donation to support its development.', 'mechanic-visitor-counter'); ?></p>
             <p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ZMEZEYTRBZP5N&lc=ID&item_name=Aditya%20Subawa&item_number=426267&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url( 'https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif' ); ?>" alt="<?php esc_attr_e('Donate', 'mechanic-visitor-counter'); ?>" /></a></p>
        </div>
         <hr>
         <div class="mvc_support_info">
             <h2><?php esc_html_e('Support & Review', 'mechanic-visitor-counter'); ?></h2>
             <p><?php
                 // translators: %1$s is the support forum URL, %2$s is the review page URL
                 printf(
                     wp_kses_post(__('If you need help, please visit the <a href="%1$s" target="_blank" rel="noopener noreferrer">Support Forum</a>. If you like the plugin, please <a href="%2$s" target="_blank" rel="noopener noreferrer">leave a review</a>!', 'mechanic-visitor-counter')),
                     esc_url('https://wordpress.org/support/plugin/mechanic-visitor-counter/'),
                     esc_url('https://wordpress.org/support/plugin/mechanic-visitor-counter/reviews/?filter=5')
                 );
             ?></p>
         </div>
    </div>
    <?php
}

add_action( 'admin_init', 'statsmechanic_check_php_version' );
function statsmechanic_check_php_version() {
    if ( defined('PHP_VERSION_ID') && PHP_VERSION_ID < 50400 ) {
        add_action( 'admin_notices', 'statsmechanic_admin_notice__php_error' );
    } elseif ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
        add_action( 'admin_notices', 'statsmechanic_admin_notice__php_error' );
    }
}

function statsmechanic_admin_notice__php_error() {
    $class = 'notice notice-error is-dismissible';
    // translators: %s is the current PHP version
    $message = sprintf(
        wp_kses_post( __( 'Mechanic Visitor Counter requires PHP version 5.4 or higher. Your current PHP version is %s. The plugin may not function correctly. Please upgrade your PHP version.', 'mechanic-visitor-counter' ) ),
        '<b>' . esc_html( phpversion() ) . '</b>'
    );

    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'statsmechanic_settings_link' );
function statsmechanic_settings_link( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=mechanic-visitor-counter-options' ) ) . '">' . esc_html__( 'Settings', 'mechanic-visitor-counter' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
?>
