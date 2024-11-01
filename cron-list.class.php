<?php

//simple cron scheduled jobs list

class Simple_Cron_Job_List{
    
    public $opt_name = 'simple_cron';
	
	public $data_labels = array();
	
	public $cron_schedules = array();
	
	
	public function __construct() {

		//For translation purposes
        $this->data_labels = array(
            'data'              => __('Data', 'simple_cron'),
			'hook'              => __('Hook', 'simple_cron'),
			'schedule_name'     => __('Schedule', 'simple_cron')
        );
		

		
		$this->load_schedules();
		
		
		if(isset($_POST['action']) && $_POST['action'] == 'add_custom_cron_schedule'){
			if($_POST['schedule_name'] == "") return;
			if($_POST['schedule_description'] == "") return;
			if(!is_numeric($_POST['schedule_interval'])) return;

			if(is_admin() ){		
				$this->add_schedule($_POST['schedule_name'], $_POST['schedule_interval'], $_POST['schedule_description']);
				$this->load_schedules();
			}else{
				die('error');
			}
		}
		
		if(isset($_POST['action']) && $_POST['action'] == 'delete_custom_cron_schedule'){
			if(is_admin() ){		
				$this->remove_schedule($_POST['delete_schedule_item']);
			}else{
				die('error');
			}
		}
		
		
		
		if(isset($_POST['action']) && $_POST['action'] == 'add_custom_cron_job'){
			if($_POST['job_hook_name'] == "") return;
			
			if(is_admin() ){		
				$args = json_decode($_POST['job_arguments'], true);
				if(!is_array($args)) $args = array();
				wp_schedule_event( time(), $_POST['job_interval'], $_POST['job_hook_name'], $args);
			}else{
				die('error');
			}
		}
		
		
		if(isset($_POST['action']) && $_POST['action'] == 'delete_cron_job'){
			if(is_admin() ){	
				$timestamp = $_POST['delete_item_time'];	
				$name = $_POST['delete_item_name'];	
				$id = $_POST['delete_item_id'];	
				$cron_jobs = _get_cron_array();
    			if( isset( $cron_jobs[$timestamp][$name][$id] ) ) {
    	    		$args = $cron_jobs[$timestamp][$name][$id]['args'];
            		wp_unschedule_event($timestamp, $name, $args);
				}
			}else{
				die('error');
			}
		}	
		
	}
	
	private function load_schedules(){
		$options = get_option('simple_cron_plugin');
		$custom_schedules = isset($options['custom_schedules']) ? $options['custom_schedules'] : array();
		
		if(is_array($custom_schedules))
		foreach($custom_schedules as $name => $schedule){
			
			add_filter( 'cron_schedules', function($schedules) use ($name, $schedule){
				
				$schedules[ $name ] = array(
					'interval' 	=> $schedule['interval'],
					'display' 	=> $schedule['display']
				);
				
				return $schedules;
			}); 	
		}
	
	}
	
	
	private function add_schedule($name, $interval, $display) {
		$options = get_option('simple_cron_plugin');
		$custom_schedules = $options['custom_schedules']; 
					
		$custom_schedules[ $name ] = array(
			'interval' 	=> (int) $interval,
			'display' 	=> $display
		);
		
		$options['custom_schedules'] = $custom_schedules;
		update_option('simple_cron_plugin', $options);
    }


