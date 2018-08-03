<?php

namespace SensitiveArticle;

class SensitiveArticleWidget
{
	private $article; // SensitiveArticle
	private $mustache; // Mustache_Engine

	public function __construct(int $pageId)
	{
		$this->article = SensitiveArticle::newFromDB($pageId);

		$this->mustache = new \Mustache_Engine([
			'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/resources' )
		]);
	}

	public function getJS(): string
	{
		return file_get_contents(__DIR__ . '/resources/sensitive_article_widget.js');
	}

	public function getHTML(): string
	{
		$vars = [
			'reasons' => $this->getReasonsForWidget(),
			'isTagged' => !empty($this->article->reasonIds),
			'title' => wfMessage('saw_title')->text(),
			'edit' => wfMessage('edit')->text(),
			'save' => wfMessage('save')->text()
		];
		return $this->mustache->render('sensitive_article_widget.mustache', $vars);
	}

	public function editArticle(int $revId, int $userId, array $reasonIds)
	{
		$this->article->revId = $revId;
		$this->article->userId = $userId;
		$this->article->reasonIds = $reasonIds;
		$this->article->date = wfTimestampNow();
		$this->article->save();
	}

	private function getReasonsForWidget(): array
	{
		$reasons = SensitiveReason::getAll();
		foreach ($reasons as $reason) {
			if (in_array($reason->id, $this->article->reasonIds)) {
				$reason->enabled = true;
				$reason->selected = true;
			}
		}
		return $reasons;
	}
}
