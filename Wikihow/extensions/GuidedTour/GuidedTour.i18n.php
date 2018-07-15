<?php
/**
 * Internationalisation file for Guided Tour extension.
 *
 * @file
 * @author Terry Chay tchay@wikimedia.org
 * @author Matthew Flaschen mflaschen@wikimedia.org
 * @author Luke Welling lwelling@wikimedia.org
 * @ingroup Extensions
 */
$messages = array();

$messages['en'] = array(
	'guidedtour-desc' => 'Allows pages to provide a popup guided tour to assist new users',
	'guidedtour-help-url' => 'Help:Guided tours',
	'guidedtour-help-guider-url' => 'Help:Guided tours/guider',
	'guidedtour-custom.css' => '/* Custom CSS for the GuidedTour extension. */',

	// Messages useful for more than one tour
	'guidedtour-next-button' => 'Next',
	'guidedtour-okay-button' => 'Okay',

	// Messages for specific tours.  These should be namespaced as
	// guidedtour-tour-specifictourname-message-name

	// wikiHow firstedit
	'guidedtour-tour-fe-initial-title' => 'Do you see a way to improve this article?',
	'guidedtour-tour-fe-initial-description' => 'Editing is easy. Press the "Edit" button to get started.',
	'guidedtour-tour-fe-editing-title' => 'Just dive in and make changes!',
	'guidedtour-tour-fe-editing-description' => 'Expanding the article and adding details helps the article grow and improve. By the way, a # will make a numbered step and a * will make a bullet point.',
	'guidedtour-tour-fe-preview-title' => 'Preview your changes (optional)',
	'guidedtour-tour-fe-preview-description' => 'Clicking "Preview" allows you to review your changes before they go live.',
	'guidedtour-tour-fe-summary-title' => '(Optional) Summarize your edit ',
	'guidedtour-tour-fe-summary-description' => 'Helping other editors understand the goal of your edit improves collaboration. You might write "fixed a spelling error",  "added details" or even "just testing out editing here."',
	'guidedtour-tour-fe-save-title' => 'You\'re almost done!',
	'guidedtour-tour-fe-save-description' => 'When you\'re ready, press "Publish" to submit your changes.',
	'guidedtour-tour-fe-end-title' => 'Congratulations on your first edit!',
	'guidedtour-tour-fe-end-description' => 'You are helping us teach everyone on the planet how to do anything! [https://ssl.wikihow.com/index.php?title=Special:UserLogin&type=signup Sign up] to create an account and get access to more ways to participate. [https://ssl.wikihow.com/index.php?title=Special:UserLogin&type=signup Click here to get started]',

	// wikiHow RC Patrol
	'guidedtour-tour-rc-initial-title' => "Welcome to Recent Changes Patrol!",
	'guidedtour-tour-rc-initial-description' => "This page is crucial to wikiHow’s quality control. Here all edits get approved or rejected. We’ll teach you how to patrol!",
	'guidedtour-tour-rc-review-title' => "Look over this change.",
	'guidedtour-tour-rc-review-description' => "The new version of the article is on the right-hand side. The old version is on the left. Can you see which version is better?",
	'guidedtour-tour-rc-rollback-title' => "The old version is better.",
	'guidedtour-tour-rc-rollback-description' => 'Press “Rollback” to reject the edit and restore the old version.',
	'guidedtour-tour-rc-patrolled-first-title' => "Look over this change.",
	'guidedtour-tour-rc-patrolled-first-description' => "The green highlighting and red words show where content has changed. The version on the right has new steps, new tips, and improved writing.",
	'guidedtour-tour-rc-patrolled-title' => "The new version is better.",
	'guidedtour-tour-rc-patrolled-description' => 'To approve the edit, press "Mark as Patrolled".',
	'guidedtour-tour-rc-talk-first-title' => "This edit is on a Talk page.",
	'guidedtour-tour-rc-talk-first-description' => 'You can tell because “User_talk” is displayed in lieu of an article title.  A talk page edit is a usually a message being sent from one editor to another.',
	'guidedtour-tour-rc-talk-title' => "You can approve most talk page messages.",
	'guidedtour-tour-rc-talk-description' => 'As long as any Talk or Discussion message isn\'t abusive or "spammy", you can mark it as patrolled. Go ahead!',
	'guidedtour-tour-rc-driveby-first-title' => "Look over this change.",
	'guidedtour-tour-rc-driveby-first-description' => 'Here somebody has just added a link.  Even if this is a useful website, our policy is to remove “drive by” links when they are added by an editor who hasn’t improved the page.',
	'guidedtour-tour-rc-driveby-title' => "Lets remove the link.",
	'guidedtour-tour-rc-driveby-description' => "Since this edit added a link without making real improvements to the page, it’s best to undo it altogether.",
	'guidedtour-tour-rc-end-title' => "Ok, you are now ready to Patrol on your own!",
	'guidedtour-tour-rc-end-description' => 'When you find an edit you aren’t sure about, just press “Skip”. And, if you have questions, just ask on the [[wikiHow_talk:Help-Team|Help Page]]!',
	// Talk Page
	'guidedtour-tour-talk-initial-title' => 'Welcome to your Talk Page!',
	'guidedtour-tour-talk-initial-description' => 'This is where you will be notified about messages that other editors leave for you on wikiHow.',
	'guidedtour-tour-talk-reply-title' => "You Have Received a Message!",
	'guidedtour-tour-talk-reply-description' => 'If you want to reply, click on the "Reply" link underneath the message.',

	// Community Dashboard
	'guidedtour-tour-dashboard-initial' => "Welcome! This is wikiHow’s Community Dashboard.",
	'guidedtour-tour-dashboard-description' => "Here you'll find a bunch of different tools you can use to contribute to wikiHow. A bad weather report means that a particular activity needs the most help right now.",
	'guidedtour-tour-dashboard-tipspatrol-title' => "This is Tips Patrol.",
	'guidedtour-tour-dashboard-tipspatrol-description' => "Tips patrol allows you to review tips that others have submitted. You can easily approve, edit, or delete tips directly from this tool with a few keystrokes.",
	'guidedtour-tour-dashboard-rc-title' => "Here you can Patrol Recent Changes.",
	'guidedtour-tour-dashboard-rc-description' => "Every edit on wikiHow is reviewed to make sure it improves the article. Recent Changes Patrol is where you can help with that!",
	'guidedtour-tour-dashboard-methodguardian-title' => "This is the Method Guardian.",
	'guidedtour-tour-dashboard-methodguardian-description' => "In Method Guardian, you can review new methods that others have submitted for articles. If they contain good advice, send them on to editing so they can be added to the article.",
	'guidedtour-tour-dashboard-spelling-title' => "This is the Spell Checker.",
	'guidedtour-tour-dashboard-spelling-description' => "Our spell checking tool allows you to quickly fix spelling errors in any of our 150,000+ articles. Fixing spelling makes articles higher quality and better for readers.",
	'guidedtour-tour-dashboard-answerrequests-title' => "Here you can Answer Requests.",
	'guidedtour-tour-dashboard-answerrequests-description' => "Help others by starting a new article on a topic that another user has requested. When you complete the article, the requestor will receive an email letting them know the article has been written.",
	'guidedtour-tour-dashboard-end-title' => "That’s it. Now it’s your turn to try!",
	'guidedtour-tour-dashboard-end-description' => 'Click on “Start” on any of these options to give it a go. Every edit helps!',
	'guidedtour-tour-dashboard-answerquestions-title' => "Answer questions from people like you!",
	'guidedtour-tour-dashboard-answerquestions-description' => "Many people submit questions related to what they read on wikiHow. Maybe you have the answers! Help someone by tackling their questions on topics you know well.",
	'guidedtour-tour-dashboard-editbytopic-title' => "Try out Topic Greenhouse.",
	'guidedtour-tour-dashboard-editbytopic-description' => "Help young articles grow! Choose topics you know about, and the Topic Greenhouse will find matching articles in need of expansion. When you find one you can improve, you can write and publish your contributions right from this tool.",

	// test
	'guidedtour-tour-test-testing' => 'Testing',
	'guidedtour-tour-test-test-description' => 'This is a test of the description. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Test callouts',
	'guidedtour-tour-test-portal-description' => 'This is the {{int:portal}} page.',
	'guidedtour-tour-test-mediawiki-parse' => 'Test MediaWiki parse',
	// TODO: This is unused since it was removed from the main code for performance reasons.
	// It is being kept for now so it can be used if https://bugzilla.wikimedia.org/show_bug.cgi?id=25349 is fixed.
	'guidedtour-tour-test-wikitext-description' => 'A guider in your on-wiki tour can contain wikitext using onShow and parseDescription. Use it to create a wikilink to the [[{{MediaWiki:Guidedtour-help-url}}|Guided tours documentation]]. Or an external link [https://github.com/tychay/mwgadget.GuidedTour to GitHub], for instance.',
	'guidedtour-tour-test-description-page' => 'Test MediaWiki description pages',
	'guidedtour-tour-test-go-description-page' => 'Go to description page',
	'guidedtour-tour-test-launch-tour' => 'Test launch tour',
	'guidedtour-tour-test-launch-tour-description' => 'Guiders can launch other guided tours. Pretty cool, huh?',
	'guidedtour-tour-test-launch-using-tours' => 'Launch a tour on using tours',

	// firstedit (wiktext version)
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Ready}} to edit?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Click the "{{int:vector-view-edit}}" button to make your changes.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Edit just a section',
	'guidedtour-tour-firstedit-edit-section-description' => 'There are "{{int:editsection}}" links for each major section in a page, so you can focus on just that part.',
	'guidedtour-tour-firstedit-preview-title' => 'Preview your changes (optional)',
	'guidedtour-tour-firstedit-preview-description' => 'Clicking "{{int:showpreview}}" allows you to check what the page will look like with your changes. Just don\'t forget to save!',
	'guidedtour-tour-firstedit-save-title' => 'You\'re almost done!',
	'guidedtour-tour-firstedit-save-description' => 'When you\'re ready, clicking "{{int:savearticle}}" will make your changes visible for everyone.',

	'guidedtour-tour-firsteditve-edit-page-description' => 'Click the "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" button to make your changes.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'There are "{{int:editsection}} {{int:visualeditor-beta-appendix}}" links for each major section in a page, so you can focus on just that part.',
	'guidedtour-tour-firsteditve-save-description' => 'When you\'re ready, clicking "{{int:visualeditor-toolbar-savedialog}}" will make your changes visible for everyone.',
);

/** Message documentation (Message documentation)
 * @author Mattflaschen
 * @author Shirayuki
 * @author Tgr
 */
