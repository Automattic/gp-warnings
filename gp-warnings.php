<?php
/**
 * Plugin name: GlotPress: Warnings Dashboard
 * Plugin author: Automattic
 * Version: 1.0
 *
 * Description: Gives an overview of the warnings in a whole project, across locales.
 */


class GP_Route_Warnings extends GP_Route_Main {
	public $segments = array();

	function __construct() {
		$this->template_path = dirname( __FILE__ ) . '/templates/';

		if ( file_exists( __DIR__ . '/segments.php' ) ) {
			$this->segments = include __DIR__ . '/segments.php';
		}
	}

	function show( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( ! $project ) $this->die_with_404();

		$translation_sets = GP::$translation_set->by_project_id( $project->id );
		if ( ! $translation_sets ) $this->die_with_404();

		global $wpdb;

		$segments = new GP_Warnings_Segments( $this->segments );

		$translation_set_ids = $translation_set_locale_map = array();
		foreach ( $translation_sets as $translation_set ) {
			$translation_set_ids[] = $translation_set->id;
			$segments->map( $translation_set->locale, $translation_set->id );

			$translation_set->url_current = gp_url_project( $project, array( $translation_set->locale, $translation_set->slug ), array( 'filters[warnings]' => 'yes', 'filters[status]' => 'current' ) );
			$translation_set->url_waiting = gp_url_project( $project, array( $translation_set->locale, $translation_set->slug ), array( 'filters[warnings]' => 'yes', 'filters[status]' => 'waiting' ) );
			$translation_set->url_fuzzy = gp_url_project( $project, array( $translation_set->locale, $translation_set->slug ), array( 'filters[warnings]' => 'yes', 'filters[status]' => 'fuzzy' ) );
			$translation_sets[$translation_set->id] = $translation_set;
		}

		$sql_for_warnings = "
			SELECT t.translation_set_id, SUM( t.status = 'current') AS current, SUM( t.status = 'waiting') AS waiting, SUM( t.status = 'fuzzy') AS fuzzy
			FROM $wpdb->gp_originals as o
			INNER JOIN $wpdb->gp_translations AS t ON o.id = t.original_id AND t.translation_set_id IN( ".$wpdb->escape( implode( ',', $translation_set_ids ) )." )
			WHERE o.project_id = " . $wpdb->escape( $project->id )." AND o.status LIKE '+%'
			AND t.warnings IS NOT NULL AND t.warnings != '' AND t.status IN ('current', 'waiting', 'fuzzy')
			GROUP BY t.translation_set_id HAVING COUNT(*) > 0
			ORDER BY " . $segments->order_by( 't.translation_set_id' ) . ", SUM( t.status = 'current') DESC
		";

		$warnings = $wpdb->get_results( $sql_for_warnings );

		$this->tmpl( 'warnings', get_defined_vars() );
	}
}

class GP_Warnings  {
	private static $instance = null;

	public static function init() {
		self::get_instance();
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	function __construct() {
		add_action( 'template_redirect', array( $this, 'register_routes' ), 5 );
		add_filter( 'gp_project_actions', array( $this, 'gp_project_actions' ), 5, 2 );
	}

	function register_routes() {
		GP::$router->prepend( '/projects/(.+?)/-warnings', array( 'GP_Route_Warnings', 'show' ), 'get' );
	}

	function gp_project_actions( $actions, $project ) {
		$actions[] = gp_link_get( gp_url( '/projects/' . $project->path . '/-warnings' ), __( 'Warnings Dashboard' ) );
		return $actions;
	}

}

class GP_Warnings_Segments {
	private $segments, $titles = array();

	public function __construct( $segments ) {
		$this->segments = $segments;
	}

	public function map( $locale, $id ) {

		foreach ( $this->segments as $title => $set_ids ) {
			foreach ( $set_ids as $k => $set_id ) {
				if ( $locale === $set_id ) {
					$this->segments[ $title ][ $k ] = intval( $id );
					$this->titles[ $id ] = $title;
					return $id;
				}
			}
		}
	}


	public function order_by( $field ) {
		if ( empty( $this->segments ) )  {
			return "1";
		}

		$case = '';
		$c = 0;
		foreach ( $this->segments as $title => $set_ids ) {
			$c += 1;
			foreach ( $set_ids as $set_id ) {
				if ( ! is_int( $set_id) ) {
					continue;
				}
				$case .= ' WHEN ' . $set_id . ' THEN ' . $c;
			}
		}

		return '(SELECT CASE ' . $field . $case . ' ELSE ' . ($c + 1) . ' END) ASC';
	}

	public function title( $set_id ) {
		if ( ! isset( $this->titles[ $set_id ] ) ) {
			return __( 'Other Locales' );
		}

		return $this->titles[ $set_id ];
	}

}


add_action( 'gp_init', array( 'GP_Warnings', 'init' ) );

