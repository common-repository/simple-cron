<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Custom_Cron_List_Table extends WP_List_Table{

    function __construct(){
	
        global $cron_table, $_wp_column_headers;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'user',     //singular name of the listed records
            'plural'    => 'users',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );

        $this->data_labels = $cron_table->data_labels;

    }
	
	
	 function get_columns(){
	
        global $status;
        $columns = array(
            'hook'         	=> __('Hook', 'simple-cron'),
			'schedule_name' => __('Schedule', 'simple-cron'),
            'time_str'      => __('Time', 'simple-cron'),
            'args'          => __('Arguments', 'simple-cron'),
			'actions'       => __('Actions', 'simple-cron'),
	
        );
        return $columns;
    }


    function get_sortable_columns(){

    }
	
	
	function extra_tablenav($which){
		if ( 'top' == $which ){
			$link = "<div class='alignleft actions'>";
			$link .= "<a  href='".get_option('siteurl')."/wp-admin/options-general.php?page=simple-cron/plugin-admin.php' >View Simple Cron Plugin Settings</a>";
			$link .= "</div>";
			echo $link;
		}	
	}
	
    function column_default($item, $column_name){
        $item = apply_filters('simple-cron-output-data', $item);

        //unset existing filter and pagination
        $args = wp_parse_args( parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY) );
        unset($args['filter']);
        unset($args['paged']);

		$this_page = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

        switch($column_name){
            
			case 'actions':
						
				$form_data = "  <form method='post' style='display:inline;' onSubmit=\"return confirm('Are You Sure?')\">";
				$form_data .= "<input type='submit' value='Delete' class='button-primary'>";
				$form_data .= "<input type='hidden' name='action' value='delete_cron_job'>";
				$form_data .= "<input type='hidden' name='delete_item_time' value='".$item['time']."'>";
				$form_data .= "<input type='hidden' name='delete_item_id' value='".$item['id']."'>";
				$form_data .= "<input type='hidden' name='delete_item_name' value='".$item['hook']."'>";
				$form_data .= "</form>  ";
				
				return $form_data;
 
            default:
                return $item[$column_name];
        }
    }




    function prepare_items(){
	
        $screen = get_current_screen();

        /**
         * setup pagination default number per page
         */
        $per_page_option = $screen->id . '_per_page';
        $per_page = get_option($per_page_option, 20);
        $per_page = ($per_page != false) ? $per_page : 20;


        /**
         * Define column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden_cols = get_user_option( 'manage' . $screen->id . 'columnshidden' );
        $hidden = ( $hidden_cols ) ? $hidden_cols : array();
        $sortable = $this->get_sortable_columns();


        /**
         * Build an array to be used by the class for column
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        $columns = get_column_headers( $screen );


        /**
         * Fetch the data for use in this table. 
         */
        $data = $this->items;


        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'time'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');


        /**
         * Figure out what page the user is currently looking at. 
         */
        $current_page = $this->get_pagenum();


        /**
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = count($data);


        /**
         * manual pagination
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);



        /**
         * Add our *sorted* data to the items property
         */
        $this->items = $data;


        /**
         * Register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //calculate the total number of items
            'per_page'    => $per_page,                     //determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //calculate the total number of pages
        ) );

    }

}

?>