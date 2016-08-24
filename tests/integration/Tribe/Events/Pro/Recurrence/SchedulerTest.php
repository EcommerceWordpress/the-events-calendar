<?php
namespace Tribe\Events\Pro\Recurrence;
use Tribe__Events__API;
use Tribe__Events__Main;
use Tribe__Events__Pro__Recurrence__Meta;
use Tribe__Events__Pro__Recurrence__Queue_Processor;

class SchedulerTest extends \Codeception\TestCase\WPTestCase {

	protected $backupGlobals = false;

	protected $defaultBeforeRange = 24;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		Tribe__Events__Pro__Recurrence__Meta::reset_scheduler();
		$this->defaultBeforeRange = \Tribe__Events__Pro__Recurrence__Scheduler::$default_before_range;
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it should default before range to 24 months
	 */
	public function it_should_default_before_range_to_24_months() {
		$this->assertEquals( $this->defaultBeforeRange,
			( new \Tribe__Events__Pro__Recurrence__Scheduler() )->get_before_range() );
	}

	/**
	 * @test
	 * it should default after range to 24 months
	 */
	public function it_should_default_after_range_to_24_months() {
		$this->assertEquals( $this->defaultBeforeRange,
			( new \Tribe__Events__Pro__Recurrence__Scheduler() )->get_after_range() );
	}

	/**
	 * @test
	 * it should allow setting range before
	 */
	public function it_should_allow_setting_range_before() {
		$sut = new \Tribe__Events__Pro__Recurrence__Scheduler( 2 );
		$this->assertEquals( 2, $sut->get_before_range() );
	}

	/**
	 * @test
	 * it should allow setting range after
	 */
	public function it_should_allow_setting_range_after() {
		$sut = new \Tribe__Events__Pro__Recurrence__Scheduler( 24, 2 );
		$this->assertEquals( 2, $sut->get_after_range() );
	}

	public function notNumeric() {
		return array_map( function ( $val ) {
			return [ $val ];
		},
			[
				'foo',
				- 123,
				array(),
				new \stdClass()
			] );
	}

	/**
	 * @test
	 * it should not allow non numeric before ranges
	 * @dataProvider notNumeric
	 */
	public function it_should_not_allow_non_numeric_before_ranges( $val ) {
		$sut = new \Tribe__Events__Pro__Recurrence__Scheduler( $val );
		$this->assertEquals( $this->defaultBeforeRange, $sut->get_before_range() );
	}

	/**
	 * @test
	 * it should not allow non numeric after ranges
	 * @dataProvider notNumeric
	 */
	public function it_should_not_allow_non_numeric_after_ranges( $val ) {
		$sut = new \Tribe__Events__Pro__Recurrence__Scheduler( $val );
		$this->assertEquals( $this->defaultBeforeRange, $sut->get_after_range() );
	}

	/**
	 * @test
	 * it should not allow setting non numeric before ranges
	 * @dataProvider notNumeric
	 */
	public function it_should_not_allow_setting_non_numeric_before_ranges( $val ) {
		$sut = new \Tribe__Events__Pro__Recurrence__Scheduler( $val );
		$sut->set_before_range( $val );
		$this->assertEquals( $this->defaultBeforeRange, $sut->get_before_range() );
	}

	/**
	 * @test
	 * it should not allow setting non numeric after ranges
	 * @dataProvider notNumeric
	 */
	public function it_should_not_allow_setting_non_numeric_after_ranges( $val ) {
		$sut = new \Tribe__Events__Pro__Recurrence__Scheduler( $val );
		$sut->set_after_range( $val );
		$this->assertEquals( $this->defaultBeforeRange, $sut->get_after_range() );
	}


