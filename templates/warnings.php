<?php
/* translators: %s is a project name */
gp_title( sprintf( __( 'Warnings Dashboard &lt; %s &lt; GlotPress' ), $project->name ) );
gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
) );

gp_tmpl_header();

?><h1><?php _e( 'Warnings Dashboard' ); ?></h1><?php

$last_title = false;
foreach( $warnings as $warning ) {

	$title = $segments->title( $warning->translation_set_id );
	if ( $title != $last_title ) {
		?><h2><?php echo esc_translation( $title ); ?></h2><?php
		$last_title = $title;
	}

	$warnings = $sep = '';
	$translation_set = $translation_sets[$warning->translation_set_id];
	if ( $warning->current ) {
		$warnings .= $sep; $sep = ', ';
		$warnings .= '<a href="' . $translation_set->url_current . '">';
		$warnings .= sprintf( _n( '<strong>1 current</strong> warning', '<strong>%d current</strong> warnings', $warning->current), $warning->current );
		$warnings .= '</a>';
	}

	if ( $warning->waiting ) {
		$warnings .= $sep; $sep = ', ';
		$warnings .= '<a href="' . $translation_set->url_waiting . '">';
		$warnings .= sprintf( _n( '<strong>1 waiting</strong> warning', '<strong>%d waiting</strong> warnings', $warning->waiting), $warning->waiting );
		$warnings .= '</a>';
	}

	if ( $warning->fuzzy ) {
		$warnings .= $sep; $sep = ', ';
		$warnings .= '<a href="' . $translation_set->url_fuzzy . '">';
		$warnings .= sprintf( _n( '<strong>1 fuzzy</strong> warning', '<strong>%d fuzzy</strong> warnings', $warning->fuzzy), $warning->fuzzy );
		$warnings .= '</a>';
	}
	/* translators: %1$s is a locale abbreviation, %2$s is a string like "3 current warnings, 2 waiting warnings" */
	printf( __( 'Locale %1$s has %2$s'), '<strong>' . esc_translation( $translation_set->locale ) . '</strong>', $warnings );
	?><br /><?php
}

gp_tmpl_footer();
