<?php
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'Easy_AJAX_Handler' ) ){
	class Easy_AJAX_Handler{
		
		protected static $allowed_ajax_actions = array();
		protected static $handlers = array();
		
		public static function add_ajax( $actions, $handler ){
			$new_actions = self::prepare_variables( $actions, $handler );
			foreach($new_actions as $action => $data){
				if( $data['access'] == 'nopriv' ){
					add_action( 'wp_ajax_nopriv_' . $action, __CLASS__ . '::handle_ajax' );
				}
				add_action( 'wp_ajax_' . $action, __CLASS__ . '::handle_ajax' );
			}
		}
		
		public static function prepare_variables( $actions, $handler ){
			self::$handlers[] = $handler;
			end(self::$handlers);
			$handler_key = key(self::$handlers);
			
			$new_actions = array();
			if( !is_array($actions) ){
				$actions = array( $actions );
			}
			foreach($actions as $key => $action){
				$action_name = is_numeric($key) ? $action : $key;
				if( isset( self::$allowed_ajax_actions[ $action_name ] ) ){
					continue;
				}
				$new_actions[ $action_name ] = array(
					'access' => ( is_array($action) && isset($action['access']) ) ? $action['access'] : 'nopriv',
					'handler' => $handler_key,
					'callback' => ( is_array($action) && isset($action['callback']) ) ? $action['callback'] : $action_name
				);
			}
			self::$allowed_ajax_actions = array_merge( self::$allowed_ajax_actions, $new_actions );
			return $new_actions;
		}
		
		public static function handle_ajax(){
			$response = array();
			try{
				$action_name = $_POST['action'];
				if( !isset( self::$allowed_ajax_actions[ $action_name ] ) ){
					throw new Exception( __( 'Unknown action.', 'zulmamwe' ) );
				}
				
				$handler_key = self::$allowed_ajax_actions[ $action_name ]['handler'];
				$handler = self::$handlers[ $handler_key ];
				$callback = self::$allowed_ajax_actions[ $action_name ]['callback'];
				
				$callable = is_object($handler) ? array( $handler, $callback ) : "{$handler}::{$callback}";
				if( !is_callable($callable) ){
					throw new Exception( sprintf( __( 'Provided function is not callable. Action name: %s.', 'zulmamwe' ), $action_name ) );
				}
				
				$result = call_user_func( $callable );
				
				$response['result'] = 'success';
				if( is_array($result) ){
					$response = array_merge( $response, $result );
				}else{
					$response['message'] = $result;
				}
			}catch(Exception $e){
				$response['result'] = 'failure';
				$response['message'] = $e->getMessage();
			}
			
			echo json_encode($response);
			wp_die();
		}
		
	}
}