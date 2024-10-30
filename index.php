<?php
/*
  Plugin Name: Landing Page Launchpad Plugin
  Plugin URI: http://landingpagelaunchpad.com
  Description: Landing Page Launchpad(LPL) Plugin helps users to add wordpress pages using LPL created pages by following few simple steps.
  Version: 1.1
  Author: Blueprint Information Products, LLC
  Author URI:
  License: GPL2
 */
if (!defined('LPLP_API_URL')) {
    define('LPLP_API_URL', 'https://www.landingpagelaunchpad.com/api/lpl/lplpages');
}

require_once(ABSPATH . 'wp-includes/pluggable.php');

if (!defined('LPLP_ORIGINAL_HOST_URL')) {
    define('LPLP_ORIGINAL_HOST_URL', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]");
}

if (isset($_GET['lplp_check_slug_by_post'])) {
    $lplp_check_slug_by_post = (int) $_GET['lplp_check_slug_by_post'];
    $metaValue = (isset($_GET['lplp_meta_value'])) ? sanitize_title($_GET['lplp_meta_value']) : '';
    $sql = "SELECT {$wpdb->postmeta}.meta_id FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = 'lplpages_slug' AND {$wpdb->postmeta}.meta_value = '{$metaValue}' AND {$wpdb->postmeta}.post_id != %d";
    $rows = $wpdb->get_results($wpdb->prepare($sql, $lplp_check_slug_by_post));
    if (!empty($rows)) {
        echo 'This slug has already been used. Please choose another one.';
    }
    exit;
}

add_action('wp', 'lplp_landingpagelaunchpad_404');

if (!function_exists('lplp_landingpagelaunchpad_404')) {

    function lplp_landingpagelaunchpad_404() {

        if (is_404()) {
            $post_id = get_option('lplp_lplpages_404_page_id');
            if ($post_id) {
                $content_post = get_post($post_id);
                $content = base64_decode($content_post->post_content);
                if (!empty($content)) {
                    $content = apply_filters('the_content', $content);
                    $content = strip_tags($content, '<script>');
                    $content = str_replace(']]>', ']]&gt;', $content);
                    die(stripslashes($content));
                }
            }
        }
        if (is_front_page()) {

            $post_id = get_option('lplp_lplpages_front_page_id');
            if ($post_id) {
                $content_post = get_post($post_id);
                $content = base64_decode($content_post->post_content);
                if (!empty($content)) {
                    $content = apply_filters('the_content', $content);
                    $content = strip_tags($content, '<script>');
                    $content = str_replace(']]>', ']]&gt;', $content);
                    die(stripslashes($content));
                }
            }
        }
    }

}

add_action('admin_menu', 'lplp_plugin_admin_add_page');

if (!function_exists('lplp_plugin_admin_add_page')) {

    function lplp_plugin_admin_add_page() {
        add_menu_page('LPL Setting', 'LPL Setting', 10, 'ag_settings_page', 'lplp_options_page');
    }

}

if (!function_exists('lplp_options_page')) {

    function lplp_options_page() {
        include('options_page.php');
    }

}

if (!function_exists('lplp_lplpages_permalink')) {

    function lplp_lplpages_permalink($url, $post) {
        if ('lplpages' == get_post_type($post)) {
            $path = esc_html(get_post_meta($post->ID, 'lplpages_slug', true));
            if ($path != '') {
                return site_url() . '/' . $path;
            } else {
                return '';
            }
        }
        return $url;
    }

}

add_filter('post_type_link', 'lplp_lplpages_permalink', 99, 2);
if (is_admin()) {

    $lplToken = get_option('lplToken');
    if (empty($lplToken)) {
//        $style = (isset($_GET['page']) && $_GET['page'] == 'ag_settings_page') ? 'margin-left: 182px' : '';
//        echo '<div class="error notice" style="' . $style . '">';
//        echo'<p>Please provide LPL Secret Key in LPL Settings screen. </p>';
//        echo'</div>';
    } else {
        add_action('init', 'lplp_lplpages_post_register');
        add_action('add_meta_boxes', 'lplp_add_lpl_custom_meta_box');
        add_action('save_post', 'lplp_save_lpl_custom_meta', 10, 2);
        add_action('save_post', 'lplp_custom_lpl_post_type_title', 10);
        add_filter('manage_edit-lplpages_columns', 'lplp_my_lpl_columns');
        add_action('manage_posts_custom_column', 'lplp_populate_lpl_columns');
        add_filter('post_updated_messages', 'lplp_pico_custom_update_messages');
    }
}