	/**
	 * test_default_settings()
	 * This test creates a scheduler that is from two years ago and checks two years form now
	 */
	public function test_default_settings() {
		$scheduler          = Tribe__Events__Pro__Recurrence__Meta::get_scheduler();
		$today              = date( 'Y-m-d' );
		$two_years_ago      = date( 'Y-m-d', strtotime( '24 months ago' ) );
		$two_years_from_now = date( 'Y-m-d', strtotime( '+24 months' ) );

		//Checks to make sure two years ago from now and today are the same thing
		$this->assertEquals( intval( substr( $two_years_from_now, 0, 4 ) ) - intval( substr( $today, 0, 4 ) ), 2 );
		//Chechs that when you minus today from two years ago you get 2
		$this->assertEquals( intval( substr( $today, 0, 4 ) ) - intval( substr( $two_years_ago, 0, 4 ) ), 2 );

		$this->assertEquals( $two_years_ago, $scheduler->get_earliest_date() );
		$this->assertEquals( $two_years_from_now, $scheduler->get_latest_date() );
	}//ends test_default_settings

	/**
	 * test_zeros()
	 * This test updates an options of a recurrenceMax and MIN months before and after.
	 * Then it checks to make sure that today is the earliers day and the latest date.
	 */
	public function test_zeros() {
		tribe_update_option( 'recurrenceMaxMonthsBefore', 0 );
		tribe_update_option( 'recurrenceMaxMonthsAfter', 0 );
		Tribe__Events__Pro__Recurrence__Meta::reset_scheduler();
		$scheduler = Tribe__Events__Pro__Recurrence__Meta::get_scheduler();
		$today     = date( 'Y-m-d' );
		//Checks that today is the earliest you can set
		$this->assertEquals( $today,
			$scheduler->get_earliest_date(),
			'Checks that today is the earliest you can set ' );
		//Checks that today is the latest date you can set
		$this->assertEquals( $today,
			$scheduler->get_latest_date(),
			'Checks that today is the latest date you can set' );
	}// ends test_zeros

