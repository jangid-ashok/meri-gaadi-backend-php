const pageType = $('#pageType').val();
const pageLimit = $('#pageLimit').val();
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

/* function buildPagination(currentPage, totalRecords) {
    let html = `
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item prev ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link pagination-link" href="javascript:void(0);" data-page="${currentPage - 1}">
                    <i class="icon-base bx bx-chevrons-left icon-sm"></i>
                </a>
            </li>`;

    for (let i = 1; i <= totalRecords; i++) {
        html += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link pagination-link" href="javascript:void(0);" data-page="${i}">${i}</a>
            </li>`;
    }

    html += `
            <li class="page-item next ${currentPage === totalRecords ? 'disabled' : ''}">
                <a class="page-link pagination-link" href="javascript:void(0);" data-page="${currentPage + 1}">
                    <i class="icon-base bx bx-chevrons-right icon-sm"></i>
                </a>
            </li>
        </ul>
    </nav>`;

    $('.pagination-link').html(html);
} */


function buildPagination(record_count, per_page, current_page) {
    record_count = parseInt(record_count);
    per_page = parseInt(per_page);
    current_page = parseInt(current_page);

    let no_of_pages = Math.ceil(record_count / per_page);
    let page_end = (current_page + 5 > no_of_pages) ? no_of_pages : Math.max((current_page + 5), 10);
    page_end = (page_end > no_of_pages) ? no_of_pages : page_end;
    let page_start = (page_end - 9 <= 0) ? 1 : (page_end - 9);

    let pagination_html = '';

    if (record_count > per_page) {
        pagination_html += `
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">`;

        // Prev button
        if (current_page !== 1) {
            pagination_html += `
                    <li class="page-item prev">
                        <a class="page-link pagination-link" href="javascript:void(0);" data-page="${current_page - 1}">
                            <i class="icon-base bx bx-chevrons-left icon-sm"></i>
                        </a>
                    </li>`;
        } else {
            pagination_html += `
                    <li class="page-item prev disabled">
                        <a class="page-link" href="javascript:void(0);">
                            <i class="icon-base bx bx-chevrons-left icon-sm"></i>
                        </a>
                    </li>`;
        }

        // Page numbers
        for (let i = page_start; i <= page_end; i++) {
            pagination_html += `
                    <li class="page-item ${i === current_page ? 'active' : ''}">
                        <a class="page-link pagination-link" href="javascript:void(0);" data-page="${i}">${i}</a>
                    </li>`;
        }

        // Next button
        if (current_page !== no_of_pages) {
            pagination_html += `
                    <li class="page-item next">
                        <a class="page-link pagination-link" href="javascript:void(0);" data-page="${current_page + 1}">
                            <i class="icon-base bx bx-chevrons-right icon-sm"></i>
                        </a>
                    </li>`;
        } else {
            pagination_html += `
                    <li class="page-item next disabled">
                        <a class="page-link" href="javascript:void(0);">
                            <i class="icon-base bx bx-chevrons-right icon-sm"></i>
                        </a>
                    </li>`;
        }

        pagination_html += `</ul></nav>`;
    }

    return pagination_html;
}
