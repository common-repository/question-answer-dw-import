<?php
/*
* @Author 		pickplugins
* Copyright: 	pickplugins.com
*/

if ( ! defined('ABSPATH')) exit;  // if direct access 
	

	function qa_dw2qa_action_admin_menus(){
		add_submenu_page( 'edit.php?post_type=question', __( 'DW Migration', QA_DWQA_TEXTDOMAIN ), __( 'DW Migration', QA_DWQA_TEXTDOMAIN ), 'manage_options', 'dw_migration', 'qa_dw2qa__migration' );	
	}
	add_action('qa_action_admin_menus','qa_dw2qa_action_admin_menus');	
	
	function qa_dw2qa__migration(){
		include( QA_DWQA_PLUGIN_DIR. 'includes/menus/migration.php' );
	}
	
	function qa_dwqa_ajax_migration() {
		
		$paged 			= (int)sanitize_text_field($_POST['paged']);
		$ppp 			= (int)sanitize_text_field($_POST['ppp']);
		
		
		$delete_dw_question	= get_option( 'qa_options_dw2qa_delete_question', 'no' );
		$delete_dw_answer 	= get_option( 'qa_options_dw2qa_delete_answer', 'no' );
		
		$wp_query = new WP_Query(
			array (
				'post_type' => 'dwqa-question',
				'post_status' => 'publish',
				'orderby' => 'Date',
				'order' => 'DESC',
				'posts_per_page' => $ppp,
				'paged' => $paged,
		) );
				
		if ( $wp_query->have_posts() ) : while ( $wp_query->have_posts() ) : $wp_query->the_post();	

			$question_post = array(
				'post_type'    	=> 'question',	
				'post_title'    => get_the_title(),
				'post_content'  => get_the_content(),
				'post_status'   => get_post_status(get_the_ID()),
				'post_author'   => get_the_author_meta('ID'),
				'post_date'   	=> get_the_date('Y-m-d H:i:s'),	  
			);
		 
			$main_questio_ID 	= get_the_ID();
			$question_id 		= wp_insert_post( $question_post );
			




			$_dwqa_views = get_post_meta( $main_questio_ID, '_dwqa_views', true );
			update_post_meta( $question_id, 'qa_view_count', $_dwqa_views );
			
			$_dwqa_status = get_post_meta( $main_questio_ID, '_dwqa_status', true );
			
			if( $_dwqa_status == 'close' || $_dwqa_status == 'resolved' ) {
				
				update_post_meta( $question_id, 'qa_question_status', 'solved' );
			
			} else {
				
				update_post_meta( $question_id, 'qa_question_status', 'processing' );
			}
			
			$_dwqa_best_answer = get_post_meta( $main_questio_ID, '_dwqa_best_answer', true );
			
			// updating Vote Count
			$_dwqa_votes_log = get_post_meta( get_the_ID(), '_dwqa_votes_log', true );
			$_dwqa_votes_log = maybe_unserialize($_dwqa_votes_log);
			//$_dwqa_votes_log = unserialize($_dwqa_votes_log);
			
				$users = array();

				foreach($_dwqa_votes_log as $user_id=>$log){
					if($log==1){
						$users[$user_id] = array('type'=>'up');
						$users_vote[$user_id] = 1;
						}
					else{
						$users[$user_id] = array('type'=>'down');
						$users_vote[$user_id] = -1;
						}
					
					
					}
					
				$total_reviews = 0;
				foreach($users_vote as $vote){
					
					$total_reviews+=$vote;
					
					}
					
				//$total_reviews = count($users);
				update_post_meta( $question_id, 'qa_answer_review', array( 'reviews' => $total_reviews, 'users' => $users ) );
			
			
			$term_list = wp_get_post_terms( $main_questio_ID, 'dwqa-question_tag', array("fields" => "all"));
			$term_name = array();
			foreach($term_list as $terms){
				$term_name[] = $terms->name;
			}
			
			wp_set_post_terms( $question_id, $term_name, 'question_tags' );
		
					
			$cat_list = wp_get_post_terms( $main_questio_ID, 'dwqa-question_category', array("fields" => "all"));
			$cats = array();
			foreach($cat_list as $cat){
				$cats[] = $cat->name;
			}
			wp_set_object_terms( $question_id, $cats, 'question_cat' );
			
		
			
			
			$question_comments = get_comments('post_id='.$main_questio_ID);
			foreach($question_comments as $comment){
			
				$data = array(
					'comment_post_ID' 		=> $question_id,
					'comment_author' 		=> $comment->comment_author,
					'comment_author_email' 	=> $comment->comment_author_email,
					'comment_author_url' 	=> $comment->comment_author_url,
					'comment_content' 		=> $comment->comment_content,
					'comment_type' 			=> $comment->comment_type,
					'comment_parent' 		=> $comment->comment_parent,
					'user_id' 				=> $comment->user_id,
					'comment_author_IP' 	=> $comment->comment_author_IP,
					'comment_agent' 		=> $comment->comment_agent,
					'comment_date' 			=> $comment->comment_date,
					'comment_approved' 		=> $comment->comment_approved,
				);		

				wp_insert_comment($data);
				if( $delete_dw_question == 'yes' ) {
					wp_delete_comment($comment->comment_ID);
				}
				
			}
			
			
			
			$wp_query_answer = new WP_Query(
				array (
					'post_type' 		=> 'dwqa-answer',
					'post_status' 		=> 'publish',
					'posts_per_page' 	=> -1,
					'meta_query' => array(
						array(
							'key'     => '_question',
							'value'   => $main_questio_ID,
							'compare' => '=',
						),
					),				
				) 
			);
			
			update_option('demo_option', $wp_query_answer->found_posts );
			
			
			if ( $wp_query_answer->have_posts() ) : while ( $wp_query_answer->have_posts() ) : $wp_query_answer->the_post();

				$answer_post = array(
					'post_type'    => 'answer',	
					'post_title'    => get_the_title(),
					'post_content'  => get_the_content(),
					'post_status'   => get_post_status(get_the_ID()),
					'post_author'   => get_the_author_meta('ID'),
					'post_date'   => get_the_date('Y-m-d H:i:s'),	  
				);
				
				update_option('demo_option', 1 );
				
				$answer_id = wp_insert_post( $answer_post, true  );
				update_post_meta($answer_id, 'qa_answer_question_id', $question_id);
				
				
				if( $_dwqa_best_answer == get_the_ID() ) {
					
					update_post_meta( $question_id, 'qa_meta_best_answer', $answer_id );
				}
				
				// updating Vote Count
				//$_dwqa_votes = get_post_meta( get_the_ID(), '_dwqa_votes_log', true );
				//update_post_meta( $answer_id, 'qa_answer_review', array( 'reviews' => $_dwqa_votes ) );
				
				// updating Vote Count
				$_dwqa_votes_log = get_post_meta( get_the_ID(), '_dwqa_votes_log', true );
				$_dwqa_votes_log = maybe_unserialize($_dwqa_votes_log);
				//$_dwqa_votes_log = unserialize($_dwqa_votes_log);
				
				$users = array();
				
				
				
				foreach($_dwqa_votes_log as $user_id=>$log){
					if($log==1){
						$users[$user_id] = array('type'=>'up');
						$users_vote[$user_id] = 1;
						}
					else{
						$users[$user_id] = array('type'=>'down');
						$users_vote[$user_id] = -1;
						}
					
					
					}
					
				$total_reviews = 0;
				foreach($users_vote as $vote){
					
					$total_reviews+=$vote;
					
					}
					
					
				//$total_reviews = count($users);
				update_post_meta( $answer_id, 'qa_answer_review', array( 'reviews' => $total_reviews, 'users' => $users ) );
				
				
				
				
				
				
				
				$comments = get_comments('post_id='.get_the_ID());
				foreach($comments as $comment){
					$data = array(
						'comment_post_ID' => $answer_id,
						'comment_author' => $comment->comment_author,
						'comment_author_email' => $comment->comment_author_email,
						'comment_author_url' => $comment->comment_author_url,
						'comment_content' => $comment->comment_content,
						'comment_type' => $comment->comment_type,
						'comment_parent' => $comment->comment_parent,
						'user_id' => $comment->user_id,
						'comment_author_IP' => $comment->comment_author_IP,
						'comment_agent' => $comment->comment_agent,
						'comment_date' => $comment->comment_date,
						'comment_approved' => $comment->comment_approved,
					);		
				
					wp_insert_comment($data);
					
					if( $delete_dw_answer == 'yes' ) {
						wp_delete_comment($comment->comment_ID);
					}
				}

				if( $delete_dw_answer == 'yes' ) {
					wp_delete_post( get_the_ID(), true );
				}
				

			endwhile; endif;
			
			if( $delete_dw_question == 'yes' ) {
				wp_delete_post( $main_questio_ID, true );
			}
			
		endwhile; wp_reset_query();endif;
		
		echo $html;
		die();
	}

	add_action('wp_ajax_qa_dwqa_ajax_migration', 'qa_dwqa_ajax_migration');
	add_action('wp_ajax_nopriv_qa_dwqa_ajax_migration', 'qa_dwqa_ajax_migration');
	
	


	function qa_filter_settings_options_function_dw( $options ){
		
		$section_options = array(
			
			'qa_options_dw2qa_delete_question'=>array(
				'css_class'=>'qa_options_dw2qa_delete_question',					
				'title'=>__('Delete Orginal DW Questions?', QA_DWQA_TEXTDOMAIN),
				'option_details'=>__('Do you want to delete all orginal questions from of DW Database.<br>Default: No.', QA_DWQA_TEXTDOMAIN),					
				'input_type'=>'select',
				'input_values'=> 'no',
				'input_args'=> array( 'no'=>__('No', QA_DWQA_TEXTDOMAIN), 'yes'=>__('Yes', QA_DWQA_TEXTDOMAIN),),
			),
			
			
			
			
			'qa_options_dw2qa_delete_answer'=>array(
				'css_class'=>'qa_options_dw2qa_delete_answer',					
				'title'=>__('Delete Orginal DW Answers?', QA_DWQA_TEXTDOMAIN),
				'option_details'=>__('Do you want to delete all orginal answers from of DW Database.<br>Default: No.', QA_DWQA_TEXTDOMAIN),					
				'input_type'=>'select',
				'input_values'=> 'no',
				'input_args'=> array( 'no'=>__('No', QA_DWQA_TEXTDOMAIN), 'yes'=>__('Yes', QA_DWQA_TEXTDOMAIN),),
			),
			
		
			
			
			

		);
		
		
		$options['<i class="fa fa-upload" aria-hidden="true"></i> '.__('Import DW', QA_DWQA_TEXTDOMAIN)] = apply_filters( 'qa_settings_section_import_dwqa', $section_options );
		
		return $options;
	}
	
	
	add_filter( 'qa_filter_settings_options', 'qa_filter_settings_options_function_dw',10,1 );

	
	
	
	
	
	
	
	
	
	
	
	
	
	function qa_dwimport_admin_notices_main_plugin_missed(){

			
			$active_plugins = get_option('active_plugins');
			
			
			$html= '';

			if(in_array( 'question-answer/question-answer.php', (array) $active_plugins )){

				}
			else{
					$admin_url = get_admin_url();
					
					$html.= '<div class="update-nag">';
					$html.= sprintf(__('Please install & activate <a href="%splugin-install.php?tab=search&s=question+answer"><b>Question Answer</b></a> plugin first. plugin link in <a href="%s">wordpress.org</a> '), $admin_url, 'https://wordpress.org/plugins/question-answer/');
					$html.= '</div>';
				
				}	

			echo $html;
		}
	
	add_action('admin_notices', 'qa_dwimport_admin_notices_main_plugin_missed');
	
	
	