$messages['qqq'] = array(
	'guidedtour-desc' => '{{desc|name=Guided Tour|url=http://www.mediawiki.org/wiki/Extension:GuidedTour}}',
	'guidedtour-help-url' => 'Main page for GuidedTour documentation',
	'guidedtour-help-guider-url' => 'Brief GuidedTour documentation page that a tour step can transclude.  Used in the test tour.',
	'guidedtour-custom.css' => '{{Optional}}
Custom CSS for the GuidedTour extension.  Empty by default.',
	'guidedtour-next-button' => 'Text for moving to next step of guided tour.
{{Identical|Next}}',
	'guidedtour-okay-button' => 'Main action button text.  Will dismiss single tour step (ending the tour if this is the last step) or do another tour-defined action.
{{Identical|OK}}',
	'guidedtour-tour-test-testing' => 'Title of first step in test tour',
	'guidedtour-tour-test-test-description' => 'Sample description to show how it is used.
{{doc-important|Do not translate the Latin "Lorem ipsum dolor sit!".}}',
	'guidedtour-tour-test-callouts' => 'Title of second step in test tour, introducing callouts',
	'guidedtour-tour-test-portal-description' => 'Description of second step in test tour.

It will be pointing to the page link given at {{msg-mw|Portal-url}}, in the toolbox.

Refers to {{msg-mw|Portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Description of third step in test tour',
	'guidedtour-tour-test-wikitext-description' => '{{doc-important|Do not translate "<code>onShow</code>" or "<code>parseDescription</code>", because they are JavaScript method names.}}
Title of third step in test tour.

Don\'t be concerned if [[{{MediaWiki:Guidedtour-help-url}}]] does not yet exist.

Refers to {{msg-mw|Guidedtour-help-url}}.',
	'guidedtour-tour-test-description-page' => 'Title of fourth step in test tour',
	'guidedtour-tour-test-go-description-page' => 'Text of the button pointing to [[{{MediaWiki:Guidedtour-help-url}}]]',
	'guidedtour-tour-test-launch-tour' => 'Title of fifth step in test tour',
	'guidedtour-tour-test-launch-tour-description' => 'Description of fifth step in test tour',
	'guidedtour-tour-test-launch-using-tours' => 'Button text for launching a tour on making tours',
	'guidedtour-tour-firstedit-edit-page-title' => 'Title of the step of the firstedit tour pointing to the main edit button',
	'guidedtour-tour-firstedit-edit-page-description' => 'Description of the step of the firstedit tour pointing to the main edit button, if VisualEditor is not installed.

See also:
* {{msg-mw|Guidedtour-tour-firstedit-edit-page-visualeditor-description}}
* {{msg-mw|Vector-view-edit}}',
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => 'Description of the step of the firstedit tour pointing to the main edit button, if VisualEditor is installed.

See also:
* {{msg-mw|Guidedtour-tour-firstedit-edit-page-description}}
* {{msg-mw|Visualeditor-ca-editsource}}',
	'guidedtour-tour-firstedit-edit-section-title' => 'Title of the step of the firstedit tour pointing to the section edit button',
	'guidedtour-tour-firstedit-edit-section-description' => 'Description of the step of the firstedit tour pointing to the section edit button, if VisualEditor is not installed

See also:
* {{msg-mw|editsection}}',
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => 'Description of the step of the firstedit tour pointing to the section edit button, if VisualEditor is installed

See also:
* {{msg-mw|visualeditor-ca-editsource-section}}',
	'guidedtour-tour-firstedit-preview-title' => 'Title of step explaining preview.

The body for this title is {{msg-mw|Guidedtour-tour-firstedit-preview-description}}.',
	'guidedtour-tour-firstedit-preview-description' => 'Description of step explaining preview

See also:
* {{msg-mw|showpreview}}',
	'guidedtour-tour-firstedit-save-title' => 'Title of step explaining saving an edit',
	'guidedtour-tour-firstedit-save-description' => 'Description of step explaining how to save

See also:
* {{msg-mw|savearticle}}',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Description of the step of the firsteditve tour pointing to the VisualEditor edit button.

Refers to:
* {{msg-mw|Vector-view-edit}}
* {{msg-mw|Visualeditor-beta-appendix}}',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Description of the step of the firsteditve tour pointing to the VE section edit button

Refers to:
* {{msg-mw|Editsection}}
* {{msg-mw|Visualeditor-beta-appendix}}',
	'guidedtour-tour-firsteditve-save-description' => 'Description of step of the firsteditve tour explaining how to save in VisualEditor.

Refers to {{msg-mw|Visualeditor-toolbar-savedialog}}.',
);

/** Arabic (العربية)
 * @author Asaifm
 * @author Ciphers
 */
$messages['ar'] = array(
	'guidedtour-desc' => 'السماح للصفحات بعرض رسائل جولات إرشادية لمساعدة المستخدمين الجدد',
	'guidedtour-help-url' => 'مساعدة:جولات إرشادية', # Fuzzy
	'guidedtour-help-guider-url' => 'مساعدة:جولات إرشادية/مرشد',
	'guidedtour-next-button' => 'التالي',
	'guidedtour-okay-button' => 'حسناً',
	'guidedtour-tour-test-testing' => 'تجربة',
	'guidedtour-tour-test-test-description' => 'هذه تجربة للوصف. أبجد هوز حطي كلمن!',
	'guidedtour-tour-test-callouts' => 'شرح التجربة',
	'guidedtour-tour-test-portal-description' => 'هذه هي صفحة {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'جرب طريقة إعراب ميديا ويكي',
	'guidedtour-tour-test-description-page' => 'جرب صفحات شرح ميديا ويكي',
	'guidedtour-tour-test-go-description-page' => 'اذهب إلى صفحة الشرح',
	'guidedtour-tour-test-launch-tour' => 'جرب جولة إفتتاحية',
	'guidedtour-tour-test-launch-tour-description' => 'من الممكن للمرشدين إطلاق المزيد من الجولات الإرشادية. أليس ذلك رائعا؟',
	'guidedtour-tour-test-launch-using-tours' => 'إطلاق جولة حول استخدام الجولات',
	'guidedtour-tour-firstedit-edit-page-title' => 'أمستعد للتحرير؟',
	'guidedtour-tour-firstedit-edit-page-description' => 'إضغط على زر "{{int:vector-view-edit}}" لإضافة تغييراتك.',
	'guidedtour-tour-firstedit-edit-section-title' => 'حرر قسم فقط',
	'guidedtour-tour-firstedit-edit-section-description' => 'هناك روابط "{{int:editsection}}" لكل قسم رئيس في الصفحة لكي تتمكن من التركيز عليه.',
	'guidedtour-tour-firstedit-preview-title' => 'معاينة تغييراتك (اختياري)',
	'guidedtour-tour-firstedit-preview-description' => 'يمكنك معاينة شكل الصفحة عند الضغط على "{{int:showpreview}}". لا تنسى النقر على زر الحفظ عند غنتهائك من المعاينة!',
	'guidedtour-tour-firstedit-save-title' => 'أنت على وشك الانتهاء!',
	'guidedtour-tour-firstedit-save-description' => 'عند انتهائك قم بالضغط على "{{int:savearticle}}" كي تظهر تغييراتك للجميع.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'إضغط على زر  "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" لحفظ تغييراتك.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'هناك روابط  "{{int:editsection}} {{int:visualeditor-beta-appendix}}" لكل قسم رئيس في الصفحة لكي تتمكن من التركيز عليه.',
	'guidedtour-tour-firsteditve-save-description' => 'عند انتهائك قم بالضغط على "{{int:visualeditor-toolbar-savedialog}}" كي تظهر تغييراتك للجميع.',
);

/** Asturian (asturianu)
 * @author Xuacu
 */
$messages['ast'] = array(
	'guidedtour-desc' => "Permite que les páxines ufran una visita guiada p'ayudar a los usuarios nuevos",
	'guidedtour-help-url' => 'Help:Visites guiaes',
	'guidedtour-help-guider-url' => 'Help:Visites guiaes/guía',
	'guidedtour-next-button' => 'Siguiente',
	'guidedtour-okay-button' => 'Aceutar',
	'guidedtour-tour-test-testing' => 'Probar',
	'guidedtour-tour-test-test-description' => 'Esto ye una prueba de la descripción. ¡Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Probar les llamaes de salida',
	'guidedtour-tour-test-portal-description' => 'Esta ye la páxina del {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => "Probar l'analís de MediaWiki",
	'guidedtour-tour-test-wikitext-description' => 'Un guía de la visita de la wiki pue contener testu wiki usando onShow y parseDescription. Uselo pa crear un enllaz a la [[{{MediaWiki:Guidedtour-help-url}}|documentación de les visites guiaes]]. O un enllaz esternu [https://github.com/tychay/mwgadget.GuidedTour a GitHub], por exemplu.',
	'guidedtour-tour-test-description-page' => 'Probar les páxines de descripción de MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Dir a la páxina de descripción',
	'guidedtour-tour-test-launch-tour' => 'Probar el llanzamientu de visites',
	'guidedtour-tour-test-launch-tour-description' => 'Los guíes puen llanzar otres visites guiaes. Prestoso, ¿acuéi?',
	'guidedtour-tour-test-launch-using-tours' => "Llanzar una visita sobro l'usu de les visites",
	'guidedtour-tour-firstedit-edit-page-title' => '¿Preparáu pa editar?',
	'guidedtour-tour-firstedit-edit-page-description' => "Calque nel botón '{{int:vector-view-edit}}' pa facer los cambios.",
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => "Calque nel botón '{{int:visualeditor-ca-editsource}}' pa facer los cambios.",
	'guidedtour-tour-firstedit-edit-section-title' => 'Editar sólo una seición',
	'guidedtour-tour-firstedit-edit-section-description' => "Hai enllaces «{{int:editsection}}» pa cada seición principal d'una páxina, de mou que pue centrase sólo nesa parte.",
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => "Hai enllaces «{{int:visualeditor-ca-editsource-section}}» pa cada seición principal d'una páxina, de mou que pue centrase sólo nesa parte.",
	'guidedtour-tour-firstedit-preview-title' => 'Vista previa de los cambios (opcional)',
	'guidedtour-tour-firstedit-preview-description' => "Si calca '{{int:showpreview}}' podrá comprobar como se verá la páxina colos cambios. ¡Pero nun escaeza guardala!",
	'guidedtour-tour-firstedit-save-title' => '¡Yá casi ta fecho!',
	'guidedtour-tour-firstedit-save-description' => "Cuando tea preparáu, en calcando '{{int:savearticle}}', los cambios sedrán visibles pa toos.",
	'guidedtour-tour-firsteditve-edit-page-description' => 'Calque nel botón "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" pa facer los sos cambios.',
	'guidedtour-tour-firsteditve-edit-section-description' => "Hai enllaces «{{int:editsection}} {{int:visualeditor-beta-appendix}}» pa cada seición principal d'una páxina, de mou que pue centrase sólo nesa parte.",
	'guidedtour-tour-firsteditve-save-description' => 'Cuando tea preparáu, en calcando "{{int:visualeditor-toolbar-savedialog}}" los cambios tarán visibles pa toos.',
);

/** Belarusian (беларуская)
 * @author Wizardist
 */
$messages['be'] = array(
	'guidedtour-desc' => 'Дазваляе паказваць на старонках усплыўныя падказкі для дапамогі новым удзельнікам',
	'guidedtour-help-url' => 'Help:Экскурсіі па сайце',
	'guidedtour-help-guider-url' => 'Help:Экскурсіі па сайце/даведка',
	'guidedtour-okay-button' => 'Далей',
	'guidedtour-tour-test-testing' => 'Тэставанне',
	'guidedtour-tour-test-test-description' => 'Гэта тэст апісання. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Тэст вынятак',
	'guidedtour-tour-test-portal-description' => 'Гэта старонка парталу супольнасці.', # Fuzzy
	'guidedtour-tour-test-mediawiki-parse' => 'Тэст сінтаксісу MediaWiki',
	'guidedtour-tour-test-wikitext-description' => 'Выняткі ў экскурсіях па сайце могуць змяшчаць вікітэкст, дзякуючы onShow і parseDescription. Іх можна выкарыстаць, каб стварыць спасылку на [[{{MediaWiki:Guidedtour-help-url}}|дакументацыю па экскурсіях]], або знешнюю спасылку на [https://github.com/tychay/mwgadget.GuidedTour GitHub].',
	'guidedtour-tour-test-description-page' => 'Тэст старонак апісання MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Перайсці на старонку апісання',
	'guidedtour-tour-test-launch-tour' => 'Тэставы запуск экскурсіі',
	'guidedtour-tour-test-launch-tour-description' => 'У часе адной экскурсіі можна пачаць і іншыя. Крута, га?',
	'guidedtour-tour-test-launch-using-tours' => 'Пачаць экскурсію пра выкарыстанне экскурсіяў',
);

/** Belarusian (Taraškievica orthography) (беларуская (тарашкевіца)‎)
 * @author Wizardist
 */
$messages['be-tarask'] = array(
	'guidedtour-desc' => 'Дазваляе паказваць на старонках усплыўныя падказкі для дапамогі новым удзельнікам',
	'guidedtour-help-url' => 'Help:Экскурсіі па сайце',
	'guidedtour-help-guider-url' => 'Help:Экскурсіі па сайце/даведка',
	'guidedtour-custom.css' => '/* Дадатковы CSS для пашырэньня GuidedTour. */',
	'guidedtour-next-button' => 'Далей',
	'guidedtour-okay-button' => 'Далей',
	'guidedtour-tour-test-testing' => 'Тэставаньне',
	'guidedtour-tour-test-test-description' => 'Гэта тэст апісаньня. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Тэст вынятак',
	'guidedtour-tour-test-portal-description' => 'Гэта старонка {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Тэст сынтакса MediaWiki',
	'guidedtour-tour-test-wikitext-description' => 'Выняткі ў экскурсіях па сайце могуць зьмяшчаць вікітэкст, дзякуючы onShow і parseDescription. Іх можна выкарыстаць, каб стварыць спасылку на [[{{MediaWiki:Guidedtour-help-url}}|дакумэнтацыю па экскурсіях]], або вонкавую спасылку на [https://github.com/tychay/mwgadget.GuidedTour GitHub].',
	'guidedtour-tour-test-description-page' => 'Тэст старонак апісаньня MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Перайсьці на старонку апісаньня',
	'guidedtour-tour-test-launch-tour' => 'Тэставы запуск экскурсіі',
	'guidedtour-tour-test-launch-tour-description' => 'У часе адной экскурсіі можна пачаць і іншыя. Крута, га?',
	'guidedtour-tour-test-launch-using-tours' => 'Пачаць экскурсію пра выкарыстаньне экскурсіяў',
);

/** Bengali (বাংলা)
 * @author Aftab1995
 * @author Bellayet
 * @author Nasir8891
 */
$messages['bn'] = array(
	'guidedtour-desc' => 'নতুন ব্যবহারকারীদের সহযোগিতার জন্য পাতায় একটি ভাসমান নির্দেশনামূলক ভ্রমণের সুযোগ করে দেয়।',
	'guidedtour-help-url' => 'Help:নির্দেশনামূলক ভ্রমণ',
	'guidedtour-help-guider-url' => 'Help:নির্দেশনামূলক ভ্রমণ/নির্দেশক',
	'guidedtour-next-button' => 'পরবর্তী',
	'guidedtour-okay-button' => 'ঠিক আছে',
	'guidedtour-tour-test-testing' => 'পরীক্ষণ',
	'guidedtour-tour-test-test-description' => 'বর্ণনার একটি উদাহারণ. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'পরীক্ষামূলক কলআউট',
	'guidedtour-tour-test-portal-description' => 'এটি {{int:portal}} পাতা।',
	'guidedtour-tour-test-mediawiki-parse' => 'মিডিয়াউইকি পার্স পরীক্ষা',
	'guidedtour-tour-test-description-page' => 'পরীক্ষামূলক মিডিয়াউইকি বিবরণ পাতা',
	'guidedtour-tour-test-go-description-page' => 'বিবরণ পাতায় যাও',
	'guidedtour-tour-test-launch-tour' => 'পরীক্ষামূলক ভ্রমণ আরম্ভ করুন',
	'guidedtour-tour-test-launch-tour-description' => 'গাইডগণ আরও নতুন নতুন ভ্রমনের আয়োজন করতে পারেন।',
	'guidedtour-tour-test-launch-using-tours' => 'ট্যুর অপশন ব্যবহার করে নতুন ট্যুর আয়োজন করুন',
	'guidedtour-tour-firstedit-edit-page-title' => 'সম্পাদনার জন্য {{GENDER:|প্রস্তুত}}?',
	'guidedtour-tour-firstedit-edit-page-description' => '"{{int:vector-view-edit}}" বাটনে ক্লিক করে আপানার পরিবর্তনগুলো সম্পন্ন করুন।',
	'guidedtour-tour-firstedit-edit-section-title' => 'একটি অনুচ্ছেদ সম্পাদনা করুন',
	'guidedtour-tour-firstedit-edit-section-description' => 'গুরুত্বপূর্ণ প্রতিটি অনুচ্ছেদের জন্য "{{int:editsection}}" লিংক রয়েছে, তাই আপনি নির্দিষ্ট অংশটি সরাসরি সম্পাদনা করতে পারবেন।',
	'guidedtour-tour-firstedit-preview-title' => 'পরিবর্তনের প্রাকদর্শন (ঐচ্ছিক)',
	'guidedtour-tour-firstedit-preview-description' => '"{{int:showpreview}}" বাটনে ক্লিক করে আপনার পরিবর্তনের পর পাতাটি কেমন দেখাবে সেটি জানতে পারবেন। সংরক্ষনের কথা ভুলবেন না!',
	'guidedtour-tour-firstedit-save-title' => 'আপনি প্রায় সম্পন্ন করেছেন!',
	'guidedtour-tour-firstedit-save-description' => 'আপনার কাজ সম্পন্ন হলে, "{{int:savearticle}}" বাটনে ক্লিক করুন এবং আপনার পরিবর্তনগুলো সকলে দেখতে পারবে।',
	'guidedtour-tour-firsteditve-edit-page-description' => 'পরিবর্তনের জন্য "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" বাটনে ক্লিক করুন।',
	'guidedtour-tour-firsteditve-edit-section-description' => 'গুরুত্বপূর্ণ প্রতিটি অনুচ্ছেদে "{{int:editsection}} {{int:visualeditor-beta-appendix}}" লিংক রয়েছে, এর ফলে আপনি সরাসরি অনুচ্ছেদগুলো সম্পাদনা করতে পারবেন।',
	'guidedtour-tour-firsteditve-save-description' => 'আপনার কাজ সম্পন্ন হলে, "{{int:visualeditor-toolbar-savedialog}}" বাটনে ক্লিক করুন এবং আপনার পরিবর্তনগুলো সকলে দেখতে পারবে।',
);

/** Breton (brezhoneg)
 * @author Fohanno
 * @author Y-M D
 */
$messages['br'] = array(
	'guidedtour-help-url' => 'Skoazell : Troiadoù heñchet', # Fuzzy
	'guidedtour-help-guider-url' => 'Skoazell : Troiadoù heñchet/heñcher',
	'guidedtour-next-button' => "War-lerc'h",
	'guidedtour-okay-button' => 'A-du',
	'guidedtour-tour-test-testing' => "Oc'h amprouiñ",
	'guidedtour-tour-test-portal-description' => 'An dra-mañ eo ar bajenn {{int:portal}}',
	'guidedtour-tour-test-description-page' => 'Amprouiñ ar pajennoù deskrivañ MediaWiki',
	'guidedtour-tour-test-go-description-page' => "Mont d'ar bajenn deskrivañ",
	'guidedtour-tour-test-launch-tour' => 'Amprouiñ an droiad lañsañ',
	'guidedtour-tour-test-launch-tour-description' => "Gallout a ra an heñcherien krouiñ troiadoù heñchet all. N'eo ket fall, geo ?",
	'guidedtour-tour-firstedit-edit-page-title' => 'Prest da aozañ ?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Klikit war ar bouton "{{int:vector-view-edit}}" evit ober ho kemmoù.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Aozañ ur rann hepken',
	'guidedtour-tour-firstedit-preview-title' => 'Rakwelet ho kemmoù (diret)',
	'guidedtour-tour-firstedit-save-title' => "Tost echu eo ganeoc'h !",
	'guidedtour-tour-firstedit-save-description' => 'Pa viot prest, klkit war "{{int:savearticle}}" hag e c\'hallo an holl gwelet ho kemmoù.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Klikit war ar bouton "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" evit ober ho kemmoù.',
	'guidedtour-tour-firsteditve-save-description' => 'Pa viot prest, klkit war "{{int:visualeditor-toolbar-savedialog}}" hag e c\'hallo an holl gwelet ho kemmoù.',
);

/** Bosnian (bosanski)
 * @author CERminator
 * @author Edinwiki
 */
$messages['bs'] = array(
	'guidedtour-desc' => 'Dozvoljava stranicama da koriste vodič sa skočnim prozorima da bi se pomoglo novim korisnicima',
	'guidedtour-help-url' => 'Help:Vodiči',
	'guidedtour-help-guider-url' => 'Pomoć:Vodiči/vodič',
	'guidedtour-next-button' => 'Sljedeće',
	'guidedtour-okay-button' => 'OK',
	'guidedtour-tour-test-testing' => 'Testiranje',
	'guidedtour-tour-test-test-description' => 'Ovo je test za opis stranice. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Test objašnjenja',
	'guidedtour-tour-test-portal-description' => 'Ovo je stranica {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Test MediaWiki parsiranje',
	'guidedtour-tour-test-description-page' => 'Test MediaWiki opis stranica',
	'guidedtour-tour-test-go-description-page' => 'Idi na opis stranice',
	'guidedtour-tour-test-launch-tour' => 'Test početak vodiča',
	'guidedtour-tour-test-launch-tour-description' => 'Vodiči mogu započeti druge vodiče. Zar nije zabavno?',
	'guidedtour-tour-test-launch-using-tours' => 'Započni vodič o korištenju vodiča',
	'guidedtour-tour-firstedit-edit-page-title' => 'Spremni za uređivanje?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Kliknite na dugme "{{int:vector-view-edit}}" da napravite vaše izmjene.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Uredi samo sekciju',
	'guidedtour-tour-firstedit-edit-section-description' => 'Postoje "{{int:editsection}}" veza za svaku glavnu sekciju na nekoj stranici, tako da se možete fokusirati samo na taj dio.',
	'guidedtour-tour-firstedit-preview-title' => 'Prikažite izgled vaših izmjena (nije obavezno)',
	'guidedtour-tour-firstedit-preview-description' => 'Kada kliknete na "{{int:showpreview}}" onda možete vidjeti kako će stranica izgledati sa vašim izmjenama. Samo nemojte zaboraviti da poslije sačuvate stranicu!',
	'guidedtour-tour-firstedit-save-title' => 'Skoro ste pri kraju!',
	'guidedtour-tour-firstedit-save-description' => 'Kada završite, možete kliknuti na "{{int:savearticle}}" da vaše izmjene budu vidljive za svakoga.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Kliknite na dugme "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" da napravite vaše izmjene.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Postoje "{{int:editsection}} {{int:visualeditor-beta-appendix}}" veza za svaku glavnu sekciju na nekoj stranici, tako da se možete fokusirati samo na taj dio.',
	'guidedtour-tour-firsteditve-save-description' => 'Kada završite, možete kliknuti na "{{int:visualeditor-toolbar-savedialog}}" da vaše izmjene budu vidljive za svakoga.',
);

/** Catalan (català)
 * @author QuimGil
 * @author Vriullop
 */
$messages['ca'] = array(
	'guidedtour-desc' => 'Permet incorporar una visita guiada a les pàgines per a ajudar nous usuaris.',
	'guidedtour-help-url' => 'Help:Visites guiades',
	'guidedtour-help-guider-url' => 'Help:Visites guiades/guia',
	'guidedtour-next-button' => 'Següent',
	'guidedtour-okay-button' => "D'acord",
	'guidedtour-tour-test-testing' => 'Proves',
	'guidedtour-tour-test-test-description' => 'Això és una prova de la descripció. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Prova les crides',
	'guidedtour-tour-test-portal-description' => 'Aquesta és la pàgina {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Prova el processament de MediaWiki',
	'guidedtour-tour-test-description-page' => 'Prova les pàgines de descripció de MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Vés a la pàgina de descripció',
	'guidedtour-tour-test-launch-tour' => "Prova l'inici de la visita",
	'guidedtour-tour-test-launch-tour-description' => 'Les guies poden iniciar altres visites guiades. Molt bo, eh?',
	'guidedtour-tour-test-launch-using-tours' => 'Inicia una visita guiada sobre visites guiades',
	'guidedtour-tour-firstedit-edit-page-title' => 'Disposat a editar?', # Fuzzy
	'guidedtour-tour-firstedit-edit-page-description' => "Cliqueu la pestanya '{{int:vector-view-edit}}' per fer els vostres canvis.",
	'guidedtour-tour-firstedit-edit-section-title' => "Modificació d'una secció",
	'guidedtour-tour-firstedit-edit-section-description' => 'Hi ha enllaços "{{int:editsection}}" per a cada secció en un article que permeten centrar-se només en una part.',
	'guidedtour-tour-firstedit-preview-title' => 'Previsualització opcional dels canvis',
	'guidedtour-tour-firstedit-preview-description' => "Si cliqueu a '{{int:showpreview}}' podreu comprovar com quedarà la pàgina amb els vostres canvis. En acabat, no us oblideu de desar!",
	'guidedtour-tour-firstedit-save-title' => 'Gairebé ja està!',
	'guidedtour-tour-firstedit-save-description' => "Quan ho tingueu a punt, cliqueu a '{{int:savearticle}}' i els vostres canvis ja seran visibles per a tothom.",
	'guidedtour-tour-firsteditve-edit-page-description' => 'Feu clic a «{{int:vector-view-edit}}» per a fer els vostres canvis.',
	'guidedtour-tour-firsteditve-edit-section-description' => "Disposeu d'enllaços «{{int:editsection}}» a cada secció principal, per a concentrar-vos només en aquesta part.",
	'guidedtour-tour-firsteditve-save-description' => 'Quan estigueu a punt, fent clic a "{{int:visualeditor-toolbar-savedialog}}" fareu els vostres canvis visibles per a tothom.',
);

/** Chechen (нохчийн)
 * @author Умар
 */
$messages['ce'] = array(
	'guidedtour-help-url' => 'Help:Хьехаран мураш',
	'guidedtour-help-guider-url' => 'Help:Хьехаран мураш/гойтурш',
	'guidedtour-next-button' => 'Кхин дӀа',
	'guidedtour-tour-test-testing' => 'Зер',
	'guidedtour-tour-test-test-description' => 'Цу Lorem ipsum dolor sit лаьцна хьажар!',
	'guidedtour-tour-test-callouts' => 'Хьажар зер',
	'guidedtour-tour-test-portal-description' => 'ХӀара агӀо ю {{int:portal}}.',
	'guidedtour-tour-test-description-page' => 'MediaWiki лаьцна агӀонаш зер',
	'guidedtour-tour-test-go-description-page' => 'Цунах лаьцнарг долу агӀонг гӀо',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Кийча дуй}} тадарш дан?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Хьай хийцам бан тӀетаӀе кнопка «{{int:vector-view-edit}}.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Дакъана бен хийцам ма бе',
	'guidedtour-tour-firstedit-edit-section-description' => 'Хьажораг «{{int:editsection}}» хӀора дакъана ю, цундела хьа йиш ю дакъа къастина тадан.',
	'guidedtour-tour-firstedit-save-title' => 'Ахьа долийнарг чакхдала гергга ду!',
	'guidedtour-tour-firstedit-save-description' => 'Хьой кичча хилча, тӀетаӀе «{{int:savearticle}}» хийцамаш массарна гуш хилийта.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Хьай хийцам бан тӀетаӀе кнопка «{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}».',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Хьажораг «{{int:editsection}} {{int:visualeditor-beta-appendix}}» хӀора дакъана ю, цундела хьа йиш ю дакъа къастина тадан.',
	'guidedtour-tour-firsteditve-save-description' => 'Хьой кичча хилча, тӀетаӀе «{{int:visualeditor-toolbar-savedialog}}» хийцамаш массарна гуш хилийта.',
);

