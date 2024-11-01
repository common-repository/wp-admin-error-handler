<?php
/*
Plugin Name: WP Admin Error Handler
Description: Taming WordPress and PHP errors and displaying them properly.
Version: 0.1
Author: Gilbert Pellegrom
Author URI: http://dev7studios.com
*/

class WPAdminErrorHandler {

    function __construct() 
    {	
    	if( !isset( $_SESSION ) ) session_start();
        set_error_handler(array(&$this, 'error_handler'));

        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('admin_bar_menu', array(&$this, 'admin_bar'), 999 );
	}

	function error_handler( $errno, $errstr, $errfile, $errline )
	{
	    if( !(error_reporting() & $errno) ) return;

	    $errors = array();

	    switch( $errno ){
		    case E_USER_ERROR:
		        echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
		        echo "  Fatal error on line $errline in file $errfile";
		        echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
		        exit(1);
		        break;

		    case E_USER_WARNING:
		        $errors[] = array(
		        	'type' => 'warning', 
		        	'number' => $errno, 
		        	'string' => $errstr,
		        	'file' => $errfile,
		        	'line' => $errline
		        );
		        break;

		    case E_USER_NOTICE:
		        $errors[] = array(
		        	'type' => 'notice', 
		        	'number' => $errno, 
		        	'string' => $errstr,
		        	'file' => $errfile,
		        	'line' => $errline
		        );
		        break;

		    default:
		        $errors[] = array(
		        	'type' => 'unknown', 
		        	'number' => $errno, 
		        	'string' => $errstr,
		        	'file' => $errfile,
		        	'line' => $errline
		        );
		        break;
	    }

	    if( !(isset($_GET['page']) && $_GET['page'] == 'wp_aeh_errors') ){
	    	$_SESSION['wp_aeh_errors_page'] = $this->get_current_url();
	    	$_SESSION['wp_aeh_errors'] = $errors;
	    }

	    /* Don't execute PHP internal error handler */
	    return true;
	}

	function admin_menu()
	{
		$errors = array();
		if(isset($_SESSION['wp_aeh_errors']) && is_array($_SESSION['wp_aeh_errors'])) $errors = $_SESSION['wp_aeh_errors'];
		$title = count($errors) .' '. ((count($errors) == 1) ? 'Error' : 'Errors');

		add_submenu_page( 'tools.php', $title, $title, 'manage_options', 'wp_aeh_errors', array(&$this, 'errors_page') ); 
	}

	function errors_page()
	{
		?>
		<div class="wrap">
			<div id="icon-tools" class="icon32"></div>
			<h2>Errors</h2>
			<p><?php
			if(isset($_SESSION['wp_aeh_errors_page']) && $_SESSION['wp_aeh_errors_page']){ 
				echo 'Displaying errors for <a href="'. $_SESSION['wp_aeh_errors_page'] .'">'. $_SESSION['wp_aeh_errors_page'] .'</a>';
			}
			$errors = array();
			if(isset($_SESSION['wp_aeh_errors']) && is_array($_SESSION['wp_aeh_errors'])) $errors = $_SESSION['wp_aeh_errors'];
			?></p>
			<table class="wp-list-table widefat plugins" cellspacing="0">
				<thead>
					<tr>
						<th scope="col" class="manage-column">Type</th>
						<th scope="col" class="manage-column">Description</th>
						<th scope="col" class="manage-column">Location</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th scope="col" class="manage-column">Type</th>
						<th scope="col" class="manage-column">Description</th>
						<th scope="col" class="manage-column">Location</th>
					</tr>
				</tfoot>
				<tbody>
				<?php $i = 0; foreach($errors as $error){ ?>
					<tr id="error<?php echo $i; ?>">
						<td><strong><?php echo ucwords($error['type']); ?></strong></td>
						<td><?php echo $error['string']; ?></td>
						<td><?php if($error['file']) echo '<code>'. $error['file'] .'</code> on line <strong>'. $error['line'] .'</strong>'; ?></td>
					</tr>
				<?php $i++; }
				if($i == 0){ ?>
					<tr><td colspan="3"><em>No Errors</em></td></tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	function admin_bar( $wp_admin_bar )
	{
		$errors = array();
		if(isset($_SESSION['wp_aeh_errors']) && is_array($_SESSION['wp_aeh_errors'])) $errors = $_SESSION['wp_aeh_errors'];

		if(!empty($errors)){
			$args = array(
				'id' => 'wp_aeh_errors',
				'title' => count($errors) .' '. ((count($errors) == 1) ? 'Error' : 'Errors'),
				'href' => admin_url('/tools.php?page=wp_aeh_errors')
			);
			$wp_admin_bar->add_node($args);

			$i = 0;
			foreach($errors as $error){
				$args = array(
					'id' => 'wp_aeh_error_'. $i,
					'title' => ucwords($error['type']) .': '. strip_tags($error['string']),
					'href' => admin_url('/tools.php?page=wp_aeh_errors#error'. $i),
					'parent' => 'wp_aeh_errors'
				);
				$wp_admin_bar->add_node($args);
				$i++;
			}
		}
	}

	private function get_current_url()
	{
		$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		if ($_SERVER["SERVER_PORT"] != "80")
		{
		    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} 
		else 
		{
		    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

}
new WPAdminErrorHandler();

?>