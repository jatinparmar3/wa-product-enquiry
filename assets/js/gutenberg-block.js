// Gutenberg Block for WhatsApp Button
const { registerBlockType } = wp.blocks;
const { useBlockProps, InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl, TextControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType('wape/whatsapp-button', {
    title: __('WhatsApp Button', 'wa-product-enquiry'),
    description: __('Add a WhatsApp enquiry button to your page', 'wa-product-enquiry'),
    category: 'wape_elements',
    icon: 'whatsapp',
    keywords: [__('WhatsApp', 'wa-product-enquiry'), __('Button', 'wa-product-enquiry'), __('Enquiry', 'wa-product-enquiry')],
    attributes: {
        postId: {
            type: 'number',
            default: 0,
        },
        buttonText: {
            type: 'string',
            default: '',
        },
        buttonStyle: {
            type: 'string',
            default: 'default',
        },
        alignment: {
            type: 'string',
            default: 'left',
        },
    },
    edit: function(props) {
        const { attributes, setAttributes } = props;
        const blockProps = useBlockProps();

        return ( <
            div {...blockProps } >
            <
            InspectorControls >
            <
            PanelBody title = { __('Button Settings', 'wa-product-enquiry') }
            initialOpen = { true } >
            <
            TextControl label = { __('Button Text', 'wa-product-enquiry') }
            value = { attributes.buttonText }
            onChange = {
                (value) => setAttributes({ buttonText: value }) }
            placeholder = { __('Order on WhatsApp', 'wa-product-enquiry') }
            /> <
            SelectControl label = { __('Button Style', 'wa-product-enquiry') }
            value = { attributes.buttonStyle }
            options = {
                [
                    { label: __('Default', 'wa-product-enquiry'), value: 'default' },
                    { label: __('Flat', 'wa-product-enquiry'), value: 'flat' },
                    { label: __('Gradient', 'wa-product-enquiry'), value: 'gradient' },
                    { label: __('Outline', 'wa-product-enquiry'), value: 'outline' },
                    { label: __('Icon Only', 'wa-product-enquiry'), value: 'icon-only' },
                    { label: __('Floating', 'wa-product-enquiry'), value: 'floating' },
                ]
            }
            onChange = {
                (value) => setAttributes({ buttonStyle: value }) }
            /> <
            SelectControl label = { __('Alignment', 'wa-product-enquiry') }
            value = { attributes.alignment }
            options = {
                [
                    { label: __('Left', 'wa-product-enquiry'), value: 'left' },
                    { label: __('Center', 'wa-product-enquiry'), value: 'center' },
                    { label: __('Right', 'wa-product-enquiry'), value: 'right' },
                ]
            }
            onChange = {
                (value) => setAttributes({ alignment: value }) }
            /> <
            /PanelBody> <
            /InspectorControls>

            <
            div style = {
                {
                    textAlign: attributes.alignment,
                    padding: '20px',
                    backgroundColor: '#f0f8f5',
                    borderRadius: '8px',
                    border: '1px solid #25D366',
                }
            } >
            <
            button style = {
                {
                    backgroundColor: '#25D366',
                    color: '#fff',
                    border: 'none',
                    padding: '12px 24px',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    fontSize: '15px',
                    fontWeight: '600',
                }
            } >
            { attributes.buttonText || __('Order on WhatsApp', 'wa-product-enquiry') } <
            /button> <
            /div> <
            /div>
        );
    },
    save: function() {
        return null; // Render using PHP callback
    },
});