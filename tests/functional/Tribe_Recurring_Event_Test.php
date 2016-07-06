<?php


/**
 * Class Tribe_Recurring_Event_Test
 *
 * @group recurrence
 */
class Tribe_Recurring_Event_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * test_is_recurring
	 * A test that creates a Recurring event and checks to see if it is recurring
	 */
	public function test_is_recurring() {
		$start_date = date_i18n( 'Y-m-d' );
		$post_id    = Tribe__Events__API::createEvent( array(
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 5,
					),
				),// end rules array
			)
		) );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );

		//Checks that the event created is recurring
		$this->assertTrue( tribe_is_recurring_event( $post_id ) );

		// recur one time
		$post_id = Tribe__Events__API::createEvent( array(
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 1,
					),
				),// end rules array
			)
		) );
		//Checks that the event is recurring
		$this->assertTrue( tribe_is_recurring_event( $post_id ) );

	}//ends test_is_recurring

	/**
	 * test_is_not_recurring
	 * A test that creates a non-recurring event and checks to see that it is not recurring
	 */
	public function test_is_not_recurring() {
		$start_date = date_i18n( 'Y-m-d', time() );
		// An event that does not recur
		$post_id = Tribe__Events__API::createEvent( array(
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
		) );
		//Checks that the event created is not recurring
		$this->assertFalse( tribe_is_recurring_event( $post_id ), 'Checks that event is not recurring' );
	}

	/**
	 * test_get_recurrence_start_dates
	 * A test that creates a recurring event and checks the start date
	 */
	public function test_get_recurrence_start_dates() {
		$start_date = date_i18n( 'Y-m-d', strtotime( 'today' ) );
		// Event that recurs 5 times
		$post_data = array(
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 5,
					),
				),// end rules array
			)
		);
		$post_id   = Tribe__Events__API::createEvent( $post_data );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_batch( $post_id );

		//checks that the event is recurring
		$this->assertTrue( tribe_is_recurring_event( $post_id ) );

		$dates    = tribe_get_recurrence_start_dates( $post_id );
		$expected = array(
			$start_date . ' 16:00:00',
			date_i18n( 'Y-m-d', strtotime( '+1 week' ) ) . ' 16:00:00',
			date_i18n( 'Y-m-d', strtotime( '+2 weeks' ) ) . ' 16:00:00',
			date_i18n( 'Y-m-d', strtotime( '+3 weeks' ) ) . ' 16:00:00',
			date_i18n( 'Y-m-d', strtotime( '+4 weeks' ) ) . ' 16:00:00',
		);
		//checks that the expected recurring dates are what they say to be
		$this->assertEqualSets( $expected, $dates );
	}//end test_get_recurrence_start_dates

	/**
	 * test_update_event
	 */
	public function test_update_event() {
		$start_date = date_i18n( 'Y-m-d', strtotime( 'today' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 5,
					),
				),// end rules array
			)
		);
		$post_id    = Tribe__Events__API::createEvent( $event_args );

		// The plugin's own queue processor may have been exhausted by other tests - so let's
		// create a fresh processor and use that to ensure all expected children are generated
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_batch( $post_id );

		$original_dates = tribe_get_recurrence_start_dates( $post_id );
		$this->assertCount( 5, $original_dates, 'Checking that 5 events were created' );

		Tribe__Events__API::updateEvent( $post_id, $event_args );

		$updated_dates = tribe_get_recurrence_start_dates( $post_id );

		//Checks that the original dates are the same as the updated dates
		$this->assertEqualSets( $original_dates, $updated_dates, 'Checks that original dates are the same as the updated' );

		$expected = array(
			$start_date . ' 16:00:00',
			date_i18n( 'Y-m-d', strtotime( '+1 week' ) ) . ' 16:00:00',
			date_i18n( 'Y-m-d', strtotime( '+2 weeks' ) ) . ' 16:00:00',
			date_i18n( 'Y-m-d', strtotime( '+3 weeks' ) ) . ' 16:00:00',
			date_i18n( 'Y-m-d', strtotime( '+4 weeks' ) ) . ' 16:00:00',
		);
		//checks that the expected dates are the same as the updated events
		$this->assertEqualSets( $expected, $updated_dates, 'Checks that the updated dates are the same to the expected' );
	}//end test_update_event

	/**
	 * test_nondestructive_update_event
	 * creates event that recurs 5 times and updates it and checks that the updated and original are the same
	 */
	public function test_nondestructive_update_event() {
		$start_date = date_i18n( 'Y-m-d', strtotime( 'today' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'post_status'      => 'publish',
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 5,
					),
				),// end rules array
			)
		);
		$post_id    = Tribe__Events__API::createEvent( $event_args );
		// give SQL the time
		sleep( 1 );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );

		$original_children = get_posts( array(
			'post_type'      => Tribe__Events__Main::POSTTYPE,
			'post_parent'    => $post_id,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 25,
		) );
		//checks to see the amount of post created were 4 excluding the parent
		$this->assertCount( 4, $original_children, 'Checks the children is 4 which excludes the parent' );

		//updates Event using the original event
		Tribe__Events__API::updateEvent( $post_id, $event_args );

		$updated_children = get_posts( array(
			'post_type'      => Tribe__Events__Main::POSTTYPE,
			'post_parent'    => $post_id,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 25,
		) );

		//checks that the updated children have 4 events which excludes the parent
		$this->assertCount( 4, $updated_children, 'Checks the updated children is 4 which excludes the parent' );

		//checks that the updated and original children have the same events
		$this->assertEqualSets( $original_children, $updated_children, 'Checks original children are the same as the updated' );
	}

	/**
	 * test_update_event_with_deleted_instances
	 * creates event that recurs 5 times, deletes an instance, and confirms that it is excluded. Then it updates the event based off the original and confirms that it does not
	 * contain the excluded date in it.
	 */
	public function test_update_event_with_deleted_instances() {
		$start_date = date_i18n( 'Y-m-d', strtotime( 'today' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'post_status'      => 'publish',
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 5,
					),
				),// end rules array
			)
		);
		$post_id    = Tribe__Events__API::createEvent( $event_args );

		// give SQL the time to write
		sleep( 1 );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );

		$original_children = get_posts( array(
			'post_type'      => Tribe__Events__Main::POSTTYPE,
			'post_parent'    => $post_id,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 25,
			'meta_key'       => '_EventStartDate',
			'orderby'        => '_EventStartDate',
			'order'          => 'ASC',
		) );
		//Checks that the original children are not empty
		$this->assertNotEmpty( $original_children, 'Checks that the original children are not empty' );

		//Checks that the original children has 4 events
		$this->assertCount( 4, $original_children, 'Checks that there are 4 children before the deletion' );

		$deleted_child_event_id = $original_children[2];
		wp_delete_post( $deleted_child_event_id, true );

		$recurrence_meta = get_post_meta( $post_id, '_EventRecurrence', true );

		$this->assertArrayHasKey( 'date', $recurrence_meta['exclusions'][0]['custom'], 'Recurrence meta is missing the date exclusion entry' );

		$excluded_dates = $recurrence_meta['exclusions'][0]['custom']['date'];
		$this->assertCount( 1, $excluded_dates, 'Recurrence meta should contain one date exclusion only' );

		$expected_deleted_date = date( 'Y-m-d', strtotime( '+3 weeks' ) ) . ' 16:00:00';
		$this->assertEquals( $expected_deleted_date, reset( $excluded_dates ) );

		// let's update the event with the updated recurrence meta
		$updated_events_args               = $event_args;
		$updated_events_args['recurrence'] = $recurrence_meta;
		Tribe__Events__API::updateEvent( $post_id, $updated_events_args );

		$updated_children = get_posts( array(
			'post_type'      => Tribe__Events__Main::POSTTYPE,
			'post_parent'    => $post_id,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 25,
			'meta_key'       => '_EventStartDate',
			'orderby'        => '_EventStartDate',
			'order'          => 'ASC',
		) );

		//Checks that the updated children is 3 because of the original children that had an exclusion
		$this->assertCount( 3, $updated_children, 'Expected children events count to be 3' );

		//Checks that the excluded date from the original children is not in the updated children
		$this->assertNotContains( $deleted_child_event_id, $updated_children, 'Expected deleted child event post id not to be among updated children' );
	}//ends test_update_event_with_deleted_instances

	/**
	 * test_changing_start_date
	 * creates event that recurs 5 times and updates it and checks that the updated and original are the same. Then it creates a new day that the start time is and updates the
	 * events and checks that the new dates are all good.
	 */
	public function test_changing_start_date() {
		$start_date = date( 'Y-m-d', strtotime( 'today' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'post_status'      => 'publish',
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Day',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 5,
					),
				),// end rules array
			)
		);
		$post_id    = Tribe__Events__API::createEvent( $event_args );

		// process the queue, otherwise all the children won't get created
		Tribe__Events__Pro__Main::instance()->queue_processor->process_queue();

		$original_dates = tribe_get_recurrence_start_dates( $post_id );

		//Checks that the original dates has 5 dates in its array
		$this->assertCount( 5, $original_dates, 'Checks that the original dates have 5 dates' );

		$expected_dates = array_map( function ( $n ) {
			return date( 'Y-m-d', strtotime( '+' . $n . ' days' ) ) . ' 16:00:00';
		}, range( 0, 4 ) );

		//Checks that the expected dates are equal to the original dates
		$this->assertEqualSets( $expected_dates, $original_dates, 'Checks that the expected dates are the same as the original dates' );

		$new_date                     = date( 'Y-m-d', strtotime( '+1 week' ) );
		$event_args['EventStartDate'] = $new_date;
		$event_args['EventEndDate']   = $new_date;
		
		Tribe__Events__API::updateEvent( $post_id, $event_args );

		// process the queue, otherwise all the children won't get created
		Tribe__Events__Pro__Main::instance()->queue_processor->process_queue();
		
		$new_dates = tribe_get_recurrence_start_dates( $post_id );

		//Checks that the new dates that were created with the updated event is 5
		$this->assertCount( 5, $new_dates, 'Checks that the new dates are 5' );

		//Checks that the original dates are not the same as the new dates
		$this->assertNotContains( $original_dates, $new_dates, 'Checks that the new dates and the original dates are not the same' );

		$expected_new_dates = array_map( function ( $n ) {
			return date( 'Y-m-d', strtotime( '+1 week +' . $n . ' days' ) ) . ' 16:00:00';
		}, range( 0, 4 ) );

		//Checks that the expected new dates are equal to the new dates
		$this->assertEqualSets( $expected_new_dates, $new_dates, 'Checks that the expected new dates are the same as the new dates' );
	}//ends test_changing_start_date

	/**
	 * test_terms_on_update
	 * creates event that recurs 5 times and updates it and checks that the updated and original are the same. Then compares that the terms in both are the same.
	 */
	public function test_terms_on_update() {
		$tags       = array(
			$this->factory->tag->create_object( array( 'name' => 'test tag a' ) ),
			$this->factory->tag->create_object( array( 'name' => 'test tag b' ) ),
		);
		$start_date = date( 'Y-m-d', strtotime( 'today' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'post_status'      => 'publish',
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'tags_input'       => array( $tags[0] ),
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 5,
					),
				),// end rules array
			)
		);
		$post_id    = Tribe__Events__API::createEvent( $event_args );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );
		sleep( 1 );

		$event_args['tags_input'] = array( $tags[1] );

		Tribe__Events__API::updateEvent( $post_id, $event_args );

		// Create another fresh queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );
		sleep( 1 );

		$children = get_posts( array(
			'post_type'   => Tribe__Events__Main::POSTTYPE,
			'post_parent' => $post_id,
			'post_status' => 'publish',
			'fields'      => 'ids',
		) );

		//Checks that each of the child terms are the same as the updated child terms
		foreach ( $children as $child_id ) {
			$child_terms = wp_get_object_terms( $child_id, 'post_tag', array( 'fields' => 'ids' ) );
			$this->assertEqualSets( array( $tags[1] ), $child_terms, 'Checks that the child term is the same as the updated term' );
		}
	}//ends test_terms_on_update

	/**
	 * test_venue_organizer_on_update
	 * Creates events then updates them using a different organizer and venue and checks them to see if the update worked
	 */
	public function test_venue_organizer_on_update() {
		$organizers = array(
			Tribe__Events__API::createOrganizer( array(
				'Organizer' => 'Test Organizer A',
			) ),
			Tribe__Events__API::createOrganizer( array(
				'Organizer' => 'Test Organizer B',
			) ),
		);
		$venues     = array(
			Tribe__Events__API::createVenue( array(
				'Venue' => 'Test Venue A',
			) ),
			Tribe__Events__API::createVenue( array(
				'Venue' => 'Test Venue B',
			) ),
		);
		$start_date = date( 'Y-m-d', strtotime( 'today' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'post_status'      => 'publish',
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'Organizer'        => array( 'OrganizerID' => $organizers[0] ),
			'Venue'            => array( 'VenueID' => $venues[0] ),
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 5,
					),
				),// end rules array
			)
		);
		$post_id    = Tribe__Events__API::createEvent( $event_args );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );
		sleep( 1 );

		$event_args['Organizer'] = array( 'OrganizerID' => $organizers[1] );
		$event_args['Venue']     = array( 'VenueID' => $venues[1] );

		Tribe__Events__API::updateEvent( $post_id, $event_args );

		// Create another fresh queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );
		sleep( 1 );

		$children = get_posts( array(
			'post_type'   => Tribe__Events__Main::POSTTYPE,
			'post_parent' => $post_id,
			'post_status' => 'publish',
			'fields'      => 'ids',
		) );
		//Checks that organizer id and venue id is the same after the update
		foreach ( $children as $child_id ) {
			$this->assertEquals( $organizers[1], get_post_meta( $child_id, '_EventOrganizerID', true ) );
			$this->assertEquals( $venues[1], get_post_meta( $child_id, '_EventVenueID', true ) );
		}
	}//ends test_venue_organizer_on_update

	/**
	 * test_permalink_creation
	 *
	 */
	public function test_permalink_creation() {
		if ( get_option( 'permalink_structure', '' ) == '' ) {
			return; // no permalinks to test
		}
		$start_date = date( 'Y-m-d', strtotime( '2014-05-01' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'post_name'        => 'test-permalinks',
			'post_status'      => 'publish',
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 2,
					),
				),// end rules array
			)
		);
		$post_id    = Tribe__Events__API::createEvent( $event_args );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );

		$base_url = home_url() . '/event/test-permalinks/';
		$this->assertEquals( $base_url . user_trailingslashit( '2014-05-01' ), get_post_permalink( $post_id ) );

		$children = get_posts( array(
			'post_type'   => Tribe__Events__Main::POSTTYPE,
			'post_parent' => $post_id,
			'fields'      => 'ids',
		) );
		$child_id = reset( $children );
		$this->assertEquals( $base_url . user_trailingslashit( '2014-05-08' ), get_post_permalink( $child_id ) );
	}//ends test_permalink_creation

	/**
	 * test_eventDate_queries
	 * Creates an event that recurs every week 5 times and checks that they are valid with the queries
	 */
	public function test_eventDate_queries() {
		if ( get_option( 'permalink_structure', '' ) == '' ) {
			return; // no permalinks to test
		}
		$start_date = date( 'Y-m-d', strtotime( '2014-05-01' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'post_name'        => 'test-permalinks',
			'post_status'      => 'publish',
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 2,
					),
				),// end rules array
			)
		);
		$post_id    = Tribe__Events__API::createEvent( $event_args );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );

		$children = get_posts( array(
			'post_type'   => Tribe__Events__Main::POSTTYPE,
			'post_parent' => $post_id,
			'fields'      => 'ids',
		) );
		$child_id = reset( $children );

		$query   = new WP_Query();
		$results = $query->query( array(
			'post_type'    => Tribe__Events__Main::POSTTYPE,
			'fields'       => 'ids',
			'tribe_events' => 'test-permalinks', // this will be present for a normal request
			'name'         => 'test-permalinks',
			'eventDate'    => '2014-05-01',
			'eventDisplay' => 'custom',
		) );
		//Checks that the post_id is the same as the reset results
		$this->assertEquals( $post_id, reset( $results ) );

		$query   = new WP_Query();
		$results = $query->query( array(
			'post_type'    => Tribe__Events__Main::POSTTYPE,
			'fields'       => 'ids',
			'tribe_events' => 'test-permalinks', // this will be present for a normal request
			'name'         => 'test-permalinks',
			'eventDate'    => '2014-05-08',
			'eventDisplay' => 'custom',
		) );
		//Checks that the child id is the same as the reset results
		$this->assertEquals( $child_id, reset( $results ) );
	}//ends test_eventDate_queries

	/**
	 * test_child_post_comments
	 * Creates an event and checks that the child post are the same
	 */
	public function test_child_post_comments() {
		$_SERVER['REMOTE_ADDR'] = null; // to avoid PHP notice

		// we need an admin user to bypass comment flood protection
		$author_id        = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$old_current_user = get_current_user_id();
		wp_set_current_user( $author_id );

		$start_date = date( 'Y-m-d', strtotime( 'today' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'post_name'        => 'test-tribeHideRecurrence',
			'post_status'      => 'publish',
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 8,
					),
				),// end rules array
			)
		);
		$post_id    = Tribe__Events__API::createEvent( $event_args );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );

		$children = get_posts( array(
			'post_type'      => Tribe__Events__Main::POSTTYPE,
			'post_parent'    => $post_id,
			'fields'         => 'ids',
			'posts_per_page' => 10,
		) );
		//Checks that the children is not empty
		$this->assertNotEmpty( $children, 'Checks that the children is not empty' );
		//Checks that teh children is 7 which is because of the exclusion of the parent
		$this->assertCount( 7, $children, 'Checks that the children is 7 excluding the parent' );

		$comment_id = wp_new_comment( array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => 'Comment Author',
			'comment_author_url'   => '',
			'comment_author_email' => 'test@example.com',
			'comment_type'         => '',
			'comment_content'      => 'This is a comment on ' . $post_id,
		) );
		$comment    = get_comment( $comment_id );
		//Checks that comment is not empty
		$this->assertNotEmpty( $comment, 'Checks that comment is not empty' );
		//Checks that the post id is the same as the comment post id
		$this->assertEquals( $post_id, $comment->comment_post_ID, 'Checks that the post id is the same as the comment post id' );


		$comment_id = wp_new_comment( array(
			'comment_post_ID'      => $children[2],
			'comment_author'       => 'Comment Author',
			'comment_author_url'   => '',
			'comment_author_email' => 'test@example.com',
			'comment_type'         => '',
			'comment_content'      => 'This is a comment on ' . $children[2],
		) );
		$comment    = get_comment( $comment_id );
		//Checks that comment is not empty
		$this->assertNotEmpty( $comment, 'Checks that comment is not empty' );
		//Checks that the post id is the same as the comment post id
		$this->assertEquals( $post_id, $comment->comment_post_ID, 'Checks that the post id is the same as the comment post id' );

		$comments = get_comments( array(
			'post_id' => $post_id,
		) );
		$this->assertCount( 2, $comments, 'Checks that there are 2 comments' );

		$comments = get_comments( array(
			'post_id' => $children[1],
		) );
		//Checks that there are 2 comments
		$this->assertCount( 2, $comments, 'Checks that there are 2 comments' );


		wp_set_current_user( $old_current_user );
	}//ends test_child_post_comments

	/**
	 * test_remove_recurrence
	 *
	 */
	public function test_remove_recurrence() {
		$start_date = date( 'Y-m-d', strtotime( 'today' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'post_status'      => 'publish',
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Every Week',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 5,
					),
				),// end rules array
			)
		);
		$post_id    = Tribe__Events__API::createEvent( $event_args );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );
		sleep( 1 );

		$original_dates = tribe_get_recurrence_start_dates( $post_id );
		//Checks that the original dates is not empty
		$this->assertNotEmpty( $original_dates, 'Checks the original dates and confirms that they are not empty' );

		//Checks that the original events is 5
		$this->assertCount( 5, $original_dates, 'Checks original dates and confirms that there are 5' );

		$event_args['recurrence'] = array();

		Tribe__Events__API::updateEvent( $post_id, $event_args );

		// process the queue, otherwise all the children won't get created
		Tribe__Events__Pro__Main::instance()->queue_processor->process_queue();
		sleep( 1 );

		$new_dates = tribe_get_recurrence_start_dates( $post_id );

		//Checks that new dates is not empty
		$this->assertNotEmpty( $new_dates, 'Checks that new dates is not empty' );

		//Checks that there is 1 new date
		$this->assertCount( 1, $new_dates, 'Checks that there is 1 new dates' );
	}//ends test_remove_recurrence

	/**
	 * test the creation of date-based recurring events
	 */
	public function test_date_based_recurrence() {
		$start_date      = date( 'Y-m-d', strtotime( 'tomorrow' ) );
		$recurrence_date = date( 'Y-m-d', strtotime( '+5 days' ) );
		$event_args      = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __FUNCTION__,
			'post_content'     => __CLASS__ . ' ' . __FUNCTION__,
			'post_status'      => 'publish',
			'EventStartDate'   => $start_date,
			'EventEndDate'     => $start_date,
			'EventStartHour'   => 16,
			'EventEndHour'     => 17,
			'EventStartMinute' => 0,
			'EventEndMinute'   => 0,
			'recurrence'       => array(
				'rules' => array(
					0 => array(
						'type'      => 'Date',
						'end-type'  => 'On',
						'end'       => $recurrence_date,
						'end-count' => null,
					),
				),// end rules array
			)
		);
		$post_id         = Tribe__Events__API::createEvent( $event_args );

		// Create a new queue processor to generate the children for this new event
		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );

		$original_dates = tribe_get_recurrence_start_dates( $post_id );
		//Checks that the original dates is not empty
		$this->assertNotEmpty( $original_dates, 'Checks the original dates and confirms that they are not empty' );

		//Checks that the original events is 5
		$this->assertCount( 2, $original_dates, 'Checks original dates and confirms that there are 2' );

		$this->assertEquals( $recurrence_date, date( 'Y-m-d', strtotime( $original_dates[1] ) ), 'Verifies that the second date is indeed 5 days after the first date' );
	}

	/**
	 * @see tribe_get_upcoming_recurring_event_id_from_url()
	 */
	public function test_can_find_upcoming_recurring_event_id_from_url() {
		// Sequence starting *yesterday* and with one future instance a week from now
		$post_id = $this->build_simple_recurring_event( __FUNCTION__, date( 'Y-m-d', strtotime( 'yesterday' ) ), [
			'rules' => [ [
				'type'      => 'Date',
				'end-type'  => 'On',
				'end'       => date( 'Y-m-d', strtotime( '+1 week' ) ),
				'end-count' => null,
			] ],
		] );

		// Simulate the /all/ events URL for this sequence
		$test_url = 'http://any.domain/event/' . sanitize_title( __FUNCTION__ ) . '/all/';
		$upcoming_id = tribe_get_upcoming_recurring_event_id_from_url( $test_url );

		// We hope to get back the post ID of a future event
		$this->assertTrue( is_numeric( $upcoming_id ) && (int) $upcoming_id > 0, 'Result is a positive integer (post ID)' );
		$this->assertNotEquals( (int) $post_id, (int) $upcoming_id, 'The parent post ID (which is in the past) was not returned' );
		$this->assertGreaterThan( date_i18n( 'Y-m-d' ), tribe_get_start_date( $upcoming_id, false, 'Y-m-d' ) );

		// Ensure it doesn't return false positives for /all/ URLs where the event does not exist/was deleted
		$test_url = 'http://any.domain/event/this-slug-does-not-exist-so-i-expect-a-null-result/all';
		$should_be_null = tribe_get_upcoming_recurring_event_id_from_url( $test_url );
		$this->assertNull( $should_be_null, 'Does not return false positives' );

		// Ensure it doesn't return false positives for URLs not matching the /all/ URL pattern
		$test_url = 'http://some.url?null=should_be_the_result';
		$should_be_null = tribe_get_upcoming_recurring_event_id_from_url( $test_url );
		$this->assertNull( $should_be_null, 'Does not return false positives' );
	}

	/**
	 * Creates a simple recurring event (and automatically processes the queue).
	 *
	 * @param string $name
	 * @param string $start_date
	 * @param array  $recurrence (should contain 'rules' and optionally 'exceptions' keys)
	 *
	 * @return int
	 */
	protected function build_simple_recurring_event( $name, $start_date, array $recurrence ) {
		$post_id = Tribe__Events__API::createEvent( [
			'post_type' => Tribe__Events__Main::POSTTYPE,
			'post_title' => $name,
			'post_content' => __CLASS__ . ' ' . $name,
			'post_status' => 'publish',
			'EventStartDate' => $start_date,
			'EventEndDate' => $start_date,
			'EventStartHour' => 16,
			'EventEndHour' => 17,
			'EventStartMinute' => 0,
			'EventEndMinute' => 0,
			'recurrence' => $recurrence
		] );

		$queue_processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$queue_processor->process_queue( $post_id );

		return $post_id;
	}
}