	private function remove_schedule($name) {
		$options = get_option('simple_cron_plugin');
		$custom_schedules = $options['custom_schedules']; 
			
		unset($custom_schedules[ $name ]);
					
		$options['custom_schedules'] = $custom_schedules;
		update_option('simple_cron_plugin', $options);
		
		add_filter( 'cron_schedules', function($schedules) use ($name){
			unset( $schedules[ $name ] );
			return $schedules;
		}); 
    }	
	
	
	function simple_cron_admin_menu(){
        global $simple_cron_jobs_page;
		$simple_cron_jobs_page = add_submenu_page( 'tools.php', __('Simple Cron Job Manager', 'simple_cron'), __('Cron Manager', 'simple_cron'), 'manage_options', 'cron_jobs', array(&$this, 'cron_manager') );
		
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
						$cron_jobs[$i]['hook'] 			= $hook;
						$cron_jobs[$i]['time'] 			= $time;
						
						//$cron_jobs[$i]['time_str'] 		= date_i18n("Y/m/d g:i:s A T", $time);
						
						$date = new DateTime("@".$time);
						$date->setTimezone(new DateTimeZone(get_option('timezone_string')));  
						
						$cron_jobs[$i]['time_str'] 		= $date->format('Y-m-d g:i:s A T');
						
						$cron_jobs[$i]['id'] 			= $id;
						$cron_jobs[$i]['interval'] 		= $params['interval'];
						$cron_jobs[$i]['schedule'] 		= $params['schedule'];
						$cron_jobs[$i]['schedule_name'] = $schedules[ $params['schedule'] ]['display'];
						$cron_jobs[$i]['args'] 			= json_encode($params['args']);	
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
	
	
	function cron_manager(){

        $cron_table = $this->cron_table;

        $cron_table->items = $this->get_cron_jobs();
        $cron_table->prepare_items();

		echo Simple_Cron_Admin::display_social_media();

		echo '<div class="wrap" id="sm_div">';
		
		echo '<div id="icon-tools" class="icon32"><br /></div>';
        echo '<h2>' . __('Simple Cron Job Manager', 'simple-cron') . '</h2>';
		//echo "<p float='left'><a  href='".get_option('siteurl')."/wp-admin/options-general.php?page=simple-cron/plugin-admin.php' >View Simple Cron Plugin Settings</a></p>";
				
		echo '<div id="poststuff" class="metabox-holder has-right-sidebar">';
		
		echo '<div id="post-body" class="metabox-holder columns-2">';
		
	        $cron_table->display();


		echo "<table width='100%' cellpadding='5px'>";
		echo "<tr valign='top'>";
			echo "<td wdith='33%'>";
			 
				Simple_Cron_Admin::HtmlPrintBoxHeader('add_cron',__('Add Cron Job','cron'),false);
				
					$this->add_cron_job_form();
				
				Simple_Cron_Admin::HtmlPrintBoxFooter(false);
			
			echo "</td>";
			
			
			echo "<td  wdith='33%'>";
				Simple_Cron_Admin::HtmlPrintBoxHeader('add_cron_schedule',__('Available Cron Schedule Intervals','cron'),false);
					$cron_schedule_table = new Custom_Cron_Schedule_List;
					$cron_schedule_table->display_admin_widget();
				Simple_Cron_Admin::HtmlPrintBoxFooter(false);
			echo "</td>";
			
			
			echo "<td wdith='33%'>";
			 
				Simple_Cron_Admin::HtmlPrintBoxHeader('add_cron_schedule',__('Add a New Cron Schedule Interval','cron'),false);
				
					$this->add_cron_schedule_form();
				
				Simple_Cron_Admin::HtmlPrintBoxFooter(false);
			
			echo "</td>";
			
		echo "</tr>";
		echo "</table>";
	
		echo "</div></div></div>";

    }


	function add_cron_job_form(){
	
		$form = "";
		$schedules = $this->get_schedules();
		foreach($schedules as $name=> $schedule){
			$form .= "<option value='$name'>".$schedule['display'] ." (".$schedule['interval'] ." seconds)</option>";
		}
		
		echo "<form method='post'>";
		echo "<input type='hidden' name='action' value='add_custom_cron_job'>";
		echo "<p>Hook Name: (ex. 'wp_update_plugins')<br><input type='text' name='job_hook_name' class='widefat'></p>";
		echo "<p>Schedule Interval:<br><select name='job_interval'>";
		echo $form;
		echo "</select></p>";
		echo '<p>Arguments: (optional)<br><input type="text" name="job_arguments" class="widefat"><br>(ex. [], ["asdf"], [1] or [1, "asdf"])</p>';
		echo "<p><input type='submit' class='button-primary' value='Save New Job'></p>";
		echo "</form>";
	}
	
	
	function add_cron_schedule_form(){
		echo "<form method='post'>";
		echo "<input type='hidden' name='action' value='add_custom_cron_schedule'>";
		echo "<p>Schedule Name: (ex. 'hourly')<br><input type='text' name='schedule_name'></p>";
		echo "<p>Schedule Description: (ex. 'Once Hourly')<br><input type='text' name='schedule_description'></p>";
		echo "<p>Schedule Interval: (ex. '3600')<br><input type='text' name='schedule_interval'> (sec.)</p>";
		echo "<p><input type='submit' class='button-primary' value='Save New Interval'></p>";
		echo "</form>";
	}
	



	function screen_options(){

        //execute only on login_log page, othewise return null
        $page = ( isset($_GET['page']) ) ? esc_attr($_GET['page']) : false;
        if( 'cron_jobs' != $page )
            return;

        $current_screen = get_current_screen();

        //define options
        $per_page_field = 'per_page';
        $per_page_option = $current_screen->id . '_' . $per_page_field;

        //Save options that were applied
        if( isset($_REQUEST['wp_screen_options']) && isset($_REQUEST['wp_screen_options']['value']) ){
            update_option( $per_page_option, esc_html($_REQUEST['wp_screen_options']['value']) );
        }

        //prepare options for display

        //if per page option is not set, use default
        $per_page_val = get_option($per_page_option, 20);
        $args = array('label' => __('Records', 'simple-security'), 'default' => $per_page_val );

        //display options
        add_screen_option($per_page_field, $args);
        $_per_page = get_option('tools_page_cron_table_per_page');

        //create custom list table class to display log data
        $this->cron_table = new Custom_Cron_List_Table;
    }



	function admin_header(){
		/**
        $page = ( isset($_GET['page']) ) ? esc_attr($_GET['page']) : false;
        if( 'cron_jobs' != $page )
            return;

        echo '<style type="text/css">';
		

		echo '.wp-list-table .column-actions { width: 20%; }';
		
		
		echo 'table.wp-list-table tbody tr:hover { background-color: rgba(255, 255, 0, 0.1) }';
		echo 'table.wp-list-table tbody tr.alternate:hover { background-color: rgba(255, 255, 0, 0.1) }';
		
        echo '</style>';
		**/
    }
	
}

?>