if (!function_exists('lplp_pico_custom_update_messages')) {

    function lplp_pico_custom_update_messages($messages) {
        global $post, $post_ID;

        $post_types = get_post_types(array('show_ui' => true, '_builtin' => false), 'objects');

        foreach ($post_types as $post_type => $post_object) {

            $messages[$post_type] = array(
                0 => '', // Unused. Messages start at index 1.
                1 => sprintf(__('%s updated. '), $post_object->labels->singular_name, esc_url(get_permalink($post_ID)), $post_object->labels->singular_name),
                2 => __('Custom field updated.'),
                3 => __('Custom field deleted.'),
                4 => sprintf(__('%s has been updated successfully.'), $post_object->labels->singular_name),
                5 => isset($_GET['revision']) ? sprintf(__('%s restored to revision from %s'), $post_object->labels->singular_name, wp_post_revision_title((int) $_GET['revision'], false)) : false,
                6 => sprintf(__('%s has been published successfully. '), $post_object->labels->singular_name, esc_url(get_permalink($post_ID)), $post_object->labels->singular_name),
                7 => sprintf(__('%s saved.'), $post_object->labels->singular_name),
                8 => sprintf(__('%s submitted. <a target="_blank" href="%s">Preview %s</a>'), $post_object->labels->singular_name, esc_url(add_query_arg('preview', 'true', get_permalink($post_ID))), $post_object->labels->singular_name),
                9 => sprintf(__('%s scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview %s</a>'), $post_object->labels->singular_name, date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(get_permalink($post_ID)), $post_object->labels->singular_name),
                10 => sprintf(__('%s draft updated. <a target="_blank" href="%s">Preview %s</a>'), $post_object->labels->singular_name, esc_url(add_query_arg('preview', 'true', get_permalink($post_ID))), $post_object->labels->singular_name),
            );
        }

        return $messages;
    }

}

add_filter('the_posts', 'lplp_check_custom_lplpage_url', 1);

if (!function_exists('lplp_lplpages_post_register')) {

    function lplp_lplpages_post_register() {

        $labels = array(
            'name' => _x('LPL Page', 'post type general name'),
            'singular_name' => _x('LPL Page', 'post type singular name'),
            'add_new' => _x('Add New', 'lplpage'),
            'add_new_item' => __('Add New LP LPage'),
            'edit_item' => __('Edit LPL Page'),
            'new_item' => __('New LPL Page'),
            'view_item' => __('View LPL Pages'),
            'search_items' => __('Search LPL Pages'),
            'not_found' => __('Nothing found'),
            'not_found_in_trash' => __('Nothing found in Trash'),
            'parent_item_colon' => ''
        );
        $args = array(
            'labels' => $labels,
            'description' => 'Allows you to have LPL Pages on your WordPress site.',
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'query_var' => true,
            'menu_icon' => 'dashicons-admin-page',
            'capability_type' => 'page',
            'menu_position' => null,
            'rewrite' => true,
            'can_export' => true,
            'hierarchical' => false,
            'has_archive' => true,
            'supports' => array(
                'title'
            )
        );
        register_post_type('lplpages', $args);
    }

}

if (!function_exists('lplp_add_lpl_custom_meta_box')) {

    function lplp_add_lpl_custom_meta_box() {

        add_meta_box(
                'lplpages_meta_box', // $id
                'Configure your LPL Page', // $title
                'lplp_show_lpl_custom_meta_box', // $callback
                'lplpages', // $page
                'normal', // $context
                'high' // $priority
        );
    }

}

