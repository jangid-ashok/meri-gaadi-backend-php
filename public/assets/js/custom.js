const pageType = $('#pageType').val();
$(document).on('click', '.alert-close-btn', function (e) {
    e.preventDefault();
    closeAlertMessages($(this));
});

function closeAlertMessages($element) {
    let closestTarget = $element.closest('.card');
    closestTarget.find('.alert').removeClass('alert-danger alert-success');
    closestTarget.find('.error-message').text('');
    closestTarget.find('.alert-close-btn').fadeOut();
    closestTarget.find('.alert-message-div').fadeOut();
}

function showAlertMessages($element, errorClass, message, redirectURL="") {
    $element.find('.alert').removeClass('alert-danger alert-success').addClass(errorClass);
    $element.find('.error-message').text(message);
    $element.show();
    const autoCloseMs = errorClass === 'alert-danger' ? 6000 : 3000;
    setTimeout(() => {
        closeAlertMessages($element);
        if (redirectURL != "") {
            location.href = redirectURL;
        }
    }, autoCloseMs);
}

function clearFormValidationErrors($form) {
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback.ajax-field-error').remove();
}

function showFormValidationErrors($form, errors) {
    clearFormValidationErrors($form);
    if (!errors) {
        return;
    }

    $.each(errors, function (field, msgs) {
        const msg = Array.isArray(msgs) ? msgs[0] : msgs;
        const $input = $form.find('[name="' + field + '"]');
        if (!$input.length) {
            return;
        }

        $input.addClass('is-invalid');
        const $feedback = $('<div class="invalid-feedback ajax-field-error d-block"></div>').text(msg);
        const $wrap = $input.closest('.col-sm-10');
        if ($wrap.length) {
            $wrap.append($feedback);
        } else {
            $input.after($feedback);
        }
    });
}

function handleAjaxFormResponse($form, res, $submitBtn, buttonHTML, resetOnSuccess) {
    clearFormValidationErrors($form);
    $submitBtn.attr('disabled', false).html(buttonHTML);

    if (res.success) {
        showAlertMessages($('.alert-message-div'), res.messageClass, res.message, res.redirectURL || '');
        if (resetOnSuccess) {
            $form.trigger('reset');
            $form.find('.created-slug').val('');
        }
        return;
    }

    const commonMessage = res.message || 'Please enter valid data.';
    if (res.errors) {
        showFormValidationErrors($form, res.errors);
    }
    showAlertMessages($('.alert-message-div'), res.messageClass || 'alert-danger', commonMessage);
}

function confirmAdminAction(message = 'Are you sure you want to continue?') {
    const modalElement = document.getElementById('admin-confirm-modal');
    if (!modalElement || typeof bootstrap === 'undefined') return Promise.resolve(false);

    return new Promise((resolve) => {
        const instance = bootstrap.Modal.getOrCreateInstance(modalElement);
        const confirmButton = modalElement.querySelector('#admin-confirm-submit');
        let confirmed = false;
        modalElement.querySelector('#admin-confirm-message').textContent = message;
        confirmButton.onclick = () => {
            confirmed = true;
            instance.hide();
            resolve(true);
        };
        modalElement.addEventListener('hidden.bs.modal', () => {
            if (!confirmed) resolve(false);
        }, { once: true });
        instance.show();
    });
}

$(document).on('submit', 'form[data-confirm]', async function (event) {
    if (this.dataset.confirmed === 'true') {
        delete this.dataset.confirmed;
        return;
    }

    event.preventDefault();
    if (await confirmAdminAction(this.dataset.confirm)) {
        this.dataset.confirmed = 'true';
        this.requestSubmit();
    }
});

$(document).on('input change', 'form .is-invalid', function () {
    $(this).removeClass('is-invalid');
    $(this).closest('.col-sm-10').find('.ajax-field-error').remove();
});

function getTableColCount($container, colspan) {
    if (colspan) {
        return parseInt(colspan, 10);
    }

    const $table = $container.closest('table');
    if ($table.length) {
        const count = $table.find('thead tr:first th, thead tr:first td').length;
        if (count) {
            return count;
        }
    }

    return 1;
}

function showTableLoader(container, options = {}) {
    const $container = container instanceof jQuery ? container : $(container);
    if (!$container.length) {
        return;
    }

    const colspan = getTableColCount($container, options.colspan);
    const message = options.message || 'Loading...';
    const spinnerHtml = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">${message}</span>
        </div>`;

    if ($container.is('tbody')) {
        $container.html(`
            <tr class="table-loader-row">
                <td colspan="${colspan}" class="table-loader-cell text-center py-5">
                    ${spinnerHtml}
                </td>
            </tr>`);
        return;
    }

    $container.html(`
        <div class="table-body-loader text-center py-5">
            ${spinnerHtml}
        </div>`);
}

function generateSlug(text) {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')  // Remove special characters
        .replace(/\s+/g, '-')          // Replace spaces with -
        .replace(/-+/g, '-');          // Collapse multiple -
}

// Event handler function
function handleSlugGeneration($input) {
    let title = $input.val();
    let slug = generateSlug(title);
    $('.created-slug').val(slug);
}

// On input or paste
$(document).on('input paste', '.create-slug', function () {
    let $this = $(this);

    // For paste, wait a tiny moment for value to appear
    setTimeout(function () {
        handleSlugGeneration($this);
    }, 10);
});
