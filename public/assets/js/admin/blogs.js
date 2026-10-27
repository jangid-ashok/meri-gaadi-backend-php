
const buttonLoaderHTML = '<div class="spinner-border spinner-border-sm text-light" role="status"><span class="visually-hidden"> Please Wait...</span></div>&nbsp;Please Wait...';
const submitButton = $('#blogForm').find('.submit-form');


/***************** Blog Add/Edit Submit *****************/
$(document).on('submit', '#blogForm', function (e) {
    e.preventDefault();

    let formData = new FormData(this);
    let formURL = $('#blogForm').attr('action');
    let buttonHTML = submitButton.html();
    $.ajax({
        url: formURL,
        type: 'POST',
        data: formData,
        dataType: 'json',
        processData: false,
        contentType: false,
        beforeSend: () => {
            submitButton.attr('disabled', true);
            submitButton.html(buttonLoaderHTML);
            clearFormValidationErrors($('#blogForm'));
        },
        success: (res) => {
            handleAjaxFormResponse(
                $('#blogForm'),
                res,
                submitButton,
                buttonHTML,
                pageType === 'add'
            );
        },
        error: function (xhr) {
            const res = xhr.responseJSON || {};
            handleAjaxFormResponse(
                $('#blogForm'),
                {
                    success: false,
                    messageClass: 'alert-danger',
                    message: res.message || 'Something went wrong. Please try again.',
                    errors: res.errors
                },
                submitButton,
                buttonHTML,
                false
            );
        }
    });
});

$(document).on('click', '.delete-popup-btn', function (e) {
    e.preventDefault();

    const url = $(this).data('url');
    $('#deleteForm').attr('action', url);
    $('#record-id').val($(this).attr('data-user'));

    const modalEl = document.getElementById('delete-popup-modal');
    const modal = new bootstrap.Modal(modalEl);
    modalEl.removeAttribute('aria-hidden');
    modal.show();
});

$(document).on('submit', '#deleteForm', function(e) {
	e.preventDefault();
    const $submitBtn = $(this).find('button[type="submit"]');
    const $submitBtnHTML = $(this).find('button[type="submit"]').html();
	if ($('#record-id').val() == '') {
		console.log('Error: no record selected');
	} else {
		let formData = new FormData(this);
		let formURL = $('#deleteForm').attr('action');
		$.ajax({
			type: 'post',
			url: formURL,
			data: formData,
			dataType: 'json',
			processData: false,
			contentType: false,
			beforeSend: () => {
                $submitBtn.attr('disabled', true);
                $submitBtn.html(buttonLoaderHTML);
			},
			success: (res) => {
                const modalEl = document.getElementById('delete-popup-modal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
                showAlertMessages($('.alert-message-div'), res.messageClass, res.message, res.redirectURL);
                $submitBtn.attr('disabled', false);
                $submitBtn.html($submitBtnHTML);
			},
			error: (error) => {
				console.log('Error: '+error);
                $submitBtn.attr('disabled', false);
                $submitBtn.html($submitBtnHTML);
			}
		});
	}
});