if (!function_exists('lplp_show_lpl_custom_meta_box')) {

    function lplp_show_lpl_custom_meta_box() {

        global $post;
        // Field Array

        $field = array(
            'label' => 'My Page',
            'desc' => 'Select from your pages.',
            'id' => 'lplpages_my_selected_page',
            'type' => 'select',
            'options' => array()
        );

        $pages = lplp_get_LPL_pages();

        if (!empty($pages) && $pages['status'] == 'success') {
            foreach ($pages['data'] as $k => $v) {
                $field['options'][$v['id']] = array(
                    'label' => $v['name'],
                    'value' => $v['id']
                );
            }
        }

        $is_nf_page = lplp_is_lpl_404_page(get_the_ID());
        $is_front_page = lplp_is_lpl_front_page($post->ID);
        $is_home_page = get_option('lplp_lplpages_front_page_id');

        $meta = get_post_meta($post->ID, 'lplpages_my_selected_page', true);
        $meta_slug = get_post_meta($post->ID, 'lplpages_slug', true);

        $delete_link = get_delete_post_link($post->ID);

        $lplpages_post_type = 'lp_normalpg';
        $redirect_method = 'http';

        if ($is_nf_page) {
            $lplpages_post_type = 'nf_404pg';
        }
        if ($is_front_page) {
            $lplpages_post_type = 'lp_homepg';
        }
        ?>

        <script>
            function selectPage(ths) {
                var selectedId = ths.options[ths.selectedIndex].value;
                var title = document.getElementById(selectedId).value;
                if (document.getElementById('title').value == '') {
                    document.getElementById('title').value = title;
                    document.getElementById("title").focus();
                }
                var content = document.getElementsByClassName(selectedId)[0].value;
                document.getElementById('content').value = content;
            }
            function selectPageType(ths) {
                var selectedId = ths.options[ths.selectedIndex].value;
                if (selectedId == 'nf_404pg' || selectedId == 'lp_homepg') {
                    document.getElementById("lplpages_slug_wrap").style.display = "none";
                } else {
                    document.getElementById("lplpages_slug_wrap").style.display = "";
                }
            }
            document.getElementById("title").addEventListener("blur", lplpOnTitleBlur);

            function  lplpOnTitleBlur() {
                var title = document.getElementById("title").value;
                title = title.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
                document.getElementById("lplpages_slug").value = title;
            }

            function lplpCheckSlug(val) {
                val = val.replace(" ", "-");
                document.getElementById("slugError").innerHTML = '';
                document.getElementById("publish").disabled = false;
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function () {
                    if (this.readyState == 4 && this.status == 200) {
                        document.getElementById("slugError").innerHTML = this.responseText;
                        if (this.responseText != '') {
                            document.getElementById("publish").disabled = true;
                        }
                    }
                };
                xhttp.open("GET", "<?php echo LPLP_ORIGINAL_HOST_URL . ''; ?>?lplp_meta_value=" + val + "&lplp_check_slug_by_post=<?php echo get_the_ID(); ?>", true);
                xhttp.send();
            }
        </script>

        <table class="form-table">
            <tbody>
                <tr class="user-email-wrap">
                    <th><label >Page type <span class="description"></span></label></th>
                    <td>
                        <select name="lplpages_post_type" onchange="selectPageType(this)">
                            <option value="lp_normalpg" <?php echo ($lplpages_post_type == 'lp_normalpg') ? 'selected' : ''; ?> >Normal Page</option>
                            <option value="lp_homepg" <?php echo ($is_front_page == 'lp_homepg') ? 'selected' : ''; ?> >Home Page</option>
                            <option value="nf_404pg" <?php echo ($lplpages_post_type == 'nf_404pg') ? 'selected' : ''; ?> >404</option>

                        </select>
                    </td>
                </tr>
                <tr class="user-url-wrap">
                    <th><label for="url">LPL Pages</label></th>
                    <td>
                        <select name="lplpages_my_selected_page" id="lplpages_my_selected_page" onchange="selectPage(this)" class="input-xlarge">
                            <?php
                            if (!empty($pages) && $pages['status'] == 'success') {

                                foreach ($pages['data'] as $key => $page) {
                                    ?>
                                    <option value="<?php echo $page['id']; ?>" <?php echo ($meta == $page['id']) ? 'selected' : ''; ?> >
                                        <?php echo esc_html(str_replace('"', "'", base64_decode($page['title']))); ?>                                    
                                    </option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                        <?php if (!empty($pages) && $pages['status'] != 'success') { ?>
                            <?php
                            $er300 = (!empty($pages['statusCode']) && $pages['statusCode'] == 300) ? 'There is something wrong. Please contact to support.' : '';
                            if (!empty($pages['status']) && $pages['status'] == 'error') {
                                echo '<span style="color:red;font-weight:bold;">' . $er300 . '</span>';
                            } else {
                                if (!empty($pages)) {
                                    $er = (!empty($pages['statusCode']) && $pages['statusCode'] == 400) ? '. Please check you API Key in LPL Setting Tab.' : '';
                                    $serverExcep = ($pages['statusCode'] === NULL) ? 'There is something wrong. Please contact to support.' : '';
                                    echo '<span style="color:red;font-weight:bold;">' . $pages['status'] . $er . $er300 . $serverExcep . '</span>';
                                }
                            }
                            ?>
                        <?php } ?>
                    </td>
                </tr>
                <tr id="lplpages_slug_wrap" class="user-url-wrap" style="display: <?php echo ($lplpages_post_type == 'nf_404pg' || $lplpages_post_type == 'lp_homepg') ? 'none' : ''; ?>">
                    <th><label for="url">Page Slug</label></th>
                    <td>
                        <?php echo LPLP_ORIGINAL_HOST_URL; ?>/<input type="text" id='lplpages_slug' name="lplpages_slug" value="<?php echo $meta_slug ?>" onblur="lplpCheckSlug(this.value)"><p id='slugError' style="color:red;"></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <input type="hidden" id="content" name="lpl_content" value="<?php echo!empty($post->post_content) ? stripslashes($post->post_content) : (!empty($pages['data'][0]['content']) ? $pages['data'][0]['content'] : ''); ?>">
        <?php
        if (!empty($pages) && $pages['status'] == 'success') {
            foreach ($pages['data'] as $key => $page) {
                ?>
                <input type="hidden" id="<?php echo $page['id']; ?>" value="<?php echo str_replace('"', "'", base64_decode($page['title']));
                ?> ">
                <input type="hidden" class="<?php echo $page['id']; ?>" value="<?php echo $page['content']; ?> ">
                <?php
            }
        }
        ?>
        <?php
    }

}

if (!function_exists('lplpages')) {

    function lplpages() {
        register_post_type('lplpages', array(
            'labels' => array(
                'name' => __('LPLPages'),
                'singular_name' => __('LPL Pages'),
                'add_new' => __('Add New LPL Page')
            ),
            'public' => true,
            'supports' => array(''),
            'menu_icon' => get_stylesheet_directory_uri() . '/images/slider-icon.png',
                )
        );
    }

}


if (!function_exists('lplp_save_lpl_custom_meta')) {

    function lplp_save_lpl_custom_meta($post_id, $post) {

        // check if this is our type
        if ($post->post_type != 'lplpages') {
            return $post_id;
        }

        $old = get_post_meta($post_id, 'lplpages_my_selected_page', true);
        $new = sanitize_text_field($_POST['lplpages_my_selected_page']);
        $lplpages_post_type = sanitize_text_field($_POST['lplpages_post_type']);
        $lp_normalpg = false;
        $lp_homepg = false;
        $nf_404pg = false;
        switch ($lplpages_post_type) {
            case 'lp_normalpg':
                break;
            case 'lp_homepg':
                $lp_homepg = true;
                break;
            case 'nf_404pg':
                $nf_404pg = true;
                break;
        }

        // HOME PAGE

        $old_homepg = lplp_get_front_lpl_page();

        if ($lp_homepg) {
            update_option('lplp_lplpages_front_page_id', $post_id);
        } elseif ($old_homepg == $post_id) {
            update_option('lplp_lplpages_front_page_id', false);
        }

        // 404 PAGE
        $old_404 = lplp_get_404_lpl_page();

        if ($nf_404pg) {
            update_option('lplp_lplpages_404_page_id', $post_id);
        } elseif ($old_404 == $post_id) {
            update_option('lplp_lplpages_404_page_id', false);
        }

        $result = lplp_get_LPL_pages();

        $pages = [];
        if (!empty($result) && $result['status'] == 'success') {
            $pages = $result['data'];
        } else {
//        print 'Critical error, loading of your pages failed!';
//        die();
        }
        // LP ID
        if ($new && $new != $old) {
            update_post_meta($post_id, 'lplpages_my_selected_page', $new);
            $data = $pages[$new];
            update_post_meta($post_id, 'lplpages_name', $data['name']);
        } elseif ('' == $new && $old) {
            delete_post_meta($post_id, 'lplpages_my_selected_page', $old);
        }

        // Custom URL
        $old = get_post_meta($post_id, 'lplpages_slug', true);
        $new = sanitize_title($_POST['lplpages_slug']);
        if ($new && $new != $old) {
            update_post_meta($post_id, 'lplpages_slug', $new);
        } elseif ('' == $new && $old) {
            delete_post_meta($post_id, 'lplpages_slug', $old);
        }

        $lplpages_my_selected_page = sanitize_text_field($_POST['lplpages_my_selected_page']);
        update_post_meta(
                $post_id, 'lplpages_split_test', (bool) $pages[$lplpages_my_selected_page]['split_test']
        );

        delete_site_transient('lplpages_page_html_cache_' . $new);

        return null;
    }

}

if (!function_exists('lplp_my_lpl_columns')) {

    function lplp_my_lpl_columns($columns) {
        $cols = array();
        $cols['cb'] = $columns['cb'];
        $cols['lplpages_name'] = 'Name';
        $cols['lplpages_type'] = 'Type';
        $cols['lplpages_path'] = 'Url';
        $cols['date'] = 'Date';
        return $cols;
    }

}

if (!function_exists('lplp_populate_lpl_columns')) {

    function lplp_populate_lpl_columns($column) {
        $path = esc_html(get_post_meta(get_the_ID(), 'lplpages_slug', true));
        $is_front_page = lplp_is_lpl_front_page(get_the_ID());
        $is_404_page = lplp_is_lpl_404_page(get_the_ID());

        $frontpage_id = get_option('lplp_lplpages_front_page_id');
        if ('lplpages_type' == $column) {
            if ($is_front_page) {
                echo '<strong style="color:#003399">Home Page</strong>';
            } elseif ($is_404_page) {
                echo '<strong style="color:#F89406">404 Page</strong>';
            } else {
                echo 'Normal';
            }
        }
        if ('lplpages_path' == $column) {
            if ($is_front_page) {
                $url = site_url() . '/';
                echo '<a href="' . $url . '" target="_blank">' . $url . '</a>';
            } elseif ($is_404_page) {
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $randomString = '';
                $length = 10;
                for ($i = 0; $i < $length; $i ++) {
                    $randomString .= $characters[rand(0, strlen($characters) - 1)];
                }
                $url = site_url() . '/test-url-' . $randomString;
                echo '<a href="' . $url . '" target="_blank">' . $url . '</a>';
            } else {
                if ($path == '') {
                    echo '<strong style="color:#ff3300">Missing path!</strong>';
                } else {
                    $url = site_url() . '/' . $path;
                    echo '<a href="' . $url . '" target="_blank">' . $url . '</a>';
                }
            }
        }
        if ('lplpages_name' == $column) {
            $url = get_edit_post_link(get_the_ID());
            $p_name = get_the_title(get_the_ID());
            if (empty($p_name)) {
                echo '<strong><a href="' . $url . '">(no title)</a></strong>';
            } else {
                echo '<strong><a href="' . $url . '">' . $p_name . '</a></strong>';
            }
        }
    }

}

if (!function_exists('lplp_is_lpl_404_page')) {

    function lplp_is_lpl_404_page($id) {
        $nf = lplp_get_404_lpl_page();
        return ( $id == $nf && $nf !== false );
    }

}

if (!function_exists('lplp_get_404_lpl_page')) {

    function lplp_get_404_lpl_page() {
        $v = get_option('lplp_lplpages_404_page_id', false);
        return ( $v == '' ) ? false : $v;
    }

}

if (!function_exists('lplp_get_LPL_pages')) {

    function lplp_get_LPL_pages() {

        $lplToken = get_option('lplToken');

        $pages = [];
        if (empty($lplToken)) {
            return array(false, 'Please provide api key');
        } else {
            $pages = lplp_api_call($lplToken);
        }
        return $pages;
    }

}

if (!function_exists('lplp_custom_lpl_post_type_title')) {

    function lplp_custom_lpl_post_type_title($post_id) {
        global $wpdb, $post_type;
        if ('lplpages' == $post_type) {
            $slug = get_post_meta($post_id, 'lplpages_slug', true);
            $content = sanitize_text_field($_POST['lpl_content']);
            $where = array('ID' => $post_id);
            $wpdb->update($wpdb->posts, array('post_content' => $content), $where);
        }
    }

}

if (!function_exists('lplp_check_custom_lplpage_url')) {

    function lplp_check_custom_lplpage_url($posts) {

        if (is_admin()) {
            return $posts;
        }

        // Determine if request should be handled by this plugin
        $requested_page = lpl_parse_request();
        if (false == $requested_page) {
            return $posts;
        }

        $content_post = get_post($requested_page['post_id']);
        $content = base64_decode($content_post->post_content);
        $content = apply_filters('the_content', $content);
        $content = strip_tags($content, '<script>');
        $content = str_replace(']]>', ']]&gt;', $content);
        die(stripslashes($content));
    }

}


if (!function_exists('lpl_parse_request')) {

    function lpl_parse_request() {
        $posts = get_all_lpl_posts();

        if (!is_array($posts)) {
            return false;
        }
        // get current url
        $current = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        // calculate the path
        $part = substr($current, strlen(site_url()));
        if ($part[0] == '/') {
            $part = substr($part, 1);
        }

        // strip parameters
        $real = explode('?', $part);
        $tokens = explode('/', $real[0]);
        if (array_key_exists($tokens[0], $posts)) {
            if ($tokens[0] == '') {
                return false;
            }
            return $posts[$tokens[0]];
        }
        return false;
    }

}


if (!function_exists('get_all_lpl_posts')) {

    function get_all_lpl_posts() {

        $p = lplp_get_my_lpl_posts();
        $res = array();
        foreach ($p as $k => $v) {
            $res[$v['lplpages_slug']] = array(
                'post_id' => $k,
                'id' => $v['lplpages_my_selected_page'],
                'name' => $v['lplpages_name'],
                'split_test' => isset($v['lplpages_split_test']) && $v['lplpages_split_test']
            );
        }
        return $res;
    }

}

if (!function_exists('lplp_get_my_lpl_posts')) {

    function lplp_get_my_lpl_posts() {
        global $wpdb;

        $sql = "SELECT {$wpdb->posts}.ID, {$wpdb->postmeta}.meta_key, {$wpdb->postmeta}.meta_value FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON ( {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id) WHERE ({$wpdb->posts}.post_type = 'lplpages') AND ({$wpdb->posts}.post_status = 'publish') AND ({$wpdb->postmeta}.meta_key IN ('lplpages_my_selected_page', 'lplpages_name', 'lplpages_my_selected_page', 'lplpages_slug', 'lplpages_split_test'))";
        $rows = $wpdb->get_results($wpdb->prepare($sql, 'lplpages'));

        $posts = array();
        foreach ($rows as $k => $row) {
            if (!array_key_exists($row->ID, $posts)) {
                $posts[$row->ID] = array();
            }
            $posts[$row->ID][$row->meta_key] = $row->meta_value;
        }

        return $posts;
    }

}

if (!function_exists('lplp_is_lpl_front_page')) {

    function lplp_is_lpl_front_page($id) {
        $front = get_lpl_front_lpl_page();
        return ( $id == $front && $front !== false );
    }

}

if (!function_exists('get_lpl_front_lpl_page')) {

    function get_lpl_front_lpl_page() {
        $v = get_option('lplp_lplpages_front_page_id', false);
        return ( $v == '' ) ? false : $v;
    }

}

if (!function_exists('lplp_get_front_lpl_page')) {

    function lplp_get_front_lpl_page() {
        $v = get_option('lplp_lplpages_front_page_id', false);
        return ( $v == '' ) ? false : $v;
    }

}

if (!function_exists('lplp_api_call')) {

    function lplp_api_call($api_key, $extra_inputs = array()) {

        $url = LPLP_API_URL . '?api_key=' . $api_key;
        $body = array(
            'api_key' => $api_key
        );

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return array(false, $response->get_error_message());
        }
        if (isset($response['response']['code'])) {
            $code_char = substr($response['response']['code'], 0, 1);
        } else {
            $code_char = '5';
        }
        if ($code_char == '5' || $code_char == '4') {
            return array(false, $response['response']['message']);
        }
        $res = json_decode($response['body'], true);
        if (!is_array($res)) {
            return array(false, 'Something is wrong. Unexpected response.');
        }
        return $res;
    }

}
