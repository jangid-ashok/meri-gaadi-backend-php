// const modal = new bootstrap.Modal(document.getElementById('delete-popup-modal'));
const buttonLoaderHTML = '<div class="spinner-border spinner-border-sm text-light" role="status"><span class="visually-hidden"> Please Wait...</span></div>&nbsp;Please Wait...';
const submitButton = $('#blogCategoryForm').find('.submit-form');


$(document).on('submit', '#blogCategoryForm', function (e) {
    e.preventDefault();

    let formData = new FormData(this);
    let formURL = $('#blogCategoryForm').attr('action');
    let buttonHTML = submitButton.html();
    console.log(formData);
    console.log(formURL);
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
            clearFormValidationErrors($('#blogCategoryForm'));
        },
        success: (res) => {
            handleAjaxFormResponse(
                $('#blogCategoryForm'),
                res,
                submitButton,
                buttonHTML,
                pageType === 'add'
            );
        },
        error: function (xhr) {
            const res = xhr.responseJSON || {};
            handleAjaxFormResponse(
                $('#blogCategoryForm'),
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

/***************** Blog Categories List *****************/
$(document).ready(() => {
    if (pageType == 'list') {
        getBlogCategories();
    }
});

$(document).on('click', '.pagination-link', function (e) {
    e.preventDefault();

    const page = parseInt($(this).data('page'));
    if (!isNaN(page)) {
        getBlogCategories(page, $('#category-keyword').val() || '');
    }
});

$('#category-search-btn').on('click', function () {
    getBlogCategories(1, $('#category-keyword').val() || '');
});

$('#category-search-reset').on('click', function () {
    $('#category-keyword').val('');
    getBlogCategories(1, '');
});

$(document).on('keydown', '#category-keyword', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        getBlogCategories(1, $(this).val() || '');
    }
});

function getBlogCategories(pageNo = 1, keyword = '') {
    $.ajax({
        type: 'get',
        url: 'get-blog-categories',
        data: { page: pageNo, keyword: keyword },
        dataType: 'json',
        beforeSend: () => {
            showTableLoader('#get-category-list');
        },
        success: (response) => {
            $('#get-category-list').html(response.html);
            $('.pagination-div').html(buildPagination(response.total_records, pageLimit, pageNo));
        }

    });
}

$(document).on('click', '.delete-popup-btn', function (e) {
    e.preventDefault();

    const url = $(this).data('url');
    // Set form action
    $('#deleteForm').attr('action', url);
    $('#record-id').val($(this).attr('data-user'));

    // Show modal using Bootstrap JS
    const modalEl = document.getElementById('delete-popup-modal');
    const modal = new bootstrap.Modal(modalEl);

    // Optional: forcibly remove aria-hidden if browser lags
    modalEl.removeAttribute('aria-hidden');

    modal.show();
});

$(document).on('submit', '#deleteForm', function(e) {
	e.preventDefault();
    const $submitBtn = $(this).find('button[type="submit"]');
    const $submitBtnHTML = $(this).find('button[type="submit"]').html();
	if ($('#record-id').val() == '') {
		console.log('Error: no user selected');
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
			}
		});
	}
});
