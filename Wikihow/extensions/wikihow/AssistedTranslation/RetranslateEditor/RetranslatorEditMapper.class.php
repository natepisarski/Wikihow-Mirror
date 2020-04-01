<?php

namespace EditMapper;

class RetranslatorEditMapper extends EditMapper {

	public function shouldMapEdit($title, $user, bool $isNew, string $comment): bool {
		return $title && $title->exists() && $title->inNamespaces(NS_MAIN)
			&& \RetranslateEditor::isUserAllowed()
			&& \RequestContext::getMain()->getRequest()->getBool('is_retrans');
	}

	public function getDestUser($title, bool $isNew) {
		$destUsername = 'wikiHow Retranslation';
		return \User::newFromName($destUsername);
	}
}
