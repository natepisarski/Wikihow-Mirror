<?php

$wgExtensionCredits['MessengerSearchBot'][] = array(
	'name' => 'AlexaSkillReadArticle Page',
	'author' => 'Jordan Small',
	'description' => 'Alexa skill that queries and reads articles',
);

$wgSpecialPages['AlexaSkillReadArticleWebHook'] = 'AlexaSkillReadArticleWebHook';
$wgAutoloadClasses['AlexaSkillReadArticleWebHook'] = __DIR__ . '/AlexaSkillReadArticleWebHook.php';

$wgAutoloadClasses['WikihowAlexaResponse'] = __DIR__ . '/amazon-alexa-php/WikihowAlexaResponse.php';
$wgAutoloadClasses['DisplayTemplateInterface'] = __DIR__ . '/amazon-alexa-php/interfaces/DisplayTemplateInterface.php';
$wgAutoloadClasses['DisplayTemplateTypes'] = __DIR__ . '/amazon-alexa-php/interfaces/DisplayTemplateTypes.php';
$wgAutoloadClasses['BodyTemplate1'] = __DIR__ . '/amazon-alexa-php/interfaces/BodyTemplate1.php';
$wgAutoloadClasses['BodyTemplate2'] = __DIR__ . '/amazon-alexa-php/interfaces/BodyTemplate2.php';
$wgAutoloadClasses['BodyTemplate3'] = __DIR__ . '/amazon-alexa-php/interfaces/BodyTemplate3.php';
$wgAutoloadClasses['BodyTemplate6'] = __DIR__ . '/amazon-alexa-php/interfaces/BodyTemplate6.php';
$wgAutoloadClasses['ImageObject'] = __DIR__ . '/amazon-alexa-php/interfaces/ImageObject.php';
$wgAutoloadClasses['TextContentObject'] = __DIR__ . '/amazon-alexa-php/interfaces/TextContentObject.php';
$wgAutoloadClasses['HintDirective'] = __DIR__ . '/amazon-alexa-php/interfaces/HintDirective.php';
$wgAutoloadClasses['VideoApp'] = __DIR__ . '/amazon-alexa-php/interfaces/VideoApp.php';
$wgAutoloadClasses['Alexa\Request\ElementSelectedRequest'] = __DIR__ . '/amazon-alexa-php/Request/ElementSelectedRequest.php';
$wgAutoloadClasses['ReadArticleSkillIntents'] = __DIR__ . '/ReadArticleSkillIntents.php';

$wgAutoloadClasses['AbstractResponseComponentFactory'] = __DIR__ . '/response_component_factories/AbstractResponseComponentFactory.php';

$wgAutoloadClasses['EndSessionFactory'] = __DIR__ . '/response_component_factories/EndSessionFactory.php';
$wgAutoloadClasses['DisplayTemplateFactory'] = __DIR__ . '/response_component_factories/DisplayTemplateFactory.php';
$wgAutoloadClasses['OutputSpeechFactory'] = __DIR__ . '/response_component_factories/OutputSpeechFactory.php';

$wgAutoloadClasses['HintFactory'] = __DIR__ . '/response_component_factories/HintFactory.php';
$wgMessagesDirs['HintFactory'] = [__DIR__ . '/response_component_factories/i18n/'];

$wgAutoloadClasses['RepromptFactory'] = __DIR__ . '/response_component_factories/RepromptFactory.php';
$wgMessagesDirs['RepromptFactory'] = [__DIR__ . '/response_component_factories/i18n/'];

$wgAutoloadClasses['VideoAppFactory'] = __DIR__ . '/response_component_factories/VideoAppFactory.php';
$wgMessagesDirs['VideoAppFactory'] = [__DIR__ . '/response_component_factories/i18n/'];

$wgAutoloadClasses['SimpleCardFactory'] = __DIR__ . '/response_component_factories/SimpleCardFactory.php';
$wgMessagesDirs['SimpleCardFactory'] = [__DIR__ . '/response_component_factories/i18n/'];

$wgAutoloadClasses['StandardCardFactory'] = __DIR__ . '/response_component_factories/StandardCardFactory.php';
$wgMessagesDirs['StandardCardFactory'] = [__DIR__ . '/response_component_factories/i18n/'];



