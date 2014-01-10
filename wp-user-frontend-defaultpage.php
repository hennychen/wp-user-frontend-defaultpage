<?php
/*
Plugin Name: wp-user-frontend-defaultpage
Description: wp-user-frontend插件创建默认页面1. Create a new Page “New Post” and insert shorcode `[wpuf_addpost]`.
    For a custom post type **event**, use it like `[wpuf_addpost post_type="event"]`
1. Create a new Page “Edit” for editing posts and insert shorcode `[wpuf_edit]`
1. Create a new Page “Profile” for editing profile and insert shorcode `[wpuf_editprofile]`
1. Create a new Page “Dashboard” and insert shorcode `[wpuf_dashboard]`
    To list custom post type **event**, use it like `[wpuf_dashboard post_type="event"]`
1. Set the *Edit Page* option from *Others* tab on settings page.
1. To show the subscription info, insert the shortcdoe `[wpuf_sub_info]`
1. To show the subscription packs, insert the shortcode `[wpuf_sub_pack]`
1. For subscription payment page, set the *Payment Page* from *Payments* tab on settings page.
1. To edit users, insert the shortcode `[wpuf-edit-users]`
Version: 1.0
Author: hennychen
Author URI: chenjunheng@foxmail.com
*/
/*  Copyright 2014  hennychen  (email : chenjunheng@foxmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (!is_plugin_active('wp-user-frontend/wpuf.php')) {
    // the plugin is active
    add_action('admin_notices','draw_notice_wp_user_frontend_defaultpage');
    return;
}
function draw_notice_wp_user_frontend_defaultpage(){
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';
    _e('<strong>wp-user-frontend</strong> requires the wp-user-frontend plugin to be activated. Please <a href="http://wordpress.org/plugins/wp-user-frontend/">install / activate wp-user-frontend</a> first, or <a href="plugins.php">deactivate wp-user-frontend for Buddypress</a>.', 'wp-user-frontend');
    echo '</p></div>';
}

if (!class_exists('WP_user_frontend_defaultpage'))
{
    class WP_user_frontend_defaultpage
    {
        public $_name;
        public $page_title;
        public $page_name;
        public $page_id;
        public $page_content;

        public function __construct()
        {
            $this->_name      = 'wp_user_frontend_defaultpage';
            $this->page_title = 'userdashboard';
            $this->page_name  = $this->_name;
            $this->page_id    = '0';

            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));
            register_uninstall_hook(__FILE__, array($this, 'uninstall'));

            add_filter('parse_query', array($this, 'query_parser'));
            add_filter('the_posts', array($this, 'page_filter'));
        }

        public function activate()
        {
            global $wpdb;

            delete_option($this->_name.'_page_title');
            add_option($this->_name.'_page_title', $this->page_title, '', 'yes');

            delete_option($this->_name.'_page_name');
            add_option($this->_name.'_page_name', $this->page_name, '', 'yes');

            delete_option($this->_name.'_page_id');
            add_option($this->_name.'_page_id', $this->page_id, '', 'yes');

            $the_page = get_page_by_title($this->page_title);

            if (!$the_page)
            {
                // Create post object
                $_p = array();
                $_p['post_title']     = $this->page_title;
                $_p['post_content']   = "[wpuf_dashboard]";
                $_p['post_status']    = 'publish';
                $_p['post_type']      = 'page';
                $_p['comment_status'] = 'closed';
                $_p['ping_status']    = 'closed';
                $_p['post_category'] = array(1); // the default 'Uncatrgorised'

                // Insert the post into the database
                $this->page_id = wp_insert_post($_p);
            }
            else
            {
                // the plugin may have been previously active and the page may just be trashed...
                $this->page_id = $the_page->ID;

                //make sure the page is not trashed...
                $the_page->post_status = 'publish';
                $this->page_id = wp_update_post($the_page);
            }

            delete_option($this->_name.'_page_id');
            add_option($this->_name.'_page_id', $this->page_id);
        }

        //创建新页面
        public function activateByParames(){
            global $wpdb;

            delete_option($this->_name.'_page_title');
            add_option($this->_name.'_page_title', $this->page_title, '', 'yes');

            delete_option($this->_name.'_page_name');
            add_option($this->_name.'_page_name', $this->page_name, '', 'yes');

            delete_option($this->_name.'_page_id');
            add_option($this->_name.'_page_id', $this->page_id, '', 'yes');

            $the_page = get_page_by_title($this->page_title);

            if (!$the_page)
            {
                // Create post object
                $_p = array();
                $_p['post_title']     = $this->page_title;
                $_p['post_content']   = $this->page_content;
                $_p['post_status']    = 'publish';
                $_p['post_type']      = 'page';
                $_p['comment_status'] = 'closed';
                $_p['ping_status']    = 'closed';
                $_p['post_category'] = array(1); // the default 'Uncatrgorised'

                // Insert the post into the database
                $this->page_id = wp_insert_post($_p);
            }
            else
            {
                // the plugin may have been previously active and the page may just be trashed...
                $this->page_id = $the_page->ID;

                //make sure the page is not trashed...
                $the_page->post_status = 'publish';
                $this->page_id = wp_update_post($the_page);
            }

            delete_option($this->_name.'_page_id');
            add_option($this->_name.'_page_id', $this->page_id);
        }

        public function deactivate()
        {
            $this->deletePage();
            $this->deleteOptions();
        }

        public function uninstall()
        {
            $this->deletePage(true);
            $this->deleteOptions();
        }

        public function query_parser($q)
        {
            if(!empty($q->query_vars['page_id']) AND (intval($q->query_vars['page_id']) == $this->page_id ))
            {
                $q->set($this->_name.'_page_is_called', true);
            }
            elseif(isset($q->query_vars['pagename']) AND (($q->query_vars['pagename'] == $this->page_name) OR ($_pos_found = strpos($q->query_vars['pagename'],$this->page_name.'/') === 0)))
            {
                $q->set($this->_name.'_page_is_called', true);
            }
            else
            {
                $q->set($this->_name.'_page_is_called', false);
            }

        }

        function page_filter($posts)
        {
            global $wp_query;

            if($wp_query->get($this->_name.'_page_is_called'))
            {
                $posts[0]->post_title = __('userdashboard');
                $posts[0]->post_content = 'The contents';
            }
            return $posts;
        }

        private function deletePage($hard = false)
        {
            global $wpdb;

            $id = get_option($this->_name.'_page_id');
            if($id && $hard == true)
                wp_delete_post($id, true);
            elseif($id && $hard == false)
                wp_delete_post($id);
        }

        private function deleteOptions()
        {
            delete_option($this->_name.'_page_title');
            delete_option($this->_name.'_page_name');
            delete_option($this->_name.'_page_id');
        }
    }
}
//初始化用户中心
$wp_user_frontend_defaultpage = new WP_user_frontend_defaultpage();
//创建添加
$wp_user_frontend_defaultpage->page_title = 'add_post';
$wp_user_frontend_defaultpage->page_content = '[wpuf_addpost]';
$wp_user_frontend_defaultpage->activateByParames();
//创建编辑
$wp_user_frontend_defaultpage->page_title = 'wpuf_edit';
$wp_user_frontend_defaultpage->page_content = '[wpuf_edit]';
$wp_user_frontend_defaultpage->activateByParames();

//创建用户中心导航菜单
add_action('bp_setup_nav', 'mb_bp_profile_menu_posts', 301 );
function mb_bp_profile_menu_posts() {
    global $bp;
    bp_core_new_nav_item(
        array(
            'name' => '文章',
            'slug' => 'userdashboard',
            'position' => 11,
            'default_subnav_slug' => 'published', // We add this submenu item below
            'screen_function' => 'mb_author_posts'
        ),
        array(
            'name' => 'My Posts111',
            'slug' => 'posts111',
            'parent_slug' => 'userdashboard'

        )
    );
}
function mb_author_posts(){
    bp_core_redirect(bp_core_get_root_domain().'/userdashboard');
}