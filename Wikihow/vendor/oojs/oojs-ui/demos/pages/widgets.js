Demo.static.pages.widgets = function ( demo ) {
	// `\u200E` is Unicode LTR marker and is added for lorem ipsum text in RTL demo, as it's not translated
	var i, fieldsets,
		loremIpsum = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, ' +
			'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\u200E',
		textInputForLabel, labelForTextInput, radioSelectInputForLabel, labelForRadioSelectInput,
		inputForValidation, fieldLayoutForValidation,
		horizontalDragItems = [],
		verticalDragItems = [],
		verticalHandledDragItems = [],
		$overlay = $( '<div>' ).addClass( 'demo-overlay' ).attr( 'id', 'demo-overlay' ),
		$demo = demo.$element,
		fixedItemsTagMultiselectWidget = new OO.ui.TagMultiselectWidget( {
			placeholder: 'Add tags',
			allowArbitrary: true
		} );

	fixedItemsTagMultiselectWidget.addTag( 'item1', 'Item 1 (optional)' );
	fixedItemsTagMultiselectWidget.addTag( 'item2', 'Item 2 (mandatory)' );
	fixedItemsTagMultiselectWidget.findItemFromData( 'item2' ).setFixed( true );

	for ( i = 0; i <= 12; i++ ) {
		horizontalDragItems.push(
			new Demo.DraggableItemWidget( {
				data: 'item' + i,
				icon: 'tag',
				label: 'Inline item ' + i
			} )
		);
		if ( i <= 6 ) {
			verticalDragItems.push(
				new Demo.DraggableItemWidget( {
					data: 'item' + i,
					icon: 'tag',
					label: 'Item ' + i
				} )
			);
			verticalHandledDragItems.push(
				new Demo.DraggableHandledItemWidget( {
					data: 'item' + i,
					icon: 'draggable',
					label: 'Item ' + i
				} )
			);
		}
	}

	textInputForLabel = new OO.ui.TextInputWidget( { value: 'Input for label above' } );
	labelForTextInput = new OO.ui.LabelWidget( {
		label: 'Label for TextInputWidget below',
		input: textInputForLabel
	} );

	radioSelectInputForLabel = new OO.ui.RadioSelectInputWidget( {
		options: [
			{
				data: 'a',
				label: 'Input for label above'
			},
			{
				data: 'b',
				label: 'Input for label above'
			}
		]
	} );
	labelForRadioSelectInput = new OO.ui.LabelWidget( {
		label: 'Label for RadioSelectInputWidget below',
		input: radioSelectInputForLabel
	} );

	inputForValidation = new OO.ui.TextInputWidget( {
		validate: function ( value ) {
			return value.length % 2 === 0;
		}
	} );

	fieldLayoutForValidation = new OO.ui.FieldLayout( inputForValidation, {
		align: 'top',
		label: 'FieldLayout aligned top with validation errors',
		help: 'Enter only even number of characters'
	} );

	inputForValidation.$input.on( 'blur', function () {
		inputForValidation.getValidity().then( function () {
			fieldLayoutForValidation.setErrors( [] );
		}, function () {
			fieldLayoutForValidation.setErrors( [
				'Please enter an even number of characters'
			] );
		} );
	} );

	fieldsets = [
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-buttons',
			label: 'Buttons',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( { label: 'Normal' } ),
					{
						label: 'ButtonWidget (normal)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Progressive',
						flags: [ 'progressive' ]
					} ),
					{
						label: 'ButtonWidget (progressive)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Destructive',
						flags: [ 'destructive' ]
					} ),
					{
						label: 'ButtonWidget (destructive)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Primary progressive',
						flags: [ 'primary', 'progressive' ]
					} ),
					{
						label: 'ButtonWidget (primary, progressive)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Primary destructive',
						flags: [ 'primary', 'destructive' ]
					} ),
					{
						label: 'ButtonWidget (primary, destructive)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Disabled',
						disabled: true
					} ),
					{
						label: 'ButtonWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Progressive',
						icon: 'tag',
						flags: [ 'progressive' ],
						disabled: true
					} ),
					{
						label: 'ButtonWidget (progressive, icon, disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Icon',
						icon: 'tag'
					} ),
					{
						label: 'ButtonWidget (icon)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Icon',
						icon: 'tag',
						flags: [ 'progressive' ]
					} ),
					{
						label: 'ButtonWidget (icon, progressive)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Indicator',
						indicator: 'down'
					} ),
					{
						label: 'ButtonWidget (indicator)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Indicator',
						indicator: 'down',
						flags: [ 'progressive' ]
					} ),
					{
						label: 'ButtonWidget (indicator, progressive)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Access key: G',
						accessKey: 'g'
					} ),
					{
						label: 'ButtonWidget (with accesskey)',
						align: 'top',
						help: new OO.ui.HtmlSnippet( 'Notice: Using `accesskey` might <a href="http://webaim.org/techniques/keyboard/accesskey" target="_blank" rel="noopener">negatively impact screen readers</a>!' )
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						icon: 'edit',
						label: 'Edit',
						invisibleLabel: true,
						title: 'Icon only, framed'
					} ),
					{
						label: 'ButtonWidget (icon only)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						framed: false,
						icon: 'edit',
						label: 'Edit',
						invisibleLabel: true,
						title: 'Icon only'
					} ),
					{
						label: 'ButtonWidget (frameless, icon only)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						framed: false,
						icon: 'tag',
						label: 'Labeled'
					} ),
					{
						label: 'ButtonWidget (frameless)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						framed: false,
						flags: [ 'progressive' ],
						icon: 'check',
						label: 'Progressive'
					} ),
					{
						label: 'ButtonWidget (frameless, progressive)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						framed: false,
						flags: [ 'warning' ],
						icon: 'alert',
						label: 'Warning'
					} ),
					{
						label: 'ButtonWidget (frameless, warning)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						framed: false,
						flags: [ 'destructive' ],
						icon: 'trash',
						label: 'Destructive'
					} ),
					{
						label: 'ButtonWidget (frameless, destructive)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						framed: false,
						flags: [ 'destructive' ],
						label: 'Cancel'
					} ),
					{
						label: 'ButtonWidget (frameless, label-only, destructive)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						framed: false,
						icon: 'tag',
						label: 'Disabled',
						disabled: true
					} ),
					{
						label: 'ButtonWidget (frameless, disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						framed: false,
						icon: 'tag',
						indicator: 'down',
						label: 'Labeled'
					} ),
					{
						label: 'ButtonWidget (frameless, icon & label & indicator)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						framed: false,
						indicator: 'down',
						label: 'Labeled'
					} ),
					{
						label: 'ButtonWidget (frameless, label & indicator)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						framed: false,
						icon: 'tag',
						indicator: 'down',
						title: 'Icon & indicator'
					} ),
					{
						label: 'ButtonWidget (frameless, icon & indicator)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonInputWidget( {
						label: 'Submit the form',
						type: 'submit',
						flags: [ 'primary', 'progressive' ],
						useInputTag: true
					} ),
					{
						align: 'top',
						label: 'ButtonInputWidget (using <input>)'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonInputWidget( {
						label: 'Another button',
						type: 'button'
					} ),
					{
						align: 'top',
						label: 'ButtonInputWidget (using <button>)'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonInputWidget( {
						label: 'Access key: H',
						title: 'Accesskey is added to the title.',
						accessKey: 'h'
					} ),
					{
						label: 'ButtonInputWidget (with accesskey & title)',
						align: 'top',
						help: new OO.ui.HtmlSnippet( 'Notice: Using `accesskey` might <a href="http://webaim.org/techniques/keyboard/accesskey" target="_blank" rel="noopener">negatively impact screen readers</a>!' )
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonInputWidget( {
						framed: false,
						label: 'Another button',
						type: 'button'
					} ),
					{
						align: 'top',
						label: 'ButtonInputWidget (frameless, using <button>)'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonInputWidget( {
						framed: false,
						label: 'Another button',
						type: 'button',
						useInputTag: true
					} ),
					{
						align: 'top',
						label: 'ButtonInputWidget (frameless, using <input>)'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ToggleButtonWidget( { label: 'Toggle' } ),
					{
						label: 'ToggleButtonWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ToggleButtonWidget( { label: 'Toggle', value: true } ),
					{
						label: 'ToggleButtonWidget (initially active)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ToggleButtonWidget( {
						icon: 'previous',
						label: 'Previous',
						invisibleLabel: true
					} ),
					{
						label: 'ToggleButtonWidget (icon only)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ToggleButtonWidget( {
						icon: 'next',
						label: 'Next',
						invisibleLabel: true,
						value: true
					} ),
					{
						label: 'ToggleButtonWidget (icon only, initially active)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-button-sets',
			label: 'Button sets',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.ButtonGroupWidget( {
						items: [
							new OO.ui.ButtonWidget( {
								icon: 'tag',
								label: 'One'
							} ),
							new OO.ui.ButtonWidget( {
								label: 'Two'
							} ),
							new OO.ui.ButtonWidget( {
								indicator: 'clear',
								label: 'Three'
							} )
						]
					} ),
					{
						label: 'ButtonGroupWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonGroupWidget( {
						items: [
							new OO.ui.ButtonWidget( {
								label: 'One',
								flags: [ 'destructive' ]
							} ),
							new OO.ui.ButtonWidget( {
								label: 'Two',
								flags: [ 'progressive' ]
							} )
						]
					} ),
					{
						label: 'ButtonGroupWidget (destructive and progressive ButtonWidget)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonGroupWidget( {
						items: [
							new OO.ui.ButtonWidget( {
								icon: 'tag',
								label: 'Tag',
								invisibleLabel: true,
								flags: [ 'destructive' ]
							} ),
							new OO.ui.ButtonWidget( {
								label: 'Two',
								flags: [ 'progressive' ]
							} )
						]
					} ),
					{
						label: 'ButtonGroupWidget (destructive icon and progressive text)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonGroupWidget( {
						items: [
							new OO.ui.ToggleButtonWidget( {
								icon: 'tag',
								label: 'One'
							} ),
							new OO.ui.ToggleButtonWidget( {
								label: 'Two'
							} ),
							new OO.ui.ToggleButtonWidget( {
								indicator: 'clear',
								label: 'Three'
							} )
						],
						title: 'Choose wisely'
					} ),
					{
						label: 'ButtonGroupWidget with ToggleButtonWidgets',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonSelectWidget( {
						items: [
							new OO.ui.ButtonOptionWidget( {
								data: 'b',
								icon: 'tag',
								label: 'One'
							} ),
							new OO.ui.ButtonOptionWidget( {
								data: 'c',
								label: 'Two'
							} ),
							new OO.ui.ButtonOptionWidget( {
								data: 'd',
								indicator: 'clear',
								label: 'Three'
							} )
						]
					} ),
					{
						label: 'ButtonSelectWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonSelectWidget( {
						disabled: true,
						items: [
							new OO.ui.ButtonOptionWidget( {
								data: 'b',
								icon: 'tag',
								label: 'One'
							} ),
							new OO.ui.ButtonOptionWidget( {
								data: 'c',
								label: 'Two'
							} ),
							new OO.ui.ButtonOptionWidget( {
								data: 'd',
								indicator: 'clear',
								label: 'Three'
							} )
						]
					} ),
					{
						label: 'ButtonSelectWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonSelectWidget( {
						items: [
							new OO.ui.ButtonOptionWidget( {
								data: 'b',
								icon: 'tag',
								label: 'One',
								disabled: true
							} ),
							new OO.ui.ButtonOptionWidget( {
								data: 'c',
								label: 'Two'
							} ),
							new OO.ui.ButtonOptionWidget( {
								data: 'd',
								indicator: 'clear',
								label: 'Three'
							} )
						]
					} ),
					{
						label: 'ButtonSelectWidget (disabled items)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonSelectWidget( {
						items: [
							new OO.ui.ButtonOptionWidget( {
								data: 'a',
								label: 'Access key: I',
								accessKey: 'i',
								title: 'Press browser accelerator key + '
							} ),
							new OO.ui.ButtonOptionWidget( {
								data: 'b',
								label: 'Access key: J',
								accessKey: 'j'
							} ),
							new OO.ui.ButtonOptionWidget( {
								data: 'c',
								label: 'Access key: K',
								accessKey: 'k'
							} )
						]
					} ),
					{
						label: 'ButtonSelectWidget (with accesskeys)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-button-showcase',
			label: 'Button style showcase',
			items: [
				new OO.ui.FieldLayout(
					new Demo.ButtonStyleShowcaseWidget(),
					{
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-inputs',
			label: 'Inputs: TextInput, TextInput, MultilineTextInput, SearchInput, NumberInput',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( { value: 'Text input' } ),
					{
						label: 'TextInputWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( { icon: 'globe' } ),
					{
						label: 'TextInputWidget (icon)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						required: true,
						validate: 'non-empty'
					} ),
					{
						label: 'TextInputWidget (required)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						validate: function ( value ) {
							return value.length % 2 === 0;
						}
					} ),
					{
						label: 'TextInputWidget (only allows even number of characters)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( { placeholder: 'Placeholder' } ),
					{
						label: 'TextInputWidget (placeholder)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						value: 'Title attribute',
						title: 'Title attribute with more information about me.'
					} ),
					{
						label: 'TextInputWidget (with title)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						value: 'Readonly',
						readOnly: true
					} ),
					{
						label: 'TextInputWidget (readonly)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						value: 'Disabled',
						disabled: true
					} ),
					{
						label: 'TextInputWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MultilineTextInputWidget( {
						value: 'Multiline\nMultiline'
					} ),
					{
						label: 'MultilineTextInputWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MultilineTextInputWidget( {
						rows: 15,
						value: 'Multiline\nMultiline'
					} ),
					{
						label: 'MultilineTextInputWidget (rows=15)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MultilineTextInputWidget( {
						autosize: true,
						value: 'Autosize\nAutosize\nAutosize\nAutosize'
					} ),
					{
						label: 'MultilineTextInputWidget (autosize)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MultilineTextInputWidget( {
						rows: 10,
						autosize: true,
						value: 'Autosize\nAutosize\nAutosize\nAutosize'
					} ),
					{
						label: 'MultilineTextInputWidget (autosize, rows=10)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MultilineTextInputWidget( {
						autosize: true,
						icon: 'tag',
						indicator: 'clear',
						label: 'Inline label',
						value: 'Autosize\nAutosize\nAutosize\nAutosize'
					} ),
					{
						label: 'MultilineTextInputWidget (autosize, icon, indicator, label)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						value: 'Text input with label',
						label: 'Inline label'
					} ),
					{
						label: 'TextInputWidget (label)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						value: 'Text input with label',
						label: 'Inline label',
						labelPosition: 'before'
					} ),
					{
						label: 'TextInputWidget (label[position=before])',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						icon: 'tag',
						indicator: 'clear',
						value: 'Text input with label',
						label: 'Inline label'
					} ),
					{
						label: 'TextInputWidget (icon, indicator, label)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						icon: 'tag',
						indicator: 'clear',
						value: 'Text input with label',
						label: 'Inline label',
						labelPosition: 'before'
					} ),
					{
						label: 'TextInputWidget (icon, indicator, label[position=before])',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						value: 'Disabled',
						icon: 'tag',
						indicator: 'clear',
						label: 'Inline label',
						disabled: true
					} ),
					{
						label: 'TextInputWidget (icon, indicator, label, disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						value: 'Access key: S',
						accessKey: 's'
					} ),
					{
						label: 'TextInputWidget (with accesskey)',
						align: 'top',
						help: new OO.ui.HtmlSnippet( 'Notice: Using `accesskey` might <a href="http://webaim.org/techniques/keyboard/accesskey" target="_blank" rel="noopener">negatively impact screen readers</a>!' )
					}
				),
				new OO.ui.FieldLayout(
					new Demo.DynamicLabelTextInputWidget( {
						getLabelText: function ( value ) {
							return String( value.length );
						}
					} ),
					{
						label: 'TextInputWidget (with dynamic label – length)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new Demo.DynamicLabelTextInputWidget( {
						maxLength: 300,
						getLabelText: function ( value ) {
							return String( 300 - value.length );
						}
					} ),
					{
						label: 'TextInputWidget (with dynamic label – remaining length)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.SearchInputWidget(),
					{
						label: 'SearchInputWidget (type=search)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.SearchInputWidget( { disabled: true } ),
					{
						label: 'SearchInputWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.SearchInputWidget( { disabled: true, value: 'test' } ),
					{
						label: 'SearchInputWidget (disabled, filled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.NumberInputWidget(),
					{
						label: 'NumberInputWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.NumberInputWidget( { disabled: true } ),
					{
						label: 'NumberInputWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.NumberInputWidget( { min: 1, max: 5, step: 1 } ),
					{
						label: 'NumberInputWidget (1–5, ints only)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.NumberInputWidget( { min: -1, max: 1, step: 0.1, pageStep: 0.5 } ),
					{
						label: 'NumberInputWidget (-1–1, step by .1, page by .5)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.NumberInputWidget( { showButtons: false } ),
					{
						label: 'NumberInputWidget (no buttons)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-inputs-binary',
			label: 'Checkbox, Radio & ToggleSwitch',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.CheckboxInputWidget( {
						selected: true
					} ),
					{
						align: 'inline',
						label: 'CheckboxInputWidget'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.CheckboxInputWidget( {
						selected: true,
						disabled: true
					} ),
					{
						align: 'inline',
						label: 'CheckboxInputWidget (disabled)'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.CheckboxInputWidget( {
						selected: true,
						accessKey: 't'
					} ),
					{
						align: 'inline',
						label: 'CheckboxInputWidget (with accesskey T and title)',
						title: 'Access key is added to the title.'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.RadioInputWidget( {
						name: 'oojs-ui-radio-demo'
					} ),
					{
						align: 'inline',
						label: 'Connected RadioInputWidget #1'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.RadioInputWidget( {
						name: 'oojs-ui-radio-demo',
						selected: true
					} ),
					{
						align: 'inline',
						label: 'Connected RadioInputWidget #2'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.RadioInputWidget( {
						selected: true,
						disabled: true
					} ),
					{
						align: 'inline',
						label: 'RadioInputWidget (disabled)'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.RadioSelectWidget( {
						items: [
							new OO.ui.RadioOptionWidget( {
								data: 'cat',
								label: 'Cat'
							} ),
							new OO.ui.RadioOptionWidget( {
								data: 'dog',
								label: 'Dog'
							} ),
							new OO.ui.RadioOptionWidget( {
								data: 'goldfish',
								label: 'Goldfish. By the way, this is a very long label. ' + loremIpsum,
								disabled: true
							} )
						]
					} ),
					{
						align: 'top',
						label: 'RadioSelectWidget'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.CheckboxMultiselectWidget( {
						items: [
							new OO.ui.CheckboxMultioptionWidget( {
								data: 'cat',
								label: 'Cat'
							} ),
							new OO.ui.CheckboxMultioptionWidget( {
								data: 'dog',
								label: 'Dog'
							} ),
							new OO.ui.CheckboxMultioptionWidget( {
								data: 'goldfish',
								label: 'Goldfish. By the way, this is a very long label. ' + loremIpsum,
								disabled: true
							} )
						]
					} ),
					{
						align: 'top',
						label: 'CheckboxMultiselectWidget'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.RadioSelectInputWidget( {
						value: 'dog',
						options: [
							{
								data: 'cat',
								label: 'Cat'
							},
							{
								data: 'dog',
								label: 'Dog'
							},
							{
								data: 'goldfish',
								label: 'Goldfish'
							}
						]
					} ),
					{
						align: 'top',
						label: 'RadioSelectInputWidget'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.CheckboxMultiselectInputWidget( {
						value: [ 'dog', 'cat' ],
						options: [
							{
								data: 'cat',
								label: 'Cat'
							},
							{
								data: 'dog',
								label: 'Dog (disabled)',
								disabled: true
							},
							{
								data: 'goldfish',
								label: 'Goldfish'
							}
						]
					} ),
					{
						align: 'top',
						label: 'CheckboxMultiselectInputWidget'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.RadioSelectWidget( {
						items: [
							new OO.ui.RadioOptionWidget( {
								data: 'a',
								label: 'Access key: M',
								accessKey: 'm'
							} ),
							new OO.ui.RadioOptionWidget( {
								data: 'b',
								label: 'Access key: N',
								accessKey: 'n'
							} ),
							new OO.ui.RadioOptionWidget( {
								data: 'c',
								label: 'Access key: O',
								accessKey: 'o'
							} )
						]
					} ),
					{
						align: 'top',
						label: 'RadioSelectWidget (with accesskeys)'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ToggleSwitchWidget(),
					{
						label: 'ToggleSwitchWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ToggleSwitchWidget( { disabled: true } ),
					{
						label: 'ToggleSwitchWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ToggleSwitchWidget( { disabled: true, value: true } ),
					{
						label: 'ToggleSwitchWidget (disabled, checked)',
						align: 'top'
					}
				),
				/* eslint-disable jquery/no-parse-html-literal */
				new OO.ui.FieldLayout(
					new OO.ui.RadioSelectWidget( {
						items: [
							new OO.ui.RadioOptionWidget( {
								data: 'a',
								label: $( $.parseHTML( 'Option A (<a href="https://example.com/a">details</a>)' ) )
							} ),
							new OO.ui.RadioOptionWidget( {
								data: 'b',
								label: $( $.parseHTML( 'Option B (<a href="https://example.com/b">details</a>)' ) )
							} ),
							new OO.ui.RadioOptionWidget( {
								data: 'c',
								label: $( $.parseHTML( 'Option C (<a href="https://example.com/c">details</a>)' ) )
							} )
						]
					} ),
					{
						label: 'RadioSelectWidget with links in the labels',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.RadioSelectWidget( {
						items: [
							new OO.ui.RadioOptionWidget( {
								data: 'foo',
								label: 'Foo'
							} ),
							new OO.ui.RadioOptionWidget( {
								data: 'bar',
								label: 'Bar'
							} ),
							new OO.ui.RadioOptionWidget( {
								data: '',
								label: $( [
									document.createTextNode( 'Other: ' ),
									new OO.ui.TextInputWidget().$element[ 0 ]
								] )
							} )
						]
					} ),
					{
						label: 'RadioSelectWidget with text input in a label',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-dropdown',
			label: 'Dropdown',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.DropdownWidget( {
						label: 'Select one',
						menu: {
							items: [
								new OO.ui.MenuOptionWidget( {
									data: 'a',
									label: 'First'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'b',
									label: 'Second',
									indicator: 'clear'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'c',
									label: 'Third'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'c',
									label: 'The fourth option has an overly long label'
								} ),
								new OO.ui.MenuOptionWidget( {
									icon: 'feedback',
									data: 'd',
									label: 'The fifth option has an icon'
								} )
							]
						}
					} ),
					{
						label: 'DropdownWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownWidget( {
						label: 'Select one',
						icon: 'tag',
						menu: {
							items: [
								new OO.ui.MenuOptionWidget( {
									data: 'a',
									label: 'First'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'b',
									label: 'Disabled second option',
									indicator: 'clear',
									disabled: true
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'c',
									label: 'Third'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'd',
									label: 'Disabled fourth option with an overly long label',
									disabled: true
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'c',
									label: 'Third'
								} )
							]
						}
					} ),
					{
						label: 'DropdownWidget (disabled options)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownWidget( {
						label: 'Select one',
						menu: {
							items: [
								new OO.ui.MenuSectionOptionWidget( {
									label: 'Dogs'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'corgi',
									label: 'Welsh Corgi',
									indicator: 'required'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'poodle',
									label: 'Standard Poodle',
									icon: 'star'
								} ),
								new OO.ui.MenuSectionOptionWidget( {
									label: 'Cats'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'lion',
									label: 'Lion'
								} )
							]
						}
					} ),
					{
						label: 'DropdownWidget (with MenuSectionOptionWidget)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownWidget( {
						label: 'Select one',
						disabled: true,
						menu: {
							items: [
								new OO.ui.MenuOptionWidget( {
									data: 'a',
									label: 'First'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'b',
									label: 'Second'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'c',
									label: 'Third'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'd',
									label: 'Fourth'
								} )
							]
						}
					} ),
					{
						label: 'DropdownWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownWidget( {
						label: 'Select one',
						$overlay: true,
						menu: {
							items: [
								new OO.ui.MenuOptionWidget( {
									data: 'a',
									label: 'First'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'b',
									label: 'Second'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'c',
									label: 'Third'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'd',
									label: 'Fourth'
								} )
							]
						}
					} ),
					{
						label: 'DropdownWidget (using default overlay)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownWidget( {
						label: 'Select one',
						$overlay: $overlay,
						menu: {
							items: [
								new OO.ui.MenuOptionWidget( {
									data: 'a',
									label: 'First'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'b',
									label: 'Second'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'c',
									label: 'Third'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'd',
									label: 'Fourth'
								} )
							]
						}
					} ),
					{
						label: 'DropdownWidget (using custom overlay)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownWidget( {
						label: 'Select one',
						menu: {
							items: [
								new OO.ui.MenuOptionWidget( {
									data: 'a',
									label: 'First'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'b',
									label: 'Second'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'c',
									label: 'Third'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'd',
									label: 'Fourth'
								} )
							],
							hideOnChoose: false
						}
					} ),
					{
						label: 'DropdownWidget (does not close on choose)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownWidget( {
						label: 'Select one',
						menu: {
							items: [
								new OO.ui.MenuOptionWidget( {
									data: 'a',
									label: 'Access key: P',
									accessKey: 'p'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'b',
									label: 'Access key: Q',
									accessKey: 'q'
								} ),
								new OO.ui.MenuOptionWidget( {
									data: 'c',
									label: 'Access key: R',
									accessKey: 'r'
								} )
							]
						}
					} ),
					{
						align: 'top',
						label: 'DropdownWidget (with accesskeys)'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownInputWidget( {
						options: [
							{
								data: 'a',
								label: 'First'
							},
							{
								data: 'b',
								label: 'Second'
							},
							{
								data: 'c',
								label: 'Third'
							}
						],
						value: 'b'
					} ),
					{
						label: 'DropdownInputWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownInputWidget( {
						disabled: true,
						title: 'DropdownInputWidget (disabled)'
					} ),
					{
						label: 'DropdownInputWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownInputWidget( {
						options: [
							{
								optgroup: 'Vowels'
							},
							{
								data: 'a',
								label: 'A'
							},
							{
								optgroup: 'Consonants'
							},
							{
								data: 'b',
								label: 'B'
							},
							{
								data: 'c',
								label: 'C'
							}
						],
						value: 'b'
					} ),
					{
						label: 'DropdownInputWidget (with optgroup)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownInputWidget( {
						options: [
							{
								data: 'top',
								label: 'Top-level option'
							},
							{
								optgroup: 'Whole optgroup disabled',
								disabled: true
							},
							{
								data: 'a',
								label: 'A'
							},
							{
								optgroup: 'Optgroup with one disabled option'
							},
							{
								data: 'b',
								label: 'B'
							},
							{
								data: 'c',
								label: 'C',
								disabled: true
							},
							{
								data: 'd',
								label: 'D'
							}
						],
						value: 'b'
					} ),
					{
						label: 'DropdownInputWidget (with disabled option and optgroup)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.DropdownInputWidget( {
						options: [
							{ data: 'sq', label: 'Albanian' },
							{ data: 'frp', label: 'Arpitan' },
							{ data: 'ba', label: 'Bashkir' },
							{ data: 'pt-br', label: 'Brazilian Portuguese' },
							{ data: 'tzm', label: 'Central Atlas Tamazight' },
							{ data: 'zh', label: 'Chinese' },
							{ data: 'co', label: 'Corsican' },
							{ data: 'del', label: 'Delaware' },
							{ data: 'eml', label: 'Emiliano-Romagnolo' },
							{ data: 'en', label: 'English' },
							{ data: 'fi', label: 'Finnish' },
							{ data: 'aln', label: 'Gheg Albanian' },
							{ data: 'he', label: 'Hebrew' },
							{ data: 'ilo', label: 'Iloko' },
							{ data: 'kbd', label: 'Kabardian' },
							{ data: 'csb', label: 'Kashubian' },
							{ data: 'avk', label: 'Kotava' },
							{ data: 'lez', label: 'Lezghian' },
							{ data: 'nds-nl', label: 'Low Saxon' },
							{ data: 'ml', label: 'Malayalam' },
							{ data: 'dum', label: 'Middle Dutch' },
							{ data: 'ary', label: 'Moroccan Arabic' },
							{ data: 'pih', label: 'Norfuk / Pitkern' },
							{ data: 'ny', label: 'Nyanja' },
							{ data: 'ang', label: 'Old English' },
							{ data: 'non', label: 'Old Norse' },
							{ data: 'pau', label: 'Palauan' },
							{ data: 'pdt', label: 'Plautdietsch' },
							{ data: 'ru', label: 'Russian' },
							{ data: 'stq', label: 'Saterland Frisian' },
							{ data: 'ii', label: 'Sichuan Yi' },
							{ data: 'bcc', label: 'Southern Balochi' },
							{ data: 'shi', label: 'Tachelhit' },
							{ data: 'th', label: 'Thai' },
							{ data: 'tr', label: 'Turkish' },
							{ data: 'fiu-vro', label: 'Võro' },
							{ data: 'vls', label: 'West Flemish' },
							{ data: 'zea', label: 'Zeelandic' }
						],
						value: 'en'
					} ),
					{
						label: 'DropdownInputWidget (long)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-comboBox',
			label: 'ComboBox',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.ComboBoxInputWidget( {
						options: [
							{ data: 'asd', label: 'Label for asd' },
							{ data: 'fgh', label: 'Label for fgh' },
							{ data: 'jkl', label: 'Label for jkl' },
							{ data: 'zxc', label: 'Label for zxc' },
							{ data: 'vbn', label: 'Label for vbn' }
						]
					} ),
					{
						label: 'ComboBoxInputWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ComboBoxInputWidget( {
						options: [
							{ data: 'asd', label: 'A Label for asd' },
							{ data: 'fgh', label: 'A Label for fgh' },
							{ data: 'jkl', label: 'A Label for jkl' },
							{ data: 'zxc', label: 'A Label for zxc' },
							{ data: 'vbn', label: 'A Label for vbn' },
							{ data: 'asd', label: 'B Label for asd' },
							{ data: 'fgh', label: 'B Label for fgh' },
							{ data: 'jkl', label: 'B Label for jkl' },
							{ data: 'zxc', label: 'B Label for zxc' },
							{ data: 'vbn', label: 'B Label for vbn' },
							{ data: 'asd', label: 'C Label for asd' },
							{ data: 'fgh', label: 'C Label for fgh' },
							{ data: 'jkl', label: 'C Label for jkl' },
							{ data: 'zxc', label: 'C Label for zxc' },
							{ data: 'vbn', label: 'C Label for vbn' },
							{ data: 'asd', label: 'D Label for asd' },
							{ data: 'fgh', label: 'D Label for fgh' },
							{ data: 'jkl', label: 'D Label for jkl' },
							{ data: 'zxc', label: 'D Label for zxc' },
							{ data: 'vbn', label: 'D Label for vbn' },
							{ data: 'asd', label: 'E Label for asd' },
							{ data: 'fgh', label: 'E Label for fgh' },
							{ data: 'jkl', label: 'E Label for jkl' },
							{ data: 'zxc', label: 'E Label for zxc' },
							{ data: 'vbn', label: 'E Label for vbn' }
						],
						menu: {
							filterFromInput: true
						}
					} ),
					{
						label: 'ComboBoxInputWidget (filtering on input)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ComboBoxInputWidget( {
						options: [
							{ data: 'Option 1' },
							{ data: 'Option 2' },
							{ data: 'Option 3' },
							{ data: 'Option 4' },
							{ data: 'Option 5' }
						]
					} ),
					{
						label: 'ComboBoxInputWidget (no labels given)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ComboBoxInputWidget( {
						disabled: true,
						options: [
							{ data: 'asd', label: 'Label for asd' },
							{ data: 'fgh', label: 'Label for fgh' },
							{ data: 'jkl', label: 'Label for jkl' },
							{ data: 'zxc', label: 'Label for zxc' },
							{ data: 'vbn', label: 'Label for vbn' }
						]
					} ),
					{
						label: 'ComboBoxInputWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ComboBoxInputWidget(),
					{
						label: 'ComboBoxInputWidget (empty)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-selectFile',
			label: 'SelectFile',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.SelectFileWidget( {} ),
					{
						label: 'SelectFileWidget',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.SelectFileWidget( {
						accept: [ 'image/png', 'image/jpeg' ],
						title: 'This SelectFileWidget accepts only PNG `image/png` and JPEG `image/jpeg` files and has `title` configured'
					} ),
					{
						label: 'SelectFileWidget (accept PNG and JPEG)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.SelectFileWidget( {
						icon: 'tag',
						indicator: 'required'
					} ),
					{
						label: 'SelectFileWidget (icon, indicator)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.SelectFileWidget( {
						icon: 'tag',
						indicator: 'required',
						disabled: true
					} ),
					{
						label: 'SelectFileWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new Demo.UnsupportedSelectFileWidget(),
					{
						label: 'SelectFileWidget (no browser support)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.SelectFileWidget( { showDropTarget: true } ),
					{
						label: 'SelectFileWidget (with drop target)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.SelectFileWidget( {
						showDropTarget: true,
						disabled: true
					} ),
					{
						label: 'SelectFileWidget (with drop target, disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new Demo.UnsupportedSelectFileWidget( {
						showDropTarget: true
					} ),
					{
						label: 'SelectFileWidget (with drop target, no browser support)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-tagMultiselect',
			label: 'TagMultiselect, MenuTagMultiselect',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.TagMultiselectWidget( {
						placeholder: 'Add tags',
						allowArbitrary: true
					} ),
					{
						label: 'TagMultiselectWidget (allowArbitrary, inline input, placeholder)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TagMultiselectWidget( {
						selected: [ loremIpsum ],
						allowArbitrary: true
					} ),
					{
						label: 'TagMultiselectWidget (very long item)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					fixedItemsTagMultiselectWidget,
					{
						label: 'TagMultiselectWidget with fixed items',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TagMultiselectWidget( {
						placeholder: 'Add tags',
						allowArbitrary: true,
						disabled: true
					} ),
					{
						label: 'TagMultiselectWidget (disabled, inline input, placeholder)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TagMultiselectWidget( {
						allowArbitrary: false,
						allowDisplayInvalidTags: true,
						allowedValues: [ 'foo', 'bar', 'baz' ]
					} ),
					{
						label: 'TagMultiselectWidget (inline input, allowed values: [ \'foo\', \'bar\', \'baz\' ], allowDisplayInvalidTags)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TagMultiselectWidget( {
						placeholder: 'Add up to 5 tags',
						allowArbitrary: true,
						tagLimit: 5,
						title: 'Add up to 5 tags'
					} ),
					{
						label: 'TagMultiselectWidget (allowArbitrary, inline input, tagLimit: 5, placeholder, title)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TagMultiselectWidget( {
						placeholder: 'Add tags',
						allowArbitrary: true,
						inputPosition: 'outline'
					} ),
					{
						label: 'TagMultiselectWidget (allowArbitrary, inputPosition: outline, placeholder)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TagMultiselectWidget( {
						allowArbitrary: true,
						inputPosition: 'outline',
						inputWidget: new OO.ui.NumberInputWidget()
					} ),
					{
						label: 'TagMultiselectWidget (inputwidget: OO.ui.NumberInputWidget, inputPosition: outline)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TagMultiselectWidget( {
						allowArbitrary: true,
						inputPosition: 'outline',
						tagLimit: 5
					} ),
					{
						label: 'TagMultiselectWidget (inputPosition: outline, tagLimit: 5)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MenuTagMultiselectWidget( {
						selected: [
							{ data: 'foo', label: 'Label for foo' },
							{ data: 'bar', label: 'Label for bar' }
						],
						options: [
							{ data: 'foo', label: 'Label for foo', icon: 'tag' },
							{ data: 'bar', label: 'Label for bar' },
							{ data: 'baz', label: 'Label for baz' }
						]
					} ),
					{
						label: 'MenuTagMultiselectWidget (initially selected, preset options)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MenuTagMultiselectWidget( {
						selected: [
							{ data: 'foo', label: 'Label for foo' },
							{ data: 'bar', label: 'Label for bar' }
						],
						options: [
							{ data: 'foo', label: 'Label for foo', icon: 'tag' },
							{ data: 'bar', label: 'Label for bar' },
							{ data: 'baz', label: 'Label for baz' },
							{ data: 'quuz', label: 'Label for quuz' },
							{ data: 'red', label: 'Label for red' },
							{ data: 'green', label: 'Label for green' },
							{ data: 'blue', label: 'Label for blue' }
						],
						tagLimit: 5
					} ),
					{
						label: 'MenuTagMultiselectWidget (initially selected, preset options, tagLimit: 5)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MenuTagMultiselectWidget( {
						selected: [ 'foo', 'bar', 'Not in menu' ],
						allowArbitrary: true,
						options: [
							{ data: 'foo', label: 'Label for foo', icon: 'tag' },
							{ data: 'bar', label: 'Label for bar' },
							{ data: 'baz', label: 'Label for baz' },
							{ data: 'quz', label: 'Label for quz' },
							{ data: 'red', label: 'Label for red' },
							{ data: 'green', label: 'Label for green' },
							{ data: 'blue', label: 'Label for blue' }
						]
					} ),
					{
						label: 'MenuTagMultiselectWidget (initially selected, allowArbitrary)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MenuTagMultiselectWidget( {
						allowArbitrary: false,
						options: [
							{ data: 'abc', label: 'Label for abc' },
							{ data: 'asd', label: 'Label for asd' },
							{ data: 'jkl', label: 'Label for jkl' }
						]
					} ),
					{
						label: 'MenuTagMultiselectWidget (allowArbitrary: false)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MenuTagMultiselectWidget( {
						allowArbitrary: false,
						menu: {
							highlightOnFilter: true
						},
						options: [
							{ data: 'abc', label: 'abc item' },
							{ data: 'asd', label: 'asd item' },
							{ data: 'jkl', label: 'jkl item' },
							{ data: 'jkl2', label: 'jkl second item' },
							{ data: 'jkl3', label: 'jkl third item' }
						]
					} ),
					{
						label: 'MenuTagMultiselectWidget (allowArbitrary: false, menu: {highlightOnFilter: true})',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MenuTagMultiselectWidget( {
						inputPosition: 'outline',
						allowArbitrary: false,
						options: [
							{ data: 'abc', label: 'Label for abc' },
							{ data: 'asd', label: 'Label for asd' },
							{ data: 'jkl', label: 'Label for jkl' }
						]
					} ),
					{
						label: 'MenuTagMultiselectWidget (inputPosition: outline, allowArbitrary: false)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.MenuTagMultiselectWidget( {
						inputPosition: 'inline',
						disabled: true,
						options: [
							{ data: 'abc', label: 'Label for abc' },
							{ data: 'asd', label: 'Label for asd' },
							{ data: 'jkl', label: 'Label for jkl' }
						]
					} ),
					{
						label: 'MenuTagMultiselectWidget (disabled)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-lookupElement',
			label: 'LookupElement',
			items: [
				new OO.ui.FieldLayout(
					new Demo.NumberLookupTextInputWidget(),
					{
						label: 'LookupElement (try inputting an integer)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new Demo.NumberLookupTextInputWidget( {
						highlightFirst: false
					} ),
					{
						label: 'LookupElement without highlighting 1st term (try inputting an integer)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-popupButton',
			label: 'PopupButton',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'info',
						framed: false,
						label: 'More information',
						invisibleLabel: true,
						popup: {
							head: true,
							label: 'More information',
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'force-left'
						}
					} ),
					{
						label: 'PopupButtonWidget (frameless, with popup head, align: force-left)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'info',
						framed: false,
						label: 'More information',
						invisibleLabel: true,
						popup: {
							head: true,
							label: 'More information',
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'force-right'
						}
					} ),
					{
						label: 'PopupButtonWidget (frameless, with popup head align: force-right)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'info',
						framed: false,
						label: 'More information',
						invisibleLabel: true,
						popup: {
							head: true,
							label: 'More information',
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'backwards'
						}
					} ),
					{
						label: 'PopupButtonWidget (frameless, with popup head align: backwards)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'info',
						framed: false,
						label: 'More information',
						invisibleLabel: true,
						popup: {
							head: true,
							label: 'More information',
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'forwards'
						}
					} ),
					{
						label: 'PopupButtonWidget (frameless, with popup head align: forwards)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'info',
						framed: false,
						label: 'More information',
						invisibleLabel: true,
						popup: {
							head: true,
							label: 'More information',
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'center'
						}
					} ),
					{
						label: 'PopupButtonWidget (frameless, with popup head align: center)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'info',
						framed: false,
						label: 'More information',
						invisibleLabel: true,
						popup: {
							head: true,
							label: 'More information',
							$content: $( '<p>' + loremIpsum + '</p><ul><li>Item one</li><li>Item two</li><li>Item three</li><li>Item four</li></ul><p>Even more text here which might well be clipped off the visible area.</p>' ),
							$footer: $( '<p>And maybe a footer whilst we\'re at it?</p>' ),
							padded: true,
							align: 'forwards'
						}
					} ),
					{
						label: 'PopupButtonWidget (frameless, with popup head and footer, align: forwards)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'menu',
						label: 'Options',
						popup: {
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'forwards'
						}
					} ),
					{
						label: 'PopupButtonWidget (framed, no popup head, align: forwards)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'menu',
						label: 'Options',
						popup: {
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'backwards'
						}
					} ),
					{
						label: 'PopupButtonWidget (framed, no popup head, align: backwards)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'menu',
						label: 'Options',
						popup: {
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'center'
						}
					} ),
					{
						label: 'PopupButtonWidget (framed, no popup head, align: center)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'menu',
						label: 'Options',
						popup: {
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'center',
							position: 'above'
						}
					} ),
					{
						label: 'PopupButtonWidget (framed, no popup head, position: above)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'menu',
						label: 'Options',
						popup: {
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'center',
							position: 'before'
						}
					} ),
					{
						label: 'PopupButtonWidget (framed, no popup head, position: before)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'menu',
						label: 'Options',
						popup: {
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							align: 'center',
							position: 'after'
						}
					} ),
					{
						label: 'PopupButtonWidget (framed, no popup head, position: after)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'menu',
						label: 'Options',
						popup: {
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							anchor: false,
							align: 'center'
						}
					} ),
					{
						label: 'PopupButtonWidget (no anchor, align: center)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'menu',
						label: 'Options',
						popup: {
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							anchor: false,
							align: 'forwards'
						}
					} ),
					{
						label: 'PopupButtonWidget (no anchor, align: forwards)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.PopupButtonWidget( {
						icon: 'menu',
						label: 'Options',
						popup: {
							$content: $( '<p>' ).text( loremIpsum ),
							padded: true,
							anchor: false,
							align: 'backwards'
						}
					} ),
					{
						label: 'PopupButtonWidget (no anchor, align: backwards)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-draggable',
			label: 'Draggable',
			items: [
				new OO.ui.FieldLayout(
					new Demo.DraggableGroupWidget( {
						orientation: 'horizontal',
						items: horizontalDragItems
					} ),
					{
						label: 'DraggableGroupWidget (horizontal)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new Demo.DraggableGroupWidget( {
						items: verticalDragItems
					} ),
					{
						label: 'DraggableGroupWidget (vertical)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new Demo.DraggableGroupWidget( {
						items: verticalHandledDragItems
					} ),
					{
						label: 'DraggableGroupWidget with handles (vertical)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-progressBar',
			label: 'Progress bar',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.ProgressBarWidget( {
						progress: 33
					} ),
					{
						label: 'Progress bar',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ProgressBarWidget( {
						progress: 50,
						disabled: true
					} ),
					{
						label: 'Progress bar (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ProgressBarWidget( {
						progress: false
					} ),
					{
						label: 'Progress bar (indeterminate)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-others',
			label: 'Other widgets',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.IconWidget( {
						icon: 'search',
						label: 'Search',
						title: 'Search icon'
					} ),
					{
						label: 'IconWidget (normal)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.IconWidget( {
						icon: 'trash',
						label: 'Trash',
						title: 'Trash icon',
						flags: 'destructive'
					} ),
					{
						label: 'IconWidget (flagged)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.IconWidget( {
						icon: 'search',
						label: 'Search',
						title: 'Search icon',
						disabled: true
					} ),
					{
						label: 'IconWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.IndicatorWidget( {
						indicator: 'required',
						label: 'Required',
						title: 'Required indicator'
					} ),
					{
						label: 'IndicatorWidget (normal)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.IndicatorWidget( {
						indicator: 'required',
						label: 'Required',
						title: 'Required indicator',
						disabled: true
					} ),
					{
						label: 'IndicatorWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.LabelWidget( {
						label: 'Label'
					} ),
					{
						label: 'LabelWidget (normal)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.LabelWidget( {
						label: 'Label',
						disabled: true
					} ),
					{
						label: 'LabelWidget (disabled)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.LabelWidget( {
						label: new OO.ui.HtmlSnippet( '<b>Fancy</b> <i>text</i> <u>formatting</u>!' )
					} ),
					{
						label: 'LabelWidget (with HTML)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					labelForTextInput,
					{
						label: 'LabelWidget (with an associated TextInputWidget)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					textInputForLabel,
					{
						label: 'TextInputWidget (with an associated label)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					labelForRadioSelectInput,
					{
						label: 'LabelWidget (with an associated RadioSelectInputWidget)',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					radioSelectInputForLabel,
					{
						label: 'RadioSelectInputWidget (with an associated label)',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-fieldLayouts',
			label: 'Field layouts',
			icon: 'tag',
			help: loremIpsum,
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'FieldLayout with help',
						help: loremIpsum,
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'FieldLayout with inlined help',
						help: 'This is some inlined help. Assistive (optional) text, that isn\'t needed to understand the widget\'s purpose.',
						helpInline: true,
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'FieldLayout with rich text help',
						help: new OO.ui.HtmlSnippet( '<b>Bold text</b> is helpful!' ),
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'FieldLayout with inlined rich text help',
						help: new OO.ui.HtmlSnippet( '<b>Strong text</b> is helpful! It should only contain assistive (optional) text.' ),
						helpInline: true,
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'FieldLayout with title',
						title: 'Field title text',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: $( '<i>' ).text( 'FieldLayout with rich text label' ),
						align: 'top'
					}
				),
				new OO.ui.ActionFieldLayout(
					new OO.ui.TextInputWidget(),
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'ActionFieldLayout aligned top',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget(),
					{
						label: 'FieldLayout aligned top with help',
						help: loremIpsum,
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget(),
					{
						label: 'FieldLayout aligned top with inlined help',
						help: 'This is some inlined help. Assistive (optional) text, that isn\'t needed to understand the widget\'s purpose.',
						helpInline: true,
						align: 'top'
					}
				),
				new OO.ui.ActionFieldLayout(
					new OO.ui.TextInputWidget(),
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'ActionFieldLayout aligned top with help',
						help: loremIpsum,
						align: 'top'
					}
				),
				new OO.ui.ActionFieldLayout(
					new OO.ui.CheckboxInputWidget( { selected: true } ),
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'ActionFieldLayout aligned inline',
						align: 'inline'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.CheckboxInputWidget( { selected: true } ),
					{
						label: 'FieldLayout aligned inline with help',
						help: loremIpsum,
						align: 'inline'
					}
				),
				new OO.ui.ActionFieldLayout(
					new OO.ui.CheckboxInputWidget( { selected: true } ),
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'ActionFieldLayout aligned inline with help',
						help: loremIpsum,
						align: 'inline'
					}
				),
				new OO.ui.ActionFieldLayout(
					new OO.ui.TextInputWidget(),
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'ActionFieldLayout aligned left',
						align: 'left'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget(),
					{
						label: 'FieldLayout aligned left with help',
						help: loremIpsum,
						align: 'left'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget(),
					{
						label: 'FieldLayout aligned left, inlined help',
						help: 'This is some inlined help',
						helpInline: true,
						align: 'left'
					}
				),
				new OO.ui.ActionFieldLayout(
					new OO.ui.TextInputWidget(),
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'ActionFieldLayout aligned left with help',
						help: loremIpsum,
						align: 'left'
					}
				),
				new OO.ui.ActionFieldLayout(
					new OO.ui.DropdownWidget( {
						label: 'Select one',
						options: [
							{
								data: 'a',
								label: 'First'
							},
							{
								data: 'b',
								label: 'Second'
							},
							{
								data: 'c',
								label: 'Third'
							}
						],
						value: 'b'
					} ),
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'ActionFieldLayout aligned left, DropdownWidget',
						help: 'This is some inlined help',
						helpInline: true,
						align: 'left'
					}
				),
				new OO.ui.ActionFieldLayout(
					new OO.ui.TextInputWidget(),
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'ActionFieldLayout aligned right',
						align: 'right'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget(),
					{
						label: 'FieldLayout aligned right with help',
						help: loremIpsum,
						align: 'right'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget(),
					{
						label: 'FieldLayout aligned right, inlined help',
						help: 'This is some inlined help',
						helpInline: true,
						align: 'right'
					}
				),
				new OO.ui.ActionFieldLayout(
					new OO.ui.TextInputWidget(),
					new OO.ui.ButtonWidget( {
						label: 'Button'
					} ),
					{
						label: 'ActionFieldLayout aligned right with help',
						help: loremIpsum,
						align: 'right'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget(),
					{
						label: 'FieldLayout aligned top with a very long label. ' + loremIpsum,
						help: loremIpsum,
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.CheckboxInputWidget( { selected: true } ),
					{
						label: 'FieldLayout aligned inline with a very long label. ' + loremIpsum,
						help: loremIpsum,
						align: 'inline'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget(),
					{
						label: 'FieldLayout aligned left with a very long label. ' + loremIpsum,
						help: loremIpsum,
						align: 'left'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget(),
					{
						label: 'FieldLayout aligned right with a very long label. ' + loremIpsum,
						help: loremIpsum,
						align: 'right'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget(),
					{
						label: 'FieldLayout aligned right with a very long label and inline help. ' + loremIpsum,
						help: 'This is some inlined help',
						helpInline: true,
						align: 'right'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						value: ''
					} ),
					{
						label: 'FieldLayout with notice',
						notices: [ 'Please input a number.' ],
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						value: 'Foo'
					} ),
					{
						label: 'FieldLayout with error message',
						errors: [ 'The value must be a number. It is more than necessary. You can\'t go on without putting a number into this input field.' ],
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.TextInputWidget( {
						value: 'Foo'
					} ),
					{
						label: 'FieldLayout with notice and error message',
						notices: [ 'Please input a number.' ],
						errors: [ 'The value must be a number.' ],
						align: 'top'
					}
				),
				fieldLayoutForValidation
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-horizontalLayout',
			label: 'HorizontalLayout',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.Widget( {
						content: [ new OO.ui.HorizontalLayout( {
							items: [
								new OO.ui.ButtonWidget( { label: 'Button' } ),
								new OO.ui.ButtonGroupWidget( { items: [
									new OO.ui.ToggleButtonWidget( { label: 'A' } ),
									new OO.ui.ToggleButtonWidget( { label: 'B' } )
								] } ),
								new OO.ui.ButtonInputWidget( { label: 'ButtonInput' } ),
								new OO.ui.TextInputWidget( { value: 'TextInput' } ),
								new OO.ui.DropdownInputWidget( { options: [
									{
										label: 'DropdownInput',
										data: null
									}
								] } ),
								new OO.ui.CheckboxInputWidget( { selected: true } ),
								new OO.ui.RadioInputWidget( { selected: true } ),
								new OO.ui.LabelWidget( { label: 'Label' } )
							]
						} ) ]
					} ),
					{
						label: 'Multiple widgets shown as a single line, ' +
							'as used in compact forms or in parts of a bigger widget.',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FieldsetLayout( {
			id: 'demo-section-other-layouts',
			label: 'Other layouts',
			items: [
				new OO.ui.FieldLayout(
					new OO.ui.Widget( {
						content: [
							new OO.ui.PanelLayout( {
								expanded: false,
								framed: true,
								content: [
									new OO.ui.BookletLayout( {
										expanded: false,
										outlined: true
									} ).addPages( [
										new Demo.SamplePage( 'first', {
											expanded: false,
											label: 'One'
										} ),
										new Demo.SamplePage( 'second', {
											expanded: false,
											label: 'Two'
										} ),
										new Demo.SamplePage( 'third', {
											expanded: false,
											label: 'Three'
										} ),
										new Demo.SamplePage( 'fourth', {
											expanded: false,
											label: 'Four'
										} ),
										new Demo.SamplePage( 'long', {
											expanded: false,
											label: 'Long',
											content: [
												$( '<p>' ).text( loremIpsum ),
												$( '<p>' ).text( loremIpsum ),
												$( '<p>' ).text( loremIpsum ),
												$( '<p>' ).text( loremIpsum ),
												$( '<p>' ).text( loremIpsum ),
												$( '<p>' ).text( loremIpsum ),
												$( '<p>' ).text( loremIpsum )
											]
										} )
									] )
								]
							} )
						]
					} ),
					{
						label: 'Outlined BookletLayout',
						align: 'top'
					}
				),
				new OO.ui.FieldLayout(
					new OO.ui.Widget( {
						content: [
							new OO.ui.PanelLayout( {
								expanded: false,
								framed: true,
								content: [
									new OO.ui.IndexLayout( {
										expanded: false
									} ).addTabPanels( [
										new Demo.SampleTabPanel( 'first', {
											expanded: false,
											label: 'One tab'
										} ),
										new Demo.SampleTabPanel( 'second', {
											expanded: false,
											label: 'Two tab'
										} ),
										new Demo.SampleTabPanel( 'third', {
											expanded: false,
											label: 'Three tab'
										} ),
										new Demo.SampleTabPanel( 'fourth', {
											expanded: false,
											label: 'Four tab'
										} ),
										new Demo.SampleTabPanel( 'long', {
											expanded: false,
											label: 'Long tab',
											content: [
												$( '<p>' ).text( loremIpsum ),
												$( '<p>' ).text( loremIpsum ),
												$( '<p>' ).text( loremIpsum )
											]
										} )
									] )
								]
							} )
						]
					} ),
					{
						label: 'IndexLayout',
						align: 'top'
					}
				)
			]
		} ),
		new OO.ui.FormLayout( {
			method: 'GET',
			action: 'demos.php',
			items: [
				new OO.ui.FieldsetLayout( {
					id: 'demo-section-formLayout',
					label: 'Form layout (compounded example)',
					items: [
						new OO.ui.FieldLayout(
							new OO.ui.TextInputWidget( {
								name: 'username'
							} ),
							{
								label: 'User name',
								align: 'top'
							}
						),
						new OO.ui.FieldLayout(
							new OO.ui.TextInputWidget( {
								name: 'password',
								type: 'password'
							} ),
							{
								label: 'Password',
								align: 'top'
							}
						),
						new OO.ui.FieldLayout(
							new OO.ui.ButtonSelectWidget( {
								items: [
									new OO.ui.ButtonOptionWidget( {
										label: 'One'
									} ),
									new OO.ui.ButtonOptionWidget( {
										label: 'Two'
									} ),
									new OO.ui.ButtonOptionWidget( {
										indicator: 'clear',
										label: 'Three'
									} )
								]
							} ),
							{
								label: 'Select one of multiple ButtonSelectWidget Buttons',
								align: 'top'
							}
						),
						new OO.ui.FieldLayout(
							new OO.ui.SelectFileWidget( {} ),
							{
								label: 'Select a file with SelectFileWidget',
								align: 'top'
							}
						),
						new OO.ui.FieldLayout(
							new OO.ui.MenuTagMultiselectWidget( {
								menu: {
									items: [
										new OO.ui.MenuOptionWidget( { data: 'abc', label: 'Abc Label' } ),
										new OO.ui.MenuOptionWidget( { data: 'def', label: 'Def Label' } ),
										new OO.ui.MenuOptionWidget( { data: 'ghi', label: 'Ghi Label' } )
									]
								},
								selected: [
									{ data: 'abc', label: 'Abc Label' },
									{ data: 'def', label: 'Def Label' }
								]
							} ),
							{
								label: 'Select from multiple TagMultiselectWidget items',
								align: 'top'
							}
						),
						new OO.ui.FieldLayout(
							new OO.ui.RadioSelectWidget( {
								items: [
									new OO.ui.RadioOptionWidget( {
										data: 'mouse',
										label: 'Mouse'
									} ),
									new OO.ui.RadioOptionWidget( {
										data: 'elephant',
										label: 'Elephant'
									} )
								]
							} ),
							{
								align: 'top',
								label: 'Toggle the RadioSelectWidget'
							}
						),
						new OO.ui.FieldLayout(
							new OO.ui.ToggleSwitchWidget( { value: true } ),
							{
								label: 'Switch the ToggleSwitchWidget (checked)',
								align: 'right'
							}
						),
						new OO.ui.FieldLayout(
							new OO.ui.CheckboxInputWidget( {
								name: 'rememberme',
								selected: true
							} ),
							{
								label: 'Remember me',
								align: 'inline'
							}
						),
						new OO.ui.FieldLayout(
							new OO.ui.HiddenInputWidget( {
								name: 'hidden',
								value: 'hidden value'
							} )
						),
						new OO.ui.FieldLayout(
							new OO.ui.ButtonInputWidget( {
								type: 'submit',
								label: 'Submit form'
							} )
						)
					]
				} ),
				new OO.ui.FieldsetLayout( {
					label: null,
					items: [
						new OO.ui.FieldLayout(
							new OO.ui.TextInputWidget(),
							{
								label: 'Summary',
								align: 'top'
							}
						),
						new OO.ui.FieldLayout(
							new OO.ui.Widget( {
								content: [ new OO.ui.HorizontalLayout( {
									items: [
										new OO.ui.ButtonInputWidget( {
											name: 'login',
											label: 'Log in',
											type: 'submit',
											flags: [ 'primary', 'progressive' ],
											icon: 'userAvatar'
										} ),
										new OO.ui.ButtonWidget( {
											framed: false,
											flags: [ 'destructive' ],
											label: 'Cancel',
											classes: [ 'demo-summary-buttonElement' ]
										} ),
										new OO.ui.ButtonWidget( {
											framed: false,
											icon: 'tag',
											label: 'Random icon button'
										} ),
										new OO.ui.ButtonWidget( {
											framed: false,
											icon: 'helpNotice',
											label: 'Help',
											invisibleLabel: true,
											title: 'Icon only'
										} )
									]
								} ) ]
							} ),
							{
								label: null,
								align: 'top'
							}
						)
					]
				} )
			]
		} )
	];

	// eslint-disable-next-line jquery/no-each-util
	$.each( fieldsets, function ( i, fieldsetLayout ) {
		var showLayoutCode = false;

		if (
			fieldsetLayout instanceof OO.ui.FormLayout ||
			fieldsetLayout.getLabel() === 'Field layouts'
		) {
			showLayoutCode = true;
		}

		// eslint-disable-next-line jquery/no-each-util
		$.each( fieldsetLayout.getItems(), function ( j, fieldLayout ) {
			fieldLayout.$element.append(
				demo.buildLinkExample( fieldLayout, fieldsetLayout instanceof OO.ui.FormLayout ? fieldLayout : fieldsetLayout ),
				demo.buildConsole( fieldLayout, 'layout', 'widget', showLayoutCode )
			);
		} );
	} );

	$demo.append(
		new OO.ui.PanelLayout( {
			expanded: false,
			framed: true
		} ).$element
			.addClass( 'demo-container' )
			.attr( 'role', 'main' )
			.append(
				$( fieldsets.map( function ( fieldset ) { return fieldset.$element[ 0 ]; } ) )
			)
	);

	$overlay.appendTo( 'body' );

	demo.once( 'destroy', function () {
		// We are removing all of the widgets from the page, so also remove their "detached"
		// menus and stuff, otherwise they can remain visible forever.
		$overlay.remove();
		OO.ui.$defaultOverlay.empty();
	} );
};
