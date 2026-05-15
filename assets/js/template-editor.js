(function($) {
    'use strict';

    var TemplateEditor = {
        currentTemplate: null,
        components: [],

        init: function() {
            this.cacheElements();
            this.bindEvents();
        },

        cacheElements: function() {
            this.$editor = $('#wape-template-editor');
            this.$previewArea = $('#wape-template-preview');
            this.$componentsList = $('#wape-components-list');
            this.$builderArea = $('#wape-template-builder');
            this.$saveBtn = $('#wape-save-template');
        },

        bindEvents: function() {
            var self = this;

            // Save template
            $(document).on('click', '.wape-save-template', function() {
                self.saveTemplate();
            });

            // Load template
            $(document).on('click', '.wape-load-template', function() {
                var templateId = $(this).data('template-id');
                self.loadTemplate(templateId);
            });

            // Preview template
            $(document).on('keyup change', 'textarea[name="template_content"]', function() {
                self.previewTemplate($(this).val());
            });

            // Drag and drop
            this.initDragDrop();
        },

        initDragDrop: function() {
            var self = this;

            $(document).on('dragstart', '.wape-component-item', function(e) {
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('text/html', $(this).html());
            });

            $(document).on('dragover', '.wape-drop-zone', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                $(this).addClass('wape-drag-over');
            });

            $(document).on('dragleave', '.wape-drop-zone', function(e) {
                $(this).removeClass('wape-drag-over');
            });

            $(document).on('drop', '.wape-drop-zone', function(e) {
                e.preventDefault();
                $(this).removeClass('wape-drag-over');

                var componentData = $(e.dataTransfer.getData('text/html'));
                self.addComponentToBuilder(componentData);
            });
        },

        addComponentToBuilder: function(component) {
            var placeholder = component.data('placeholder') || component.text();
            var textarea = $('textarea[name="template_content"]');
            var currentContent = textarea.val();

            if (currentContent && !currentContent.endsWith('\n')) {
                currentContent += '\n';
            }

            textarea.val(currentContent + placeholder).trigger('change');
        },

        saveTemplate: function() {
            var self = this;
            var templateName = $('input[name="template_name"]').val();
            var templateContent = $('textarea[name="template_content"]').val();
            var templateType = $('select[name="template_type"]').val() || 'product';

            if (!templateName || !templateContent) {
                alert(wapeTemplateEditor.i18n.error_required_fields);
                return;
            }

            $.ajax({
                url: wapeTemplateEditor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wape_save_template',
                    nonce: wapeTemplateEditor.nonce,
                    template_name: templateName,
                    template_content: templateContent,
                    template_type: templateType,
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        self.currentTemplate = response.data.template_id;
                    } else {
                        alert(response.data.message || wapeTemplateEditor.i18n.error_saving);
                    }
                },
                error: function() {
                    alert(wapeTemplateEditor.i18n.error_ajax);
                },
            });
        },

        loadTemplate: function(templateId) {
            var self = this;

            $.ajax({
                url: wapeTemplateEditor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wape_load_template',
                    nonce: wapeTemplateEditor.nonce,
                    template_id: templateId,
                },
                success: function(response) {
                    if (response.success) {
                        var template = response.data.template;
                        $('input[name="template_name"]').val(template.name);
                        $('textarea[name="template_content"]').val(template.content).trigger('change');
                        self.currentTemplate = templateId;
                    } else {
                        alert(response.data.message || wapeTemplateEditor.i18n.error_loading);
                    }
                },
                error: function() {
                    alert(wapeTemplateEditor.i18n.error_ajax);
                },
            });
        },

        previewTemplate: function(templateContent) {
            var self = this;

            $.ajax({
                url: wapeTemplateEditor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wape_preview_template',
                    nonce: wapeTemplateEditor.nonce,
                    template_content: templateContent,
                },
                success: function(response) {
                    if (response.success) {
                        var preview = response.data.preview;
                        $('#wape-preview-content').html(
                            '<pre>' + $('<div>').text(preview).html() + '</pre>'
                        );
                    }
                },
                error: function() {
                    console.error('Error previewing template');
                },
            });
        },
    };

    $(document).ready(function() {
        if ($('#wape-template-editor').length) {
            TemplateEditor.init();
        }
    });
})(jQuery);