	/**
	 * test_cleanup()
	 * This test creates an event that recurs 200 times and then it updates it just to
	 * go out 6 months and checks that the recurrence is less than 30.
	 */
	public function test_cleanup() {
		$start_date = date( 'Y-m-d', strtotime( '1 year ago' ) );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __CLASS__,
			'post_content'     => __FUNCTION__,
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
						'end-count' => 200,
					),
				),// end rules array
			)
		);

		$post_id = Tribe__Events__API::createEvent( $event_args );

		$this->assertNotEmpty( $post_id, 'Checking that the event post has been succesfully created.' );

		// Process the queue for this event to ensure the expected range of child events are generated
		$processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$processor->process_batch( $post_id, PHP_INT_MAX );

		//checking to make sure that there is 200 events created
		$recurrence_spec = get_post_meta( $post_id, '_EventRecurrence', true );
		//200 Events are created by the end-count and it checks to make sure that there are infact 200 created
		$this->assertEquals( 200, $recurrence_spec['rules'][0]['end-count'], 'Check that there are 200 events' );

		/** @var wpdb $wpdb */
		global $wpdb;
		$instances = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id AND (p.post_parent=%d OR p.ID=%d) WHERE meta_key='_EventStartDate' AND meta_value < %s",
			$post_id,
			$post_id,
			date( 'Y-m-d' ) ) );
		//Checks that the instances of the event is more than 50
		$this->assertGreaterThan( 50,
			count( $instances ),
			'Checks that there are more than 50 weekly events in an year' );
		$this->assertLessThanOrEqual( 54,
			count( $instances ),
			'Checks that there are at most 54 weekly events in an year' );

		tribe_update_option( 'recurrenceMaxMonthsBefore', 6 );

		Tribe__Events__Pro__Recurrence__Meta::reset_scheduler();
		$scheduler = Tribe__Events__Pro__Recurrence__Meta::get_scheduler();
		$scheduler->clean_up_old_recurring_events();

		$instances = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id AND (p.post_parent=%d OR p.ID=%d) WHERE meta_key='_EventStartDate' AND meta_value < %s",
			$post_id,
			$post_id,
			date( 'Y-m-d' ) ) );
		//After the cleanup old recurreing events and making sure it is less than 30
		// Why 30? 6 months * ~4 weekly events = ~24
		$this->assertLessThan( 30,
			count( $instances ),
			'Checks that after clean up there are now less than 30 events' );

		// the first instance should always remain
		$this->assertContains( $start_date . ' 16:00:00', $instances, 'Checks that the start date is still the same' );
	}//ends test_cleanup


	public function test_future_scheduling() {
		tribe_update_option( 'recurrenceMaxMonthsAfter', 6 );
		Tribe__Events__Pro__Recurrence__Meta::reset_scheduler();
		$scheduler  = Tribe__Events__Pro__Recurrence__Meta::get_scheduler();
		$start_date = date( 'Y-m-d' );
		$event_args = array(
			'post_type'        => Tribe__Events__Main::POSTTYPE,
			'post_title'       => __CLASS__,
			'post_content'     => __FUNCTION__,
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
						'end-count' => 200,
					),
				),// end rules array
			)
		);

		$post_id = Tribe__Events__API::createEvent( $event_args );

		// Process the queue for this event to ensure the expected range of child events are generated
		$processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$processor->process_batch( $post_id, PHP_INT_MAX );

		$this->assertNotEmpty( $event_args, 'Checking that there number of events is not 0' );

		//checking to make sure that there is 200 events created
		$recurrence_spec = get_post_meta( $post_id, '_EventRecurrence', true );
		//200 Events are created by the end-count and it checks to make sure that there are inface 200 created
		$this->assertEquals( 200, $recurrence_spec['rules'][0]['end-count'], 'Check that there are 200 events' );

		/** @var wpdb $wpdb */
		global $wpdb;
		$instances = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id AND (p.post_parent=%d OR p.ID=%d) WHERE meta_key='_EventStartDate' AND meta_value >= %s",
			$post_id,
			$post_id,
			$start_date ) );
		$this->assertLessThan( 28, count( $instances ) );
		$this->assertGreaterThan( 24, count( $instances ) );

		tribe_update_option( 'recurrenceMaxMonthsAfter', 8 );
		Tribe__Events__Pro__Recurrence__Meta::reset_scheduler();
		$scheduler = Tribe__Events__Pro__Recurrence__Meta::get_scheduler();
		$scheduler->schedule_future_recurring_events();
		$scheduler->clean_up_old_recurring_events();

		$instances = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id AND (p.post_parent=%d OR p.ID=%d) WHERE meta_key='_EventStartDate'",
			$post_id,
			$post_id ) );
		$this->assertGreaterThan( 30, count( $instances ) );
		$this->assertLessThan( 40, count( $instances ) );
	}


	/**
	 * This tests for a bug from issue #22420
	 */
	public function ignore_test_update_with_recurrence() {
		$start_date     = date( 'Y-m-d' );
		$expected_dates = array( $start_date . ' 16:00:00' );
		for ( $i = 1; $i < 5; $i ++ ) {
			$expected_dates[] = date( 'Y-m-d 16:00:00', strtotime( $start_date . ' +' . $i . ' weeks' ) );
		}
		$event_args = array(
			'post_title'       => __CLASS__,
			'post_content'     => __FUNCTION__,
			'post_type'        => Tribe__Events__Main::POSTTYPE,
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

		$post_id = Tribe__Events__API::createEvent( $event_args );

		// Process the queue for this event to ensure the expected range of child events are generated
		$processor = new Tribe__Events__Pro__Recurrence__Queue_Processor;
		$processor->process_batch( $post_id, PHP_INT_MAX );

		$instances = get_post_meta( $post_id, '_EventStartDate', false );
		$this->assertEqualSets( $expected_dates, $instances );

		// now change the order of the meta entries in the DB
		// this replicates a db that might be configured to return in arbitrary order
		delete_post_meta( $post_id, '_EventStartDate' );
		$rearranged = array( $instances[1], $instances[0], $instances[2], $instances[3], $instances[4] );
		foreach ( $rearranged as $date ) {
			add_post_meta( $post_id, '_EventStartDate', $date );
		}

		// according to the bug, when we update the event, the second instance will disappear
		$instances = get_post_meta( $post_id, '_EventStartDate', false );
		$this->assertEqualSets( $expected_dates, $instances );
		Tribe__Events__API::updateEvent( $post_id,
			array(
				'post_title'       => __CLASS__,
				'post_content'     => __FUNCTION__ . ' (updating)',
				'post_type'        => Tribe__Events__Main::POSTTYPE,
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

		$instances = get_post_meta( $post_id, '_EventStartDate', false );
		$this->assertEqualSets( $expected_dates, $instances );

	}
}