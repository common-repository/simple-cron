<?php

class Simple_Cron_Admin extends Simple_Cron {
	/**
	 * Error messages to diplay
	 *
	 * @var array
	 */
	private $_messages = array();
	
	public $cron_table;
	
	
	/**
	 * Class constructor
	 *
	 */
	public function __construct() {
		global  $cron_table;
       
		
		$this->_plugin_dir   = DIRECTORY_SEPARATOR . str_replace(basename(__FILE__), null, plugin_basename(__FILE__));
		$this->_settings_url = 'options-general.php?page=' . plugin_basename(__FILE__);
		
		
		$allowed_options = array();	
	
		
		// register installer function
		register_activation_hook(SC_LOADER, array(&$this, 'activate_simple_cron'));
	
		// add plugin "Settings" action on plugin list
		add_action('plugin_action_links_' . plugin_basename(SC_LOADER), array(&$this, 'add_plugin_actions'));
		
		// add links for plugin help, donations,...
		add_filter('plugin_row_meta', array(&$this, 'add_plugin_links'), 10, 2);
		
		// push options page link, when generating admin menu
		add_action('admin_menu', array(&$this, 'admin_menu'));
		
		add_filter('contextual_help', array(&$this,'admin_help'), 10, 3);
			
		$sc_options = get_option('simple_cron_plugin'); 
		if($sc_options['enable_schedule_manager']){
			//simple cron job table
			$cron_table = new Simple_Cron_Job_List();
			$this->cron_table = $cron_table;
			add_action( 'admin_head', array($cron_table, 'admin_header') );
			add_action( 'admin_head', array($cron_table, 'screen_options') );
			add_action( 'admin_menu', array($cron_table, 'simple_cron_admin_menu') );
			
		}
	}
	
	
	
	public function admin_help($contextual_help, $screen_id, $screen){
	
		global $simple_cron_admin_page, $simple_cron_jobs_page;
		
		if ($screen_id == $simple_cron_admin_page || $screen_id == $simple_cron_jobs_page) {
			
			$support_the_dev = $this->display_support_us();
			$screen->add_help_tab(array(
				'id' => 'developer-support',
				'title' => "Support the Developer",
				'content' => "<h2>Support the Developer</h2><p>".$support_the_dev."</p>"
			));
			
			
			$screen->add_help_tab(array(
				'id' => 'plugin-support',
				'title' => "Plugin Support",
				'content' => "<h2>Plugin Support</h2><p>For Plugin Support please visit <a href='http://mywebsiteadvisor.com/support/' target='_blank'>MyWebsiteAdvisor.com</a></p>"
			));


			$screen->set_help_sidebar("<p>Please Visit us online for more Free WordPress Plugins!</p><p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/' target='_blank'>MyWebsiteAdvisor.com</a></p><br>");
			
		}
		
	}		
	




	public function display_support_us(){
				
		$html = '<p><b>Thank You for using the Simple Cron Plugin for WordPress!</b></p>';
		$html .= "<p>Please take a moment to <b>Support the Developer</b> by doing some of the following items:</p>";
		
		$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
		$html .= "<li><a href='$rate_url' target='_blank' title='Click Here to Rate and Review this Plugin on WordPress.org'>Click Here</a> to Rate and Review this Plugin on WordPress.org!</li>";
		
		$html .= "<li><a href='http://facebook.com/MyWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Facebook'>Click Here</a> to Follow MyWebsiteAdvisor on Facebook!</li>";
		$html .= "<li><a href='http://twitter.com/MWebsiteAdvisor' target='_blank' title='Click Here to Follow us on Twitter'>Click Here</a> to Follow MyWebsiteAdvisor on Twitter!</li>";
		$html .= "<li><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/' target='_blank' title='Click Here to Purchase one of our Premium WordPress Plugins'>Click Here</a> to Purchase Premium WordPress Plugins!</li>";
	
	
		return $html;
	
	}



