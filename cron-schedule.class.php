<?php

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

		
class Custom_Cron_Schedule_List extends WP_List_Table {
		
	function __construct(){
		 $this->prepare_items();	 
	}

	function display_admin_widget() {
		$this->build_admin_widget_table();
	} 
	

	function build_admin_widget_table(){
		$parent_args = array(
            'singular'  => 'schedule',    //singular name of the listed records
            'plural'    => 'schedules',   	//plural name of the listed records
            'ajax'      => false        //does this table support ajax?
    	);
		
		parent::__construct($parent_args);
		
	  	$this->display(); 					
	}

	
	function get_widget_data(){

		$schedules = Simple_Cron_Job_List::get_schedules();
		$schedule_data = array();
		$i = 0;
		
		foreach($schedules as $name => $schedule){
			$schedule_data[ $i ]['name'] 		= $name;
			$schedule_data[ $i ]['interval'] 	= $schedule['interval'];
			$schedule_data[ $i ]['display'] 	= $schedule['display'];
			$i++;
		}
		
		return $schedule_data;
	}


	function get_columns(){
		$columns = array(
			'name' 		=> 'Name',
			'display'   => 'Description',
			'interval'	=> 'Interval',
			'actions' 	=> 'Actions'
		);
	  	return $columns;
	}
	


	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $this->get_widget_data();
		

		$total_items = count($this->items);
		$this->found_data = $this->items;

		$this->set_pagination_args( array(
			'total_items' => $total_items,   
			'per_page'    => $total_items 
		) );
		$this->items = $this->found_data;

	}


	function column_default( $item, $column_name ) {
	  switch( $column_name ) { 
		case 'name':
			return $item[ $column_name ];
			break;
		case 'interval':
			return $item[ $column_name ];
			break;
		case 'display':
			return $item[ $column_name ];
			break;
  		case 'actions':
			if( !in_array($item['name'], array('hourly', 'daily', 'twicedaily'))){
				$form_data = "  <form method='post' style='display:inline;' onSubmit=\"return confirm('Are You Sure?')\">";
				$form_data .= "<input type='submit' value='Delete' class='button-primary'>";
				$form_data .= "<input type='hidden' name='action' value='delete_custom_cron_schedule'>";
				$form_data .= "<input type='hidden' name='delete_schedule_item' value='".$item['name']."'>";
				$form_data .= "</form>  ";
			}else{
				
				$form_data = "<input type='submit' value='Built In' disabled='disabled' class='button-primary'>";
			}
			return $form_data;
			break;
	  }
	}

}

?>