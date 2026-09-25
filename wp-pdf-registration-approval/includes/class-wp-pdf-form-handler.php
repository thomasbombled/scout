<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_PDF_Form_Handler {

    public function init() {
        add_shortcode('pdf_registration_form', array($this, 'render_shortcode'));
        add_action('wp_ajax_submit_pdf_registration', array($this, 'handle_ajax_submission'));
        add_action('wp_ajax_nopriv_submit_pdf_registration', array($this, 'handle_ajax_submission'));

        // Secure PDF download endpoint for verified tokens
        add_action('init', array($this, 'handle_secure_download'));

        // Divi 4 Integration / Shortcode registration compatibility check
        add_action('et_builder_ready', array($this, 'register_divi_compatibility'));
    }

    public function register_divi_compatibility() {
        if (function_exists('et_builder_add_main_elements')) {
            // Shortcode support is natively present in Divi modules
        }
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Formulaire d\'inscription',
            'subtitle' => 'Inscrivez-vous pour recevoir votre document PDF après validation.'
        ), $atts, 'pdf_registration_form');

        ob_start();
        ?>
        <div class="wp-pdf-reg-container et_pb_module et_pb_text">
            <div class="wp-pdf-reg-card">
                <?php if (!empty($atts['title'])): ?>
                    <h2 class="wp-pdf-reg-title"><?php echo esc_html($atts['title']); ?></h2>
                <?php endif; ?>
                <?php if (!empty($atts['subtitle'])): ?>
                    <p class="wp-pdf-reg-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
                <?php endif; ?>

                <div class="wp-pdf-reg-alert" id="wp-pdf-reg-alert" style="display:none;"></div>

                <form id="wp-pdf-reg-form" class="wp-pdf-reg-form" method="post" action="">
                    <?php wp_nonce_field('wp_pdf_reg_nonce', 'wp_pdf_reg_nonce_field'); ?>

                    <!-- Honeypot anti-spam field -->
                    <div style="display:none;" aria-hidden="true">
                        <input type="text" name="wp_pdf_website_hp" id="wp_pdf_website_hp" tabindex="-1" autocomplete="off" />
                    </div>

                    <div class="wp-pdf-reg-field-group">
                        <label for="wp_pdf_first_name">Prénom <span class="required">*</span></label>
                        <input type="text" id="wp_pdf_first_name" name="first_name" class="wp-pdf-reg-input" placeholder="Votre prénom" required maxlength="50" />
                    </div>

                    <div class="wp-pdf-reg-field-group">
                        <label for="wp_pdf_last_name">Nom <span class="required">*</span></label>
                        <input type="text" id="wp_pdf_last_name" name="last_name" class="wp-pdf-reg-input" placeholder="Votre nom" required maxlength="50" />
                    </div>

                    <div class="wp-pdf-reg-field-group">
                        <label for="wp_pdf_email">Adresse E-mail <span class="required">*</span></label>
                        <input type="email" id="wp_pdf_email" name="email" class="wp-pdf-reg-input" placeholder="exemple@domaine.com" required />
                    </div>

                    <div class="wp-pdf-reg-field-group">
                        <label for="wp_pdf_company">Société / Organisation (optionnel)</label>
                        <input type="text" id="wp_pdf_company" name="company" class="wp-pdf-reg-input" placeholder="Nom de votre entreprise" maxlength="100" />
                    </div>

                    <div class="wp-pdf-reg-field-group wp-pdf-reg-submit-group">
                        <button type="submit" id="wp-pdf-reg-btn" class="wp-pdf-reg-submit-btn et_pb_button">
                            <span class="btn-text">S'inscrire et demander le PDF</span>
                            <span class="btn-spinner" style="display:none;"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_ajax_submission() {
        check_ajax_referer('wp_pdf_reg_nonce', 'nonce');

        // Check honeypot field
        if (!empty($_POST['wp_pdf_website_hp'])) {
            wp_send_json_error(array('message' => 'Soumission suspecte détectée.'));
        }

        // Rate limiting check per IP (5 submissions per 10 minutes)
        $user_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '0.0.0.0';
        $transient_key = 'wp_pdf_reg_limit_' . md5($user_ip);
        $attempts = get_transient($transient_key);

        if ($attempts && $attempts >= 5) {
            wp_send_json_error(array('message' => 'Trop de tentatives en peu de temps. Veuillez réessayer plus tard.'));
        }

        set_transient($transient_key, ($attempts ? $attempts + 1 : 1), 600);

        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name  = isset($_POST['last_name'])  ? sanitize_text_field($_POST['last_name'])  : '';
        $email      = isset($_POST['email'])      ? sanitize_email($_POST['email'])          : '';
        $company    = isset($_POST['company'])    ? sanitize_text_field($_POST['company'])    : '';

        // Strict Server-Side Controls & Validations
        if (empty($first_name) || mb_strlen($first_name) < 2 || mb_strlen($first_name) > 50) {
            wp_send_json_error(array('message' => 'Le prénom est obligatoire et doit contenir entre 2 et 50 caractères.'));
        }

        if (empty($last_name) || mb_strlen($last_name) < 2 || mb_strlen($last_name) > 50) {
            wp_send_json_error(array('message' => 'Le nom est obligatoire et doit contenir entre 2 et 50 caractères.'));
        }

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => 'L\'adresse e-mail fournie est invalide.'));
        }

        if (!empty($company) && mb_strlen($company) > 100) {
            wp_send_json_error(array('message' => 'Le nom de l\'entreprise ne doit pas dépasser 100 caractères.'));
        }

        // Create PDF Registration submission entry
        $post_title = $first_name . ' ' . $last_name . ' - ' . $email;
        $post_id = wp_insert_post(array(
            'post_type'   => 'pdf_registration',
            'post_title'  => $post_title,
            'post_status' => 'publish'
        ));

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.'));
        }

        // Generate unique security token for this submission
        $token = WP_PDF_Security::generate_secure_token($post_id, $email);

        update_post_meta($post_id, '_pdf_reg_first_name', $first_name);
        update_post_meta($post_id, '_pdf_reg_last_name', $last_name);
        update_post_meta($post_id, '_pdf_reg_email', $email);
        update_post_meta($post_id, '_pdf_reg_company', $company);
        update_post_meta($post_id, '_pdf_reg_status', 'pending'); // pending, approved, rejected
        update_post_meta($post_id, '_pdf_reg_date', current_time('mysql'));
        update_post_meta($post_id, '_pdf_reg_access_token', $token);

        // Notify admin about new submission if option enabled
        $admin_email = get_option('admin_email');
        $subject = sprintf('[Nouvelle inscription] Demande de PDF de %s %s', $first_name, $last_name);
        $message = sprintf("Une nouvelle demande d'inscription avec document PDF a été soumise.\n\nNom: %s %s\nEmail: %s\nSociété: %s\n\nConnectez-vous au back-office WordPress pour valider ou rejeter cette demande.", $first_name, $last_name, $email, $company ? $company : 'N/A');

        wp_mail($admin_email, $subject, $message);

        wp_send_json_success(array('message' => 'Votre demande d\'inscription a été transmise avec succès ! Un administrateur va la valider sous peu et vous recevrez votre PDF par email.'));
    }

    public function handle_secure_download() {
        if (isset($_GET['wp_pdf_download']) && isset($_GET['post_id']) && isset($_GET['token'])) {
            $post_id = intval($_GET['post_id']);
            $token   = sanitize_text_field($_GET['token']);

            if (!WP_PDF_Security::verify_secure_token($post_id, $token)) {
                wp_die('Lien de téléchargement invalide ou expiré.', 'Erreur de Sécurité', array('response' => 403));
            }

            $status = get_post_meta($post_id, '_pdf_reg_status', true);
            if ($status !== 'approved') {
                wp_die('Cette demande n\'a pas encore été validée par un administrateur.', 'Accès Refusé', array('response' => 403));
            }

            $document_id = get_option('wp_pdf_reg_document_id', '');
            if (!$document_id) {
                wp_die('Aucun document associé.', 'Fichier Non Trouvé', array('response' => 404));
            }

            $file_path = get_attached_file($document_id);
            if (!$file_path || !file_exists($file_path)) {
                wp_die('Fichier introuvable sur le serveur.', 'Fichier Non Trouvé', array('response' => 404));
            }

            // Verify MIME type is PDF
            $file_type = wp_check_filetype($file_path);
            if ($file_type['type'] !== 'application/pdf') {
                wp_die('Fichier non autorisé.', 'Type de Fichier Invalide', array('response' => 400));
            }

            // Serve file securely
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }
}
