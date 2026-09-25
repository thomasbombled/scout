<?php

if (!defined('ABSPATH')) {
    exit;
}

class WP_PDF_Mailer {

    public function send_approval_email($post_id) {
        $first_name = get_post_meta($post_id, '_pdf_reg_first_name', true);
        $last_name  = get_post_meta($post_id, '_pdf_reg_last_name', true);
        $email      = get_post_meta($post_id, '_pdf_reg_email', true);
        $company    = get_post_meta($post_id, '_pdf_reg_company', true);

        if (empty($email) || !is_email($email)) {
            return false;
        }

        $document_id = get_option('wp_pdf_reg_document_id', '');
        $subject_template = get_option('wp_pdf_reg_email_subject', 'Votre document PDF est disponible');
        $body_template = get_option('wp_pdf_reg_email_body', "Bonjour {first_name} {last_name},\n\nVotre demande d'inscription a été validée par un administrateur.\nVous trouverez ci-joint votre document PDF.\n\nCordialement,\nL'équipe.");

        // Replace placeholders
        $replacements = array(
            '{first_name}' => $first_name,
            '{last_name}'  => $last_name,
            '{email}'      => $email,
            '{company}'    => $company
        );

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject_template);
        $body    = str_replace(array_keys($replacements), array_values($replacements), $body_template);

        // Prepare attachments
        $attachments = array();
        if ($document_id) {
            $file_path = get_attached_file($document_id);
            if ($file_path && file_exists($file_path)) {
                $attachments[] = $file_path;
            }
        }

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Convert body line breaks to <br /> if HTML mail
        $formatted_body = wpautop($body);

        $result = wp_mail($email, $subject, $formatted_body, $headers, $attachments);

        return $result;
    }
}
