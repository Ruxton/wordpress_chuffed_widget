<?php
/*
Plugin Name:  Chuffed Donation Widget
Plugin URI:   http://ignite.digitalignition.net/articlesexamples/chuffed-donation-widget
Description:  Easily add a widget for your chuffed campaign
Author:       Greg Tangey
Author URI:   http://ignite.digitalignition.net/
Version:      0.1
*/

/*  Copyright 2015  Greg Tangey  (email : greg@digitalignition.net)

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

class ChuffedWidget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'chuffed_widget', // Base ID
			__( 'Chuffed Campaign', 'text_domain' ), // Name
			array( 'description' => __( 'A chuffed.org campaign widget', 'text_domain' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
    $campaign_id = $instance['campaign_id'];

    if( ! empty($campaign_id) ) {
      $transName = "chuffed-widget-$campaign_id";
      $cacheTime = 30; // minutes
      // delete_transient($transName);
      if(false === ($chuffedData = get_transient($transName) ) ){
        $json = wp_remote_get("http://chuffed.org/api/v1/campaign/$campaign_id");

        // Check the response code
      	$response_code    = wp_remote_retrieve_response_code( $json );
      	$response_message = wp_remote_retrieve_response_message( $json );


      	if ( 200 != $response_code && ! empty( $response_message ) ) {
      		$err = $response_message;
      	} elseif ( 200 != $response_code ) {
      		$err = "Uknown err";
      	} else {
      		$chuffedData = wp_remote_retrieve_body( $json );
        }

        $chuffedData = json_decode($chuffedData, true);
        set_transient($transName, $chuffedData, 60 * $cacheTime);
      }
      $targetAmount = intval($chuffedData['data']['camp_amount']);
      $collectedAmount = intval($chuffedData['data']['camp_amount_collected']);
      $percWidth = intval(($collectedAmount/$targetAmount)*100);
      $slug = $chuffedData['data']['slug'];
      $title = $chuffedData['data']['title'];

      echo $args['before_widget'];
      if ( ! empty( $instance['title'] ) ) {
        echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
      }
      ?>
      <a style="text-decoration: none" href="https://chuffed.org/project/<?php echo $slug; ?>">
        <div style="position:relative;">
            <h1><?php echo $title; ?></h1>
            <div style="width: 100%;height:15px;background-color: #F9F9F9 !important;">
                <div style="width: <?php echo $percWidth;?>%; height: 15px;background-color: #28ab60 !important;"></div>
            </div>
            <h2 style="font-size: 50px;margin-bottom: 0;padding-bottom: 0;line-height: 56px;">
                $<span><?php echo $collectedAmount; ?></span>
            </h2>
            <p style="color:#9b9b9b;"><?php echo __("Raised of", "text_domain"); ?>
                $<span><?php echo $targetAmount; ?></span>
            </p>
        </div>
      </a>
      <?php

      echo $args['after_widget'];
    }
    else {
      echo __("A Campaign ID is not set in the widgets setting", "text_domain");
    }
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
    $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Chuffed Campaign', 'text_domain' );
    $campaign_id = ! empty( $instance['campaign_id'] ) ? $instance['campaign_id'] : '';

		?>
		<p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		<label for="<?php echo $this->get_field_id( 'campaign_id' ); ?>"><?php _e( 'Campaign ID:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'campaign_id' ); ?>" name="<?php echo $this->get_field_name( 'campaign_id' ); ?>" type="text" value="<?php echo esc_attr( $campaign_id ); ?>">
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['campaign_id'] = ( ! empty( $new_instance['campaign_id'] ) ) ? strip_tags( $new_instance['campaign_id'] ) : '';
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

}

function register_chuffed_widget() {
    register_widget( 'ChuffedWidget' );
}
add_action( 'widgets_init', 'register_chuffed_widget' );