/** Czech (čeština)
 * @author Mormegil
 * @author Utar
 * @author YjM
 */
$messages['cs'] = array(
	'guidedtour-desc' => 'Umožňuje stránkám poskytovat vyskakovacího průvodce pro pomoc novým uživatelům',
	'guidedtour-help-url' => 'Help:Prohlídky',
	'guidedtour-help-guider-url' => 'Help:Prohlídky/Průvodce',
	'guidedtour-next-button' => 'Další',
	'guidedtour-okay-button' => 'OK',
	'guidedtour-tour-test-testing' => 'Testování',
	'guidedtour-tour-test-test-description' => 'Toto je test popisu. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Testování popisku',
	'guidedtour-tour-test-portal-description' => 'Toto je stránka „{{int:portal}}“.',
	'guidedtour-tour-test-mediawiki-parse' => 'Testování parsování MediaWiki',
	'guidedtour-tour-test-description-page' => 'Testování dokumentačních stránek MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Přejděte na stránku s popisem',
	'guidedtour-tour-test-launch-tour' => 'Zkušební spuštění prohlídky',
	'guidedtour-tour-test-launch-tour-description' => 'Průvodce může spouštět další prohlídky. Dost dobrý, ne?',
	'guidedtour-tour-test-launch-using-tours' => 'Spustit prohlídku o používání prohlídek',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Připraven|Připravena|Připraveni}} editovat?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Pro provedení změn klikněte na tlačítko „{{int:vector-view-edit}}“.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Editovat pouze jednu sekci',
	'guidedtour-tour-firstedit-edit-section-description' => 'Pro každou sekci stránky existují odkazy  „{{int:editsection}}“, takže se můžete soustředit pouze na danou část.',
	'guidedtour-tour-firstedit-preview-title' => 'Prohlédněte si své změny (nepovinné)',
	'guidedtour-tour-firstedit-preview-description' => 'Kliknutím na „{{int:showpreview}}“ si můžete zkontrolovat, jak bude stránka vypadat po provedení změn. Jen ji pak nezapomeňte uložit!',
	'guidedtour-tour-firstedit-save-title' => 'Už jste skoro hotovi!',
	'guidedtour-tour-firstedit-save-description' => 'Až budete s editací hotovi, kliknutím na tlačítko „{{int:savearticle}}“ své změny zveřejníte.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Pro provedení změn klikněte na tlačítko „{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}“.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Pro každou sekci stránky existují odkazy  „{{int:editsection}} {{int:visualeditor-beta-appendix}}“, takže se můžete soustředit pouze na danou část.',
	'guidedtour-tour-firsteditve-save-description' => 'Až budete s editací hotovi, kliknutím na tlačítko „{{int:visualeditor-toolbar-savedialog}}“ své změny zveřejníte.',
);

/** German (Deutsch)
 * @author Kghbln
 * @author Metalhead64
 * @author Rillke
 */
$messages['de'] = array(
	'guidedtour-desc' => 'Ermöglicht Pop-up-gestützte Rundgänge zur Unterstützung neuer Benutzer',
	'guidedtour-help-url' => 'Help:Geführte Touren',
	'guidedtour-help-guider-url' => 'Help:Geführte Touren/Guider',
	'guidedtour-next-button' => 'Weiter',
	'guidedtour-okay-button' => 'Okay',
	'guidedtour-tour-test-testing' => 'Testen',
	'guidedtour-tour-test-test-description' => 'Dies ist ein Test der Beschreibung. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Legenden testen',
	'guidedtour-tour-test-portal-description' => 'Dies ist die Seite „{{int:portal}}“.',
	'guidedtour-tour-test-mediawiki-parse' => 'MediaWiki-Parser testen',
	'guidedtour-tour-test-wikitext-description' => 'Ein Guider in deiner Wikitour kann Wikitext durch Verwendung von onShow und parseDescription beinhalten. Benutze es, um beispielsweise einen Wikilink zur [[{{MediaWiki:Guidedtour-help-url}}|Dokumentation]] oder einen externen Link [https://github.com/tychay/mwgadget.GuidedTour zu GitHub] zu erstellen.',
	'guidedtour-tour-test-description-page' => 'MediaWiki-Beschreibungsseiten testen',
	'guidedtour-tour-test-go-description-page' => 'Gehe zur Beschreibungsseite',
	'guidedtour-tour-test-launch-tour' => 'Tourstart testen',
	'guidedtour-tour-test-launch-tour-description' => 'Guider können andere geführte Touren starten. Ziemlich cool, nicht wahr?',
	'guidedtour-tour-test-launch-using-tours' => 'Eine Tour durch Verwendung von Touren starten',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Bereit}} zum Bearbeiten?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Klicke auf „{{int:vector-view-edit}}“, um deine Änderungen durchzuführen.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Nur einen Abschnitt bearbeiten',
	'guidedtour-tour-firstedit-edit-section-description' => 'Es gibt „{{int:editsection}}“-Links für jeden Seitenabschnitt, so dass du dich nur auf diesen Teil fokussieren kannst.',
	'guidedtour-tour-firstedit-preview-title' => 'Vorschau deiner Änderungen (optional)',
	'guidedtour-tour-firstedit-preview-description' => 'Mit Klick auf „{{int:showpreview}}“ kannst du überprüfen, wie die Seite mit deinen Änderungen aussehen wird. Vergiss nicht zu speichern!',
	'guidedtour-tour-firstedit-save-title' => 'Du bist fast fertig!',
	'guidedtour-tour-firstedit-save-description' => 'Wenn du fertig bist, macht das Klicken auf „{{int:savearticle}}“ deine Änderungen für jeden sichtbar.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Klicke auf „{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}“, um deine Änderungen durchzuführen.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Für jeden Seitenabschnitt gibt es die Links „{{int:editsection}} {{int:visualeditor-beta-appendix}}“, so dass du dich nur auf diesen Teil konzentrieren kannst.',
	'guidedtour-tour-firsteditve-save-description' => 'Wenn du fertig bist, klicke auf „{{int:visualeditor-toolbar-savedialog}}“, um deine Änderungen für jeden sichtbar zu machen.',
);

/** Zazaki (Zazaki)
 * @author Gorizon
 */
$messages['diq'] = array(
	'guidedtour-next-button' => 'Bahdoyên',
	'guidedtour-okay-button' => 'Okay',
	'guidedtour-tour-test-testing' => 'Testing',
);

/** Greek (Ελληνικά)
 * @author Geraki
 * @author Nikosguard
 */
$messages['el'] = array(
	'guidedtour-desc' => 'Επιτρέπει στις σελίδες να παρέχουν μια αναδυόμενη ξενάγηση για να βοηθήσει τους νέους χρήστες',
	'guidedtour-help-url' => 'Βοήθεια:Ξενάγηση', # Fuzzy
	'guidedtour-help-guider-url' => 'Βοήθεια:Ξεναγήσεις/οδηγητής',
	'guidedtour-next-button' => 'Επόμενο',
	'guidedtour-okay-button' => 'Εντάξει',
	'guidedtour-tour-test-testing' => 'Δοκιμή',
	'guidedtour-tour-test-test-description' => 'Αυτή είναι μια δοκιμή της περιγραφής. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Δοκιμή επεξήγησης',
	'guidedtour-tour-test-portal-description' => 'Αυτή είναι η σελίδα {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Δοκιμή MediaWiki ανάλυσης',
	'guidedtour-tour-test-description-page' => 'Δοκιμή MediaWiki σελίδες περιγραφής',
	'guidedtour-tour-test-go-description-page' => 'Μεταβείτε στη σελίδα περιγραφής',
	'guidedtour-tour-test-launch-tour' => 'Δοκιμή έναρξης περιοδείας',
	'guidedtour-tour-test-launch-tour-description' => 'Οι οδηγητές μπορούν να ξεκινήσουν άλλες ξεναγήσεις. Αρκετά καλό, ε;',
	'guidedtour-tour-test-launch-using-tours' => 'Εκκίνηση μιας περιοδείας για τη χρήση περιοδειών',
	'guidedtour-tour-firstedit-edit-page-title' => 'Έτοιμοι να επεξεργαστείτε;',
	'guidedtour-tour-firstedit-edit-page-description' => 'Κάντε κλικ στο κουμπί "{{int:vector-view-edit}}" για να κάνετε τις αλλαγές σας.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Επεξεργαστείτε μόνο ένα τμήμα',
	'guidedtour-tour-firstedit-edit-section-description' => 'Υπάρχουν σύνδεσμοι "{{int:editsection}}" για κάθε σημαντικό τμήμα σε μια σελίδα, ώστε να μπορείτε να εστιάσετε μόνο σε αυτό το κομμάτι.',
	'guidedtour-tour-firstedit-preview-title' => 'Προεπισκόπηση των αλλαγών σας (προαιρετικό)',
	'guidedtour-tour-firstedit-preview-description' => 'Κάνοντας κλικ στο "{{int:showpreview}}" σας επιτρέπει να ελέγχετε πως θα μοιάζει η σελίδα με τις αλλαγές σας. Απλά μην ξεχάσετε να αποθηκεύσετε!',
	'guidedtour-tour-firstedit-save-title' => 'Είστε σχεδόν έτοιμοι!',
	'guidedtour-tour-firstedit-save-description' => 'Όταν είστε έτοιμοι, κάνοντας κλικ στο κουμπί "{{int:savearticle}}" θα κάνει τις αλλαγές σας ορατές σε όλους.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Κάντε κλικ στο κουμπί "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" για να κάνετε τις αλλαγές σας.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Υπάρχουν σύνδεσμοι "{{int:editsection}} {{int:visualeditor-beta-appendix}}" για κάθε σημαντική ενότητα σε μια σελίδα, ώστε να μπορείτε να εστιάσετε μόνο σε αυτό το κομμάτι.',
	'guidedtour-tour-firsteditve-save-description' => 'Όταν είστε έτοιμοι, κάνοντας κλικ στο κουμπί  "{{int:visualeditor-toolbar-savedialog}}" θα κάνει τις αλλαγές σας ορατές σε όλους.',
);

/** Spanish (español)
 * @author Benfutbol10
 * @author Ciencia Al Poder
 * @author Fitoschido
 * @author QuimGil
 */
$messages['es'] = array(
	'guidedtour-desc' => 'Permite a las páginas ofrecer una visita guiada para ayudar a nuevos usuarios',
	'guidedtour-help-url' => 'Help:Visitas guiadas',
	'guidedtour-help-guider-url' => 'Help:Visitas guiadas/guía',
	'guidedtour-next-button' => 'Siguiente',
	'guidedtour-okay-button' => 'Aceptar',
	'guidedtour-tour-test-testing' => 'Pruebas',
	'guidedtour-tour-test-test-description' => 'Esta es una prueba de la descripción. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Prueba de llamadas',
	'guidedtour-tour-test-portal-description' => 'Esta es la página de {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Prueba del procesamiento de MediaWiki',
	'guidedtour-tour-test-description-page' => 'Probar las páginas de descripción de MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Ir a la página de descripción',
	'guidedtour-tour-test-launch-tour' => 'Prueba de inicio de la visita guiada',
	'guidedtour-tour-test-launch-tour-description' => 'Las guías pueden iniciar otras visitas guiada. Muy bueno, ¿eh?',
	'guidedtour-tour-test-launch-using-tours' => 'Iniciar una visita guiada sobre visitas guiadas',
	'guidedtour-tour-firstedit-edit-page-title' => '¿Te animas a editar?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Haz clic en el botón de "{{int:vector-view-edit}}" para realizar tus cambios.',
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => 'Haz clic en el botón de "{{int:visualeditor-ca-editsource}}" para realizar tus cambios.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Editar solo una sección',
	'guidedtour-tour-firstedit-edit-section-description' => 'Existen enlaces de "{{int:editsection}}" en cada sección de la página, para que puedes centrarte solo en esa parte.',
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => 'Existen enlaces de "{{int:visualeditor-ca-editsource-section}}" en cada sección de la página, para que puedes centrarte solo en esa parte.',
	'guidedtour-tour-firstedit-preview-title' => 'Previsualiza tus cambios (opcional)',
	'guidedtour-tour-firstedit-preview-description' => 'Cliqueando en "{{int:showpreview}}" te permite ver como se verá la página con tus cambios. ¡No olvides guardarlos!',
	'guidedtour-tour-firstedit-save-title' => '¡Ya casi has terminado!',
	'guidedtour-tour-firstedit-save-description' => 'Cuando termines, pulsa en «{{int:savearticle}}» para que todos puedan ver tus cambios.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Haz clic en "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" para realizar tus cambios.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Existen enlaces de "{{int:editsection}} {{int:visualeditor-beta-appendix}}" en cada sección de la página, para que puedes centrarte solo en esa parte.',
	'guidedtour-tour-firsteditve-save-description' => 'Cuándo estés listo, haz clic en "{{int:visualeditor-toolbar-savedialog}}" para hacer que tus cambios sean visibles para todos',
);

