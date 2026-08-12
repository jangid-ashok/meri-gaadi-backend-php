const buttonLoaderHTML = '<div class="spinner-border spinner-border-sm text-light" role="status"><span class="visually-hidden">Please Wait...</span></div>&nbsp;Please Wait...';

const galleryUploadUrl = $('#galleryUploadUrl').val();
const galleryDeleteUrl = $('#galleryDeleteUrl').val();

function uploadGalleryFiles(files) {
    if (!files || files.length === 0) {
        return;
    }

    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }
    formData.append('_token', $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val());

    $.ajax({
        url: galleryUploadUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: () => {
            $('#gallery-upload-progress').show();
        },
        success: (res) => {
            showAlertMessages($('.alert-message-div'), res.messageClass, res.message);
            if (res.html) {
                $('#blog-gallery-grid').html(res.html);
            }
            $('#blogGalleryInput').val('');
        },
        error: (xhr) => {
            const msg = xhr.responseJSON?.message || 'Upload failed. Please try again.';
            showAlertMessages($('.alert-message-div'), 'alert-danger', msg);
        },
        complete: () => {
            $('#gallery-upload-progress').hide();
        }
    });
}

$(document).on('change', '#blogGalleryInput', function () {
    uploadGalleryFiles(this.files);
});

const dropzone = document.getElementById('blogGalleryDropzone');
if (dropzone) {
    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('border-primary');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('border-primary');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        uploadGalleryFiles(e.dataTransfer.files);
    });
}

$(document).on('click', '.gallery-delete-btn', function () {
    const mediaId = $(this).data('id');
    if (!mediaId || !confirm('Remove this file from the gallery?')) {
        return;
    }

    const $btn = $(this);
    const btnHtml = $btn.html();
    $btn.prop('disabled', true).html(buttonLoaderHTML);

    $.ajax({
        url: galleryDeleteUrl,
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val(),
            record_id: mediaId
        },
        dataType: 'json',
        success: (res) => {
            showAlertMessages($('.alert-message-div'), res.messageClass, res.message);
            if (res.success) {
                $btn.closest('.col-6, .col-md-4, .col-lg-3, .col-12').remove();
                if ($('#blog-gallery-grid').children().length === 0) {
                    $('#blog-gallery-grid').html('<div class="col-12"><p class="text-center text-muted mb-0 py-4">No gallery items yet. Upload images or videos above.</p></div>');
                }
            }
        },
        error: () => {
            showAlertMessages($('.alert-message-div'), 'alert-danger', 'Could not delete file.');
        },
        complete: () => {
            $btn.prop('disabled', false).html(btnHtml);
        }
    });
});