	function activation_notice_settings(){
		global $current_user ;
		global $pagenow;
		if(isset($_GET['page'])){
			if ( $pagenow == 'options-general.php' || $pagenow == 'users.php'){
				if ( $_GET['page'] == 'simple-cron/plugin-admin.php'  || $_GET['page'] == 'access_log' ) {
					$user_id = $current_user->ID;
					if ( false === ( $simple_security_nag = get_transient( 'simple_security_nag' ) ) ) {
						echo '<div class="updated">';
						echo $this->display_support_us();
						echo "<br>";
						echo '<p><a href="'.$_SERVER['REQUEST_URI'].'&simple_cron_nag_ignore=0" >Click Here to Dismiss this Message.</a></p>';
						echo "</div>";
					}
				}
			}
		}
	}
	

	
	function nag_ignore() {
		if ( isset($_GET['simple_cron_nag_ignore']) && '0' == $_GET['simple_cron_nag_ignore'] ) {
			 $expiration = 60 * 60 * 24 * 30;
			 $simple_security_nag = "true";
			 set_transient( 'simple_security_nag', $simple_security_nag, $expiration );
		}
	}
		
	 

		
	/**
	 * Add "Settings" action on installed plugin list
	 */
	public function add_plugin_actions($links) {
		array_unshift($links, '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __('Settings') . '</a>');
		
		return $links;
	}
	
	/**
	 * Add links on installed plugin list
	 */
	public function add_plugin_links($links, $file) {
		if($file == plugin_basename(SC_LOADER)) {
			$rate_url = 'http://wordpress.org/support/view/plugin-reviews/' . basename(dirname(__FILE__)) . '?rate=5#postform';
			$links[] = '<a href="'.$rate_url.'" target="_blank" title="Click Here to Rate and Review this Plugin on WordPress.org">Rate This Plugin</a>';
		}
		
		return $links;
	}
	
	/**
	 * Add menu entry for Simple Optimizer settings and attach style and script include methods
	 */
	public function admin_menu() {		
		// add option in admin menu, for settings
		global $simple_cron_admin_page;
		$simple_cron_admin_page = add_options_page('Simple Cron Plugin', 'Simple Cron', 'manage_options', __FILE__, array(&$this, 'optionsPage'));

		add_action('admin_print_styles-' . $simple_cron_admin_page,     array(&$this, 'installStyles'));
	}
	
	/**
	 * Include styles used by Simple Optimizer Plugin
	 */
	public function installStyles() {
		wp_enqueue_style('simple-cron', WP_PLUGIN_URL . $this->_plugin_dir . 'style.css');
	}
	




	public function HtmlPrintBoxHeader($id, $title, $right = false) {
		
		?>
		<div id="<?php echo $id; ?>" class="postbox">
			<h3 class="hndle"><span><?php echo $title ?></span></h3>
			<div class="inside">
		<?php	
	}
	
	public function HtmlPrintBoxFooter( $right = false) {
		?>
			</div>
		</div>
		<?php
	}
	
	
	
	/**
	* Gets a list of the cron jobs
	*/
	public function get_cron_jobs(){
	
		$schedules = $this->get_schedules();
		$cron_times = _get_cron_array();
		
		$cron_jobs = array();
		$i = 0;
		
		if(count($cron_times) > 0)
		foreach($cron_times as $time => $cron_items){				
			if(count($cron_items) > 0){
				foreach($cron_items as $hook => $data){
									
					foreach($data as $id => $params){
						$cron_jobs[$i]['hook'] = $hook;
						$cron_jobs[$i]['time'] = $time;
						$cron_jobs[$i]['id'] = $id;
						$cron_jobs[$i]['interval'] = $params['interval'];
						$cron_jobs[$i]['schedule'] = $params['schedule'];
						$cron_jobs[$i]['schedule_name'] = $schedules[ $params['schedule'] ]['display'];
						$cron_jobs[$i]['args'] = json_encode($params['args']);	
					}
					
					$i++;
				}
			}
		}
		
		return $cron_jobs;
	}

	


	/**
	 * Gets a list of the cron schedules sorted by to interval
	 */
	public function get_schedules() {
		$schedules = wp_get_schedules();
		uasort($schedules, create_function('$a,$b', 'return $a["interval"]-$b["interval"];'));
		return $schedules;
	}
					
					
		

		public function display_social_media(){
	
	$social = '<style>

.fb_edge_widget_with_comment {
	position: absolute;
	top: 0px;
	right: 200px;
}

</style>

<div  style="height:20px; vertical-align:top; width:50%; float:right; text-align:right; margin-top:5px; padding-right:16px; position:relative;">

	<div id="fb-root"></div>
	<script>(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) return;
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=253053091425708";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, "script", "facebook-jssdk"));</script>
	
	<div class="fb-like" data-href="http://www.facebook.com/MyWebsiteAdvisor" data-send="true" data-layout="button_count" data-width="450" data-show-faces="false"></div>
	
	
	<a href="https://twitter.com/MWebsiteAdvisor" class="twitter-follow-button" data-show-count="false"  >Follow @MWebsiteAdvisor</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>


</div>';

return $social;


}			

	
	
	/**
	 * Display options page
	 */
	public function optionsPage() {
		// if user clicked "Save Changes" save them
		if(isset($_POST['Submit'])) {
			foreach($this->_options as $option => $value) {
				if(array_key_exists($option, $_POST)) {
					update_option($option, $_POST[$option]);
				} else {
					update_option($option, $value);
				}
			}

			$this->_messages['updated'][] = 'Options updated!';
		}


		
		
	
		foreach($this->_messages as $namespace => $messages) {
			foreach($messages as $message) {
?>
<div class="<?php echo $namespace; ?>">
	<p>
		<strong><?php echo $message; ?></strong>
	</p>
</div>
<?php
			}
		}
		
		
			
			
				
?>

	
									  
<script type="text/javascript">var wpurl = "<?php bloginfo('wpurl'); ?>";</script>



<?php echo $this->display_social_media(); ?>


<div class="wrap" id="sm_div">

	<div id="icon-options-general" class="icon32"><br /></div>
	<h2>Simple Cron Plugin Settings</h2>
	
	<p><a href='<?php echo get_option('siteurl'); ?>/wp-admin/tools.php?page=cron_jobs'>View Simple Cron Custom Schedule Manager</a></p>
		
		
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">
			
<?php $this->HtmlPrintBoxHeader('pl_diag',__('Plugin Diagnostic Check','diagnostic'),true); ?>

				<?php
				
				echo "<p>Plugin Version: $this->version</p>";
				
				echo "<p>Server OS: ".PHP_OS."</p>";
				
				echo "<p>Required PHP Version: 5.0+<br>";
				echo "Current PHP Version: " . phpversion() . "</p>";
			
							
				echo "<p>Memory Use: " . number_format(memory_get_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
				
				echo "<p>Peak Memory Use: " . number_format(memory_get_peak_usage()/1024/1024, 1) . " / " . ini_get('memory_limit') . "</p>";
				
				$lav = sys_getloadavg();
				echo "<p>Server Load Average: ".$lav[0].", ".$lav[1].", ".$lav[2]."</p>";
				
				?>

<?php $this->HtmlPrintBoxFooter(true); ?>



<?php $this->HtmlPrintBoxHeader('pl_resources',__('Plugin Resources','resources'),true); ?>

	<p><a href='http://mywebsiteadvisor.com/wordpress-plugins/simple-cron/' target='_blank'>Plugin Homepage</a></p>
	<p><a href='http://mywebsiteadvisor.com/support/'  target='_blank'>Plugin Support</a></p>
	<p><a href='http://mywebsiteadvisor.com/contact-us/'  target='_blank'>Contact Us</a></p>
	<p><a href='http://wordpress.org/support/view/plugin-reviews/simple-cron?rate=5#postform'  target='_blank'>Rate and Review This Plugin</a></p>
	
<?php $this->HtmlPrintBoxFooter(true); ?>


<?php $this->HtmlPrintBoxHeader('more_plugins',__('More Plugins','more_plugins'),true); ?>
	
	<p><a href='http://mywebsiteadvisor.com/tools/premium-wordpress-plugins/'  target='_blank'>Premium WordPress Plugins!</a></p>
	<p><a href='http://profiles.wordpress.org/MyWebsiteAdvisor/'  target='_blank'>Free Plugins on Wordpress.org!</a></p>
	<p><a href='http://mywebsiteadvisor.com/tools/wordpress-plugins/'  target='_blank'>Free Plugins on Our Website!</a></p>	
				
<?php $this->HtmlPrintBoxFooter(true); ?>


<?php $this->HtmlPrintBoxHeader('follow',__('Follow MyWebsiteAdvisor','follow'),true); ?>

	<p><a href='http://facebook.com/MyWebsiteAdvisor/'  target='_blank'>Follow us on Facebook!</a></p>
	<p><a href='http://twitter.com/MWebsiteAdvisor/'  target='_blank'>Follow us on Twitter!</a></p>
	<p><a href='http://www.youtube.com/mywebsiteadvisor'  target='_blank'>Watch us on YouTube!</a></p>
	<p><a href='http://MyWebsiteAdvisor.com/'  target='_blank'>Visit our Website!</a></p>	
	
<?php $this->HtmlPrintBoxFooter(true); ?>


</div>
</div>



	<div class="has-sidebar sm-padded" >			
		<div id="post-body-content" class="has-sidebar-content">
			<div class="meta-box-sortabless">
	
			<form method='post'>
	
				<?php $this->HtmlPrintBoxHeader('security-settings',__('Simple Cron Plugin Settings','security-settings'),false); ?>	
			
				
		
					<?php $sc_options = get_option('simple_cron_plugin'); ?>
					
					<?php $checked = $sc_options['enable_schedule_manager'] ? 'checked="checked"' : ''; ?>
					<?php $link = $sc_options['enable_schedule_manager'] ? ' - <a href="'.get_option('siteurl').'/wp-admin/tools.php?page=cron_jobs">Custom Schedule Manager</a>' : ''; ?>
					<p><input type='checkbox' name='simple_cron_plugin[enable_schedule_manager]' <?php echo $checked; ?> /> Enable Custom Schedule Manager <?php echo $link; ?></p>
					

					<input type="submit" class='button-primary' name='Submit' value='Save Settings' />
				
			
				<?php $this->HtmlPrintBoxFooter(false); ?>
				
				
				
				<table width=100%>
				<tr valign="top">
				<td width="50%">
				
				<?php $this->HtmlPrintBoxHeader('security-settings',__('Available Cron Schedule Intervals','security-settings'),false); ?>	
					<style>
						table.wp-list-table{clear:left;}
						div.tablenav{clear:left;}
					</style>
					<?php
					
						$cron_schedule_table = new Custom_Cron_Schedule_List;
						$cron_schedule_table->display_admin_widget();
					
					?>
				
			
				<?php $this->HtmlPrintBoxFooter(false); ?>
				
				</td>
				
				
				
				
				<td>
				<?php $this->HtmlPrintBoxHeader('security-settings',__('Add A Custom Cron Schedule Interval','security-settings'),false); ?>	
					<?php
				
						Simple_Cron_Job_List::add_cron_schedule_form();
				
					?>
				<?php $this->HtmlPrintBoxFooter(false); ?>
				</td>
				</tr>
				</table>
				
			
			
			</form>
		
		
</div></div></div></div>

</div>


<?php
	}
	
}

?>