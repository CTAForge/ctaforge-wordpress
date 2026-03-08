/**
 * CTAForge Signup Form — Gutenberg Block (Editor UI)
 *
 * Registers the ctaforge/signup-form block with an InspectorControls
 * sidebar for all shortcode attributes. Preview is server-side-rendered.
 */
( function () {
	const { registerBlockType } = wp.blocks;
	const { __ }                = wp.i18n;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, TextControl, ToggleControl, Placeholder, Spinner } = wp.components;
	const { useSelect } = wp.data;
	const el = wp.element.createElement;

	registerBlockType( 'ctaforge/signup-form', {
		title:       __( 'CTAForge Signup Form', 'ctaforge' ),
		description: __( 'Embed a CTAForge email signup form in your content.', 'ctaforge' ),
		category:    'widgets',
		icon:        'email-alt',
		keywords:    [ 'ctaforge', 'email', 'newsletter', 'signup', 'form' ],

		edit: function ( { attributes, setAttributes } ) {
			const { listId, title, description, button, placeholder, fields } = attributes;
			const blockProps = useBlockProps();

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Form Settings', 'ctaforge' ), initialOpen: true },
						el( TextControl, {
							label:    __( 'List ID', 'ctaforge' ),
							value:    listId,
							onChange: ( val ) => setAttributes( { listId: val } ),
							help:     __( 'UUID of the CTAForge list. Leave blank to use the plugin default.', 'ctaforge' ),
						} ),
						el( TextControl, {
							label:    __( 'Title', 'ctaforge' ),
							value:    title,
							onChange: ( val ) => setAttributes( { title: val } ),
						} ),
						el( TextControl, {
							label:    __( 'Description', 'ctaforge' ),
							value:    description,
							onChange: ( val ) => setAttributes( { description: val } ),
						} ),
						el( TextControl, {
							label:    __( 'Button Label', 'ctaforge' ),
							value:    button,
							onChange: ( val ) => setAttributes( { button: val } ),
						} ),
						el( TextControl, {
							label:    __( 'Email Placeholder', 'ctaforge' ),
							value:    placeholder,
							onChange: ( val ) => setAttributes( { placeholder: val } ),
						} ),
						el( TextControl, {
							label:    __( 'Extra Fields', 'ctaforge' ),
							value:    fields,
							onChange: ( val ) => setAttributes( { fields: val } ),
							help:     __( 'Comma-separated: first_name, last_name', 'ctaforge' ),
						} )
					)
				),
				// Editor preview placeholder
				el(
					Placeholder,
					{
						icon:        'email-alt',
						label:       __( 'CTAForge Signup Form', 'ctaforge' ),
						instructions: title || __( 'Configure the form in the sidebar →', 'ctaforge' ),
					},
					el(
						'p',
						{ style: { color: '#555', fontSize: '13px' } },
						__( 'This form will render on the front end. Use the sidebar to configure title, button label and which list to subscribe to.', 'ctaforge' )
					)
				)
			);
		},

		// The block is server-side rendered — no save() output.
		save: function () { return null; },
	} );
} )();
