<?php
/**
 * Plugin Name:       The Events Calendar Extension: Export Events from Events +
 * Plugin URI:        https://wearezipline.com
 * Description:       Export Events from WPMU Dev Events +
 * Version:           1.0.0
 * Extension Class:   Tribe__Extension__Export_Events_Events_Plus
 * GitHub Plugin URI: https://github.com/sjregan/tribe-ext-export-events-plus-events
 * Author:            Zipline
 * Author URI:        https://wearezipline.com
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-export-events-plus-events
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if ( class_exists( 'Tribe__Extension' ) && ! class_exists( 'Tribe__Extension__Export_Events_Events_Plus' ) ) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Tribe__Extension__Export_Events_Events_Plus extends Tribe__Extension {

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			$this->add_required_plugin( 'Tribe__Events__Main' );
			$this->set_url( 'https://theeventscalendar.com/extensions/' );
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {

			// Loads the extension’s translated strings
			load_plugin_textdomain( 'tribe-ext-export-events-plus-events', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			add_action( 'admin_init', array( $this, 'add_admin_settings' ) );
			add_action( 'load-tribe_events_page_' . Tribe__Settings::$parent_slug, array(
				$this,
				'listen_for_export_button',
			), 10, 0 );
		}

		/**
		 * Add the Admin Settings
		 */
		public function add_admin_settings() {
			if ( ! class_exists( 'Tribe__Extension__Settings_Helper' ) ) {
				require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';
			}

			$setting_helper = new Tribe__Settings_Helper();

			$setting_helper->add_field( 'export-defaults-events-plus', array(
				'type' => 'html',
				'html' => '<h3 id="tribe-ext-export-events-plus-events">' . esc_html__( 'Migration Tools - Events +', 'tribe-ext-export-events-plus-events' ) . '</h3>',
			), 'imports', 'tribe_aggregator_disable', false );

			$setting_helper->add_field( 'events_plus_export_events', array(
				'type' => 'html',
				'html' => '<fieldset class="tribe-field tribe-field-html"><legend>' . esc_html__( 'Events', 'tribe-ext-export-events-plus-events' ) . '</legend><div class="tribe-field-wrap">' . $this->export_button( 'export_events', esc_html__( 'Export Events', 'tribe-ext-export-events-plus-events' ) ) . '<p class="tribe-field-indent description">' . esc_html__( 'Export your events from Events + and use the CSV file to import them to The Event Calendar. Before exporting your data, please make sure that Events + is enabled on your site. Before importing your data ensure Google Maps Pro by WPMU Dev is disabled.', 'tribe-ext-export-events-plus-events' )  . '</p></div></fieldset><div class="clear"></div>',
			), 'imports', 'tribe_aggregator_disable', false );

			// $setting_helper->add_field( 'events_plus_export_organizers', array(
			// 	'type' => 'html',
			// 	'html' => '<fieldset class="tribe-field tribe-field-html"><legend>' . esc_html__( 'Organizers', 'tribe-ext-export-events-plus-events' ) . '</legend><div class="tribe-field-wrap">' . $this->export_button( 'export_organizers', esc_html__( 'Export Organizers', 'tribe-ext-export-events-plus-events' ) ) . '<p class="tribe-field-indent description">' . esc_html__( 'Export your organizers from Events +.', 'tribe-ext-export-events-plus-events' ) . '</p></div></fieldset><div class="clear"></div>',
			// ), 'imports', 'tribe_aggregator_disable', false );

			$setting_helper->add_field( 'events_plus_export_venues', array(
				'type' => 'html',
				'html' => '<fieldset class="tribe-field tribe-field-html"><legend>' . esc_html__( 'Venues', 'tribe-ext-export-events-plus-events' ) . '</legend><div class="tribe-field-wrap">' . $this->export_button( 'export_venues', esc_html__( 'Export Venues', 'tribe-ext-export-events-plus-events' ) ) . '<p class="tribe-field-indent description">' . esc_html__( 'Export your venues from Events +.', 'tribe-ext-export-events-plus-events' ) . '</p></div></fieldset><div class="clear"></div>',
			), 'imports', 'tribe_aggregator_disable', false );
		}

		/**
		 * Add a button to trigger the CSV creation process
		 *
		 * @param string $text
		 *
		 * @return string
		 */
		public function export_button( $type, $text = '' ) {
			$text     = $text ? $text : __( 'Export Events', 'tribe-ext-export-events-plus-events' );
			$settings = Tribe__Settings::instance();

			// get the base settings page url
			$url = apply_filters( 'tribe_settings_url', add_query_arg( array(
				'post_type' => Tribe__Events__Main::POSTTYPE,
				'page'      => $settings->adminSlug,
				'tab'       => 'imports',
			), admin_url( 'edit.php' ) ) );

			$url = add_query_arg( array( $type => '1' ), $url );
			$url = wp_nonce_url( $url, $type );

			return sprintf( '<a href="%s" class="button">%s</a>', $url, $text );
		}

		/**
		 * If the button is clicked, start working
		 */
		public function listen_for_export_button() {

			/**
			 * Don't run the script if Events + is deactivated.
			 */
			if ( ! class_exists( 'Eab_EventsHub' ) ) {
				return;
			}

			if ( empty( $_REQUEST['export_events'] ) && empty( $_REQUEST['export_organizers'] ) && empty( $_REQUEST['export_venues'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_events' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_organizers' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_venues' ) ) {
				return;
			} elseif ( ! empty( $_REQUEST['export_events'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_events' ) ) {
				$this->events_csv_setup();
			// } elseif ( ! empty( $_REQUEST['export_organizers'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_organizers' ) ) {
			// 	$this->organizers_csv_setup();
			} elseif ( ! empty( $_REQUEST['export_venues'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_venues' ) ) {
				$this->venues_csv_setup();
			}
		}

		/**
		 * Get the event data from Events +
		 *
		 * @return array $event_data
		 */
		public function get_event_data() {

			$event_data = array();
			$event_tag  = array();
			$event_cat  = array();

			global $wpdb;

            /**
             * Events + Meta:
             * - incsub_event_paid - boolean
             * - incsub_event_fee - number
             * - eab_event_recurring - string 'weekly', 'week_count', 'dow',
             * - eab_event_recurrence_parts - php serialized array
             * - eab_event_recurrence_starts - unix time
             * - eab_event_recurrence_ends - unix time
             * - incsub_event_start - datetime
             * - incsub_event_no_start - boolean
             * - incsub_event_end - datetime
             * - incsub_event_no_end - boolean
             */
			$events = $wpdb->get_results( "
                SELECT p.ID,
                    p.post_title,
                    p.post_content,
                    pm_start_date.meta_value as start_date,
                    pm_no_start.meta_value as no_start_time,
                    pm_end_date.meta_value as end_date,
                    pm_no_end.meta_value as no_end_time,
                    pm_paid.meta_value as paid,
                    pm_fee.meta_value as fee,
                    pm_venue.meta_value as venue,
                    pm_recurring.meta_value as recurring,
                    pm_recurring_parts.meta_value as recurring_parts,
                    pm_recurring_starts.meta_value as recurring_starts,
                    pm_recurring_ends.meta_value as recurring_ends
                    
                FROM {$wpdb->posts} AS p
                
                LEFT JOIN {$wpdb->postmeta} AS pm_start_date
                    ON pm_start_date.post_id = p.ID
                    AND pm_start_date.meta_key = 'incsub_event_start'
                    
                LEFT JOIN {$wpdb->postmeta} AS pm_no_start
                    ON pm_no_start.post_id = p.ID
                    AND pm_no_start.meta_key = 'incsub_event_no_start'
                    
                LEFT JOIN {$wpdb->postmeta} AS pm_end_date
                    ON pm_end_date.post_id = p.ID
                    AND pm_end_date.meta_key = 'incsub_event_end'
                    
                LEFT JOIN {$wpdb->postmeta} AS pm_no_end
                    ON pm_no_end.post_id = p.ID
                    AND pm_no_end.meta_key = 'incsub_event_no_end'
                    
                LEFT JOIN {$wpdb->postmeta} AS pm_paid
                    ON pm_paid.post_id = p.ID
                    AND pm_paid.meta_key = 'incsub_event_paid'
                    
                LEFT JOIN {$wpdb->postmeta} AS pm_fee
                    ON pm_fee.post_id = p.ID
                    AND pm_fee.meta_key = 'incsub_event_fee'
                    
                LEFT JOIN {$wpdb->postmeta} AS pm_venue ON
                    pm_venue.post_id = p.ID
                    AND pm_venue.meta_key = 'incsub_event_venue'
                    
                LEFT JOIN {$wpdb->postmeta} AS pm_recurring ON
                    pm_recurring.post_id = p.ID
                    AND pm_recurring.meta_key = 'eab_event_recurring'
                    
                LEFT JOIN {$wpdb->postmeta} AS pm_recurring_parts ON
                    pm_recurring_parts.post_id = p.ID
                    AND pm_recurring_parts.meta_key = 'eab_event_recurrence_parts'
                    
                LEFT JOIN {$wpdb->postmeta} AS pm_recurring_starts ON
                    pm_recurring_starts.post_id = p.ID
                    AND pm_recurring_starts.meta_key = 'eab_event_recurrence_starts'
                    
                LEFT JOIN {$wpdb->postmeta} AS pm_recurring_ends ON
                    pm_recurring_ends.post_id = p.ID
                    AND pm_recurring_ends.meta_key = 'eab_event_recurrence_ends'
                    
                WHERE p.post_type = 'incsub_event'
                    AND p.post_status = 'publish'
                    
                GROUP BY p.ID
  			" );

			$events_taxonomy = $wpdb->get_results( "
				SELECT t1.object_id AS post_id,
					GROUP_CONCAT(DISTINCT IF(t3.taxonomy = 'post_tag', t2.name, NULL), '') AS post_tag,
					GROUP_CONCAT(DISTINCT IF(t3.taxonomy = 'eab_events_category', t2.name, NULL), '') AS post_category
				FROM {$wpdb->term_relationships} AS t1 
				INNER JOIN {$wpdb->terms} AS t2 ON t1.term_taxonomy_id = t2.term_id
				INNER JOIN {$wpdb->term_taxonomy} AS t3 ON t2.term_id = t3.term_id
				WHERE t3.taxonomy IN ('post_tag', 'eab_events_category')
				GROUP BY t1.object_id
			" );

			foreach ( $events_taxonomy as $event_taxonomy ) {
				$event_tag[ $event_taxonomy->post_id ] = $event_taxonomy->post_tag;
				$event_cat[ $event_taxonomy->post_id ] = $event_taxonomy->post_category;
			}

			foreach ( $events as $event ) {
                $start_date = '';
                $start_time = '';
                $end_date   = '';
                $end_time   = '';
                $venue      = '';

                if ( $event->start_date ) {
                    $start_date = substr( $event->start_date, 0, 10 );

                    if ( ! (int) $event->no_start_time ) {
                        $start_time = gmdate( 'H:i:s', strtotime( $event->start_date ) );
                    }
                }

                if ( $event->end_date ) {
                    $end_date = substr( $event->end_date, 0, 10 );

                    if ( ! (int) $event->no_end_time ) {
                        $end_time = gmdate( 'H:i:s', strtotime( $event->end_date ) );
                    }
                }

                $all_day = (int) $event->no_start_time && (int) $event->no_end_time;
                $recurring_summary = '';

                if ( $event->recurring ) {
                    if ( $event->recurring_starts ) {
                        $start_date = date( 'Y-m-d', $event->recurring_starts );

                        if ( $event->recurring_parts ) {
                            $parts = maybe_unserialize( $event->recurring_parts );

                            if ( is_array( $parts ) ) {
                                $recurring_month    = $parts['month'];
                                $recurring_day      = $parts['day'];
                                $recurring_weekday  = $parts['weekday'];
                                $recurring_week     = $parts['week'];
                                $recurring_time     = $parts['time'];
                                $recurring_duration = $parts['duration'];

                                if ( $recurring_time ) {
                                    $start_time = $recurring_time . ':00';

                                    if ( $recurring_duration ) {
                                        $ends = strtotime( $start_date . ' ' . $start_time ) +
                                                ( (int) $recurring_duration * 3600 );
                                        $end_date = date( 'Y-m-d', $ends );
                                        $end_time = date( 'H:i:s', $ends );
                                    }
                                }

                                $recurring_summary = sprintf(
                                    "Month: %s. Day: %s. Weekday: %s. Week: %s. Time: %s. Duration: %s.\nFrom %s to %s.",
                                    $recurring_month,
                                    $recurring_day,
                                    is_array( $recurring_weekday ) ? print_r( $recurring_weekday, 1 ) : $recurring_weekday,
                                    $recurring_week,
                                    $recurring_time,
                                    $recurring_duration,
                                    date( 'Y-m-d H:i:s', $event->recurring_starts ),
                                    date( 'Y-m-d H:i:s', $event->recurring_ends )
                                );
                            }
                        }
                    }
                }

                if ( $event->venue ) {
                    $venue = $this->normalise_venue_name( $event->venue );
                }

                /**
                 * CSV columns:
                 *
                 * 0  - Event Name
                 * 1  - Event Description
                 * 2  - Event Start Date
                 * 4  - Event End Date
                 * 3  - Event Start Time
                 * 5  - Event End Time
                 * 6  - All Day Event
                 * 7  - Event Venue Name
                 * 8  - Event Organizer Name
                 * 9  - Event Cost
                 * 10 - Event Website
                 * 11 - Event Tags
                 * 12 - Event Category
                 * 13 - Event ID
                 * 14 - Recurring
                 * 15 - Recurring Summary
                 */
				$row     = array();
				$row[0]  = $event->post_title;
				$row[1]  = $event->post_content;
				$row[2]  = $start_date;
				$row[3]  = $end_date;
				$row[4]  = $start_time;
				$row[5]  = $end_time;
				$row[6]  = $all_day;
				$row[7]  = $venue;
				$row[8]  = '';
				$row[9]  = $event->paid ? $event->fee : '';
				$row[10]  = '';
				$row[11] = ( isset( $event_tag[ $event->ID ] ) ? $event_tag[ $event->ID ] : '' );
				$row[12] = ( isset( $event_cat[ $event->ID ] ) ? $event_cat[ $event->ID ] : '' );
				$row[13] = $event->ID;
				$row[14] = $event->recurring;
                $row[15] = $recurring_summary;

				$event_data[] = $row;
			}

			return $event_data;
		}

		/**
		 * Get the organizer data from Events +
		 *
		 * @return array $organizer_data
		 */
		// public function get_organizer_data() {
		// 	$organizer_data = array();
        //
		// 	global $wpdb;
        //
		// 	/**
		// 	 * Get the organizers data from Events + and stores it in a variable.
		// 	 */
        //
		// 	$organizers = $wpdb->get_results( "
		// 		SELECT DISTINCT term_id, description
		// 		FROM {$wpdb->term_taxonomy}
		// 		WHERE taxonomy = 'mec_organizer'
		// 	" );
        //
		// 	foreach ( $organizers as $organizer ) {
		// 		$row    = array();
		// 		$row[0] = get_term( $organizer->term_id )->name;
		// 		$row[1] = $organizer->description;
		// 		$row[2] = get_term_meta( $organizer->term_id, 'email', true );
		// 		$row[3] = get_term_meta( $organizer->term_id, 'tel', true );
		// 		$row[4] = get_term_meta( $organizer->term_id, 'url', true );
		// 		$row[5] = get_term_meta( $organizer->term_id, 'thumbnail', true );
        //
		// 		$organizer_data[] = $row;
		// 	}
        //
		// 	return $organizer_data;
		// }

		/**
		 * Get the venue data from Events +
		 *
		 * @return array $venue_data
		 */
		public function get_venue_data() {
			$venue_data = array();
            $used_names = array();

			global $wpdb;
			/**
			 * Get the venues data from Events + and stores it in a variable.
			 */
            $venues = $wpdb->get_results( "
                SELECT DISTINCT pm_venue.meta_value AS venue_name
                FROM {$wpdb->posts} AS p
                INNER JOIN {$wpdb->postmeta} AS pm_venue ON
                    pm_venue.post_id = p.ID
                    AND pm_venue.meta_key = 'incsub_event_venue'
                WHERE p.post_type = 'incsub_event'
                GROUP BY p.ID
  			" );

			foreach ( $venues as $venue ) {
                $name = $this->normalise_venue_name( $venue->venue_name );

                if ( ! mb_strlen( $name ) ) {
                    continue;
                }

                if ( in_array( $name, $used_names ) ) {
                    continue;
                }

                /**
                 * CSV columns:
                 *
                 * 0 - Venue Name
                 * 1 - Venue Description
                 * 2 - Venue Address
                 * 3 - Venue Latitude
                 * 4 - Venue Longitude
                 * 5 - Venue Featured Image
                 */
                $row    = [];
                $row[0] = $name;
                $row[1] = '';
                $row[2] = '';
                $row[3] = '';
                $row[4] = '';
                $row[5] = '';

                $venue_data[] = $row;
                $used_names[] = $name;
            }

			return $venue_data;
		}

		/**
		 * Configure the CSV file for events
		 */
		public function events_csv_setup() {

			$data = $this->get_event_data();

			/**
			 * The name of the CSV file.
			 */
			$csv_file_name = 'tribe-ext-export-events-plus-events.csv';

			/**
			 * The name of the columns in the CSV file.
			 */
			$header = array(
				0  => 'Event Name',
				1  => 'Event Description',
				2  => 'Event Start Date',
				4  => 'Event End Date',
				3  => 'Event Start Time',
				5  => 'Event End Time',
				6  => 'All Day Event',
				7  => 'Event Venue Name',
				8  => 'Event Organizer Name',
				9  => 'Event Cost',
				10 => 'Event Website',
				11 => 'Event Tags',
				12 => 'Event Category',
                13 => '__id',
                14 => '__recurring',
                15 => '__recurring_summary',
			);

			/**
			 * Generates the CSV file.
			 */
			$this->generate_csv( $csv_file_name, $header, $data );
		}

		/**
		 * Configure the CSV file for organizers
		 */
		public function organizers_csv_setup() {

			$data = $this->get_organizer_data();

			/**
			 * The name of the CSV file.
			 */
			$csv_file_name = 'tribe-ext-export-events-plus-organizers.csv';

			/**
			 * The name of the columns in the CSV file.
			 */
			$header = array(
				0 => 'Organizer Name',
				1 => 'Organizer Description',
				2 => 'Organizer Email',
				3 => 'Organizer Phone',
				4 => 'Organizer Website',
				5 => 'Organizer Featured Image',
			);

			/**
			 * Generate the CSV file.
			 */
			$this->generate_csv( $csv_file_name, $header, $data );
		}

		/**
		 * Configure the CSV file for venues
		 */
		public function venues_csv_setup() {

			$data = $this->get_venue_data();

			/**
			 * The name of the CSV file.
			 */
			$csv_file_name = 'tribe-ext-export-events-plus-venues.csv';

			/**
			 * The name of the columns in the CSV file.
			 */
			$header = array(
				0 => 'Venue Name',
				1 => 'Venue Description',
				2 => 'Venue Address',
				3 => 'Venue Latitude',
				4 => 'Venue Longitude',
				5 => 'Venue Featured Image',
			);

			/**
			 * Generate the CSV file.
			 */
			$this->generate_csv( $csv_file_name, $header, $data );
		}

		/**
		 * Generate the CSV files.
		 *
		 * @param string $csv_file_name - The name of the CSV file to be created
		 * @param array  $header        - The name of the columns
		 * @param array  $data
		 */
		public function generate_csv( $csv_file_name, $header, $data ) {

			$fh = fopen( 'php://output', 'w' );

			/**
			 * Write the file header for correct encoding ( UTF8 )
             * Disabled as this causes 'Cannot modify header information - headers already sent' error - SJ
			 */
			// fprintf( $fh, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-type: text/csv' );
			header( "Content-Disposition: attachment; filename={$csv_file_name}" );
			header( 'Expires: 0' );
			header( 'Pragma: public' );

			fputcsv( $fh, $header );

			foreach ( $data as $data_row ) {
				fputcsv( $fh, $data_row );
			}

			fclose( $fh );
			die();
		}

        /**
         * Remove newlines, tabs and trim venue name.
         *
         * @param string $name
         * @return string
         */
		public function normalise_venue_name( $name ) {
		    return trim( str_replace( [ "\r\n", "\t", "\n" ], [ ' ', ' ', ' ' ], $name ) );
		}
	}
}
