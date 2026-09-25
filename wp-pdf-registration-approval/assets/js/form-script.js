jQuery(document).ready(function ($) {
    var $form = $('#wp-pdf-reg-form');
    var $alert = $('#wp-pdf-reg-alert');
    var $btn = $('#wp-pdf-reg-btn');

    if ($form.length === 0) return;

    $form.on('submit', function (e) {
        e.preventDefault();

        $alert.hide().removeClass('wp-pdf-reg-alert-success wp-pdf-reg-alert-error');
        $btn.prop('disabled', true).css('opacity', '0.7');

        var formData = {
            action: 'submit_pdf_registration',
            nonce: wpPdfRegAjax.nonce,
            first_name: $('#wp_pdf_first_name').val(),
            last_name: $('#wp_pdf_last_name').val(),
            email: $('#wp_pdf_email').val(),
            company: $('#wp_pdf_company').val()
        };

        $.ajax({
            url: wpPdfRegAjax.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                $btn.prop('disabled', false).css('opacity', '1');
                if (response.success) {
                    $alert.addClass('wp-pdf-reg-alert-success').html(response.data.message).fadeIn();
                    $form[0].reset();
                } else {
                    $alert.addClass('wp-pdf-reg-alert-error').html(response.data.message || 'Une erreur est survenue.').fadeIn();
                }
            },
            error: function () {
                $btn.prop('disabled', false).css('opacity', '1');
                $alert.addClass('wp-pdf-reg-alert-error').html('Une erreur réseau ou serveur est survenue. Veuillez réessayer.').fadeIn();
            }
        });
    });
});