/** Basque (euskara)
 * @author An13sa
 */
$messages['eu'] = array(
	'guidedtour-okay-button' => 'Ados',
);

/** Persian (فارسی)
 * @author Ebraminio
 * @author Omidh
 */
$messages['fa'] = array(
	'guidedtour-desc' => 'اجازه می‌دهند صفحه‌ها یک پاپ‌آپ راهنمای تور برای راهنمایی کاربران جدید فراهم کنند',
	'guidedtour-help-url' => 'Help:‌تور‌های راهنما',
	'guidedtour-help-guider-url' => 'راهنما: تور‌های راهنما/راهنما‌ها',
	'guidedtour-next-button' => 'بعدی',
	'guidedtour-okay-button' => 'باشد',
	'guidedtour-tour-test-testing' => 'آزمایش',
	'guidedtour-tour-test-test-description' => 'این یک آزمایش برای توضیحات است. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'آزمایش راهنما',
	'guidedtour-tour-test-portal-description' => 'این صفحهٔ {{int:portal}} است.',
	'guidedtour-tour-test-mediawiki-parse' => 'آزمون تجزیه‌گر مدیاویکی',
	'guidedtour-tour-test-description-page' => 'آزمایش صفحه‌های توضیحات مدیاویکی',
	'guidedtour-tour-test-go-description-page' => 'رفتن به صفحهٔ توضیحات',
	'guidedtour-tour-test-launch-tour' => 'آزمایش راه‌اندازی تور',
	'guidedtour-tour-test-launch-tour-description' => 'راهنمایان می‌توانند تور‌های دیگری نیز اجرا کنند. خیلی جالب هست، نه؟',
	'guidedtour-tour-test-launch-using-tours' => 'راه‌اندازی یک تور در استفاده از تورها',
	'guidedtour-tour-firstedit-edit-page-title' => 'برای ویرایش {{GENDER:|آماده‌اید}}؟',
	'guidedtour-tour-firstedit-edit-page-description' => 'برای ذخیرهٔ تغییراتتان بر روی دکمهٔ «{{int:vector-view-edit}}» کلیک کنید.',
	'guidedtour-tour-firstedit-edit-section-title' => 'فقط ویرایش یک بخش',
	'guidedtour-tour-firstedit-edit-section-description' => 'پیوندها «{{int:editsection}}» برای هر بحش اصلی در صفحه وجود دارد، پس شما می‌تواند فقط به آن بحش تمرکز کنید.',
	'guidedtour-tour-firstedit-preview-title' => 'پیش‌نمایش تغییرات شما (اختیاری)',
	'guidedtour-tour-firstedit-preview-description' => 'کلیک‌کردن بر «{{int:showpreview}}» اجازه می‌دهد که شما بررسی کنید چه صفحه‌هایی شبیه تغییراتتان خواهند بود. فقط دکمهٔ ذخیره را فراموش نکنید!',
	'guidedtour-tour-firstedit-save-title' => 'تقریباً تمام است!',
	'guidedtour-tour-firstedit-save-description' => 'هنگامی که انجام دادید، کلیک بر «{{int:savearticle}}» تغییراتتان را برای همه قابل مشاهده خواهد کرد.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'با کلیک بر دکمهٔ «{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}» تغییراتتان را اعمال کنید.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'پیوندهای «{{int:editsection}} {{int:visualeditor-beta-appendix}}» برای بحش اصلی در صفحه وجود دارد، بنابراین می‌تواند فقط بر آن بخش تمرکز کنید.',
	'guidedtour-tour-firsteditve-save-description' => 'هنگامی که کارتان را انجام دادید، کلیک بر «{{int:visualeditor-toolbar-savedialog}}» تغییراتتان را برای همه قابل مشاهده خواهد کرد.',
);

/** Finnish (suomi)
 * @author Nike
 * @author Pxos
 * @author Samoasambia
 * @author Silvonen
 * @author Stryn
 */
$messages['fi'] = array(
	'guidedtour-help-url' => 'Help:Opastetut kiertueet',
	'guidedtour-help-guider-url' => 'Help:Opastetut kiertueet/opas',
	'guidedtour-next-button' => 'Seuraava',
	'guidedtour-okay-button' => 'Selvä',
	'guidedtour-tour-test-testing' => 'Testaus',
	'guidedtour-tour-test-test-description' => 'Tämä on kuvauksen testi. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-go-description-page' => 'Siirry kuvaussivulle',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Valmiina}} muokkaamaan?',
	'guidedtour-tour-firstedit-preview-title' => 'Esikatsella muutoksiasi (valinnainen)',
	'guidedtour-tour-firstedit-save-title' => 'Olet melkein valmis!',
);

/** French (français)
 * @author Gomoko
 * @author Jean-Frédéric
 * @author Ltrlg
 * @author Seb35
 * @author Trizek
 * @author Urhixidur
 * @author Verdy p
 */
$messages['fr'] = array(
	'guidedtour-desc' => 'Autoriser les pages à afficher une visite guidée par pop-up pour aider les nouveaux utilisateurs',
	'guidedtour-help-url' => 'Help:Visites guidées',
	'guidedtour-help-guider-url' => 'Help:Visites guidées/guide',
	'guidedtour-custom.css' => '/* CSS personnalisé pour l’extension Visite guidée. */',
	'guidedtour-next-button' => 'Suivant',
	'guidedtour-okay-button' => 'D’accord',
	'guidedtour-tour-test-testing' => 'Tester',
	'guidedtour-tour-test-test-description' => 'Ceci est un test de la description. Lorem ipsum dolor sit !',
	'guidedtour-tour-test-callouts' => 'Tester les liens de sortie',
	'guidedtour-tour-test-portal-description' => 'Ceci est la page {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Tester le rendu MediaWiki',
	'guidedtour-tour-test-wikitext-description' => 'Un élément du guide de visite peut contenir du wikitexte utilisant onShow et parseDescription. Utilisez-le pour créer un lien wiki vers la [[{{MediaWiki:Guidedtour-help-url}}|documentation des visites guidées]]. Ou un lien externe [https://github.com/tychay/mwgadget.GuidedTour vers GitHub] par exemple.',
	'guidedtour-tour-test-description-page' => 'Tester les pages de description MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Aller à la page de description',
	'guidedtour-tour-test-launch-tour' => 'Tester la visite de lancement',
	'guidedtour-tour-test-launch-tour-description' => 'Les guides peuvent créer d’autres visites guidées. Plutôt cool, non ?',
	'guidedtour-tour-test-launch-using-tours' => "Lancer une visite sur l'utilisation des visites",
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Prêt|Prête}} à modifier ?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Cliquez sur le bouton « {{int:vector-view-edit}} » pour faire vos modifications.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Modifier seulement une section',
	'guidedtour-tour-firstedit-edit-section-description' => 'Il y a des liens « {{int:editsection}} » pour chaque section majeure d’une page, afin que vous puissiez vous concentrer sur cette partie.',
	'guidedtour-tour-firstedit-preview-title' => 'Prévisualisez vos modifications (facultatif)',
	'guidedtour-tour-firstedit-preview-description' => 'Cliquer sur « {{int:showpreview}} » vous permet de vérifier à quoi ressemblera la page avec vos modifications. Ensuite, n’oubliez pas de publier pour enregistrer.',
	'guidedtour-tour-firstedit-save-title' => 'Vous avez presque terminé !',
	'guidedtour-tour-firstedit-save-description' => 'Lorsque vous êtes prêt, cliquez sur « {{int:savearticle}} » pour rendre vos modifications visibles à tous.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Cliquez sur le bouton « {{int:vector-view-edit}} {{int:visualeditor-beta-appendix}} » pour effectuer vos modifications.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Il y a des liens « {{int:editsection}} {{int:visualeditor-beta-appendix}} » pour chaque section principale dans une page, de sorte que vous puissiez vous focaliser uniquement sur cette partie.',
	'guidedtour-tour-firsteditve-save-description' => 'Quand vous serez prêt, cliquer sur « {{int:visualeditor-toolbar-savedialog}} » rendra vos modifications visibles pour tout le monde.',
);

/** Western Frisian (Frysk)
 * @author Kening Aldgilles
 */
$messages['fy'] = array(
	'guidedtour-next-button' => 'Folgjende',
	'guidedtour-okay-button' => 'Okee',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'guidedtour-desc' => 'Permite que as páxinas proporcionen unha visita guiada para axudar aos usuarios novos',
	'guidedtour-help-url' => 'Help:Visitas guiadas',
	'guidedtour-help-guider-url' => 'Help:Visitas guiadas/guía',
	'guidedtour-custom.css' => '/* CSS personalizado para a extensión de visitas guiadas. */',
	'guidedtour-next-button' => 'Seguinte',
	'guidedtour-okay-button' => 'Aceptar',
	'guidedtour-tour-test-testing' => 'Probas',
	'guidedtour-tour-test-test-description' => 'Esta é unha proba da descrición. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Probe as ligazóns de saída',
	'guidedtour-tour-test-portal-description' => 'Esta é a páxina do {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Probe o rendemento do MediaWiki',
	'guidedtour-tour-test-wikitext-description' => 'Os elemento da guía poden conter texto wiki usando onShow e parseDescription. Utilíceo para crear unha ligazón cara á [[{{MediaWiki:Guidedtour-help-url}}|documentación sobre as visitas guiadas]]. Ou unha ligazón externa [https://github.com/tychay/mwgadget.GuidedTour cara a GitHub], por exemplo.',
	'guidedtour-tour-test-description-page' => 'Probe as páxinas de descrición de MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Ir á páxina de descrición',
	'guidedtour-tour-test-launch-tour' => 'Probe a visita de iniciación',
	'guidedtour-tour-test-launch-tour-description' => 'As guías poden lanzar outras visitas guiadas. Caralludo, ou?',
	'guidedtour-tour-test-launch-using-tours' => 'Iniciar a guía sobre como usar as visitas',
	'guidedtour-tour-firstedit-edit-page-title' => 'Está listo para editar?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Prema no botón "{{int:vector-view-edit}}" para facer as súas modificacións.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Editar só unha sección',
	'guidedtour-tour-firstedit-edit-section-description' => 'Hai unha ligazón "{{int:editsection}}" en cada sección, para que poida centrarse só nesa parte da páxina.',
	'guidedtour-tour-firstedit-preview-title' => 'Vista previa dos cambios (opcional)',
	'guidedtour-tour-firstedit-preview-description' => 'Premer en "{{int:showpreview}}" serve para comprobar como se verá a páxina coas modificacións. Non esqueza gardar despois!',
	'guidedtour-tour-firstedit-save-title' => 'Xa case rematou!',
	'guidedtour-tour-firstedit-save-description' => 'Cando remate, prema en "{{int:savearticle}}" para que os cambios sexan visibles para todos.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Prema no botón "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" para realizar as súas modificacións.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Hai unha ligazón "{{int:editsection}} {{int:visualeditor-beta-appendix}}" en cada sección, para que poida centrarse só nesa parte da páxina.',
	'guidedtour-tour-firsteditve-save-description' => 'Cando remate, prema en "{{int:visualeditor-toolbar-savedialog}}" para que os cambios sexan visibles para todos.',
);

/** Gujarati (ગુજરાતી)
 * @author Ashok modhvadia
 * @author Dsvyas
 * @author KartikMistry
 */
$messages['gu'] = array(
	'guidedtour-next-button' => 'આગળ',
	'guidedtour-okay-button' => 'બરોબર',
	'guidedtour-tour-test-testing' => 'ચકાસણી',
	'guidedtour-tour-test-test-description' => 'આ વર્ણનનું પરિક્ષણ છે. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Test callouts',
	'guidedtour-tour-test-portal-description' => 'આ {{int:portal}}નું પાનું છે.',
	'guidedtour-tour-test-mediawiki-parse' => 'Test MediaWiki parse',
	'guidedtour-tour-test-description-page' => 'પરિક્ષણ મીડિયાવિકિ વર્ણન પૃષ્ઠ',
	'guidedtour-tour-test-go-description-page' => 'વર્ણનના પૃષ્ઠ પર જાવ',
);

/** Hebrew (עברית)
 * @author Amire80
 * @author ExampleTomer
 * @author Guycn2
 * @author NLIGuy
 * @author דוד
 */
$messages['he'] = array(
	'guidedtour-desc' => 'מאפשר לספק בדפים חלונות קופצים שעוזרים למשתמשים חדשים',
	'guidedtour-help-url' => 'Help:סיורים מודרכים',
	'guidedtour-help-guider-url' => 'עזרה: סיורים מודרכים/צעדים בסיור',
	'guidedtour-next-button' => 'הבא',
	'guidedtour-okay-button' => 'סבבה',
	'guidedtour-tour-test-testing' => 'בדיקות',
	'guidedtour-tour-test-test-description' => 'זוהי בדיקה של תיאור. צנח לו זלזל!',
	'guidedtour-tour-test-callouts' => 'בדיקת חלונות הסבר',
	'guidedtour-tour-test-portal-description' => 'זהו דף ה{{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'בדיקות פענוח מדיה-ויקי',
	'guidedtour-tour-test-description-page' => 'בדקו את דפי התיאור של מדיה-ויקי',
	'guidedtour-tour-test-go-description-page' => 'עברו לדף התיאור',
	'guidedtour-tour-test-launch-tour' => 'בדקו את סיור הניסיון',
	'guidedtour-tour-test-launch-tour-description' => 'שלבים בסיור יכולים להפעיל סיורים מודרכים אחרים. די מגניב, הא?',
	'guidedtour-tour-test-launch-using-tours' => 'התחילו סיור היכרות על אודות השימוש בסיורים',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|מוכן|מוכנה}} לערוך?',
	'guidedtour-tour-firstedit-edit-page-description' => 'נא ללחוץ על הכפתור "{{int:vector-view-edit}}" כדי לבצע את השינויים שלך.',
	'guidedtour-tour-firstedit-edit-section-title' => 'לערוך רק פסקה',
	'guidedtour-tour-firstedit-edit-section-description' => 'יש קישורי "{{int:editsection}}" לכל פסקה גדולה בדף, אז אפשר להתמקד רק בחלק ההוא.',
	'guidedtour-tour-firstedit-preview-title' => 'תצוגה מקדימה של השינויים שלך (לא חובה)',
	'guidedtour-tour-firstedit-preview-description' => 'לחיצה על "{{int:showpreview}}" מאפשרת לך לבדוק איך הדף ייראה עם השינויים שלך. רק לא לשכוח לשמור!',
	'guidedtour-tour-firstedit-save-title' => 'כמעט סיימנו!',
	'guidedtour-tour-firstedit-save-description' => 'כשהכול מוכן, לחיצה על "{{int:savearticle}}" תהפוך את השינויים שלך לגלויים לכולם.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'נא ללחוץ על הכפתור "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" כדי לבצע את השינויים שלך.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'יש קישורי "{{int:editsection}} {{int:visualeditor-beta-appendix}}" לכל אחד מהחלקים החשובים בדף ואפשר להתרכז רק בחלק ההוא.',
	'guidedtour-tour-firsteditve-save-description' => 'כשהכול מוכן, לחיצה על "{{int:visualeditor-toolbar-savedialog}}" תהפוך את השינויים שלך לגלויים לכולם.',
);

/** Upper Sorbian (hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'guidedtour-help-url' => 'Help:Wjedźene tury',
	'guidedtour-help-guider-url' => 'Help:Wjedźene tury/wjednik',
	'guidedtour-okay-button' => 'W porjadku',
	'guidedtour-tour-test-testing' => 'Testowanje',
	'guidedtour-tour-test-test-description' => 'To je test wopisanja. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Wujasnjenja testować',
	'guidedtour-tour-test-portal-description' => 'To je strona {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Parsowanje MediaWiki testować',
	'guidedtour-tour-test-description-page' => 'Wopisanske strony mdiaWiki testować',
	'guidedtour-tour-test-go-description-page' => 'Dźi k wopisanskej stronje',
	'guidedtour-tour-test-launch-tour' => 'Turowy start testować',
	'guidedtour-tour-test-launch-tour-description' => 'Wjednicy móža druhe wjedźene tury startować. Dosć cool, něwěrno?',
);

/** Hungarian (magyar)
 * @author Tgr
 */
