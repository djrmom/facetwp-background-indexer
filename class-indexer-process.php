<?php

class FWP_Indexer_Process extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'facetwp_indexer_process';

	/**
	 * Task
	 *
	 * Return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {

		FWP()->indexer->index( $item );

		return false;
	}

	/**
	 * Complete
	 */
	protected function complete() {
		parent::complete();

		update_option( 'facetwp_last_indexed', time() );

		error_log( 'FacetWP Background Indexing Complete' );
	}

}