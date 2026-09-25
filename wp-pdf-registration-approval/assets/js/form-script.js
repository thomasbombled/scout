jQuery(document).ready(function ($) {
    var $form = $('#wp-pdf-reg-form');
    var $alert = $('#wp-pdf-reg-alert');
    var $btn = $('#wp-pdf-reg-btn');

    if ($form.length === 0) return;

    function validateEmail(email) {
        var re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(String(email).toLowerCase());
    }

    function validateName(name) {
        // Accepts letters, spaces, hyphens and apostrophes (min 2 chars)
        var re = /^[a-zA-ZÀ-ÿ\s'-]{2,50}$/;
        return re.test(String(name).trim());
    }

    function showError($input, message) {
        $input.addClass('is-invalid');
        var $fieldGroup = $input.closest('.wp-pdf-reg-field-group');
        var $error = $fieldGroup.find('.wp-pdf-field-error');
        if ($error.length === 0) {
            $error = $('<div class="wp-pdf-field-error"></div>');
            $fieldGroup.append($error);
        }
        $error.text(message).show();
    }

    function clearError($input) {
        $input.removeClass('is-invalid');
        var $fieldGroup = $input.closest('.wp-pdf-reg-field-group');
        $fieldGroup.find('.wp-pdf-field-error').hide().text('');
    }

    // Real-time inline validation
    $('#wp_pdf_first_name').on('input blur', function () {
        var val = $(this).val().trim();
        if (val === '') {
            showError($(this), 'Le prénom est obligatoire.');
        } else if (!validateName(val)) {
            showError($(this), 'Le prénom doit contenir entre 2 et 50 caractères valides.');
        } else {
            clearError($(this));
        }
    });

    $('#wp_pdf_last_name').on('input blur', function () {
        var val = $(this).val().trim();
        if (val === '') {
            showError($(this), 'Le nom est obligatoire.');
        } else if (!validateName(val)) {
            showError($(this), 'Le nom doit contenir entre 2 et 50 caractères valides.');
        } else {
            clearError($(this));
        }
    });

    $('#wp_pdf_email').on('input blur', function () {
        var val = $(this).val().trim();
        if (val === '') {
            showError($(this), 'L\'adresse e-mail est obligatoire.');
        } else if (!validateEmail(val)) {
            showError($(this), 'Veuillez saisir une adresse e-mail valide.');
        } else {
            clearError($(this));
        }
    });

    $('#wp_pdf_company').on('input blur', function () {
        var val = $(this).val().trim();
        if (val !== '' && val.length > 100) {
            showError($(this), 'Le nom de la société ne doit pas dépasser 100 caractères.');
        } else {
            clearError($(this));
        }
    });

    $form.on('submit', function (e) {
        e.preventDefault();

        // Trigger validation on all fields
        $('#wp_pdf_first_name, #wp_pdf_last_name, #wp_pdf_email, #wp_pdf_company').trigger('blur');

        if ($form.find('.is-invalid').length > 0) {
            $alert.addClass('wp-pdf-reg-alert-error')
                  .html('Veuillez corriger les erreurs indiquées dans le formulaire avant de soumettre.')
                  .fadeIn();
            return;
        }

        $alert.hide().removeClass('wp-pdf-reg-alert-success wp-pdf-reg-alert-error');
        $btn.prop('disabled', true).css('opacity', '0.7');

        var formData = {
            action: 'submit_pdf_registration',
            nonce: wpPdfRegAjax.nonce,
            first_name: $('#wp_pdf_first_name').val().trim(),
            last_name: $('#wp_pdf_last_name').val().trim(),
            email: $('#wp_pdf_email').val().trim(),
            company: $('#wp_pdf_company').val().trim(),
            wp_pdf_website_hp: $('#wp_pdf_website_hp').val()
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
                    $('.wp-pdf-field-error').hide();
                    $('.wp-pdf-reg-input').removeClass('is-invalid');
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