$messages['hu'] = array(
	'guidedtour-desc' => 'Lehetővé teszi szövegbuborékokból álló bemutatók megjelenítését az új felhasználóknak.',
	'guidedtour-help-url' => 'Help:Útikalauz',
	'guidedtour-help-guider-url' => 'Segítség:Útikalauz/kalauz',
	'guidedtour-next-button' => 'Tovább',
	'guidedtour-okay-button' => 'Rendben',
	'guidedtour-tour-test-testing' => 'Teszt',
	'guidedtour-tour-test-test-description' => 'Ez egy teszt leírás. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Szövegbuborék teszt',
	'guidedtour-tour-test-portal-description' => 'Ez a {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'MediaWiki parser teszt',
	'guidedtour-tour-test-description-page' => 'MediaWiki leírás teszt',
	'guidedtour-tour-test-go-description-page' => 'Ugrás a leírásra',
	'guidedtour-tour-test-launch-tour' => 'Útikalauz-indítás teszt',
	'guidedtour-tour-test-launch-tour-description' => 'Az útikalauzok újabb útikalauzokat indíthatnak. Jó, mi?',
	'guidedtour-tour-test-launch-using-tours' => 'Útikalauz-kalauz indítása',
	'guidedtour-tour-firstedit-edit-page-title' => 'Készen állsz szerkeszteni?', # Fuzzy
	'guidedtour-tour-firstedit-edit-page-description' => 'Kattints a „{{int:vector-view-edit}}” gombra a szöveg módosításához.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Egyetlen szakasz szerkesztése',
	'guidedtour-tour-firstedit-edit-section-description' => 'A lap minden nagyobb szakaszához tartozik egy „{{int:editsection}}” link, ha csak azzal a résszel akarsz foglalkozni.',
	'guidedtour-tour-firstedit-preview-title' => 'Nézd meg az eredményt (opcionális)',
	'guidedtour-tour-firstedit-preview-description' => 'Az „{{int:showpreview}}” gombra kattintva ellenőrizheted, hogyan fog kinézni az oldal a módosításaiddal együtt. Ne felejtsd el a végén elmenteni!',
	'guidedtour-tour-firstedit-save-title' => 'Majdnem kész vagy!',
	'guidedtour-tour-firstedit-save-description' => 'Ha végeztél, a „{{int:savearticle}}” gombra kattintva a változtatásaid bekerülnek a cikkbe.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'A lap módosításához kattints a „{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}” gombra.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'A lap minden nagyobb szakaszához tartozik egy „{{int:editsection}} {{int:visualeditor-beta-appendix}}” link, ha csak azzal a résszel akarsz foglalkozni.',
	'guidedtour-tour-firsteditve-save-description' => 'Ha kész vagy, a „{{int:visualeditor-toolbar-savedialog}}” gombra kattintva teheted a változtatásaidat mindenki számára láthatóvá.',
);

/** Interlingua (interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'guidedtour-desc' => 'Permitte que un guida appare in paginas pro assister nove usatores',
	'guidedtour-help-url' => 'Help:Visitas guidate',
	'guidedtour-help-guider-url' => 'Help:Visitas guidate/guida',
	'guidedtour-next-button' => 'Sequente',
	'guidedtour-okay-button' => 'OK',
	'guidedtour-tour-test-testing' => 'Test',
	'guidedtour-tour-test-test-description' => 'Isto es un test del description. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-portal-description' => 'Iste es le pagina del {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Test del analysator syntactic de MediaWiki',
	'guidedtour-tour-test-description-page' => 'Test del paginas de description de MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Vader al pagina de description',
	'guidedtour-tour-test-launch-tour' => 'Test del lanceamento del visita guidate',
	'guidedtour-tour-test-launch-tour-description' => 'Le guidas pote lancear altere visitas guidate. Multo bon, nonne?',
	'guidedtour-tour-test-launch-using-tours' => 'Lancear un visita guidate sur le uso de visitas guidate',
	'guidedtour-tour-firstedit-edit-page-title' => 'Preste a modificar?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Clicca sur le button "{{int:vector-view-edit}}" pro facer tu cambiamentos.',
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => 'Clicca sur le button "{{int:visualeditor-ca-editsource}}" pro facer tu cambiamentos.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Modificar solmente un section',
	'guidedtour-tour-firstedit-edit-section-description' => 'Il ha ligamines "{{int:editsection}}" pro cata major section in un pagina, de sorta que tu pote concentrar te a iste parte.',
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => 'Il ha ligamines "{{int:visualeditor-ca-editsource-section}}" pro cata major section in un pagina, de sorta que tu pote concentrar te a iste parte.',
	'guidedtour-tour-firstedit-preview-title' => 'Previsualisar tu modificationes (optional)',
	'guidedtour-tour-firstedit-preview-description' => 'Cliccar sur "{{int:showpreview}}" permitte verificar le aspecto del pagina con tu modificationes. Solmente non oblida de salveguardar lo!',
	'guidedtour-tour-firstedit-save-title' => 'Tu ha quasi finite!',
	'guidedtour-tour-firstedit-save-description' => 'Quando tu es preste, un clic sur "{{int:savearticle}}" rendera tu cambiamentos visibile a tote le mundo.',
);

/** Icelandic (íslenska)
 * @author Snævar
 */
$messages['is'] = array(
	'guidedtour-next-button' => 'Áfram',
	'guidedtour-okay-button' => 'Í lagi',
	'guidedtour-tour-test-testing' => 'Prufa',
	'guidedtour-tour-test-test-description' => 'Þetta er prófun á lýsingu. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-portal-description' => 'Þetta er síðan {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Prufa þáttun MediaWiki',
	'guidedtour-tour-firstedit-edit-page-title' => 'Tilbúin/n að breyta?', # Fuzzy
	'guidedtour-tour-firstedit-edit-page-description' => 'Smelltu á „{{int:vector-view-edit}}” takkann til að gera breytingarnar þínar.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Breyta eingöngu kafla',
	'guidedtour-tour-firstedit-edit-section-description' => 'Það eru „{{int:editsection}}” tenglar fyrir hvern kafla á síðu, svo þú getir einblínt á þann hluta.',
	'guidedtour-tour-firstedit-preview-title' => 'Forskoða breytingarnar þínar (valfrjálst)',
	'guidedtour-tour-firstedit-preview-description' => 'Með því að smella á „{{int:showpreview}}” getur þú séð hvernig síðan mun líta út með breytingunum þínum. Bara ekki gleyma að vista!',
	'guidedtour-tour-firstedit-save-title' => 'Þú ert næstum búin/n!',
	'guidedtour-tour-firstedit-save-description' => 'Þegar þú ert tilbúin/n, mun smellur á „{{int:savearticle}}” gera breytingarnar þínar sýnilegar öllum.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Smelltu á „{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}” takkann til að gera breytingarnar þínar.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Það eru „{{int:editsection}} {{int:visualeditor-beta-appendix}}” tenglar fyrir hvern kafla á síðu, svo þú getir einbínt á þann hluta.',
	'guidedtour-tour-firsteditve-save-description' => 'Þegar þú ert tilbúin/n, mun smellur á „{{int:visualeditor-toolbar-savedialog}}” gera breytingarnar þínar sýnilegar öllum.',
);

/** Italian (italiano)
 * @author Beta16
 * @author Gianfranco
 */
$messages['it'] = array(
	'guidedtour-desc' => 'Consente di gestire pagine per fornire un tour guidato tramite popup per assistere i nuovi utenti',
	'guidedtour-help-url' => 'Help:Visite guidate',
	'guidedtour-help-guider-url' => 'Aiuto:Visite guidate/guida',
	'guidedtour-next-button' => 'Successivo',
	'guidedtour-okay-button' => 'Ok',
	'guidedtour-tour-test-testing' => 'Prova',
	'guidedtour-tour-test-test-description' => 'Questa è una prova della descrizione. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Prova didascalie',
	'guidedtour-tour-test-portal-description' => 'Questa è la pagina {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Prova MediaWiki parse',
	'guidedtour-tour-test-description-page' => 'Prova le pagine di descrizione di MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Vai alla pagina di descrizione',
	'guidedtour-tour-test-launch-tour' => 'Prova la visita di partenza',
	'guidedtour-tour-test-launch-tour-description' => 'Le guide possono lanciare altre visite guidate. Non male, eh?',
	'guidedtour-tour-test-launch-using-tours' => "Lancia un tour sull'utilizzo delle visite",
	'guidedtour-tour-firstedit-edit-page-title' => 'Pronto a modificare?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Clicca "{{int:vector-view-edit}}" per fare le tue modifiche.',
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => 'Clicca "{{int:visualeditor-ca-editsource}}" per fare le tue modifiche.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Modifica solo una sezione',
	'guidedtour-tour-firstedit-edit-section-description' => 'Esistono collegamenti "{{int:editsection}}" per ogni sezione principale di una pagina, così puoi concentrarti solo quella parte.',
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => 'Esistono collegamenti "{{int:visualeditor-ca-editsource-section}}" per ogni sezione principale di una pagina, così puoi concentrarti solo quella parte.',
	'guidedtour-tour-firstedit-preview-title' => "Vedi un'anteprima delle tue modifiche (facoltativo)",
	'guidedtour-tour-firstedit-preview-description' => 'Cliccando su "{{int:showpreview}}" ti permette di verificare come sarà l\'aspetto della pagina con le modifiche apportate. Non ti dimenticare di salvare!',
	'guidedtour-tour-firstedit-save-title' => 'Hai quasi finito!',
	'guidedtour-tour-firstedit-save-description' => 'Quando sei pronto, clicca su "{{int:savearticle}}" e renderai le tue modifiche visibili a tutti.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Clicca "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" per fare le tue modifiche.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Esistono collegamenti "{{int:editsection}} {{int:visualeditor-beta-appendix}}" per ogni sezione principale di una pagina, così puoi concentrarti solo quella parte.',
	'guidedtour-tour-firsteditve-save-description' => 'Quando sei pronto, clicca su "{{int:visualeditor-toolbar-savedialog}}" e renderai le tue modifiche visibili a tutti.',
);

/** Japanese (日本語)
 * @author Shirayuki
 */
$messages['ja'] = array(
	'guidedtour-desc' => '新しい利用者を支援するポップアップのガイド付きツアーをページで提供できるようにする',
	'guidedtour-help-url' => 'Help:ガイド付きツアー',
	'guidedtour-help-guider-url' => 'Help:ガイド付きツアー/guider',
	'guidedtour-custom.css' => '/* ガイドツアー拡張機能用のカスタムCSS */',
	'guidedtour-next-button' => '次へ',
	'guidedtour-okay-button' => 'OK',
	'guidedtour-tour-test-testing' => 'テスト',
	'guidedtour-tour-test-test-description' => 'これは説明のテストです。Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => '呼び出しのテスト',
	'guidedtour-tour-test-portal-description' => 'これは{{int:portal}}のページです。',
	'guidedtour-tour-test-mediawiki-parse' => 'MediaWiki の構文解析のテスト',
	'guidedtour-tour-test-description-page' => 'MediaWiki 説明ページのテスト',
	'guidedtour-tour-test-go-description-page' => '説明ページに移動',
	'guidedtour-tour-firstedit-edit-page-description' => '「{{int:vector-view-edit}}」ボタンをクリックして編集します。',
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => '「{{int:visualeditor-ca-editsource}}」ボタンをクリックして編集します。',
	'guidedtour-tour-firstedit-edit-section-description' => 'ページ内の主要な節それぞれに「{{int:editsection}}」リンクがあるため、その節のみに着目できます。',
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => 'ページ内の主要な節それぞれに「{{int:visualeditor-ca-editsource-section}}」リンクがあるため、その節のみに着目できます。',
	'guidedtour-tour-firstedit-preview-title' => '変更内容のプレビュー (省略可能)',
	'guidedtour-tour-firstedit-save-title' => 'もう少しで完了します!',
	'guidedtour-tour-firsteditve-edit-page-description' => '変更するには、「{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}」をクリックしてください。',
	'guidedtour-tour-firsteditve-edit-section-description' => 'ページ内の主要な節それぞれに「{{int:editsection}} {{int:visualeditor-beta-appendix}}」リンクがあるため、その節のみに着目できます。',
);

/** Georgian (ქართული)
 * @author David1010
 */
$messages['ka'] = array(
	'guidedtour-tour-test-testing' => 'ტესტირება',
);

/** Korean (한국어)
 * @author Priviet
 * @author 아라
 */
$messages['ko'] = array(
	'guidedtour-desc' => '새 사용자를 돕기 위해 팝업 가이드 투어를 문서에 제공할 수 있습니다',
	'guidedtour-help-url' => 'Help:가이드 투어',
	'guidedtour-help-guider-url' => 'Help:가이드 투어/안내자',
	'guidedtour-custom.css' => '/* 이 CSS 설정은 가이드 투어 확장 기능에 적용됩니다. */',
	'guidedtour-next-button' => '다음',
	'guidedtour-okay-button' => '확인',
	'guidedtour-tour-test-testing' => '테스트',
	'guidedtour-tour-test-test-description' => '설명의 테스트입니다. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => '호출 텍스트',
	'guidedtour-tour-test-portal-description' => '{{int:portal}} 문서입니다.',
	'guidedtour-tour-test-mediawiki-parse' => '미디어위키 구문 분석 테스트',
	'guidedtour-tour-test-wikitext-description' => '위키 투어의 안내자는 onShow와 parseDescription을 사용하여 포함할 수 있습니다. [[{{MediaWiki:Guidedtour-help-url}}|가이드 투어 설명서]]에 위키링크를 만드는 데 사용하세요. 또는 예를 들어 [https://github.com/tychay/mwgadget.GuidedTour GitHub로] 바깥 링크를 만드는 데 사용하세요.',
	'guidedtour-tour-test-description-page' => '미디어위키 설명 문서 테스트',
	'guidedtour-tour-test-go-description-page' => '설명 문서로 가기',
	'guidedtour-tour-test-launch-tour' => '시작 투어 테스트',
	'guidedtour-tour-test-launch-tour-description' => '안내자는 다른 가이드 투어를 시작할 수 있습니다. 멋지죠?',
	'guidedtour-tour-test-launch-using-tours' => '투어를 사용하여 투어 시작',
	'guidedtour-tour-firstedit-edit-page-title' => '편집할 {{GENDER:준비가 되었습니까}}?',
	'guidedtour-tour-firstedit-edit-page-description' => '내용을 바꾸려면 "{{int:vector-view-edit}}" 버튼을 클릭하세요.',
	'guidedtour-tour-firstedit-edit-section-title' => '문단 편집',
	'guidedtour-tour-firstedit-edit-section-description' => '각 주요 문단 부분에 집중할 수 있도록 문서에 각 주요 문단에 대한 "{{int:editsection}}" 링크가 있습니다.',
	'guidedtour-tour-firstedit-preview-title' => '바뀐 내용 미리 보기 (선택 사항)',
	'guidedtour-tour-firstedit-preview-description' => '"{{int:showpreview}}"를 클릭하면 문서가 어떻게 바뀌었는지 확인할 수 있습니다. 저장하는 것을 잊지 마세요!',
	'guidedtour-tour-firstedit-save-title' => '거의 끝나갑니다!',
	'guidedtour-tour-firstedit-save-description' => '준비가 되면, "{{int:savearticle}}"을 클릭하면 바꾼 내용을 모두에게 보여줍니다.',
	'guidedtour-tour-firsteditve-edit-page-description' => '내용을 바꾸려면 "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" 단추를 클릭하세요.',
	'guidedtour-tour-firsteditve-edit-section-description' => '각 주요 문단 부분에 집중할 수 있도록 문서에 각 주요 문단에 대한 "{{int:editsection}} {{int:visualeditor-beta-appendix}}" 링크가 있습니다.',
	'guidedtour-tour-firsteditve-save-description' => '준비가 되면 "{{int:savearticle}}"을 클릭하여 바꾼 내용을 모두에게 보이도록 해주세요.',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 * @author Soued031
 */
$messages['lb'] = array(
	'guidedtour-desc' => "Erméiglecht Pop-up-ënnerstëtzt Toure fir nei Benotzer z'assistéieren",
	'guidedtour-help-url' => 'Help:Guidéiert Touren',
	'guidedtour-help-guider-url' => 'Help:Guided tours/Guide',
	'guidedtour-next-button' => 'Nächst',
	'guidedtour-okay-button' => 'OK',
	'guidedtour-tour-test-testing' => 'Testen',
	'guidedtour-tour-test-test-description' => 'Dëst ass en Test vun der Beschreiwung. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-portal-description' => "Dëst ass d'{{int:portal}}-Säit",
	'guidedtour-tour-test-mediawiki-parse' => 'MediaWiki-Parser testen',
	'guidedtour-tour-test-description-page' => 'MediaWiki-Beschreiwungssäiten testen',
	'guidedtour-tour-test-go-description-page' => "Op d'Beschreiwungssäit goen",
	'guidedtour-tour-test-launch-tour' => 'Ufank vum Tour testen',
	'guidedtour-tour-test-launch-using-tours' => "En Tour ufänken duerch d'Benotze vun Touren",
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Sidd Dir}} prëtt fir ze änneren?',
	'guidedtour-tour-firstedit-edit-page-description' => "Klickt op de Knäppchen '{{int:vector-view-edit}}' fir Är Ännerungen ze maachen.",
	'guidedtour-tour-firstedit-edit-section-title' => 'Ännert just een Abschnitt',
	'guidedtour-tour-firstedit-edit-section-description' => 'Et gëtt "{{int:editsection}}"-Linke fir all gréisseren Abschnitt op enger Säit, sou kënnt Dir Iech just op deen Deel konzentréieren.',
	'guidedtour-tour-firstedit-preview-title' => 'Kuckt Är Ännerungen ouni ze späicheren (fakultativ)',
	'guidedtour-tour-firstedit-preview-description' => 'Klicken op "{{int:showpreview}}", erlaabt Iech ze gesinn, wéi d\'Säit mat Ären Ännerungen ausgesäit. Vergiesst net ze späicheren.',
	'guidedtour-tour-firstedit-save-title' => 'Dir sidd bal fäerdeg!',
	'guidedtour-tour-firstedit-save-description' => "Wann Dir fäerdeg sidd, klickt op {{int:savearticle}}' fir Är Ännerunge jiddwerengem ze weisen.",
	'guidedtour-tour-firsteditve-edit-page-description' => 'Klickt op de "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" Knäppche fir e maachen.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Et gëtt "{{int:editsection}} {{int:visualeditor-beta-appendix}}"-Linke fir all gréisseren Abschnitt op enger Säit, sou kënnt Dir Iech just op deen Deel konzentréieren.',
	'guidedtour-tour-firsteditve-save-description' => 'Wann Dir fäerdeg sidd, klickt op "{{int:visualeditor-toolbar-savedialog}}" fir Är Ännerunge fir jiddweree visibel ze maachen.',
);

