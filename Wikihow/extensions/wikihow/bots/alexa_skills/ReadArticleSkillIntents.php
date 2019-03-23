<?php

class ReadArticleSkillIntents {
	const INTENT_START = 'StartIntent';
	const INTENT_FALLBACK = 'FallbackIntent';
	const INTENT_HOWTO = 'QueryIntent';
	const INTENT_GOTO_STEP = 'GoToStepIntent';
	const INTENT_START_OVER = 'AMAZON.StartOverIntent';
	const INTENT_FIRST_STEP = 'FirstStep';
	const INTENT_LAST_STEP = 'LastStep';
	const INTENT_STOP = 'AMAZON.StopIntent';
	const INTENT_PAUSE = 'AMAZON.PauseIntent';
	const INTENT_REPEAT = 'AMAZON.RepeatIntent';
	const INTENT_PREVIOUS = 'AMAZON.PreviousIntent';
	const INTENT_RESUME = 'ResumeIntent';
	const INTENT_AMAZON_RESUME = 'AMAZON.ResumeIntent';
	const INTENT_NEXT = 'AMAZON.NextIntent';
	const INTENT_NO = 'AMAZON.NoIntent';
	const INTENT_YES = 'AMAZON.YesIntent';
	const INTENT_NEXT_STEP = 'NextStep'; // Deprecated
	const INTENT_STEP_DETAILS = 'StepDetails'; // Deprecated
	const INTENT_CANCEL = 'AMAZON.CancelIntent';
	const INTENT_HELP = 'AMAZON.HelpIntent';
	const INTENT_SUMMARY_VIDEO = 'PlaySummaryVideo';
}
