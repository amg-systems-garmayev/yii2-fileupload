// src/assets/js/uploader.js
(function($) {
    'use strict';

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    function validateFile(file, options) {
        if (file.size > options.maxFileSize) {
            return 'Файл слишком большой. Максимальный размер: ' + formatBytes(options.maxFileSize);
        }

        const ext = file.name.split('.').pop().toLowerCase();
        if (options.allowedExtensions && options.allowedExtensions.length > 0) {
            if (!options.allowedExtensions.includes(ext)) {
                return 'Недопустимый формат файла. Разрешены: ' + options.allowedExtensions.join(', ');
            }
        }
        return null;
    }

    $.fn.uploader = function(options) {
        return this.each(function() {
            const $widget = $(this);
            const $fileInput = $widget.find('.file-upload-control');
            const $hiddenInput = $widget.find('.file-upload-input');
            const $progress = $widget.find('.progress');
            const $progressBar = $progress.find('.progress-bar');
            const $error = $widget.find('.upload-error');
            const $preview = $widget.find('.upload-preview');
            const $deleteBtn = $widget.find('.btn-delete');
            const $previewContainer = $widget.find('.preview-container');

            const config = $.extend({
                uploadUrl: '',
                cropUrl: '',
                deleteUrl: '',
                previewUrl: '',
                maxFileSize: 5242880,
                allowedExtensions: ['jpg', 'jpeg', 'png', 'gif'],
                enableCropper: false,
                cropOptions: {},
                csrfToken: '',
                csrfParam: '_csrf',
                widgetId: '',
                inputId: '',
                errorContainerId: '',
                previewSize: 'medium'
            }, options);

            // Кнопка выбора файла
            $widget.find('.btn-browse').on('click', function() {
                $fileInput.trigger('click');
            });

            // Обработка выбора файла
            $fileInput.off('change').on('change', function(e) {
                const files = e.target.files;
                if (files.length > 0) {
                    uploadFile(files[0]);
                }
            });

            // Удаление файла
            if ($deleteBtn.length) {
                $deleteBtn.off('click').on('click', function() {
                    const $btn = $(this);
                    const filename = $btn.data('filename');

                    if (confirm('Вы уверены, что хотите удалить файл?')) {
                        deleteFile(filename, $btn);
                    }
                });
            }

            function uploadFile(file) {
                const validationError = validateFile(file, config);
                if (validationError) {
                    showError(validationError);
                    $fileInput.val('');
                    return false;
                }

                const formData = new FormData();
                formData.append('file', file);
                formData.append(config.csrfParam, config.csrfToken);

                $progress.removeClass('d-none');
                $error.addClass('d-none');

                const $errorContainer = $('#' + config.errorContainerId);
                if ($errorContainer.length) {
                    $errorContainer.html('').removeClass('d-block');
                }
                $fileInput.removeClass('is-invalid');

                $.ajax({
                    url: config.uploadUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        const xhr = new XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                const percent = Math.round((e.loaded / e.total) * 100);
                                $progressBar.css('width', percent + '%');
                            }
                        }, false);
                        return xhr;
                    },
                    // В функции uploadFile, после успешной загрузки:
                    success: function(response) {
                        console.log('Upload success:', response);
                        if (response.success) {
                            // Сохраняем полный путь к файлу (относительный или абсолютный)
                            var fileValue = response.filePath || response.fileUrl || response.filename;
                            $hiddenInput.val(fileValue);

                            if (config.enableCropper && response.isImage) {
                                console.log('Cropper enabled, showing modal...'); // Добавьте лог
                                console.log('showCropperModal available:', typeof window.showCropperModal === 'function'); // Добавьте лог

                                const cropOptions = window.cropperOptions && window.cropperOptions[config.widgetId]
                                    ? window.cropperOptions[config.widgetId]
                                    : config.cropOptions || {};

                                console.log('Crop options:', cropOptions); // Добавьте лог

                                if (typeof window.showCropperModal === 'function') {
                                    window.showCropperModal($widget, response.fileUrl, response.filename, cropOptions);
                                } else {
                                    console.error('showCropperModal is not defined!');
                                    // Fallback - сохраняем без обрезки
                                    $hiddenInput.val(response.filename);
                                    updatePreview(response.filename);
                                }
                            } else {
                                updatePreview(response.filename);
                            }

                            // Очищаем ошибки
                            if ($errorContainer.length) {
                                $errorContainer.html('').removeClass('d-block');
                            }
                            $fileInput.removeClass('is-invalid');
                            $hiddenInput.removeClass('is-invalid');
                        } else {
                            showError(response.message || 'Ошибка загрузки');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Upload error:', xhr, status, error);
                        let errorMessage = 'Ошибка соединения с сервером';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch(e) {
                            if (xhr.responseText) {
                                errorMessage = xhr.responseText;
                            }
                        }
                        showError(errorMessage);

                        if ($errorContainer.length) {
                            $errorContainer.html(errorMessage).addClass('d-block');
                            $fileInput.addClass('is-invalid');
                        }
                    },
                    complete: function() {
                        $progress.addClass('d-none');
                        $progressBar.css('width', '0%');
                        $fileInput.val('');
                    }
                });
            }

            function deleteFile(filename, $btn) {
                const formData = new FormData();
                formData.append('filename', filename);
                formData.append(config.csrfParam, config.csrfToken);

                $.ajax({
                    url: config.deleteUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Delete success:', response);
                        if (response.success) {
                            $hiddenInput.val('');
                            $widget.find('.upload-preview').remove();
                            $btn.closest('.mt-2').remove();

                            const $errorContainer = $('#' + config.errorContainerId);
                            if ($errorContainer.length) {
                                $errorContainer.html('').removeClass('d-block');
                            }
                        } else {
                            alert(response.message || 'Ошибка при удалении файла');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', xhr, status, error);
                        alert('Ошибка соединения с сервером: ' + error);
                    }
                });
            }

            function showError(message) {
                $error.text(message).removeClass('d-none');
            }

            function updatePreview(filename) {
                const previewUrl = config.previewUrl + '?filename=' + encodeURIComponent(filename) +
                    '&size=' + config.previewSize + '&t=' + new Date().getTime();

                if ($preview.length) {
                    $preview.attr('src', previewUrl);
                } else if ($previewContainer.length) {
                    const previewHtml = '<img src="' + previewUrl + '" class="img-thumbnail upload-preview" style="max-width: 200px; max-height: 200px; object-fit: contain;">';
                    $previewContainer.html(previewHtml);
                } else {
                    const $container = $widget.find('.upload-container');
                    $container.prepend(
                        '<div class="preview-container mb-3">' +
                        '<img src="' + previewUrl + '" class="img-thumbnail upload-preview" style="max-width: 200px; max-height: 200px; object-fit: contain;">' +
                        '</div>'
                    );
                }

                const newFilename = $hiddenInput.val();
                let $existingDeleteBtn = $widget.find('.btn-delete');

                if ($existingDeleteBtn.length) {
                    $existingDeleteBtn.data('filename', newFilename);
                    $existingDeleteBtn.attr('data-filename', newFilename);
                } else {
                    const deleteBtnHtml =
                        '<div class="mt-2">' +
                        '<button type="button" class="btn btn-danger btn-sm btn-delete"' +
                        ' data-filename="' + newFilename + '">' +
                        '<i class="bi bi-trash"></i> Удалить</button>' +
                        '</div>';

                    $widget.find('.upload-container').append(deleteBtnHtml);

                    $widget.find('.btn-delete').off('click').on('click', function() {
                        const $btn = $(this);
                        const filename = $btn.data('filename');

                        if (confirm('Вы уверены, что хотите удалить файл?')) {
                            deleteFile(filename, $btn);
                        }
                    });
                }
            }
        });
    };

})(jQuery);

// Добавьте это в КОНЕЦ файла uploader.js
$(document).ready(function() {
    $('.file-upload-widget').each(function() {
        var $widget = $(this);
        var widgetId = $widget.attr('id');

        // Получаем конфигурацию из data-атрибута
        var config = $widget.data('uploader-config');

        if (config) {
            $widget.uploader(config);
        } else {
            console.warn('No config found for widget:', widgetId);
        }
    });
});