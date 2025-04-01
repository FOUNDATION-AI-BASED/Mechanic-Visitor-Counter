<?php
/*
Plugin Name: Mechanic Visitor Counter
Plugin URI: https://www.adityasubawa.com/mechanic-visitor-counter/
Description: Mechanic Visitor Counter is a widget which will display the Visitor counter and traffic statistics on WordPress.
Version: 3.3.3
Author: Aditya Subawa
Author URI: https://www.adityasubawa.com
Contributor: FOUNDATION-AI-BASED (https://github.com/FOUNDATION-AI-BASED)
License: GPL-2.0-or-later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: mechanic-visitor-counter
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load local language
add_action('plugins_loaded', 'statsmechanic_load_textdomain');
function statsmechanic_load_textdomain() {
    load_plugin_textdomain('mechanic-visitor-counter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

global $wpdb;
define('BMW_TABLE_NAME', $wpdb->prefix . 'mech_statistik');
define('BMW_PATH', ABSPATH . 'wp-content/plugins/mechanic-visitor-counter');
require_once(ABSPATH . 'wp-includes/pluggable.php');

function install() {
    global $wpdb;
    $table_name = BMW_TABLE_NAME;
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
        $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
            `ip` varchar(20) NOT NULL default '',
            `tanggal` date NOT NULL,
            `hits` int(10) NOT NULL default '1',
            `online` varchar(255) NOT NULL,
            PRIMARY KEY (`ip`, `tanggal`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
        $wpdb->query($sql);
    }
}

function uninstall() {
    global $wpdb;
    $table_name = BMW_TABLE_NAME;
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_name));
}

function acak($path, $exclude = ".|..|.svn|.DS_Store", $recursive = true) {
    $path = rtrim($path, "/") . "/";
    $folder_handle = opendir($path) or die("Eof");
    $exclude_array = explode("|", $exclude);
    $result = array();
    while (false !== ($filename = readdir($folder_handle))) {
        if (!in_array(strtolower($filename), $exclude_array)) {
            if (is_dir($path . $filename)) {
                if ($recursive) {
                    $result[] = acak($path . $filename, $exclude, true);
                }
            } elseif ($filename === '0.gif') {
                if (empty($done[$path])) {
                    $result[] = $path;
                    $done[$path] = 1;
                }
            }
        }
    }
    closedir($folder_handle);
    return $result;
}

register_activation_hook(__FILE__, 'install');
register_deactivation_hook(__FILE__, 'uninstall');

class Wp_StatsMechanic extends WP_Widget {
    function __construct() {
        $params = array(
            'description' => esc_html__('Display Visitor Counter and Statistics Traffic', 'mechanic-visitor-counter'),
            'name' => esc_html__('Mechanic - Visitor Counter', 'mechanic-visitor-counter')
        );
        parent::__construct('WP_StatsMechanic', '', $params);
    }

    public function form($instance) {
        $instance = wp_parse_args((array) $instance, array('title' => ''));
        $title = esc_attr($instance['title']);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'mechanic-visitor-counter'); ?>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                       value="<?php echo esc_attr($title); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('font_color')); ?>">
                <?php esc_html_e('Font Color:', 'mechanic-visitor-counter'); ?>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('font_color')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('font_color')); ?>" type="text"
                       value="<?php echo esc_attr($instance['font_color'] ?? ''); ?>" />
            </label>
        </p>
        <p><font size='2'><?php esc_html_e('To change the font color, fill the field with the HTML color code. Example: #333', 'mechanic-visitor-counter'); ?></font></p>
        <p><font size='2'><a href="https://www.adityasubawa.com/color-picker/" target="_blank"><?php esc_html_e('Click here to select another color variation.', 'mechanic-visitor-counter'); ?></a></font></p>
        <p><font size='3'><b><?php esc_html_e('Widget Options', 'mechanic-visitor-counter'); ?></b></font></p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('count_start')); ?>">
                <?php esc_html_e('Counter Start:', 'mechanic-visitor-counter'); ?>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('count_start')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('count_start')); ?>" type="text"
                       value="<?php echo esc_attr($instance['count_start'] ?? ''); ?>" />
            </label>
        </p>
        <p><font size='2'><?php esc_html_e('Fill in with numbers to start the initial calculation of the counter, if empty counter will start from 1', 'mechanic-visitor-counter'); ?></font></p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('hits_start')); ?>">
                <?php esc_html_e('Hits Start:', 'mechanic-visitor-counter'); ?>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('hits_start')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('hits_start')); ?>" type="text"
                       value="<?php echo esc_attr($instance['hits_start'] ?? ''); ?>" />
            </label>
        </p>
        <p><font size='2'><?php esc_html_e('Fill in the numbers to start the initial calculation of the hits, if empty hits will start from 1', 'mechanic-visitor-counter'); ?></font></p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('count_length')); ?>">
                <?php esc_html_e('Image Counter Length:', 'mechanic-visitor-counter'); ?>
                <select class="select" id="<?php echo esc_attr($this->get_field_id('count_length')); ?>"
                        name="<?php echo esc_attr($this->get_field_name('count_length')); ?>">
                    <option value="<?php echo esc_attr($instance['count_length'] ?? '4'); ?>" selected><?php echo esc_html($instance['count_length'] ?? '4'); ?></option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                </select>
            </label>
        </p>
        <p><font size='2'><?php esc_html_e('Define your Image counter length, the default length is 4', 'mechanic-visitor-counter'); ?></font></p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('today_view')); ?>">
                <?php esc_html_e('Enable Visit Today display?', 'mechanic-visitor-counter'); ?>
                <input type="checkbox" class="checkbox" <?php checked($instance['today_view'] ?? '', 'on'); ?>
                       id="<?php echo esc_attr($this->get_field_id('today_view')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('today_view')); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('yesterday_view')); ?>">
                <?php esc_html_e('Enable Visit Yesterday display?', 'mechanic-visitor-counter'); ?>
                <input type="checkbox" class="checkbox" <?php checked($instance['yesterday_view'] ?? '', 'on'); ?>
                       id="<?php echo esc_attr($this->get_field_id('yesterday_view')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('yesterday_view')); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('month_view')); ?>">
                <?php esc_html_e('Enable Month display?', 'mechanic-visitor-counter'); ?>
                <input type="checkbox" class="checkbox" <?php checked($instance['month_view'] ?? '', 'on'); ?>
                       id="<?php echo esc_attr($this->get_field_id('month_view')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('month_view')); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('year_view')); ?>">
                <?php esc_html_e('Enable Year display?', 'mechanic-visitor-counter'); ?>
                <input type="checkbox" class="checkbox" <?php checked($instance['year_view'] ?? '', 'on'); ?>
                       id="<?php echo esc_attr($this->get_field_id('year_view')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('year_view')); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('total_view')); ?>">
                <?php esc_html_e('Enable Total Visit display?', 'mechanic-visitor-counter'); ?>
                <input type="checkbox" class="checkbox" <?php checked($instance['total_view'] ?? '', 'on'); ?>
                       id="<?php echo esc_attr($this->get_field_id('total_view')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('total_view')); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('hits_view')); ?>">
                <?php esc_html_e('Enable Hits Today display?', 'mechanic-visitor-counter'); ?>
                <input type="checkbox" class="checkbox" <?php checked($instance['hits_view'] ?? '', 'on'); ?>
                       id="<?php echo esc_attr($this->get_field_id('hits_view')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('hits_view')); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('totalhits_view')); ?>">
                <?php esc_html_e('Enable Total Hits display?', 'mechanic-visitor-counter'); ?>
                <input type="checkbox" class="checkbox" <?php checked($instance['totalhits_view'] ?? '', 'on'); ?>
                       id="<?php echo esc_attr($this->get_field_id('totalhits_view')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('totalhits_view')); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('online_view')); ?>">
                <?php esc_html_e('Enable Whos Online display?', 'mechanic-visitor-counter'); ?>
                <input type="checkbox" class="checkbox" <?php checked($instance['online_view'] ?? '', 'on'); ?>
                       id="<?php echo esc_attr($this->get_field_id('online_view')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('online_view')); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('ip_display')); ?>">
                <?php esc_html_e('Enable IP address display?', 'mechanic-visitor-counter'); ?>
                <input type="checkbox" class="checkbox" <?php checked($instance['ip_display'] ?? '', 'on'); ?>
                       id="<?php echo esc_attr($this->get_field_id('ip_display')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('ip_display')); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('server_time')); ?>">
                <?php esc_html_e('Enable Server Time display?', 'mechanic-visitor-counter'); ?>
                <input type="checkbox" class="checkbox" <?php checked($instance['server_time'] ?? '', 'on'); ?>
                       id="<?php echo esc_attr($this->get_field_id('server_time')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('server_time')); ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('statsmechanic_align')); ?>">
                <?php esc_html_e('Plugins align?', 'mechanic-visitor-counter'); ?>
                <select class="select" id="<?php echo esc_attr($this->get_field_id('statsmechanic_align')); ?>"
                        name="<?php echo esc_attr($this->get_field_name('statsmechanic_align')); ?>">
                    <option value="<?php echo esc_attr($instance['statsmechanic_align'] ?? 'Left'); ?>" selected><?php echo esc_html($instance['statsmechanic_align'] ?? 'Left'); ?></option>
                    <option value="Left"><?php esc_html_e('Left', 'mechanic-visitor-counter'); ?></option>
                    <option value="Center"><?php esc_html_e('Center', 'mechanic-visitor-counter'); ?></option>
                    <option value="Right"><?php esc_html_e('Right', 'mechanic-visitor-counter'); ?></option>
                </select>
            </label>
        </p>
        <p>
            <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ZMEZEYTRBZP5N&lc=ID&item_name=Aditya%20Subawa&item_number=426267&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" target="_blank">
                <img src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" alt="<?php esc_attr_e('Donate', 'mechanic-visitor-counter'); ?>" />
            </a>
        </p>
        <?php
    }

    public function widget($args, $instance) {
        extract($args, EXTR_SKIP);

        echo $before_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
        if (!empty($title)) {
            echo $before_title . esc_html($title) . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        $ipaddress = isset($instance['ip_display']) && $instance['ip_display'] === 'on';
        $stime = isset($instance['server_time']) && $instance['server_time'] === 'on';
        $fontcolor = esc_attr($instance['font_color'] ?? '');
        $count_length = absint($instance['count_length'] ?? 4);
        $style = esc_attr(get_option('statsmechanic_style', 'default'));
        $align = esc_attr($instance['statsmechanic_align'] ?? 'Left');
        $todayview = isset($instance['today_view']) && $instance['today_view'] === 'on';
        $yesview = isset($instance['yesterday_view']) && $instance['yesterday_view'] === 'on';
        $monthview = isset($instance['month_view']) && $instance['month_view'] === 'on';
        $yearview = isset($instance['year_view']) && $instance['year_view'] === 'on';
        $totalview = isset($instance['total_view']) && $instance['total_view'] === 'on';
        $hitsview = isset($instance['hits_view']) && $instance['hits_view'] === 'on';
        $totalhitsview = isset($instance['totalhits_view']) && $instance['totalhits_view'] === 'on';
        $onlineview = isset($instance['online_view']) && $instance['online_view'] === 'on';
        $count_start = absint($instance['count_start'] ?? 0);
        $hits_start = absint($instance['hits_start'] ?? 0);

        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
        $tanggal = gmdate("Y-m-d");
        $waktu = time();
        $bln = gmdate("m");
        $tgl = gmdate("d");
        $blan = gmdate("Y-m");
        $thn = gmdate("Y");
        $tglk = $tgl - 1;

        global $wpdb;
        $table_name = BMW_TABLE_NAME;

        // Check if user has visited today
        $sql = $wpdb->prepare("SELECT * FROM `$table_name` WHERE ip = %s AND tanggal = %s", $ip, $tanggal);
        $visited = $wpdb->get_results($sql, ARRAY_A);
        if (empty($visited)) {
            $wpdb->insert($table_name, array(
                'ip' => $ip,
                'tanggal' => $tanggal,
                'hits' => 1,
                'online' => $waktu
            ));
        } else {
            $wpdb->update($table_name, array('hits' => $visited[0]['hits'] + 1, 'online' => $waktu),
                array('ip' => $ip, 'tanggal' => $tanggal));
        }

        // Yesterday by IP
        $kemarin1 = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE tanggal = %s", "$thn-$bln-$tglk"));
        // This month by IP
        $bulan1 = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE tanggal LIKE %s", "%$blan%"));
        // This year by IP
        $tahunini1 = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE tanggal LIKE %s", "%$thn%"));
        // Visitor today by IP
        $pengunjung = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT ip) FROM `$table_name` WHERE tanggal = %s", $tanggal));
        // Total visitor by IP
        $totalpengunjung = $wpdb->get_var("SELECT COUNT(DISTINCT ip) FROM `$table_name`");
        // Hits today
        $hits = $wpdb->get_var($wpdb->prepare("SELECT SUM(hits) FROM `$table_name` WHERE tanggal = %s", $tanggal));
        // Total hits
        $totalhits = $wpdb->get_var("SELECT SUM(hits) FROM `$table_name`");
        // Unique visitor by IP
        $tothitsgbr = $wpdb->get_var("SELECT COUNT(DISTINCT ip) FROM `$table_name`");
        // Who's online
        $bataswaktu = $waktu - 300;
        $pengunjungonline = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE online > %d", $bataswaktu));

        $ext = ".gif";
        $new_count_length = $count_length ?: 4;
        if (!$count_start) {
            $tothitsgbr = sprintf("%0{$new_count_length}d", $tothitsgbr);
        } else {
            $tothitsgbr = sprintf("%07d", $tothitsgbr + $count_start);
        }
        $tothitsstring = "";
        $arr = str_split($tothitsgbr);
        foreach ($arr as $value) {
            $tothitsstring .= sprintf('<img src="%s" alt="%s">',
                esc_url(plugin_dir_url(__FILE__) . "styles/$style/$value$ext"),
                esc_attr($value)
            );
        }
        $tothitsgbr = $tothitsstring;

        // Images (Note: Ideally use wp_get_attachment_image, but this is a custom counter system)
        $imgvisit = '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'counter/mvcvisit.png') . '" alt="' . esc_attr__('Visit Today', 'mechanic-visitor-counter') . '">';
        $yesterday = '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'counter/mvcyesterday.png') . '" alt="' . esc_attr__('Yesterday', 'mechanic-visitor-counter') . '">';
        $month = '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'counter/mvcmonth.png') . '" alt="' . esc_attr__('This Month', 'mechanic-visitor-counter') . '">';
        $year = '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'counter/mvcyear.png') . '" alt="' . esc_attr__('This Year', 'mechanic-visitor-counter') . '">';
        $imgtotal = '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'counter/mvctotal.png') . '" alt="' . esc_attr__('Total Visit', 'mechanic-visitor-counter') . '">';
        $imghits = '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'counter/mvctoday.png') . '" alt="' . esc_attr__('Hits Today', 'mechanic-visitor-counter') . '">';
        $imgtotalhits = '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'counter/mvctotalhits.png') . '" alt="' . esc_attr__('Total Hits', 'mechanic-visitor-counter') . '">';
        $imgonline = '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'counter/mvconline.png') . '" alt="' . esc_attr__('Online', 'mechanic-visitor-counter') . '">';

        // Enqueue stylesheet
        wp_enqueue_style('mechanic-visitor-counter', plugin_dir_url(__FILE__) . 'styles/css/default.css', array(), '3.3.3');
        ?>
        <div id='mvcwid' style='font-size:2; text-align:<?php echo esc_attr($align); ?>; color:<?php echo esc_attr($fontcolor); ?>;'>
            <div id="mvccount"><?php echo $tothitsgbr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
            <div id="mvctable">
                <table width='100%'>
                    <?php if ($todayview) { ?>
                        <tr>
                            <td style='font-size:2; text-align:<?php echo esc_attr($align); ?>; color:<?php echo esc_attr($fontcolor); ?>;'>
                                <?php echo $imgvisit; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php esc_html_e('Visit Today:', 'mechanic-visitor-counter'); ?>
                                <?php echo esc_html($pengunjung); ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if ($yesview) { ?>
                        <tr>
                            <td style='font-size:2; text-align:<?php echo esc_attr($align); ?>; color:<?php echo esc_attr($fontcolor); ?>;'>
                                <?php echo $yesterday; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php esc_html_e('Visit Yesterday:', 'mechanic-visitor-counter'); ?>
                                <?php echo esc_html($kemarin1); ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if ($monthview) { ?>
                        <tr>
                            <td style='font-size:2; text-align:<?php echo esc_attr($align); ?>; color:<?php echo esc_attr($fontcolor); ?>;'>
                                <?php echo $month; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php esc_html_e('This Month:', 'mechanic-visitor-counter'); ?>
                                <?php echo esc_html($bulan1); ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if ($yearview) { ?>
                        <tr>
                            <td style='font-size:2; text-align:<?php echo esc_attr($align); ?>; color:<?php echo esc_attr($fontcolor); ?>;'>
                                <?php echo $year; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php esc_html_e('This Year:', 'mechanic-visitor-counter'); ?>
                                <?php echo esc_html($tahunini1); ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if ($totalview) { ?>
                        <tr>
                            <td style='font-size:2; text-align:<?php echo esc_attr($align); ?>; color:<?php echo esc_attr($fontcolor); ?>;'>
                                <?php echo $imgtotal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php esc_html_e('Total Visit:', 'mechanic-visitor-counter'); ?>
                                <?php echo esc_html($totalpengunjung + $count_start); ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if ($hitsview) { ?>
                        <tr>
                            <td style='font-size:2; text-align:<?php echo esc_attr($align); ?>; color:<?php echo esc_attr($fontcolor); ?>;'>
                                <?php echo $imghits; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php esc_html_e('Hits Today:', 'mechanic-visitor-counter'); ?>
                                <?php echo esc_html($hits); ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if ($totalhitsview) { ?>
                        <tr>
                            <td style='font-size:2; text-align:<?php echo esc_attr($align); ?>; color:<?php echo esc_attr($fontcolor); ?>;'>
                                <?php echo $imgtotalhits; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php esc_html_e('Total Hits:', 'mechanic-visitor-counter'); ?>
                                <?php echo esc_html($hits_start ? $totalhits + $hits_start : $totalhits); ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if ($onlineview) { ?>
                        <tr>
                            <td style='font-size:2; text-align:<?php echo esc_attr($align); ?>; color:<?php echo esc_attr($fontcolor); ?>;'>
                                <?php echo $imgonline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php esc_html_e("Who's Online:", 'mechanic-visitor-counter'); ?>
                                <?php echo esc_html($pengunjungonline); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
            <?php if ($ipaddress) { ?>
                <div id="mvcip">
                    <?php esc_html_e('Your IP Address:', 'mechanic-visitor-counter'); ?>
                    <?php echo esc_html($ip); ?>
                </div>
            <?php } ?>
            <?php if ($stime) { ?>
                <div id="mvcserver">
                    <?php esc_html_e('Server Time:', 'mechanic-visitor-counter'); ?>
                    <?php echo esc_html($tanggal); ?>
                </div>
            <?php } ?>
        </div>
        <?php
        echo $after_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

add_action('widgets_init', 'register_wp_statsmechanic');

function mvc_shortcode($atts) {
    global $wp_widget_factory;
    $atts = shortcode_atts(array(
        'widget_name' => 'Wp_StatsMechanic',
        'instance' => ''
    ), $atts, 'mechanic_visitor');
    $widget_name = esc_html($atts['widget_name']);
    $instance = str_ireplace("&amp;", '&', $atts['instance']);
    ob_start();
    the_widget($widget_name, $instance, array(
        'widget_id' => 'arbitrary-instance-' . uniqid(),
        'before_widget' => '',
        'after_widget' => '',
        'before_title' => '',
        'after_title' => ''
    ));
    $output = ob_get_clean();
    return $output;
}
add_shortcode('mechanic_visitor', 'mvc_shortcode');

function register_wp_statsmechanic() {
    register_widget('Wp_StatsMechanic');
}

// ADMIN OPTIONS
add_action('admin_menu', 'statsmechanic_menu');
function statsmechanic_menu() {
    register_setting('plugin_statsmechanic_menu', 'statsmechanic_style', 'sanitize_text_field');
    add_options_page(
        esc_html__('Plugin Stats Mechanic', 'mechanic-visitor-counter'),
        esc_html__('Visitor Counter Options', 'mechanic-visitor-counter'),
        'manage_options',
        'plugin_statsmechanic_menu',
        'statsmechanic_options'
    );
}

function statsmechanic_options() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'mechanic-visitor-counter'));
    }
    ?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"></div>
        <h2><?php esc_html_e('Plugin Options Mechanic Visitor Counter', 'mechanic-visitor-counter'); ?></h2><br/>
        <div class="mvc_plugins_wrap">
            <div class="mvc_right_sidebar">
                <div class="mvc_plugins_text">
                    <div class="mvc_option_wrap">
                        <h3 class="hndle"><?php esc_html_e('Donate', 'mechanic-visitor-counter'); ?></h3>
                        <p><?php esc_html_e('If you like and helped with my plugins, please donate to the developer. How much your nominal will help developers to develop these plugins.', 'mechanic-visitor-counter'); ?></p><br/>
                        <p style="margin:-48px 0px 0px 10px;">
                            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                                <input type="hidden" name="cmd" value="_s-xclick">
                                <input type="hidden" name="encrypted" value="...[Encrypted PayPal data]...">
                                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="<?php esc_attr_e('PayPal - The safer, easier way to pay online!', 'mechanic-visitor-counter'); ?>">
                                <img alt="" border="0" src="https://www.paypalobjects.com/id_ID/i/scr/pixel.gif" width="1" height="1">
                            </form>
                        </p>
                    </div>
                </div>
            </div>
            <div class="mvc_left_sidebar">
                <div class="mvc_plugins_text">
                    <div class="mvc_option_wrap">
                        <h3 class="hndle"><?php esc_html_e('Join my mailing list', 'mechanic-visitor-counter'); ?></h3>
                        <p><?php esc_html_e('Join my mailing list for tips, tricks, and Website secrets.', 'mechanic-visitor-counter'); ?></p>
                        <form action="http://feedburner.google.com/fb/a/mailverify" method="post" target="popupwindow" onsubmit="window.open('http://feedburner.google.com/fb/a/mailverify?uri=adityasubawa', 'popupwindow', 'scrollbars=yes,width=550,height=520');return true">
                            <p>
                                <?php esc_html_e('Enter your email address:', 'mechanic-visitor-counter'); ?>
                                <input type="text" style="width:140px" name="email"/>
                                <input type="hidden" value="adityasubawa" name="uri"/>
                                <input type="hidden" name="loc" value="en_US"/>
                                <input type="submit" value="<?php esc_attr_e('Subscribe', 'mechanic-visitor-counter'); ?>" />
                            </p>
                        </form>
                    </div>
                </div>
                <div class="mvc_option_wrap">
                    <div class="mvc_plugins_text">
                        <h3 class="hndle"><?php esc_html_e('Image Counter', 'mechanic-visitor-counter'); ?></h3>
                        <form method="post" action="options.php">
                            <?php settings_fields('plugin_statsmechanic_menu'); ?>
                            <?php
                            $data = acak(WP_CONTENT_DIR . '/plugins/mechanic-visitor-counter/styles/');
                            $groups = array();
                            foreach ($data as $records) {
                                foreach ($records as $test) {
                                    if (preg_match('/styles\/(.*?)\/(.*?)\//', $test, $match)) {
                                        $groups[$match[1]][] = $match[2];
                                    }
                                }
                            }
                            foreach ($groups as $style_name => $style) {
                                ?>
                                <p><b><?php printf(esc_html__('Choose one of the %s counter styles below:', 'mechanic-visitor-counter'), esc_html($style_name)); ?></b></p>
                                <table class="form-table">
                                    <?php
                                    foreach ($style as $name) {
                                        ?>
                                        <tr>
                                            <td>
                                                <input type="radio" id="img1" name="statsmechanic_style"
                                                       value="<?php echo esc_attr("$style_name/$name"); ?>"
                                                       <?php checked("$style_name/$name", get_option('statsmechanic_style')); ?> />
                                                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . "styles/$style_name/$name/0.gif"); ?>" alt="0">
                                                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . "styles/$style_name/$name/1.gif"); ?>" alt="1">
                                                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . "styles/$style_name/$name/2.gif"); ?>" alt="2">
                                                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . "styles/$style_name/$name/3.gif"); ?>" alt="3">
                                                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . "styles/$style_name/$name/4.gif"); ?>" alt="4">
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </table>
                                <?php
                            }
                            ?>
                            <p style="margin-top:20px;">
                                <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'mechanic-visitor-counter'); ?>" />
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style type="text/css">
        /* ADMIN STYLING */
        .form-table { clear: none; }
        .form-table td { vertical-align: top; padding: 16px 20px 5px; line-height: 10px; font-size: 12px; }
        .form-table th { width: 200px; padding: 10px 0 12px 9px; }
        .mvc_right_sidebar { width: 42%; float: right; }
        .mvc_left_sidebar { width: 55%; margin-left: 10px; }
        .mvc_plugins_text { margin-bottom: 0px; }
        .mvc_plugins_text p { padding: 5px 10px 10px 10px; width: 90%; }
        .mvc_plugins_text h2 { font-size: 14px; padding: 0px; font-weight: bold; line-height: 29px; }
        .mvc_plugins_wrap .hndle { font-size: 15px; font-family: Georgia,"Times New Roman","Bitstream Charter",Times,serif; font-weight: normal; padding: 7px 10px; margin: 0; line-height: 1; border-top-left-radius: 3px; border-top-right-radius: 3px; border-bottom-color: rgb(223, 223, 223); text-shadow: 0px 1px 0px rgb(255, 255, 255); box-shadow: 0px 1px 0px rgb(255, 255, 255); background: linear-gradient(to top, rgb(236, 236, 236), rgb(249, 249, 249)) repeat scroll 0% 0% rgb(241, 241, 241); margin-top: 1px; border-bottom-width: 1px; border-bottom-style: solid; -moz-user-select: none; }
        .mvc_option_wrap { border:1px solid rgb(223, 223, 223); width:100%; margin-bottom:30px; height:auto; }
    </style>
    <?php
}

function statsmechanic_admin_notice__error() {
    if (version_compare(phpversion(), '5.4', '<')) {
        $class = 'notice notice-error';
        $message = sprintf(
            esc_html__('Your PHP version must be above 5.4. Mechanic Visitor Counter plugin no longer supports PHP legacy versions (5.2.x, 5.3.x). Your current PHP version is %s.', 'mechanic-visitor-counter'),
            '<b>' . esc_html(phpversion()) . '</b>'
        );
        printf('<div id="message" class="%s is-dismissable"><p>%s</p></div>', esc_attr($class), $message); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        deactivate_plugins('/mechanic-visitor-counter/wp-statsmechanic.php');
    }
}
add_action('admin_notices', 'statsmechanic_admin_notice__error');
?>
