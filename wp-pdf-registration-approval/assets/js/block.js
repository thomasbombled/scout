(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var InspectorControls = wp.blockEditor ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var MediaUpload = wp.blockEditor ? wp.blockEditor.MediaUpload : wp.editor.MediaUpload;
    var Button = wp.components.Button;

    registerBlockType('wp-pdf-registration/form-block', {
        title: 'Formulaire d\'Inscription PDF',
        description: 'Affiche un formulaire d\'inscription élégant pour demander un document PDF soumis à validation administrative.',
        icon: 'email-alt',
        category: 'widgets',
        keywords: ['inscription', 'formulaire', 'pdf', 'divi', 'gutenberg'],
        attributes: {
            title: {
                type: 'string',
                default: 'Formulaire d\'inscription'
            },
            subtitle: {
                type: 'string',
                default: 'Inscrivez-vous pour recevoir votre document PDF après validation.'
            },
            document_id: {
                type: 'number',
                default: 0
            }
        },

        edit: function (props) {
            var attributes = props.attributes;

            function onChangeTitle(newTitle) {
                props.setAttributes({ title: newTitle });
            }

            function onChangeSubtitle(newSubtitle) {
                props.setAttributes({ subtitle: newSubtitle });
            }

            function onSelectMedia(media) {
                if (media && media.id) {
                    props.setAttributes({ document_id: media.id });
                }
            }

            function onRemoveMedia() {
                props.setAttributes({ document_id: 0 });
            }

            return [
                el(
                    InspectorControls,
                    { key: 'inspector' },
                    el(
                        PanelBody,
                        { title: 'Réglages du formulaire', initialOpen: true },
                        el(TextControl, {
                            label: 'Titre du formulaire',
                            value: attributes.title,
                            onChange: onChangeTitle
                        }),
                        el(TextControl, {
                            label: 'Sous-titre',
                            value: attributes.subtitle,
                            onChange: onChangeSubtitle
                        }),
                        el(
                            'div',
                            { style: { marginTop: '15px', marginBottom: '15px' } },
                            el('label', { style: { display: 'block', fontWeight: 'bold', marginBottom: '5px' } }, 'Document PDF spécifique :'),
                            el(MediaUpload, {
                                onSelect: onSelectMedia,
                                type: 'application/pdf',
                                value: attributes.document_id,
                                render: function (obj) {
                                    return el(
                                        Button,
                                        {
                                            isPrimary: true,
                                            onClick: obj.open
                                        },
                                        attributes.document_id ? 'Changer le PDF (ID: ' + attributes.document_id + ')' : 'Choisir un PDF spécifique'
                                    );
                                }
                            }),
                            attributes.document_id ? el(
                                Button,
                                {
                                    isDefault: true,
                                    isDestructive: true,
                                    onClick: onRemoveMedia,
                                    style: { marginLeft: '10px' }
                                },
                                'Utiliser le PDF global'
                            ) : null,
                            el('p', { className: 'description', style: { fontSize: '12px', color: '#666', marginTop: '5px' } }, 'Si aucun PDF spécifique n\'est sélectionné, le PDF par défaut configuré dans Réglages PDF sera envoyé.')
                        )
                    )
                ),
                el(
                    'div',
                    { key: 'preview', className: 'wp-pdf-reg-container et_pb_module' },
                    el(
                        'div',
                        { className: 'wp-pdf-reg-card' },
                        el('h2', { className: 'wp-pdf-reg-title' }, attributes.title || 'Formulaire d\'inscription'),
                        el('p', { className: 'wp-pdf-reg-subtitle' }, attributes.subtitle || ''),
                        attributes.document_id ? el('p', { style: { fontSize: '12px', color: '#2b6cb0', textAlign: 'center', fontWeight: 'bold' } }, '📄 Document PDF spécifique associé ID: ' + attributes.document_id) : null,
                        el(
                            'div',
                            { className: 'wp-pdf-reg-form' },
                            el(
                                'div',
                                { className: 'wp-pdf-reg-field-group' },
                                el('label', {}, 'Prénom *'),
                                el('input', { type: 'text', className: 'wp-pdf-reg-input', disabled: true, placeholder: 'Votre prénom' })
                            ),
                            el(
                                'div',
                                { className: 'wp-pdf-reg-field-group' },
                                el('label', {}, 'Nom *'),
                                el('input', { type: 'text', className: 'wp-pdf-reg-input', disabled: true, placeholder: 'Votre nom' })
                            ),
                            el(
                                'div',
                                { className: 'wp-pdf-reg-field-group' },
                                el('label', {}, 'Adresse E-mail *'),
                                el('input', { type: 'email', className: 'wp-pdf-reg-input', disabled: true, placeholder: 'exemple@domaine.com' })
                            ),
                            el(
                                'div',
                                { className: 'wp-pdf-reg-field-group' },
                                el('label', {}, 'Société / Organisation'),
                                el('input', { type: 'text', className: 'wp-pdf-reg-input', disabled: true, placeholder: 'Nom de votre entreprise' })
                            ),
                            el(
                                'div',
                                { className: 'wp-pdf-reg-submit-group' },
                                el('button', { className: 'wp-pdf-reg-submit-btn et_pb_button', disabled: true }, 'S\'inscrire et demander le PDF (Aperçu Editeur)')
                            )
                        )
                    )
                )
            ];
        },

        save: function () {
            return null;
        }
    });
})(window.wp);
