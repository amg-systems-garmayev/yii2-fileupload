// src/assets/js/cropper.js

(function ($) {
    'use strict';

    var cropperInstances = {};

    // Загружаем Cropper.js принудительно сразу при загрузке скрипта
    (function loadCropperLibs() {
        if (typeof Cropper !== 'undefined') {
            console.log('Cropper.js already loaded');
            return;
        }

        console.log('Loading Cropper.js from CDN...');

        // Загружаем CSS
        if (!$('link[href*="cropper.min.css"]').length) {
            $('<link>')
                .attr({
                    rel: 'stylesheet',
                    href: 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css',
                    type: 'text/css'
                })
                .appendTo('head');
        }

        // Загружаем JS
        if (!$('script[src*="cropper.min.js"]').length) {
            $.getScript('https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js')
                .done(function () {
                    console.log('Cropper.js loaded successfully');
                })
                .fail(function () {
                    console.error('Failed to load Cropper.js from CDN');
                    // Fallback к другому CDN
                    $.getScript('https://cdn.jsdelivr.net/npm/cropperjs@1.5.12/dist/cropper.min.js')
                        .done(function () {
                            console.log('Cropper.js loaded from fallback CDN');
                        })
                        .fail(function () {
                            console.error('Failed to load Cropper.js from fallback CDN');
                        });
                });
        }
    })();

    window.showCropperModal = function ($widget, imageUrl, filename, options) {
        console.log('showCropperModal called', {widget: $widget.attr('id'), imageUrl, filename});

        var widgetId = $widget.attr('id');
        var modalId = 'cropperModal-' + widgetId;
        var imageId = 'cropperImage-' + widgetId;

        // Уничтожаем старый экземпляр cropper
        if (cropperInstances[widgetId]) {
            cropperInstances[widgetId].destroy();
            delete cropperInstances[widgetId];
        }

        // Показываем модальное окно
        var $modal = $('#' + modalId);
        var $image = $('#' + imageId);

        // Устанавливаем src с временной меткой для избежания кеширования
        $image.attr('src', imageUrl + '?t=' + new Date().getTime());

        // Очищаем предыдущие данные
        $widget.removeData('cropData');
        console.log($image)
        $modal.modal('show');

        // Инициализируем cropper после показа модального окна
        $modal.off('shown.bs.modal').on('shown.bs.modal', function () {
            // Уничтожаем старый cropper если существует
            if (cropperInstances[widgetId]) {
                cropperInstances[widgetId].destroy();
            }
            cropperInstances[widgetId] = new Cropper($image[0], $.extend({
                aspectRatio: options.aspectRatio || null,
                viewMode: 2,
                autoCropArea: 1,
                responsive: true,
                restore: true,
                checkCrossOrigin: false,
                ready: function () {
                    console.log('Cropper ready for widget:', widgetId);
                },
                crop: function (event) {
                    // Сохраняем данные обрезки
                    $widget.data('cropData', {
                        x: Math.round(event.detail.x),
                        y: Math.round(event.detail.y),
                        width: Math.round(event.detail.width),
                        height: Math.round(event.detail.height),
                        rotate: event.detail.rotate || 0,
                        scaleX: event.detail.scaleX || 1,
                        scaleY: event.detail.scaleY || 1
                    });
                }
            }, options));
        });

        // Обработка кнопки обрезки
        $modal.find('.btn-crop').off('click').on('click', function () {
            cropImage($widget, filename);
        });

        // Очистка при закрытии
        $modal.off('hidden.bs.modal').on('hidden.bs.modal', function () {
            if (cropperInstances[widgetId]) {
                cropperInstances[widgetId].destroy();
                delete cropperInstances[widgetId];
            }
        });
    };

    function cropImage($widget, originalFilename) {
        console.log(this);
        var widgetId = $widget.attr('id');
        var cropData = $widget.data('cropData');

        if (!cropData) {
            alert('Пожалуйста, выберите область для обрезки');
            return;
        }

        var cropper = cropperInstances[widgetId];
        if (!cropper) {
            alert('Ошибка инициализации cropper');
            return;
        }

        var canvas = cropper.getCroppedCanvas();
        if (!canvas) {
            alert('Не удалось получить область обрезки');
            return;
        }

        // Показываем индикатор загрузки
        var $modal = $('#cropperModal-' + widgetId);
        var $cropBtn = $modal.find('.btn-crop');
        var originalText = $cropBtn.html();
        $cropBtn.html('<span class="spinner-border spinner-border-sm" role="status"></span> Обработка...');
        $cropBtn.prop('disabled', true);

        // Конвертируем canvas в blob
        canvas.toBlob(function (blob) {
            if (!blob) {
                alert('Ошибка при создании изображения');
                restoreButton();
                return;
            }

            var formData = new FormData();
            formData.append('file', blob, originalFilename);
            formData.append('cropData', JSON.stringify(cropData));
            formData.append('originalFilename', originalFilename);
            formData.append('_csrf', yii.getCsrfToken());

            // Получаем URL из конфига или из data-атрибута
            var config = $widget.data('uploader-config') || {};
            var uploadUrl = $widget.find('.file-upload-control').data('url');
            console.log($widget.find('.file-upload-control'));

            if (!uploadUrl) {
                console.error('Upload URL not found!');
                alert('Ошибка конфигурации: URL загрузки не определен');
                return;
            }

            // Формируем URL для обрезки
            var cropUrl;
            if (config.cropUrl) {
                // Используем явно указанный URL для обрезки
                cropUrl = config.cropUrl;
            } else {
                // Пытаемся сформировать URL из uploadUrl
                if (uploadUrl.indexOf('/upload') !== -1) {
                    cropUrl = uploadUrl.replace('/upload', '/crop');
                } else if (uploadUrl.indexOf('?') !== -1) {
                    cropUrl = uploadUrl.replace(/action=.*$/, '') + '?action=crop';
                } else {
                    cropUrl = uploadUrl + (uploadUrl.indexOf('?') === -1 ? '?action=crop' : '&action=crop');
                }
            }

            console.log('Crop URL:', cropUrl);

            $.ajax({
                url: cropUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        var $hiddenInput = $widget.find('.file-upload-input');
                        var $modal = $('#cropperModal-' + widgetId);

                        // Сохраняем полный путь
                        var fileValue = response.filePath || response.fileUrl || response.filename;
                        $hiddenInput.val(fileValue);

                        // Обновляем превью с новым URL
                        var previewUrl = response.fileUrl || (config.previewUrl + '?filename=' + response.filename);
                        updatePreview($widget, previewUrl);

                        $modal.modal('hide');
                    } else {
                        alert(response.message || 'Ошибка при обрезке изображения');
                    }
                    restoreButton();
                },
                error: function (xhr, status, error) {
                    alert('Ошибка соединения с сервером: ' + error);
                    restoreButton();
                }
            });
        }, 'image/jpeg', 0.95); // Качество 95%

        function restoreButton() {
            $cropBtn.html(originalText);
            $cropBtn.prop('disabled', false);
        }
    }

    function updatePreview($widget, url) {
        var $preview = $widget.find('.upload-preview');
        var $hiddenInput = $widget.find('.file-upload-input');
        var $previewContainer = $widget.find('.preview-container');

        // Добавляем временную метку для избежания кеширования
        var previewUrl = url + '?t=' + new Date().getTime();

        if ($preview.length) {
            $preview.attr('src', previewUrl);
        } else if ($previewContainer.length) {
            $previewContainer.html(
                '<img alt="" src="' + previewUrl + '" class="img-thumbnail upload-preview" style="max-width: 200px; max-height: 200px;">'
            );
        } else {
            // Создаем контейнер для превью если его нет
            var $container = $widget.find('.upload-container');
            $container.prepend(
                '<div class="preview-container mb-3">' +
                '<img alt="" src="' + previewUrl + '" class="img-thumbnail upload-preview" style="max-width: 200px; max-height: 200px;">' +
                '</div>'
            );
        }

        // Обновляем кнопку удаления
        var $deleteBtn = $widget.find('.btn-delete');
        var filename = $hiddenInput.val();

        if ($deleteBtn.length) {
            $deleteBtn.data('filename', filename);
        } else {
            // Создаем кнопку удаления если её нет
            var deleteUrl = $widget.find('.file-upload-control').data('url').replace('upload', 'delete');
            $widget.find('.upload-container').append(
                '<div class="mt-2">' +
                '<button type="button" class="btn btn-danger btn-sm btn-delete"' +
                ' data-url="' + deleteUrl + '"' +
                ' data-filename="' + filename + '">' +
                '<i class="bi bi-trash"></i> Удалить</button>' +
                '</div>'
            );

            // Привязываем обработчик к новой кнопке
            $widget.find('.btn-delete').off('click').on('click', function () {
                var $btn = $(this);
                var filename = $btn.data('filename');
                var deleteUrl = $btn.data('url');

                if (confirm('Вы уверены, что хотите удалить файл?')) {
                    $.post(deleteUrl, {
                        filename: filename,
                        _csrf: yii.getCsrfToken()
                    })
                        .done(function (response) {
                            if (response.success) {
                                $hiddenInput.val('');
                                $widget.find('.upload-preview').remove();
                                $btn.closest('.mt-2').remove();
                            } else {
                                alert(response.message || 'Ошибка при удалении файла');
                            }
                        })
                        .fail(function () {
                            alert('Ошибка соединения с сервером');
                        });
                }
            });
        }
    }

    // Проверяем наличие Cropper.js и загружаем если нужно
    function loadCropperLibs(callback) {
        if (typeof Cropper !== 'undefined') {
            if (callback) callback();
            return;
        }

        // Загружаем CSS
        if (!$('link[href*="cropper.min.css"]').length) {
            var cropperCss = document.createElement('link');
            cropperCss.rel = 'stylesheet';
            cropperCss.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css';
            document.head.appendChild(cropperCss);
        }

        // Загружаем JS
        if (!$('script[src*="cropper.min.js"]').length) {
            var cropperScript = document.createElement('script');
            cropperScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js';
            cropperScript.onload = function () {
                console.log('Cropper.js loaded');
                if (callback) callback();
            };
            document.head.appendChild(cropperScript);
        } else if (callback) {
            // Если скрипт уже загружается/загружен, ждем
            var checkInterval = setInterval(function () {
                if (typeof Cropper !== 'undefined') {
                    clearInterval(checkInterval);
                    callback();
                }
            }, 100);
        }
    }

    // Автоматическая загрузка библиотеки при необходимости
    $(document).ready(function () {
        // Проверяем, есть ли на странице виджеты с обрезкой
        if ($('.file-upload-widget [data-enable-cropper="1"]').length > 0) {
            loadCropperLibs();
        }
    });

})(jQuery);