/** Lithuanian (lietuvių)
 * @author Mantak111
 */
$messages['lt'] = array(
	'guidedtour-next-button' => 'Kitas',
	'guidedtour-okay-button' => 'Gerai',
);

/** Latvian (latviešu)
 * @author Papuass
 */
$messages['lv'] = array(
	'guidedtour-next-button' => 'Tālāk',
	'guidedtour-okay-button' => 'Labi',
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'guidedtour-desc' => 'Овозможува страниците да даваат отскочни прозорци со водени надгледни упатства за новите корисници',
	'guidedtour-help-url' => 'Help:Водени тури',
	'guidedtour-help-guider-url' => 'Help:Водени тури/водич',
	'guidedtour-custom.css' => '/* Прилагоден CSS за додатокот GuidedTour. */',
	'guidedtour-next-button' => 'Следно',
	'guidedtour-okay-button' => 'Во ред',
	'guidedtour-tour-test-testing' => 'Испробување',
	'guidedtour-tour-test-test-description' => 'Ова е проба за описот. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Испробување на повици',
	'guidedtour-tour-test-portal-description' => 'Ова е страница на порталот {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Испробување на парсирањето на МедијаВики',
	'guidedtour-tour-test-wikitext-description' => 'Водичот во вашата викутира може да содржи викитекст што користи onShow и parseDescription. Користете го за да направите викиврска до [[{{MediaWiki:Guidedtour-help-url}}|Документацијата за Водени тури]]. Или пак надворешна врска, на пример [https://github.com/tychay/mwgadget.GuidedTour до github].',
	'guidedtour-tour-test-description-page' => 'Испробување на описните страници на МедијаВики',
	'guidedtour-tour-test-go-description-page' => 'Појди на описна страница',
	'guidedtour-tour-test-launch-tour' => 'Испробување на пуштањето на турата',
	'guidedtour-tour-test-launch-tour-description' => 'Водичите можат да пуштаат други водени тури. Баш добро, нели?',
	'guidedtour-tour-test-launch-using-tours' => 'Пушти тура за користење на тури',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Спремни}} сте да уредувате?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Стиснете на копчето „{{int:vector-view-edit}}“ за да ги направите саканите измени.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Уредете го само овој дел',
	'guidedtour-tour-firstedit-edit-section-description' => 'Постојат врски „{{int:editsection}}“ во секој дел (поднаслов) од страницата, за да можете да го уредите само него.',
	'guidedtour-tour-firstedit-preview-title' => 'Прегледајте ги измените (по желба)',
	'guidedtour-tour-firstedit-preview-description' => 'Стискајќи на „{{int:showpreview}}“ можете да видите како ќе изгледа изменетата страница. Само не заборавајте да ја зачувате!',
	'guidedtour-tour-firstedit-save-title' => 'Речиси сте готови!',
	'guidedtour-tour-firstedit-save-description' => 'Кога сте готови, стиснете на „{{int:savearticle}}“ и измените ќе бидат видливи за секого.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Стиснете на копчето „{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}“ за да ги направите промените.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Секој поважен дел (поднаслов) од страницата има врски „{{int:editsection}} {{int:visualeditor-beta-appendix}}“ за да можете да му се посветите само на тој дел.',
	'guidedtour-tour-firsteditve-save-description' => 'Кога ќе сте готови, стиснете на „{{int:visualeditor-toolbar-savedialog}}“ и така промените ќе станат видливи за секого.',
);

/** Malayalam (മലയാളം)
 * @author Praveenp
 * @author Santhosh.thottingal
 */
$messages['ml'] = array(
	'guidedtour-desc' => 'പുതിയ ഉപയോക്താക്കൾക്ക് സഹായമാവുന്ന വിധത്തിൽ മാർഗ്ഗദർശി പര്യടനത്തിനുള്ള പോപ്-അപ് താളുകളിൽ നൽകാൻ സഹായിക്കുന്നു',
	'guidedtour-help-url' => 'Help:വഴികാട്ടി ഉപയോഗിച്ചുള്ള പര്യടനം',
	'guidedtour-help-guider-url' => 'Help:വഴികാട്ടി ഉപയോഗിച്ചുള്ള പര്യടനം/വഴികാട്ടി',
	'guidedtour-next-button' => 'അടുത്തത്',
	'guidedtour-okay-button' => 'ശരി',
	'guidedtour-tour-test-testing' => 'പരീക്ഷിക്കുന്നു',
	'guidedtour-tour-test-test-description' => 'ഇത് വിവരണത്തിന്റെ പരീക്ഷണമാണ്. അയ്യപ്പന്റമ്മ നെയ്യപ്പം ചുട്ടു!',
	'guidedtour-tour-test-callouts' => 'സഹായാഭ്യർത്ഥനകൾ പരീക്ഷിക്കുക',
	'guidedtour-tour-test-portal-description' => 'ഇത് {{int:portal}} താളാണ്.',
	'guidedtour-tour-test-mediawiki-parse' => 'മീഡിയവിക്കി പാഴ്സ്  പരീക്ഷിക്കുക',
	'guidedtour-tour-test-description-page' => 'മീഡിയവിക്കി വിവരണ താളുകൾ പരീക്ഷിക്കുക',
	'guidedtour-tour-test-go-description-page' => 'വിവരണ താളിലേക്ക് പോവുക',
	'guidedtour-tour-test-launch-tour' => 'പര്യടനം തുടങ്ങൽ പരീക്ഷിക്കുക',
	'guidedtour-tour-test-launch-tour-description' => 'വഴികാട്ടികൾക്ക് മറ്റ് വഴികാട്ടികൾ ഉപയോഗിച്ചുള്ള പര്യടനവും തുടങ്ങാനാവും. കൊള്ളാം, ല്ലേ?',
	'guidedtour-tour-test-launch-using-tours' => 'പര്യടനം ഉപയോഗിച്ച് മറ്റൊരു പര്യടനം നടത്തുക',
	'guidedtour-tour-firstedit-edit-page-title' => 'തിരുത്താൻ തയ്യാറല്ലേ?',
	'guidedtour-tour-firstedit-edit-page-description' => 'താങ്കളുടെ മാറ്റങ്ങൾ വരുത്താനായി  "{{int:vector-view-edit}}" ബട്ടൺ അമർത്തുക.',
	'guidedtour-tour-firstedit-edit-section-title' => 'ഒരു ഭാഗം മാത്രം തിരുത്തുക',
	'guidedtour-tour-firstedit-edit-section-description' => 'താളിലെ ഒരോ ഭാഗത്തും "{{int:editsection}}" കണ്ണികളുണ്ട്, അവിടെ മാത്രം അങ്ങനെ താങ്കൾക്ക് ശ്രദ്ധ കേന്ദ്രീകരിക്കാം.',
	'guidedtour-tour-firstedit-preview-title' => 'താങ്കളുടെ മാറ്റങ്ങൾ എങ്ങനെയുണ്ടെന്ന് കാണുക (ഐച്ഛികം)',
	'guidedtour-tour-firstedit-preview-description' => '"{{int:showpreview}}" അമർത്തുമ്പോൾ, താങ്കൾ ചേർത്ത തിരുത്തുകളുൾപ്പെടെ താൾ എപ്രകാരമാണ് പ്രത്യക്ഷപ്പെടുക എന്ന് കാണാനാവും. പിന്നീട് മാറ്റങ്ങൾ സേവ് ചെയ്യാൻ മറക്കരുത്!',
	'guidedtour-tour-firstedit-save-title' => 'മിക്കവാറും പൂർത്തിയായിരിക്കുന്നു!',
	'guidedtour-tour-firstedit-save-description' => 'താങ്കൾ തയ്യാറായി കഴിഞ്ഞാൽ, "{{int:savearticle}}" അമർത്തി താങ്കൾ വരുത്തിയ മാറ്റങ്ങൾ എല്ലാവർക്കും കാണാനാവുന്ന വിധത്തിൽ പങ്ക് വെയ്ക്കാം.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'മാറ്റങ്ങൾ വരുത്താനായി "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" ബട്ടൺ അമർത്തുക.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'താളിലെ ഒരോ ഭാഗത്തും "{{int:editsection}} {{int:visualeditor-beta-appendix}}" കണ്ണികളുണ്ട്, അവിടെ മാത്രം അങ്ങനെ താങ്കൾക്ക് ശ്രദ്ധ കേന്ദ്രീകരിക്കാം.',
	'guidedtour-tour-firsteditve-save-description' => 'താങ്കൾ തയ്യാറായി കഴിഞ്ഞാൽ, "{{int:visualeditor-toolbar-savedialog}}" അമർത്തി താങ്കൾ വരുത്തിയ മാറ്റങ്ങൾ എല്ലാവർക്കും കാണാനാവുന്ന വിധത്തിൽ പങ്ക് വെയ്ക്കാം.',
);

/** Marathi (मराठी)
 * @author V.narsikar
 */
$messages['mr'] = array(
	'guidedtour-tour-firstedit-edit-page-title' => 'संपादनास {{GENDER:|तयार}} ?',
);

/** Malay (Bahasa Melayu)
 * @author Anakmalaysia
 */
$messages['ms'] = array(
	'guidedtour-desc' => 'Membolehkan halaman untuk menyediakan lawatan berpandu popup untuk membantu pengguna baru',
	'guidedtour-help-url' => 'Help:Lawatan berpandu',
	'guidedtour-help-guider-url' => 'Help:Lawatan berpandu/pemandu',
	'guidedtour-next-button' => 'Berikutnya',
	'guidedtour-okay-button' => 'Baik',
	'guidedtour-tour-test-testing' => 'Menguji',
	'guidedtour-tour-test-test-description' => 'Ini ialah ujian untuk keterangan. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Uji petak bual',
	'guidedtour-tour-test-portal-description' => 'Ini ialah halaman {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Uji huraian MediaWiki',
	'guidedtour-tour-test-wikitext-description' => 'Pemandu dalam jelajah dalam wiki anda boleh mengandungi teks wiki dengan menggunakan onShow dan parseDescription. Gunakannya untuk membuat pautan wiki ke [[{{MediaWiki:Guidedtour-help-url}}|dokumentasi Jelajah berpandu]]. Atau pautan luar [https://github.com/tychay/mwgadget.GuidedTour ke GitHub], contohnya.',
	'guidedtour-tour-test-description-page' => 'Uji halaman keterangan MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Pergi ke halaman keterangan',
	'guidedtour-tour-test-launch-tour' => 'Uji lancar jelajah',
	'guidedtour-tour-test-launch-tour-description' => 'Pemandu boleh melancarkan jelajah berpandu yang lain. Hebat, kan?',
	'guidedtour-tour-test-launch-using-tours' => 'Lancarkan jelajah menggunakan jelajah',
	'guidedtour-tour-firstedit-edit-page-title' => 'Sedia untuk menyunting?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Klik butang "{{int:vector-view-edit}}" untuk menyiarkan suntingan anda.',
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => 'Klik butang "{{int:visualeditor-ca-editsource}}" untuk menyiarkan suntingan anda.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Sunting satu bahagian sahaja',
	'guidedtour-tour-firstedit-edit-section-description' => 'Terdapat pautan "{{int:editsection}}" untuk setiap bahagian utama pada sesebuah rencana supaya anda boleh hanya bertumpu pada bahagian berkenaan.', # Fuzzy
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => 'Terdapat pautan "{{int:visualeditor-ca-editsource-section}}" untuk setiap bahagian utama pada sesebuah rencana supaya anda boleh hanya bertumpu pada bahagian berkenaan.', # Fuzzy
	'guidedtour-tour-firstedit-preview-title' => 'Pratayangkan suntingan anda (tidak wajib)',
	'guidedtour-tour-firstedit-preview-description' => 'Anda boleh menyemak rupa halaman selepas suntingan dengan mengklik "{{int:showpreview}}". Tapi jangan lupa untuk menyimpan!',
	'guidedtour-tour-firstedit-save-title' => 'Hampir siap!',
	'guidedtour-tour-firstedit-save-description' => 'Apabila anda sedia, klik "{{int:savearticle}}" dan perlihatkan suntingan anda kepada semua pembaca.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Klik butang "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" untuk menyiarkan suntingan.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Terdapat pautan "{{int:editsection}}{{int:visualeditor-beta-appendix}}" untuk setiap bahagian utama pada sesebuah rencana supaya anda boleh hanya bertumpu pada bahagian berkenaan.', # Fuzzy
	'guidedtour-tour-firsteditve-save-description' => 'Apabila anda sedia, klik "{{int:visualeditor-toolbar-savedialog}}" dan perlihatkan suntingan anda kepada semua pembaca.',
);

/** Dutch (Nederlands)
 * @author Carsrac
 * @author Siebrand
 */
$messages['nl'] = array(
	'guidedtour-desc' => 'Maakt het mogelijk om een rondleiding weer te geven voor nieuwe gebruikers',
	'guidedtour-help-url' => 'Help:Rondleidingen',
	'guidedtour-help-guider-url' => 'Help:Rondleidingen/gids',
	'guidedtour-next-button' => 'Volgende',
	'guidedtour-okay-button' => 'Oké',
	'guidedtour-tour-test-testing' => 'Testen',
	'guidedtour-tour-test-test-description' => 'Dit is een test van de beschrijving. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Test toelichtingen',
	'guidedtour-tour-test-portal-description' => 'Dit is de pagina {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Test mediawiki parse',
	'guidedtour-tour-test-wikitext-description' => 'Een raadgever in uw wikirondleiding die wikitekst kan bevatten met gebruik van onShow en parseDescription. Gebruik het om een wikikoppeling te maken naar de [[{{MediaWiki:Guidedtour-help-url}}|documentatie voor rondleidingen]]. U kunt er ook een externe koppeling mee maken, zoals bijvoorbeeld [https://github.com/tychay/mwgadget.GuidedTour naar GitHub].',
	'guidedtour-tour-test-description-page' => "Test MediaWiki beschrijvingspagina's",
	'guidedtour-tour-test-go-description-page' => 'Ga naar beschrijvingspagina',
	'guidedtour-tour-test-launch-tour' => 'Test start rondleiding',
	'guidedtour-tour-test-launch-tour-description' => 'Via raadgevers kunnen andere rondleidingen gestart worden. Leuk, toch?',
	'guidedtour-tour-test-launch-using-tours' => 'Start een rondleiding bij het gebruik van rondleidingen',
	'guidedtour-tour-firstedit-edit-page-title' => 'Klaar om te bewerken?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Klik op de knop "{{int:vector-view-edit}}" om uw wijzigingen te maken.',
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => 'Klik op de knop "{{int:visualeditor-ca-editsource}}" om uw wijzigingen te maken.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Alleen een paragraaf bewerken',
	'guidedtour-tour-firstedit-edit-section-description' => 'Er zijn koppelingen "{{int:editsection}}" voor iedere onderdeel van een pagina, zodat u zich kunt concentreren op dat onderdeel.',
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => 'Er zijn koppelingen "{{int:visualeditor-ca-editsource-section}}" voor iedere onderdeel van een pagina, zodat u zich kunt concentreren op dat onderdeel.',
	'guidedtour-tour-firstedit-preview-title' => 'Voorvertoning van uw wijzigingen (optioneel)',
	'guidedtour-tour-firstedit-preview-description' => 'Door te klikken op "{{int:showpreview}}" krijgt u de pagina te zien inclusief uw wijzigingen. Vergeet niet de pagina ook echt op te slaan als u dat wilt!',
	'guidedtour-tour-firstedit-save-title' => 'U bent bijna klaar!',
	'guidedtour-tour-firstedit-save-description' => 'Als u klaar bent, kunt u op "{{int:savearticle}}" klikken om uw wijzigingen voor iedereen zichtbaar te maken.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Klik op de knop "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" om uw wijzigingen te maken.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Er zijn koppelingen "{{int:editsection}} {{int:visualeditor-beta-appendix}}" voor iedere onderdeel van een pagina, zodat u zich kunt concentreren op dat onderdeel.',
	'guidedtour-tour-firsteditve-save-description' => 'Als u klaar bent, kunt u op "{{int:visualeditor-toolbar-savedialog}}" klikken om uw wijzigingen voor iedereen zichtbaar te maken.',
);

