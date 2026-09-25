<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_PDF_Admin {

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_pdf_registration', array($this, 'save_post_meta'));
        add_action('admin_post_pdf_reg_approve', array($this, 'handle_action_approve'));
        add_action('admin_post_pdf_reg_reject', array($this, 'handle_action_reject'));
        add_filter('manage_pdf_registration_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_pdf_registration_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);
        add_filter('post_row_actions', array($this, 'add_row_actions'), 10, 2);
        add_action('admin_notices', array($this, 'render_admin_notices'));
        add_action('admin_init', array($this, 'register_settings'));

        // Bulk Actions support
        add_filter('bulk_actions-edit-pdf_registration', array($this, 'register_bulk_actions'));
        add_filter('handle_bulk_actions-edit-pdf_registration', array($this, 'handle_bulk_actions'), 10, 3);
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=pdf_registration',
            'Réglages PDF & Emails',
            'Réglages PDF',
            'manage_options',
            'wp-pdf-registration-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('wp_pdf_reg_settings_group', 'wp_pdf_reg_document_id');
        register_setting('wp_pdf_reg_settings_group', 'wp_pdf_reg_email_subject');
        register_setting('wp_pdf_reg_settings_group', 'wp_pdf_reg_email_body');
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $document_id = get_option('wp_pdf_reg_document_id', '');
        $email_subject = get_option('wp_pdf_reg_email_subject', 'Votre document PDF est disponible');
        $email_body = get_option('wp_pdf_reg_email_body', "Bonjour {first_name} {last_name},\n\nVotre demande d'inscription a été validée par un administrateur.\nVous trouverez ci-joint votre document PDF.\n\nCordialement,\nL'équipe.");

        $document_url = $document_id ? wp_get_attachment_url($document_id) : '';

        ?>
        <div class="wrap">
            <h1>Réglages Inscription & Validation PDF (Par Défaut)</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_pdf_reg_settings_group');
                do_settings_sections('wp_pdf_reg_settings_group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Document PDF par défaut :</th>
                        <td>
                            <input type="hidden" name="wp_pdf_reg_document_id" id="wp_pdf_reg_document_id" value="<?php echo esc_attr($document_id); ?>" />
                            <input type="text" id="wp_pdf_reg_document_url" class="regular-text" value="<?php echo esc_attr($document_url); ?>" readonly />
                            <button type="button" class="button button-secondary" id="wp_pdf_reg_upload_btn">Sélectionner / Téleverser un PDF</button>
                            <p class="description">Ce PDF sera utilisé si aucun PDF spécifique n'est défini sur le formulaire ou l'inscription.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Sujet de l'email :</th>
                        <td>
                            <input type="text" name="wp_pdf_reg_email_subject" class="large-text" value="<?php echo esc_attr($email_subject); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Corps de l'email :</th>
                        <td>
                            <?php
                            wp_editor($email_body, 'wp_pdf_reg_email_body', array(
                                'textarea_name' => 'wp_pdf_reg_email_body',
                                'textarea_rows' => 8,
                                'media_buttons' => false
                            ));
                            ?>
                            <p class="description">Balises disponibles : <code>{first_name}</code>, <code>{last_name}</code>, <code>{company}</code>, <code>{email}</code>, <code>{download_link}</code></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Enregistrer les modifications'); ?>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#wp_pdf_reg_upload_btn').click(function(e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: 'Choisir le document PDF par défaut',
                    button: { text: 'Utiliser ce fichier' },
                    multiple: false,
                    library: { type: 'application/pdf' }
                });

                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#wp_pdf_reg_document_id').val(attachment.id);
                    $('#wp_pdf_reg_document_url').val(attachment.url);
                });

                mediaUploader.open();
            });
        });
        </script>
        <?php
    }

    public function add_meta_boxes() {
        add_meta_box(
            'wp_pdf_reg_details',
            'Détails & Validation de l\'inscription',
            array($this, 'render_meta_box_details'),
            'pdf_registration',
            'normal',
            'high'
        );
    }

    public function render_meta_box_details($post) {
        wp_nonce_field('wp_pdf_reg_save_meta', 'wp_pdf_reg_meta_nonce');

        $first_name  = get_post_meta($post->ID, '_pdf_reg_first_name', true);
        $last_name   = get_post_meta($post->ID, '_pdf_reg_last_name', true);
        $email       = get_post_meta($post->ID, '_pdf_reg_email', true);
        $company     = get_post_meta($post->ID, '_pdf_reg_company', true);
        $status      = get_post_meta($post->ID, '_pdf_reg_status', true);
        $date        = get_post_meta($post->ID, '_pdf_reg_date', true);
        $document_id = get_post_meta($post->ID, '_pdf_reg_document_id', true);

        if (!$document_id) {
            $document_id = get_option('wp_pdf_reg_document_id', '');
        }

        $document_url = $document_id ? wp_get_attachment_url($document_id) : '';

        if (!$status) {
            $status = 'pending';
        }

        $status_labels = array(
            'pending'  => '<span style="color:#d97706; font-weight:bold;">En attente de validation</span>',
            'approved' => '<span style="color:#16a34a; font-weight:bold;">Validée & Email Envoyé</span>',
            'rejected' => '<span style="color:#dc2626; font-weight:bold;">Rejetée</span>'
        );

        $approve_url = wp_nonce_url(
            admin_url('admin-post.php?action=pdf_reg_approve&post_id=' . $post->ID),
            'pdf_reg_approve_' . $post->ID
        );

        $reject_url = wp_nonce_url(
            admin_url('admin-post.php?action=pdf_reg_reject&post_id=' . $post->ID),
            'pdf_reg_reject_' . $post->ID
        );

        ?>
        <div style="padding: 10px 0;">
            <p><strong>Prénom :</strong> <?php echo esc_html($first_name); ?></p>
            <p><strong>Nom :</strong> <?php echo esc_html($last_name); ?></p>
            <p><strong>Email :</strong> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>
            <p><strong>Société :</strong> <?php echo esc_html($company ? $company : 'Non renseignée'); ?></p>
            <p><strong>Date de soumission :</strong> <?php echo esc_html($date); ?></p>
            <p><strong>Statut actuel :</strong> <?php echo isset($status_labels[$status]) ? $status_labels[$status] : esc_html($status); ?></p>

            <hr style="margin: 20px 0;" />

            <div style="margin-bottom:20px;">
                <label for="pdf_reg_doc_url"><strong>Document PDF spécifique associé :</strong></label><br />
                <input type="hidden" name="pdf_reg_document_id" id="pdf_reg_document_id" value="<?php echo esc_attr($document_id); ?>" />
                <input type="text" id="pdf_reg_document_url" class="regular-text" value="<?php echo esc_attr($document_url); ?>" readonly style="margin-top:5px;" />
                <button type="button" class="button button-secondary" id="wp_pdf_reg_meta_upload_btn" style="margin-top:5px;">Changer le PDF pour cette inscription</button>
            </div>

            <div style="display:flex; gap:10px;">
                <?php if ($status !== 'approved'): ?>
                    <a href="<?php echo esc_url($approve_url); ?>" class="button button-primary button-large">Valider & Envoyer le PDF</a>
                <?php endif; ?>

                <?php if ($status !== 'rejected'): ?>
                    <a href="<?php echo esc_url($reject_url); ?>" class="button button-secondary button-large" style="color:#dc2626; border-color:#dc2626;" onclick="return confirm('Êtes-vous sûr de vouloir rejeter cette demande ?');">Rejeter</a>
                <?php endif; ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#wp_pdf_reg_meta_upload_btn').click(function(e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: 'Changer le PDF pour cette inscription',
                    button: { text: 'Utiliser ce fichier' },
                    multiple: false,
                    library: { type: 'application/pdf' }
                });

                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#pdf_reg_document_id').val(attachment.id);
                    $('#pdf_reg_document_url').val(attachment.url);
                });

                mediaUploader.open();
            });
        });
        </script>
        <?php
    }

    public function save_post_meta($post_id) {
        if (!isset($_POST['wp_pdf_reg_meta_nonce']) || !wp_verify_nonce($_POST['wp_pdf_reg_meta_nonce'], 'wp_pdf_reg_save_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['pdf_reg_document_id'])) {
            update_post_meta($post_id, '_pdf_reg_document_id', intval($_POST['pdf_reg_document_id']));
        }
    }

    public function handle_action_approve() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }

        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        check_admin_referer('pdf_reg_approve_' . $post_id);

        if ($post_id) {
            $mailer = new WP_PDF_Mailer();
            $sent = $mailer->send_approval_email($post_id);

            if ($sent) {
                update_post_meta($post_id, '_pdf_reg_status', 'approved');
                $redirect = add_query_arg('pdf_msg', 'approved', admin_url('edit.php?post_type=pdf_registration'));
            } else {
                $redirect = add_query_arg('pdf_msg', 'error', admin_url('edit.php?post_type=pdf_registration'));
            }
            wp_redirect($redirect);
            exit;
        }
    }

    public function handle_action_reject() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }

        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        check_admin_referer('pdf_reg_reject_' . $post_id);

        if ($post_id) {
            update_post_meta($post_id, '_pdf_reg_status', 'rejected');
            $redirect = add_query_arg('pdf_msg', 'rejected', admin_url('edit.php?post_type=pdf_registration'));
            wp_redirect($redirect);
            exit;
        }
    }

    public function add_row_actions($actions, $post) {
        if ($post->post_type === 'pdf_registration') {
            $status = get_post_meta($post->ID, '_pdf_reg_status', true);

            if ($status !== 'approved') {
                $approve_url = wp_nonce_url(
                    admin_url('admin-post.php?action=pdf_reg_approve&post_id=' . $post->ID),
                    'pdf_reg_approve_' . $post->ID
                );
                $actions['approve'] = '<a href="' . esc_url($approve_url) . '" style="color:#16a34a; font-weight:bold;">Valider & Envoyer Mail</a>';
            }

            if ($status !== 'rejected') {
                $reject_url = wp_nonce_url(
                    admin_url('admin-post.php?action=pdf_reg_reject&post_id=' . $post->ID),
                    'pdf_reg_reject_' . $post->ID
                );
                $actions['reject'] = '<a href="' . esc_url($reject_url) . '" style="color:#dc2626;">Rejeter</a>';
            }
        }
        return $actions;
    }

    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['pdf_bulk_approve'] = 'Valider et envoyer le PDF';
        $bulk_actions['pdf_bulk_reject'] = 'Rejeter les demandes';
        return $bulk_actions;
    }

    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        if ($action === 'pdf_bulk_approve') {
            $approved_count = 0;
            $mailer = new WP_PDF_Mailer();

            foreach ($post_ids as $post_id) {
                if ($mailer->send_approval_email($post_id)) {
                    update_post_meta($post_id, '_pdf_reg_status', 'approved');
                    $approved_count++;
                }
            }
            $redirect_to = add_query_arg('pdf_bulk_approved_count', $approved_count, $redirect_to);
        } elseif ($action === 'pdf_bulk_reject') {
            foreach ($post_ids as $post_id) {
                update_post_meta($post_id, '_pdf_reg_status', 'rejected');
            }
            $redirect_to = add_query_arg('pdf_bulk_rejected_count', count($post_ids), $redirect_to);
        }
        return $redirect_to;
    }

    public function render_admin_notices() {
        if (isset($_GET['pdf_msg'])) {
            $msg = sanitize_text_field($_GET['pdf_msg']);
            if ($msg === 'approved') {
                echo '<div class="notice notice-success is-dismissible"><p><strong>Inscription validée !</strong> L\'e-mail avec le document PDF a été transmis au destinataire avec succès.</p></div>';
            } elseif ($msg === 'rejected') {
                echo '<div class="notice notice-warning is-dismissible"><p>L\'inscription a été marquée comme rejetée.</p></div>';
            } elseif ($msg === 'error') {
                echo '<div class="notice notice-error is-dismissible"><p><strong>Erreur :</strong> Impossible d\'envoyer l\'email avec le PDF. Veuillez vérifier la configuration de messagerie WordPress.</p></div>';
            }
        }

        if (isset($_GET['pdf_bulk_approved_count'])) {
            $count = intval($_GET['pdf_bulk_approved_count']);
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . $count . ' inscription(s) validée(s) !</strong> Les e-mails contenant le PDF ont été envoyés.</p></div>';
        }

        if (isset($_GET['pdf_bulk_rejected_count'])) {
            $count = intval($_GET['pdf_bulk_rejected_count']);
            echo '<div class="notice notice-warning is-dismissible"><p>' . $count . ' inscription(s) rejetée(s).</p></div>';
        }
    }

    public function set_custom_columns($columns) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'title' => 'Demandeur',
            'email' => 'Email',
            'company' => 'Société',
            'document' => 'PDF Associé',
            'status' => 'Statut',
            'date' => 'Date'
        );
        return $new_columns;
    }

    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'email':
                $email = get_post_meta($post_id, '_pdf_reg_email', true);
                echo esc_html($email);
                break;
            case 'company':
                $company = get_post_meta($post_id, '_pdf_reg_company', true);
                echo esc_html($company ? $company : '-');
                break;
            case 'document':
                $doc_id = get_post_meta($post_id, '_pdf_reg_document_id', true);
                if (!$doc_id) {
                    $doc_id = get_option('wp_pdf_reg_document_id', '');
                }
                if ($doc_id) {
                    $file_url = wp_get_attachment_url($doc_id);
                    echo '<a href="' . esc_url($file_url) . '" target="_blank" style="text-decoration:none;">📄 ' . esc_html(basename(get_attached_file($doc_id))) . '</a>';
                } else {
                    echo '<span style="color:#94a3b8;">Aucun PDF</span>';
                }
                break;
            case 'status':
                $status = get_post_meta($post_id, '_pdf_reg_status', true);
                if ($status === 'approved') {
                    echo '<span style="background:#dcfce7; color:#15803d; padding:3px 8px; border-radius:4px; font-weight:600;">Validée & Email Envoyé</span>';
                } elseif ($status === 'rejected') {
                    echo '<span style="background:#fee2e2; color:#b91c1c; padding:3px 8px; border-radius:4px; font-weight:600;">Rejetée</span>';
                } else {
                    echo '<span style="background:#fef3c7; color:#b45309; padding:3px 8px; border-radius:4px; font-weight:600;">En attente</span>';
                }
                break;
        }
    }
}
