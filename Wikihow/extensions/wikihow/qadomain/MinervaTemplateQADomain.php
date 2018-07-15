<?
/*
 * Specialized version of the MinervaTemplate with wikiHowAnswers customizations
 */
class MinervaTemplateQADomain extends MinervaTemplate {

	protected function renderContentWrapper( $data ) {
		if ( !$data['amp'] == true ):  ?>
			<script>
				if (typeof mw != 'undefined') {
					mw.mobileFrontend.emit('header-loaded');
				}
			</script>
		<?php endif;

		$this->renderPreContent( $data );
		$this->renderContent( $data );
	}

	protected function render( $data ) { // FIXME: replace with template engines
		// begin rendering
		echo $data[ 'headelement' ];

		$this->renderContentWrapper( $data );
		?>
		<div id='servedtime'><?= Misc::reportTimeMS(); ?></div>
		<?php
		echo MWDebug::getDebugHTML( $this->getSkin()->getContext() );
		echo $data['reporttime'];

		// Reuben: using this hook to post-load the ResourceLoader startup
		wfRunHooks( 'MobileEndOfPage', array( $data ) );
		?>
		</body>
		</html>
		<?php
	}

}