/** Polish (polski)
 * @author Chrumps
 * @author Matma Rex
 * @author Peter Bowman
 * @author Tar Lócesilion
 */
$messages['pl'] = array(
	'guidedtour-desc' => 'Umożliwia korzystanie z interaktywnych przewodników dla początkujących',
	'guidedtour-help-url' => 'Help:Interaktywny przewodnik',
	'guidedtour-help-guider-url' => 'Pomoc:Interaktywny przewodnik/przewodnik',
	'guidedtour-next-button' => 'Dalej',
	'guidedtour-okay-button' => 'Okej',
	'guidedtour-tour-test-testing' => 'Testowanie',
	'guidedtour-tour-test-test-description' => 'To jest przykładowy tekst. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Test odniesień',
	'guidedtour-tour-test-portal-description' => 'To jest strona „{{int:portal}}”.',
	'guidedtour-tour-test-mediawiki-parse' => 'Test składni MediaWiki',
	'guidedtour-tour-test-description-page' => 'Test stron opisowych MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Przejdź na stronę opisu',
	'guidedtour-tour-test-launch-tour' => 'Test rozpoczęcia wycieczki',
	'guidedtour-tour-test-launch-tour-description' => 'Jeden przewodnik może uruchomić drugi. Fajne, prawda?',
	'guidedtour-tour-test-launch-using-tours' => 'Rozpocznij przewodnik po korzystaniu z przewodników',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Gotowy|Gotowa}} do edytowania?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Kliknij na „{{int:vector-view-edit}}”, aby dokonać zmian.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Edytuj tylko jedna sekcję',
	'guidedtour-tour-firstedit-edit-section-description' => 'Obok każdego nagłówka znajduje się odnośnik „{{int:editsection}}”, aby móc skupić się tylko na tej części.',
	'guidedtour-tour-firstedit-preview-title' => 'Przejrzyj swoje zmiany (opcjonalne)',
	'guidedtour-tour-firstedit-preview-description' => 'Naciśnięcie na „{{int:showpreview}}” pozwoli Ci obejrzeć wygląd strony po wprowadzeniu Twoich zmian. Nie zapomnij ich zapisać!',
	'guidedtour-tour-firstedit-save-title' => 'Prawie gotowe!',
	'guidedtour-tour-firstedit-save-description' => 'Gdy będziesz {{GENDER:|gotowy|gotowa}}, kliknij „{{int:savearticle}}”, aby zmiany stały się widoczne dla każdego.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Kliknij na „{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}”, aby rozpocząć edycję strony.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Obok każdego nagłówka znajduje się odnośnik „{{int:editsection}} {{int:visualeditor-beta-appendix}}”, aby móc skupić się tylko na tej części.',
	'guidedtour-tour-firsteditve-save-description' => 'Gdy skończysz, kliknięcie na „{{int:visualeditor-toolbar-savedialog}}” sprawi, że zmiany będą widoczne dla każdego.',
);

/** Piedmontese (Piemontèis)
 * @author Borichèt
 * @author Dragonòt
 */
$messages['pms'] = array(
	'guidedtour-desc' => "A përmët le pàgine për fornì na vìsita guidà da fnestre an riliev për giuté j'utent neuv",
	'guidedtour-help-url' => 'Help:Vìsite guidà',
	'guidedtour-tour-test-testing' => 'Prové',
	'guidedtour-tour-test-test-description' => "Costa a l'é na preuva dla descrission. Lorem ipsum dolor sit!",
	'guidedtour-tour-test-callouts' => 'Fumèt ëd test',
	'guidedtour-tour-test-portal-description' => "Costa a l'é la pàgina {{int:portal}}.",
	'guidedtour-tour-test-mediawiki-parse' => "Prové l'arzultà MediaWiki",
	'guidedtour-tour-test-wikitext-description' => "N'element ëd la guida dla vìsita a peul conten-e dël wikitest ch'a deuvra onShow e parseDescription. Ch'a lo deuvra për creé na liura wiki a la [[{{MediaWiki:Guidedtour-help-url}}|Documentassion dle vìsite guidà]]. O na liura esterna [https://github.com/tychay/mwgadget.GuidedTour a Github], për esempi.",
	'guidedtour-tour-test-description-page' => 'Prové le pàgine ëd descrission ëd mediawiki',
	'guidedtour-tour-test-go-description-page' => 'Andé a la pàgina ëd descrission',
	'guidedtour-tour-test-launch-tour' => 'Prové la vìsita inissial',
	'guidedtour-tour-test-launch-tour-description' => "Le guide a peulo creé d'àutre vìsite guidà. Ròba da pocio, neh?",
	'guidedtour-tour-test-launch-using-tours' => 'Ancaminé na vìsita su coma dovré le vìsite',
);

/** Portuguese (português)
 * @author Alchimista
 * @author Fúlvio
 * @author Hamilton Abreu
 * @author HenriqueCrang
 * @author Imperadeiro98
 * @author Josep Maria 15.
 * @author Raylton P. Sousa
 * @author Vitorvicentevalente
 */
$messages['pt'] = array(
	'guidedtour-desc' => 'Permite que páginas proporcionem uma visita guiada por pop-ups, para ajudar os novos utilizadores.',
	'guidedtour-help-url' => 'Help:Visitas guiadas',
	'guidedtour-help-guider-url' => 'Help:Visitas guiadas/guia',
	'guidedtour-next-button' => 'Próximo',
	'guidedtour-okay-button' => 'OK',
	'guidedtour-tour-test-testing' => 'A testar',
	'guidedtour-tour-test-test-description' => 'Este é um teste da descrição. Lorem ipsum sit de dolor!',
	'guidedtour-tour-test-callouts' => 'Textos explicativos de teste',
	'guidedtour-tour-test-portal-description' => 'Esta é a página de {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Testar a análise sintáctica do Mediawiki',
	'guidedtour-tour-test-wikitext-description' => 'Um guia na sua visita guiada pela wiki pode conter wikitexto usando onShow e parseDescription. Use-os para criar um wikilink para o [[{{MediaWiki:Guidedtour-help-url}}|Documentação das visitas guiadas]]. Ou um link externo [https://github.com/tychay/mwgadget.GuidedTour para o GitHub], por exemplo.',
	'guidedtour-tour-test-description-page' => 'Testar páginas de descrição do MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Ir para a página de descrição',
	'guidedtour-tour-test-launch-tour' => 'Teste do lançamento do guia',
	'guidedtour-tour-test-launch-tour-description' => 'Os guias podem lançar outras visitas guiadas, muito interessante, não?',
	'guidedtour-tour-test-launch-using-tours' => 'Lançar um guia sobre como usar visitas guiadas',
	'guidedtour-tour-firstedit-edit-page-title' => 'Pronto para editar?', # Fuzzy
	'guidedtour-tour-firstedit-edit-page-description' => 'Clique no botão "{{int:vector-view-edit}}" para realizar as suas mudanças.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Editar apenas uma secção',
	'guidedtour-tour-firstedit-edit-section-description' => 'Há ligações "{{int:editsection}}" em cada secção da página, para que você possa concentrar-se apenas nesta parte.',
	'guidedtour-tour-firstedit-preview-title' => 'Pré-visualize as suas alterações (opcional)',
	'guidedtour-tour-firstedit-preview-description' => 'Clique em "{{int:showpreview}}" para ver como ficará a página após as suas alterações. Não esqueça de salvá-las!',
	'guidedtour-tour-firstedit-save-title' => 'Está quase pronto!',
	'guidedtour-tour-firstedit-save-description' => 'Quando estiver pronto, clique em "{{int:savearticle}}" para as alterações ficarem visíveis para todos.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Clique no botão "{{int:vector-view-edit}}" para realizar as suas mudanças.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Há ligações "{{int:editsection}}" em cada secção da página, para que você possa concentrar-se apenas nesta parte.',
	'guidedtour-tour-firsteditve-save-description' => 'Quando terminar, clique em "{{int:visualeditor-toolbar-savedialog}}" para as alterações ficarem visíveis para todos.',
);

/** Brazilian Portuguese (português do Brasil)
 * @author HenriqueCrang
 * @author Raylton P. Sousa
 */
$messages['pt-br'] = array(
	'guidedtour-desc' => 'Permite que páginas proporcionem uma visita guiada por pop-ups, para ajudar os novos utilizadores.',
	'guidedtour-help-url' => 'Help:Visitas guiadas',
	'guidedtour-help-guider-url' => 'Help:Visitas guiadas/tutorial',
	'guidedtour-next-button' => 'Próximo',
	'guidedtour-okay-button' => 'Entendido',
	'guidedtour-tour-test-testing' => 'Teste',
	'guidedtour-tour-test-test-description' => 'Está é uma descrição teste. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Teste ao balão do guia',
	'guidedtour-tour-test-portal-description' => 'Esta é a página do {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Teste a análise sintáctica do Mediawiki',
	'guidedtour-tour-test-wikitext-description' => 'Um guia na sua visita guiada pela wiki pode conter wikitexto usando onShow e parseDescription. Use-os para criar um wikilink para o [[{{MediaWiki:Guidedtour-help-url}}|Documentação das visitas guiadas]]. Ou um link externo [https://github.com/tychay/mwgadget.GuidedTour para o GitHub], por exemplo.',
	'guidedtour-tour-test-description-page' => 'Teste de páginas de descrição do MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Ir para página de descrição',
	'guidedtour-tour-test-launch-tour' => 'Teste do lançamento do guia',
	'guidedtour-tour-test-launch-tour-description' => 'Os guias podem lançar outras visitas guiadas, muito legal, não?',
	'guidedtour-tour-test-launch-using-tours' => 'Lançar um guia sobre como usar visitas guiadas',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Pronto|Pronta}} para editar?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Clique no botão "{{int:vector-view-edit}}" para fazer suas mudanças.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Edite apenas uma seção',
	'guidedtour-tour-firstedit-edit-section-description' => 'Existem links para "{{int:editsection}}" em cada seção principal de um artigo, assim você pode se focar na edição apenas naquela parte.',
	'guidedtour-tour-firstedit-preview-title' => 'Prever suas mudanças (opcional)',
	'guidedtour-tour-firstedit-preview-description' => 'Clicando em "{{int:showpreview}}" você pode conferir como vai ficar com suas mudanças. Só não esqueça de salvar!',
	'guidedtour-tour-firstedit-save-title' => 'Você está quase terminando!',
	'guidedtour-tour-firstedit-save-description' => 'Quando estiver pronto, clique em "{{int:savearticle}}" para tornar suas mudanças visíveis para todos.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Clique no botão "{{int:vector-view-edit}}" para fazer suas mudanças.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Existem links para "{{int:editsection}}" em cada seção principal de um artigo, assim você pode se focar na edição apenas naquela parte.',
	'guidedtour-tour-firsteditve-save-description' => 'Quando estiver pronto, clique em "{{int:visualeditor-toolbar-savedialog}}" para tornar suas mudanças visíveis para todos.',
);

/** tarandíne (tarandíne)
 * @author Joetaras
 */
$messages['roa-tara'] = array(
	'guidedtour-desc' => "Permette a le pàggene de avè 'nu popup cu gire guidate pe aijutà le utinde nuève",
	'guidedtour-help-url' => 'Help:Gire guidate',
	'guidedtour-help-guider-url' => 'Help:Gire guidate/guidature',
	'guidedtour-next-button' => 'Prossime',
	'guidedtour-okay-button' => 'Apposte',
	'guidedtour-tour-test-testing' => 'Stoche a teste',
	'guidedtour-tour-test-test-description' => "Quiste jè 'nu teste d'a descrizione. Lorem ipsum dolor sit!",
	'guidedtour-tour-test-callouts' => 'Teste de le didascalie',
	'guidedtour-tour-test-portal-description' => "Queste jè 'a pàgene {{int:portal}}.",
	'guidedtour-tour-test-mediawiki-parse' => "Analizzatore d'u teste de MediaUicchi",
	'guidedtour-tour-test-description-page' => 'Test pàggene de descrizione de MediaUicchi',
	'guidedtour-tour-test-go-description-page' => "Vèje sus 'a pàgene de descrizione",
	'guidedtour-tour-test-launch-tour' => "Test de lange d'u gire",
	'guidedtour-tour-test-launch-tour-description' => 'Le guidature ponne langià otre gire guidate. A uerre, no!?',
	'guidedtour-tour-test-launch-using-tours' => "Lange 'nu gire pe ausè le gire",
	'guidedtour-tour-firstedit-edit-page-title' => 'Pronde a cangià?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Cazze \'u buttone "{{int:vector-view-edit}}" pe fà le cangiaminde.',
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => 'Cazze \'u buttone "{{int:visualeditor-ca-editsource}}" pe fà le cangiaminde.',
	'guidedtour-tour-firstedit-edit-section-title' => "Cange giuste 'na sezione",
	'guidedtour-tour-firstedit-edit-section-description' => 'Stonne collegaminde "{{int:editsection}}" pe ogne sezione prengepàle jndr\'à vôsce, accussì tu puè congendrarte sus a quedda parte.', # Fuzzy
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => 'Stonne collegaminde "{{int:visualeditor-ca-editsource-section}}" pe ogne sezione prengepàle jndr\'à vôsce, accussì tu puè congendrarte sus a quedda parte.', # Fuzzy
	'guidedtour-tour-firstedit-preview-title' => "Fà vedè l'andeprime de le cangiaminde tune (facoltative)",
	'guidedtour-tour-firstedit-preview-description' => "Cazzanne '{{int:showpreview}}' te permette de verificà ca 'a pàgene iesse cu le cangiaminde tune. No te demendicà de reggistrà.",
	'guidedtour-tour-firstedit-save-title' => "Tu l'è ggià fatte!",
	'guidedtour-tour-firstedit-save-description' => 'Quanne si pronde, cazzanne "{{int:savearticle}}" face devendà le cangiaminde tune visibbile a tutte.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Cazze \'u buttone "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" pe fà le cangiaminde tune.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Stonne collegaminde "{{int:editsection}} {{int:visualeditor-beta-appendix}}" pe ogne sezione prengepàle jndr\'à vôsce, accussì tu puè congendrarte sus a quedda parte.', # Fuzzy
	'guidedtour-tour-firsteditve-save-description' => 'Quanne sì pronde, cazzanne "{{int:visualeditor-toolbar-savedialog}}" le cangiaminde tune addevendane visibbile a tutte.',
);

/** Russian (русский)
 * @author DCamer
 * @author KPu3uC B Poccuu
 * @author Okras
 */
$messages['ru'] = array(
	'guidedtour-desc' => 'Позволяет добавлять на страницы обучающие туры со всплывающими подсказками, чтобы помочь новым пользователям',
	'guidedtour-help-url' => 'Help:Обучающие туры',
	'guidedtour-help-guider-url' => 'Help:Обучающие туры/гиды',
	'guidedtour-next-button' => 'Далее',
	'guidedtour-okay-button' => 'Ок',
	'guidedtour-tour-test-testing' => 'Тестирование',
	'guidedtour-tour-test-test-description' => 'Это проверка описания. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Тест выноски',
	'guidedtour-tour-test-portal-description' => 'Это страница, относящаяся к разделу {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Тест mediawiki parse',
	'guidedtour-tour-test-description-page' => 'Тестирование страниц описания MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Перейти на страницу описания',
	'guidedtour-tour-test-launch-tour' => 'Тестовый запуск тура',
	'guidedtour-tour-test-launch-tour-description' => 'Гиды могут запускать другие туры. Довольно круто, да?',
	'guidedtour-tour-test-launch-using-tours' => 'Запустить тур по использованию туров',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Готовы}} редактировать?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Нажмите кнопку «{{int:vector-view-edit}}, чтобы внести изменения.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Измените только раздел',
	'guidedtour-tour-firstedit-edit-section-description' => 'Cсылки «{{int:editsection}}» есть для каждого из основных разделов страницы, так что вы можете сосредоточиться только на конкретной её части.',
	'guidedtour-tour-firstedit-preview-title' => 'Предварительно просмотрите свои изменения (не обязательно)',
	'guidedtour-tour-firstedit-preview-description' => 'Нажатие «{{int:showpreview}}» позволяет проверить, как будет выглядеть страница с вашими изменениями. Только не забудьте её потом сохранить!',
	'guidedtour-tour-firstedit-save-title' => 'Вы почти закончили!',
	'guidedtour-tour-firstedit-save-description' => 'Когда вы будете готовы, нажатие «{{int:savearticle}}» сделает изменения видимыми для всех.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Нажмите кнопку «{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}», чтобы внести свои изменения.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Ссылки «{{int:editsection}} {{int:visualeditor-beta-appendix}}» существуют для каждого раздела страницы, так что вы можете сосредоточиться только на этой её части.',
	'guidedtour-tour-firsteditve-save-description' => 'Когда вы будете готовы, нажатие «{{int:visualeditor-toolbar-savedialog}}» сделает изменения видимыми для всех.',
);

/** Slovenian (slovenščina)
 * @author Dbc334
 * @author Eleassar
 */
$messages['sl'] = array(
	'guidedtour-tour-firsteditve-edit-page-description' => 'Za prikaz sprememb, ki ste jih vnesli, kliknite gumb  »{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}«.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'V vsakem večjem razdelku strani so na razpolago povezave »{{int:editsection}} {{int:visualeditor-beta-appendix}}«, s katerimi se lahko osredotočite samo na ta del.',
	'guidedtour-tour-firsteditve-save-description' => 'Ko boste pripravljeni, lahko s klikom gumba »{{int:visualeditor-toolbar-savedialog}}« svoje spremembe napravite vidne vsakomur.',
);

/** Serbian (Cyrillic script) (српски (ћирилица)‎)
 * @author Milicevic01
 */
$messages['sr-ec'] = array(
	'guidedtour-next-button' => 'Следеће',
);

/** Swedish (svenska)
 * @author Ainali
 * @author Jopparn
 * @author Lokal Profil
 * @author Tobulos1
 */
$messages['sv'] = array(
	'guidedtour-desc' => 'Tillåter sidor att ge en popup-guidad tur för att hjälpa nya användare',
	'guidedtour-help-url' => 'Help:Guidade turer',
	'guidedtour-help-guider-url' => 'Help:Guidade turer/guider',
	'guidedtour-next-button' => 'Nästa',
	'guidedtour-okay-button' => 'Okej',
	'guidedtour-tour-test-testing' => 'Testar',
	'guidedtour-tour-test-test-description' => 'Detta är ett test av beskrivningen. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Testa utpekning',
	'guidedtour-tour-test-portal-description' => 'Detta är sidan {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Testa MediaWikitolkning',
	'guidedtour-tour-test-description-page' => 'Testa sidbeskrivning för MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Gå till beskrivningssida',
	'guidedtour-tour-test-launch-tour' => 'Testa starta tur',
	'guidedtour-tour-test-launch-tour-description' => 'Turer kan starta andra guidade turer. Ganska coolt, va?',
	'guidedtour-tour-test-launch-using-tours' => 'Starta en tur om att använda turer',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Redo}} att redigera?',
	'guidedtour-tour-firstedit-edit-page-description' => "Klicka på '{{int:vector-view-edit}}'-knappen för att göra dina ändringar.",
	'guidedtour-tour-firstedit-edit-section-title' => 'Redigera bara ett avsnitt',
	'guidedtour-tour-firstedit-edit-section-description' => 'Det finns "{{int:editsection}}"-länkar för varje större avsnitt på en sida, så att du kan fokusera på endast den delen.',
	'guidedtour-tour-firstedit-preview-title' => 'Förhandsgranska dina ändringar (valfritt)',
	'guidedtour-tour-firstedit-preview-description' => 'Genom att klicka på "{{int:showpreview}}" kan du kontrollera hur sidan ser ut med dina ändringar. Glöm bara inte att spara!',
	'guidedtour-tour-firstedit-save-title' => 'Du är nästan klar!',
	'guidedtour-tour-firstedit-save-description' => 'När du är redo, kommer ett klick på "{{int:savearticle}}" att synliggöra dina ändringar för alla.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Klicka på knappen "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}" för att göra dina ändringar.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Det finns "{{int:editsection}} {{int:visualeditor-beta-appendix}}"-länkar för varje större avsnitt på en sida, så att du kan fokusera på endast den delen.',
	'guidedtour-tour-firsteditve-save-description' => 'När du är redo, kommer ett klick på "{{int:visualeditor-toolbar-savedialog}}" att synliggöra dina ändringar för alla.',
);

/** Ukrainian (українська)
 * @author Andriykopanytsia
 * @author Base
 */
$messages['uk'] = array(
	'guidedtour-desc' => 'Дозволяє сторінкам виводити вспливаюче навчання для допомоги новачкам',
	'guidedtour-help-url' => 'Help:Інтерактивні тури',
	'guidedtour-help-guider-url' => 'Довідка: інтерактивні тури/гід',
	'guidedtour-next-button' => 'Далі',
	'guidedtour-okay-button' => 'Гаразд',
	'guidedtour-tour-test-testing' => 'Тестування',
	'guidedtour-tour-test-test-description' => 'Це випробування опису. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Тест виноски',
	'guidedtour-tour-test-portal-description' => 'Це сторінка {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Тест аналізу Медіавікі',
	'guidedtour-tour-test-description-page' => 'Тест опису сторінок Медіавікі',
	'guidedtour-tour-test-go-description-page' => 'Перейти на сторінку опису',
	'guidedtour-tour-test-launch-tour' => 'Тестовий запуск туру',
	'guidedtour-tour-test-launch-tour-description' => 'Гід може запускати інші інтерактивні тури. Досить непогано, так?',
	'guidedtour-tour-test-launch-using-tours' => 'Запуск тур по використанню турів',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|Готовий|Готова}} редагувати?',
	'guidedtour-tour-firstedit-edit-page-description' => "Натисніть кнопку  '{{int:vector-view-edit}}', щоб внести зміни.",
	'guidedtour-tour-firstedit-edit-section-title' => 'Редагувати тільки в розділі',
	'guidedtour-tour-firstedit-edit-section-description' => 'Існують посилання "{{int:editsection}}" для кожного головного розділу на сторінці, тому ви можете зосередитися тільки на цій частині.',
	'guidedtour-tour-firstedit-preview-title' => 'Переглянути ваші зміни (за бажанням)',
	'guidedtour-tour-firstedit-preview-description' => 'Клацання "{{int:showpreview}}" дає змогу перевіряти вигляд сторінки із внесеними змінами. Тільки не забудьте зберегти!',
	'guidedtour-tour-firstedit-save-title' => 'Майже все готове!',
	'guidedtour-tour-firstedit-save-description' => "Коли ви будете готові, натискання '{{int:savearticle}}' зробить ваші зміни видимими для всіх.",
	'guidedtour-tour-firsteditve-edit-page-description' => 'Натисніть на кнопку "{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}", аби внести свої зміни.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Існують посилання "{{int:editsection}} {{int:visualeditor-beta-appendix}}" для кожного головного розділу на сторінці, тому ви можете зосередитися тільки на цій частині.',
	'guidedtour-tour-firsteditve-save-description' => 'Коли ви будете готові, натискання "{{int:visualeditor-toolbar-savedialog}}" зробить ваші зміни видимими для всіх.',
);

/** Vietnamese (Tiếng Việt)
 * @author Minh Nguyen
 */
$messages['vi'] = array(
	'guidedtour-desc' => 'Hiển thị chương trình hướng dẫn sử dụng cho những người dùng mới',
	'guidedtour-help-url' => 'Help:Chương trình hướng dẫn sử dụng',
	'guidedtour-help-guider-url' => 'Help:Chương trình hướng dẫn sử dụng/giới thiệu',
	'guidedtour-custom.css' => '/* Mã CSS tùy biến dành cho phần mở rộng GuidedTour. */',
	'guidedtour-next-button' => 'Tiếp',
	'guidedtour-okay-button' => 'OK',
	'guidedtour-tour-test-testing' => 'Đo thử',
	'guidedtour-tour-test-test-description' => 'Thử lời miêu tả đây. Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => 'Thử ô gọi',
	'guidedtour-tour-test-portal-description' => 'Đây là trang {{int:portal}}.',
	'guidedtour-tour-test-mediawiki-parse' => 'Kiểm tra bộ phân tích MediaWiki',
	'guidedtour-tour-test-wikitext-description' => 'Một hộp trong chương trình hướng dẫn wiki có thể sử dụng onShow và parseDescription trong văn bản wiki. Bạn có thể chẳng hạn đặt một liên kết đến [[{{MediaWiki:Guidedtour-help-url}}|tài liệu chương trình hướng dẫn]] hoặc đến [https://github.com/tychay/mwgadget.GuidedTour tài liệu tại GitHub] trong hộp hướng dẫn.',
	'guidedtour-tour-test-description-page' => 'Kiểm tra trang miêu tả MediaWiki',
	'guidedtour-tour-test-go-description-page' => 'Mở trang miêu tả.',
	'guidedtour-tour-test-launch-tour' => 'Thử mở hướng dẫn',
	'guidedtour-tour-test-launch-tour-description' => 'Các hộp hướng dẫn có thể mở hướng dẫn khác. Hay nhỉ?',
	'guidedtour-tour-test-launch-using-tours' => 'Mở hướng dẫn chỉ cách sử dụng hướng dẫn',
	'guidedtour-tour-firstedit-edit-page-title' => 'Có sẵn sàng để sửa đổi?',
	'guidedtour-tour-firstedit-edit-page-description' => 'Bấm nút “{{int:vector-view-edit}}” để sửa đổi trang.',
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => 'Bấm nút “{{int:visualeditor-ca-editsource}}” để sửa đổi trang.',
	'guidedtour-tour-firstedit-edit-section-title' => 'Chỉ việc sửa đôi một phần trang',
	'guidedtour-tour-firstedit-edit-section-description' => 'Mỗi tiêu đề lớn trên trang có liên kết “{{int:editsection}}” để chỉ sửa đổi phần trang đó.',
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => 'Mỗi tiêu đề lớn trên trang có liên kết “{{int:visualeditor-ca-editsource-section}}” để nhảy tới tiêu đề khi bắt đầu sửa đổi.',
	'guidedtour-tour-firstedit-preview-title' => 'Xem trước các thay đổi của bạn (tùy chọn)',
	'guidedtour-tour-firstedit-preview-description' => 'Bấm “{{int:showpreview}}” để kiểm tra các thay đổi của bạn có hiển thị như bạn muốn. Đừng quên lưu trang!',
	'guidedtour-tour-firstedit-save-title' => 'Gần xong rồi!',
	'guidedtour-tour-firstedit-save-description' => 'Sau khi sửa đổi xong, bấm “{{int:savearticle}}” để xuất bản các thay đổi của bạn để cho mọi người xem.',
	'guidedtour-tour-firsteditve-edit-page-description' => 'Bấm nút “{{int:vector-view-edit}} {{int:visualeditor-beta-appendix}}” để thực hiện các thay đổi.',
	'guidedtour-tour-firsteditve-edit-section-description' => 'Mỗi tiêu đề lớn trên trang có liên kết “{{int:editsection}} {{int:visualeditor-beta-appendix}}” để nhảy tới tiêu đề khi bắt đầu sửa đổi.',
	'guidedtour-tour-firsteditve-save-description' => 'Sau khi sửa đổi xong, bấm “{{int:visualeditor-toolbar-savedialog}}” để xuất bản các thay đổi của bạn để cho mọi người xem.',
);

/** Wu (吴语)
 * @author Benojan
 * @author 十弌
 */
$messages['wuu'] = array(
	'guidedtour-tour-firstedit-edit-page-title' => '準備開改來爻朆？',
	'guidedtour-tour-firstedit-edit-page-visualeditor-description' => '點 "{{int:visualeditor-ca-editsource}}" 捺鈕準定改動。',
	'guidedtour-tour-firstedit-edit-section-title' => '便改一個章節',
	'guidedtour-tour-firstedit-edit-section-visualeditor-description' => '文章各個主要章節都有 "{{int:visualeditor-ca-editsource-section}}" 鏈接, 爾好專門入心一箇部份。', # Fuzzy
	'guidedtour-tour-firstedit-preview-title' => '变化望望相起（随意）',
	'guidedtour-tour-firstedit-save-title' => '爾便要妝了滯爻！',
	'guidedtour-tour-firstedit-save-description' => '準備起爻，點"{{int:savearticle}}"大家人便都望得著爾個改動爻。',
);

/** Simplified Chinese (中文（简体）‎)
 * @author Byfserag
 * @author Hydra
 * @author Li3939108
 * @author Liflon
 * @author Liuxinyu970226
 * @author Shizhao
 * @author Yfdyh000
 * @author 乌拉跨氪
 */
$messages['zh-hans'] = array(
	'guidedtour-desc' => '允许页面提供弹出式导览来引导新用户',
	'guidedtour-help-url' => 'Help:导览',
	'guidedtour-help-guider-url' => 'Help:导览/向导',
	'guidedtour-next-button' => '下一步',
	'guidedtour-okay-button' => '好的',
	'guidedtour-tour-test-testing' => '测试',
	'guidedtour-tour-test-test-description' => '这是一个描述的测试。Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => '测试标注',
	'guidedtour-tour-test-portal-description' => '这是{{int:portal}}页。',
	'guidedtour-tour-test-mediawiki-parse' => '测试MediaWiki解析器',
	'guidedtour-tour-test-description-page' => '测试MediaWiki描述页',
	'guidedtour-tour-test-go-description-page' => '转到描述页面',
	'guidedtour-tour-test-launch-tour' => '测试启动导览',
	'guidedtour-tour-test-launch-tour-description' => '向导可以启动其他导览。很酷，对吧？',
	'guidedtour-tour-test-launch-using-tours' => '在使用的导览上启动一个导览',
	'guidedtour-tour-firstedit-edit-page-title' => '{{GENDER:|准备好}}编辑了吗？',
	'guidedtour-tour-firstedit-edit-page-description' => '点击“{{int:vector-view-edit}}”按钮，确认你的修改。',
	'guidedtour-tour-firstedit-edit-section-title' => '只编辑一个章节',
	'guidedtour-tour-firstedit-edit-section-description' => '页面中每个主要章节都有“{{int:editsection}}”链接，让你可以只集中精神在这一部分上。',
	'guidedtour-tour-firstedit-preview-title' => '预览您的更改（可选）',
	'guidedtour-tour-firstedit-preview-description' => '点击“{{int:showpreview}}”，您将看到您作出了更改后页面看上去会如何。请不要忘记保存！',
	'guidedtour-tour-firstedit-save-title' => '你马上就要完成了！',
	'guidedtour-tour-firstedit-save-description' => '当你准备好时，点击“{{int:savearticle}}”，这样您所做的修改所有人都会看到。',
	'guidedtour-tour-firsteditve-edit-page-description' => '单击“{{int:vector-view-edit}}{{int:visualeditor-beta-appendix}}”按钮以作出您的更改。',
	'guidedtour-tour-firsteditve-edit-section-description' => '页面中每个主要章节都有“{{int:editsection}}{{int:visualeditor-beta-appendix}}”链接，让你可以只集中精神在这一部分上。',
	'guidedtour-tour-firsteditve-save-description' => '当你准备好时，点击“{{int:visualeditor-toolbar-savedialog}}”，便会让所有人都能看到你所做的修改。',
);

/** Traditional Chinese (中文（繁體）‎)
 * @author Byfserag
 * @author Simon Shek
 */
$messages['zh-hant'] = array(
	'guidedtour-desc' => '允許頁面提供彈出式導覽來引導新用戶',
	'guidedtour-help-url' => 'Help:導覽',
	'guidedtour-help-guider-url' => 'Help:導覽/嚮導',
	'guidedtour-next-button' => '下一步',
	'guidedtour-okay-button' => '好的',
	'guidedtour-tour-test-testing' => '測試',
	'guidedtour-tour-test-test-description' => '這是一個描述的測試。 Lorem ipsum dolor sit!',
	'guidedtour-tour-test-callouts' => '測試標注',
	'guidedtour-tour-test-portal-description' => '這是 {{int:portal}} 頁。',
	'guidedtour-tour-test-mediawiki-parse' => '測試MediaWiki解析器',
	'guidedtour-tour-test-description-page' => '測試 MediaWiki 描述頁',
	'guidedtour-tour-test-go-description-page' => '轉到描述頁',
	'guidedtour-tour-test-launch-tour' => '測試啟動導覽',
	'guidedtour-tour-test-launch-tour-description' => '嚮導可以啟動其他導覽。很酷，對吧？',
	'guidedtour-tour-test-launch-using-tours' => '在使用的導覽上啟動一個導覽',
	'guidedtour-tour-firstedit-edit-page-title' => '準備好編輯了嗎？',
	'guidedtour-tour-firstedit-edit-page-description' => '點擊“{{int:vector-view-edit}}”按鈕，確認你的修改。',
	'guidedtour-tour-firstedit-edit-section-title' => '只編輯一個章節',
	'guidedtour-tour-firstedit-edit-section-description' => '頁面中每個主要章節都有“{{int:editsection}}”鏈接，讓你可以只集中精神在這一部分上。',
	'guidedtour-tour-firstedit-preview-title' => '預覽您的更改（可選）',
	'guidedtour-tour-firstedit-preview-description' => '點擊“{{int:showpreview}}”，您將看到您作出了更改後頁面看上去會如何。請不要忘記保存！',
	'guidedtour-tour-firstedit-save-title' => '你馬上就要完成了！',
	'guidedtour-tour-firstedit-save-description' => '當你準備好時，點擊“{{int:savearticle}}”，這樣您所做的修改所有人都會看到。',
	'guidedtour-tour-firsteditve-edit-page-description' => '單擊“{{int:vector-view-edit}}{{int:visualeditor-beta-appendix}}”按鈕以作出您的更改。',
	'guidedtour-tour-firsteditve-edit-section-description' => '頁面中每個主要章節都有“{{int:editsection}}{{int:visualeditor-beta-appendix}}”鏈接，讓你可以只集中精神在這一部分上。',
	'guidedtour-tour-firsteditve-save-description' => '當你準備好時，點擊“{{int:visualeditor-toolbar-savedialog}}”，便會讓所有人都能看到你所做的修改。',
);
