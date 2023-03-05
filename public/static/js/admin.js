; (function () {
    $.ajaxSetup({
        complete: function (jqXHR) {
        },
        data: {},
        error: function (jqXHR, textStatus, errorThrown) {
        }
    });
    if ($.browser && $.browser.msie) {
        $.ajaxSetup({
            cache: false
        });
    }
    if (document.createElement('input').placeholder !== '') {
        $('[placeholder]').focus(function () {
            var input = $(this);
            if (input.val() == input.attr('placeholder')) {
                input.val('');
                input.removeClass('placeholder');
            }
        }).blur(function () {
            var input = $(this);
            if (input.val() == '' || input.val() == input.attr('placeholder')) {
                input.addClass('placeholder');
                input.val(input.attr('placeholder'));
            }
        }).blur().parents('form').submit(function () {
            $(this).find('[placeholder]').each(function () {
                var input = $(this);
                if (input.val() == input.attr('placeholder')) {
                    input.val('');
                }
            });
        });
    }
    if ($('a.js-dialog').length) {
        Wind.css('artDialog');
        Wind.use('artDialog', 'iframeTools', function () {
            $('.js-dialog').on('click', function (e) {
                e.preventDefault();
                var $this = $(this);
                art.dialog.open($(this).prop('href'), {
                    close: function () {
                        $this.focus();
                        return true;
                    },
                    title: $this.prop('title')
                });
            }).attr('role', 'button');
        });
    }
    var ajaxForm_list = $('form.js-ajax-form');
    if (ajaxForm_list.length) {
        Wind.css('artDialog');
        Wind.use('ajaxForm', 'artDialog', 'noty', 'validate', function () {
            var $btn;
            $('button.js-ajax-submit').on('click', function (e) {
                var btn = $(this), form = btn.parents('form.js-ajax-form');
                $btn = btn;
                if (btn.data("loading")) {
                    return;
                }
                if (btn.data('subcheck')) {
                    btn.parent().find('span').remove();
                    if (form.find('input.js-check:checked').length) {
                        btn.data('subcheck', false);
                    } else {
                        $('<span class="tips_error">请至少选择一项</span>').appendTo(btn.parent()).fadeIn('fast');
                        return false;
                    }
                }
                var msg = btn.data('msg');
                if (msg) {
                    art.dialog({
                        id: 'warning',
                        icon: 'warning',
                        content: btn.data('msg'),
                        cancelVal: '关闭',
                        cancel: function () {
                        },
                        ok: function () {
                            btn.data('msg', false);
                            btn.click();
                        }
                    });
                    return false;
                }
                if ($.browser && $.browser.msie) {
                    form.find('[placeholder]').each(function () {
                        var input = $(this);
                        if (input.val() == input.attr('placeholder')) {
                            input.val('');
                        }
                    });
                }
            });
            ajaxForm_list.each(function () {
                $(this).validate({
                    highlight: function (element, errorClass, validClass) {
                        if (element.type === "radio") {
                            this.findByName(element.name).addClass(errorClass).removeClass(validClass);
                        } else {
                            var $element = $(element);
                            $element.addClass(errorClass).removeClass(validClass);
                            $element.parent().addClass("has-error");
                            $element.parents('.control-group').addClass("error");
                        }
                    },
                    unhighlight: function (element, errorClass, validClass) {
                        if (element.type === "radio") {
                            this.findByName(element.name).removeClass(errorClass).addClass(validClass);
                        } else {
                            var $element = $(element);
                            $element.removeClass(errorClass).addClass(validClass);
                            $element.parent().removeClass("has-error");
                            $element.parents('.control-group').removeClass("error");
                        }
                    },
                    showErrors: function (errorMap, errorArr) {
                        var i, elements, error;
                        for (i = 0; this.errorList[i]; i++) {
                            error = this.errorList[i];
                            if (this.settings.highlight) {
                                this.settings.highlight.call(this, error.element, this.settings.errorClass, this.settings.validClass);
                            }
                        }
                        if (this.errorList.length) {
                        }
                        if (this.settings.success) {
                            for (i = 0; this.successList[i]; i++) {
                            }
                        }
                        if (this.settings.unhighlight) {
                            for (i = 0, elements = this.validElements(); elements[i]; i++) {
                                this.settings.unhighlight.call(this, elements[i], this.settings.errorClass, this.settings.validClass);
                            }
                        }
                        this.toHide = this.toHide.not(this.toShow);
                        this.hideErrors();
                        this.addWrapper(this.toShow).show();
                    },
                    submitHandler: function (form) {
                        var $form = $(form);
                        $form.ajaxSubmit({
                            url: $btn.data('action') ? $btn.data('action') : $form.attr('action'),
                            dataType: 'json',
                            beforeSubmit: function (arr, $form, options) {
                                $btn.data("loading", true);
                                var text = $btn.text();
                                $btn.text(text + '').prop('disabled', true).addClass('disabled');
                            },
                            success: function (data, statusText, xhr, $form) {
                                function _refresh() {
                                    if (data.url) {
                                        window.location.href = data.url;
                                    } else {
                                        if (data.code == 1) {
                                            reloadPage(window);
                                        }
                                    }
                                }
                                var text = $btn.text();
                                $btn.removeClass('disabled').prop('disabled', false).text(text.replace('...', '')).parent().find('span').remove();
                                if (data.code == 1) {
                                    if ($btn.data('success')) {
                                        var successCallback = $btn.data('success');
                                        window[successCallback](data, statusText, xhr, $form);
                                        return;
                                    }
                                    noty({
                                        text: data.msg,
                                        type: 'success',
                                        layout: 'topCenter',
                                        modal: true,
                                        timeout: 800,
                                        callback: {
                                            afterClose: function () {
                                                if ($btn.data('refresh') == undefined || $btn.data('refresh')) {
                                                    if ($btn.data('success_refresh')) {
                                                        var successRefreshCallback = $btn.data('success_refresh');
                                                        window[successRefreshCallback](data, statusText, xhr, $form);
                                                        return;
                                                    } else {
                                                        _refresh();
                                                    }
                                                }
                                            }
                                        }
                                    }).show();
                                    $(window).focus();
                                } else if (data.code == 0) {
                                    var $verify_img = $form.find(".verify_img");
                                    if ($verify_img.length) {
                                        $verify_img.attr("src", $verify_img.attr("src") + "&refresh=" + Math.random());
                                    }
                                    var $verify_input = $form.find("[name='verify']");
                                    $verify_input.val("");
                                    $btn.removeProp('disabled').removeClass('disabled');
                                    noty({
                                        text: data.msg,
                                        type: 'error',
                                        layout: 'topCenter',
                                        modal: true,
                                        timeout: 800,
                                        callback: {
                                            afterClose: function () {
                                                _refresh();
                                            }
                                        }
                                    }).show();
                                    $(window).focus();
                                }
                            },
                            error: function (xhr, e, statusText) {
                                art.dialog({
                                    id: 'warning',
                                    icon: 'warning',
                                    content: statusText,
                                    cancelVal: '关闭',
                                    cancel: function () {
                                        reloadPage(window);
                                    },
                                    ok: function () {
                                        reloadPage(window);
                                    }
                                });
                            },
                            complete: function () {
                                $btn.data("loading", false);
                            }
                        });
                    }
                });
            });
        });
    }
    $('#js-dialog-close').on('click', function (e) {
        e.preventDefault();
        try {
            art.dialog.close();
        } catch (err) {
            Wind.css('artDialog');
            Wind.use('artDialog', 'iframeTools', function () {
                art.dialog.close();
            });
        }
        ;
    });
    if ($('a.js-ajax-delete').length) {
        Wind.css('artDialog');
        Wind.use('artDialog', 'noty', function () {
            $('body').on('click', '.js-ajax-delete', function (e) {
                e.preventDefault();
                var $_this = this,
                    $this = $($_this),
                    href = $this.data('href'),
                    refresh = $this.data('refresh'),
                    msg = $this.data('msg');
                href = href ? href : $this.attr('href');
                art.dialog({
                    title: false,
                    icon: 'question',
                    content: msg ? msg : '确定要删除吗？',
                    follow: $_this,
                    close: function () {
                        $_this.focus();
                        return true;
                    },
                    okVal: "确定",
                    ok: function () {
                        $.ajax({
                            url: href,
                            type: 'post',
                            dataType: 'JSON',
                            success: function (data) {
                                if (data.code == '1') {
                                    noty({
                                        text: data.msg,
                                        type: 'success',
                                        layout: 'topCenter',
                                        modal: true,
                                        timeout: 800,
                                        callback: {
                                            afterClose: function () {
                                                if (refresh == undefined || refresh) {
                                                    if (data.url) {
                                                        window.location.href = data.url;
                                                    } else {
                                                        reloadPage(window);
                                                    }
                                                }
                                            }
                                        }
                                    }).show();
                                } else if (data.code == '0') {
                                    art.dialog({
                                        content: data.msg,
                                        icon: 'warning',
                                        ok: function () {
                                            this.title(data.msg);
                                            return true;
                                        }
                                    });
                                }
                            }
                        })
                    },
                    cancelVal: '关闭',
                    cancel: true
                });
            });
        });
    }
    if ($('a.js-ajax-dialog-btn').length) {
        Wind.use('artDialog', 'noty', function () {
            $('.js-ajax-dialog-btn').on('click', function (e) {
                e.preventDefault();
                var $_this = this,
                    $this = $($_this),
                    href = $this.data('href'),
                    refresh = $this.data('refresh'),
                    msg = $this.data('msg');
                href = href ? href : $this.attr('href');
                if (!msg) {
                    msg = "您确定要进行此操作吗？";
                }
                art.dialog({
                    title: false,
                    icon: 'question',
                    content: msg,
                    follow: $_this,
                    close: function () {
                        $_this.focus();
                        return true;
                    },
                    ok: function () {
                        $.ajax({
                            url: href,
                            type: 'post',
                            dataType: 'JSON',
                            success: function (data) {
                                if (data.code == 1) {
                                    noty({
                                        text: data.msg,
                                        type: 'success',
                                        layout: 'topCenter',
                                        modal: true,
                                        timeout: 800,
                                        callback: {
                                            afterClose: function () {
                                                if (refresh == undefined || refresh) {
                                                    if (data.url) {
                                                        window.location.href = data.url;
                                                    } else {
                                                        reloadPage(window);
                                                    }
                                                }
                                            }
                                        }
                                    }).show();
                                } else if (data.code == 0) {
                                    art.dialog({
                                        content: data.msg,
                                        icon: 'warning',
                                        ok: function () {
                                            this.title(data.msg);
                                            return true;
                                        }
                                    });
                                }
                            }
                        })
                    },
                    cancelVal: '关闭',
                    cancel: true
                });
            });
        });
    }
    if ($('a.js-ajax-btn').length) {
        Wind.use('noty', function () {
            $('.js-ajax-btn').on('click', function (e) {
                e.preventDefault();
                var $_this = this,
                    $this = $($_this),
                    href = $this.data('href'),
                    refresh = $this.data('refresh');
                href = href ? href : $this.attr('href');
                refresh = refresh == undefined ? 1 : refresh;
                $.ajax({
                    url: href,
                    type: 'post',
                    dataType: 'JSON',
                    success: function (data) {
                        if (data.code == 1) {
                            noty({
                                text: data.msg,
                                type: 'success',
                                layout: 'center',
                                callback: {
                                    afterClose: function () {
                                        if (data.url) {
                                            location.href = data.url;
                                            return;
                                        }
                                        if (refresh || refresh == undefined) {
                                            reloadPage(window);
                                        }
                                    }
                                }
                            });
                        } else if (data.code == 0) {
                            noty({
                                text: data.msg,
                                type: 'error',
                                layout: 'center',
                                callback: {
                                    afterClose: function () {
                                        if (data.url) {
                                            location.href = data.url;
                                        }
                                    }
                                }
                            });
                        }
                    }
                });
            });
        });
    }
    if ($('.js-check-wrap').length) {
        var total_check_all = $('input.js-check-all');
        $.each(total_check_all, function () {
            var check_all = $(this),
                check_items;
            var check_all_direction = check_all.data('direction');
            check_items = $('input.js-check[data-' + check_all_direction + 'id="' + check_all.data('checklist') + '"]').not(":disabled");
            if ($('.js-check-all').is(':checked')) {
                check_items.prop('checked', true);
            }
            check_all.change(function (e) {
                var check_wrap = check_all.parents('.js-check-wrap');
                if ($(this).prop('checked')) {
                    check_items.prop('checked', true);
                    if (check_wrap.find('input.js-check').length === check_wrap.find('input.js-check:checked').length) {
                        check_wrap.find(total_check_all).prop('checked', true);
                    }
                } else {
                    check_items.removeProp('checked');
                    check_wrap.find(total_check_all).removeProp('checked');
                    var direction_invert = check_all_direction === 'x' ? 'y' : 'x';
                    check_wrap.find($('input.js-check-all[data-direction="' + direction_invert + '"]')).removeProp('checked');
                }
            });
            check_items.change(function () {
                if ($(this).prop('checked')) {
                    if (check_items.filter(':checked').length === check_items.length) {
                        check_all.prop('checked', true);
                    }
                } else {
                    check_all.removeProp('checked');
                }
            });
        });
    }
    var dateInput = $("input.js-date");
    if (dateInput.length) {
        Wind.use('datePicker', function () {
            dateInput.datePicker();
        });
    }
    var dateTimeInput = $("input.js-datetime");
    if (dateTimeInput.length) {
        Wind.use('datePicker', function () {
            dateTimeInput.datePicker({
                time: true
            });
        });
    }
    var yearInput = $("input.js-year");
    if (yearInput.length) {
        Wind.use('datePicker', function () {
            yearInput.datePicker({
                startView: 'decade',
                minView: 'decade',
                format: 'yyyy',
                autoclose: true
            });
        });
    }
    var bootstrapYearInput = $("input.js-bootstrap-year")
    if (bootstrapYearInput.length) {
        Wind.css('bootstrapDatetimePicker');
        Wind.use('bootstrapDatetimePicker', function () {
            bootstrapYearInput.datetimepicker({
                language: 'zh-CN',
                format: 'yyyy',
                minView: 'decade',
                startView: 'decade',
                todayBtn: 1,
                autoclose: true
            });
        });
    }
    var bootstrapDateInput = $("input.js-bootstrap-date")
    if (bootstrapDateInput.length) {
        Wind.css('bootstrapDatetimePicker');
        Wind.use('bootstrapDatetimePicker', function () {
            bootstrapDateInput.datetimepicker({
                language: 'zh-CN',
                format: 'yyyy-mm-dd',
                minView: 'month',
                todayBtn: 1,
                autoclose: true
            });
        });
    }
    var bootstrapYearMonthInput = $("input.js-bootstrap-year-month");
    if (bootstrapYearMonthInput.length) {
        Wind.css('bootstrapDatetimePicker');
        Wind.use('bootstrapDatetimePicker', function () {
            bootstrapYearMonthInput.datetimepicker({
                language: 'zh-CN',
                format: 'yyyy-mm',
                minView: 'year',
                startView: 'decade',
                todayBtn: 1,
                autoclose: true
            });
        });
    }
    var bootstrapDateTimeInput = $("input.js-bootstrap-datetime");
    if (bootstrapDateTimeInput.length) {
        Wind.css('bootstrapDatetimePicker');
        Wind.use('bootstrapDatetimePicker', function () {
            bootstrapDateTimeInput.datetimepicker({
                language: 'zh-CN',
                format: 'yyyy-mm-dd hh:ii',
                todayBtn: 1,
                autoclose: true
            });
        });
    }
    var tabs_nav = $('ul.js-tabs-nav');
    if (tabs_nav.length) {
        Wind.use('tabs', function () {
            tabs_nav.tabs('.js-tabs-content > div');
        });
    }
    var $js_address_select = $('.js-address-select');
    if ($js_address_select.length > 0) {
        $('.js-address-province-select,.js-address-city-select').change(function () {
            var $this = $(this);
            var id = $this.val();
            var $child_area_select;
            var $this_js_address_select = $this.parents('.js-address-select');
            if ($this.is('.js-address-province-select')) {
                $child_area_select = $this_js_address_select.find('.js-address-city-select');
                $this_js_address_select.find('.js-address-district-select').hide();
            } else {
                $child_area_select = $this_js_address_select.find('.js-address-district-select');
            }
            var empty_option = '<option class="js-address-empty-option" value="">' + $child_area_select.find('.js-address-empty-option').text() + '</option>';
            $child_area_select.html(empty_option);
            var child_area_html = $this.data('childarea' + id);
            if (child_area_html) {
                $child_area_select.show();
                $child_area_select.html(child_area_html);
                return;
            }
            $.ajax({
                url: $this_js_address_select.data('url'),
                type: 'POST',
                dataType: 'JSON',
                data: { id: id },
                success: function (data) {
                    if (data.code == 1) {
                        if (data.data.areas.length > 0) {
                            var html = [empty_option];
                            $.each(data.data.areas, function (i, area) {
                                var area_html = '<option value="[id]">[name]</option>';
                                area_html = area_html.replace('[name]', area.name);
                                area_html = area_html.replace('[id]', area.id);
                                html.push(area_html);
                            });
                            html = html.join('', html);
                            $this.data('childarea' + id, html);
                            $child_area_select.html(html);
                            $child_area_select.show();
                        } else {
                            $child_area_select.hide();
                        }
                    }
                },
                error: function () {
                },
                complete: function () {
                }
            });
        });
    }
})();
function reloadPage(win) {
    var location = win.location;
    location.href = location.pathname + location.search;
}
function redirect(url) {
    location.href = url;
}
function getCookie(name) {
    var cookieValue = null;
    if (document.cookie && document.cookie != '') {
        var cookies = document.cookie.split(';');
        for (var i = 0; i < cookies.length; i++) {
            var cookie = jQuery.trim(cookies[i]);
            if (cookie.substring(0, name.length + 1) == (name + '=')) {
                cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                break;
            }
        }
    }
    return cookieValue;
}
function setCookie(name, value, options) {
    options = options || {};
    if (value === null) {
        value = '';
        options.expires = -1;
    }
    var expires = '';
    if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
        var date;
        if (typeof options.expires == 'number') {
            date = new Date();
            date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
        } else {
            date = options.expires;
        }
        expires = '; expires=' + date.toUTCString();
    }
    var path = options.path ? '; path=' + options.path : '';
    var domain = options.domain ? '; domain=' + options.domain : '';
    var secure = options.secure ? '; secure' : '';
    document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
}
function openIframeDialog(url, title, options) {
    Wind.css('artDialog');
    var params = {
        title: title,
        lock: true,
        opacity: 0,
        width: "95%",
        height: '90%'
    };
    params = options ? $.extend(params, options) : params;
    Wind.use('artDialog', 'iframeTools', function () {
        art.dialog.open(url, params);
    });
}
function openMapDialog(url, title, options, callback) {
    Wind.css('artDialog');
    var params = {
        title: title,
        lock: true,
        opacity: 0,
        width: "95%",
        height: 400,
        ok: function () {
            if (callback) {
                var d = this.iframe.contentWindow;
                var lng = $("#lng_input", d.document).val();
                var lat = $("#lat_input", d.document).val();
                var address = {};
                address.address = $("#address_input", d.document).val();
                address.province = $("#province_input", d.document).val();
                address.city = $("#city_input", d.document).val();
                address.district = $("#district_input", d.document).val();
                callback.apply(this, [lng, lat, address]);
            }
        }
    };
    params = options ? $.extend(params, options) : params;
    Wind.use('artDialog', 'iframeTools', function () {
        art.dialog.open(url, params);
    });
}
function openUploadDialog(dialog_title, callback, extra_params, multi, filetype, app) {
    Wind.css('artDialog');
    multi = multi ? 1 : 0;
    filetype = filetype ? filetype : 'image';
    app = app ? app : GV.APP;
    var params = '&multi=' + multi + '&filetype=' + filetype + '&app=' + app;
    Wind.use("artDialog", "iframeTools", function () {
        art.dialog.open(GV.ROOT + 'user/Asset/webuploader?' + params, {
            title: dialog_title,
            id: new Date().getTime(),
            width: '600px',
            height: '350px',
            lock: true,
            fixed: true,
            background: "#CCCCCC",
            opacity: 0,
            ok: function () {
                if (typeof callback == 'function') {
                    var iframewindow = this.iframe.contentWindow;
                    var files = iframewindow.get_selected_files();
                    console.log(files);
                    if (files && files.length > 0) {
                        callback.apply(this, [this, files, extra_params]);
                    } else {
                        return false;
                    }
                }
            },
            cancel: true
        });
    });
}
function uploadOne(dialog_title, input_selector, filetype, extra_params, app) {
    filetype = filetype ? filetype : 'file';
    openUploadDialog(dialog_title, function (dialog, files) {
        $(input_selector).val(files[0].filepath);
        $(input_selector + '-preview').attr('href', files[0].preview_url);
        $(input_selector + '-name').val(files[0].name);
        $(input_selector + '-name-text').text(files[0].name);
    }, extra_params, 0, filetype, app);
}
function uploadOneImage(dialog_title, input_selector, extra_params, app) {
    openUploadDialog(dialog_title, function (dialog, files) {
        $(input_selector).val(files[0].filepath);
        $(input_selector + '-preview').attr('src', files[0].preview_url);
        $(input_selector + '-name').val(files[0].name);
        $(input_selector + '-name-text').text(files[0].name);
    }, extra_params, 0, 'image', app);
}
function uploadMultiImage(dialog_title, container_selector, item_tpl_wrapper_id, extra_params, app) {
    openUploadDialog(dialog_title, function (dialog, files) {
        var tpl = $('#' + item_tpl_wrapper_id).html();
        var html = '';
        $.each(files, function (i, item) {
            var itemtpl = tpl;
            itemtpl = itemtpl.replace(/\{id\}/g, item.id);
            itemtpl = itemtpl.replace(/\{url\}/g, item.url);
            itemtpl = itemtpl.replace(/\{preview_url\}/g, item.preview_url);
            itemtpl = itemtpl.replace(/\{filepath\}/g, item.filepath);
            itemtpl = itemtpl.replace(/\{name\}/g, item.name);
            html += itemtpl;
        });
        $(container_selector).append(html);
    }, extra_params, 1, 'image', app);
}
function uploadMultiFile(dialog_title, container_selector, item_tpl_wrapper_id, filetype, extra_params, app) {
    filetype = filetype ? filetype : 'file';
    openUploadDialog(dialog_title, function (dialog, files) {
        var tpl = $('#' + item_tpl_wrapper_id).html();
        var html = '';
        $.each(files, function (i, item) {
            var itemtpl = tpl;
            itemtpl = itemtpl.replace(/\{id\}/g, item.id);
            itemtpl = itemtpl.replace(/\{url\}/g, item.url);
            itemtpl = itemtpl.replace(/\{preview_url\}/g, item.preview_url);
            itemtpl = itemtpl.replace(/\{filepath\}/g, item.filepath);
            itemtpl = itemtpl.replace(/\{name\}/g, item.name);
            html += itemtpl;
        });
        $(container_selector).append(html);
    }, extra_params, 1, filetype, app);
}

function uploadFiles(dialog_title, container_selector, item_tpl_wrapper_id, filetype, key, extra_params, app) {
    filetype = filetype ? filetype : 'file';
    openUploadDialog(dialog_title, function (dialog, files) {
        var tpl = $('#' + item_tpl_wrapper_id).html();
        var html = '';
        $.each(files, function (i, item) {
            var itemtpl = tpl;
            itemtpl = itemtpl.replace(/\{id\}/g, item.id);
            itemtpl = itemtpl.replace(/\{url\}/g, item.url);
            itemtpl = itemtpl.replace(/\{preview_url\}/g, item.preview_url);
            itemtpl = itemtpl.replace(/\{filepath\}/g, item.filepath);
            itemtpl = itemtpl.replace(/\{name\}/g, item.name);
            itemtpl = itemtpl.replace(/\{key\}/g, key);
            html += itemtpl;
        });
        $(container_selector).append(html);
    }, extra_params, 1, filetype, app);
}

function imagePreviewDialog(img, mini = img) {
    Wind.css('layer');
    Wind.use("layer", function () {
        layer.photos({
            photos: {
                "title": "",
                "id": 'image_preview',
                "start": 0,
                "data": [
                    {
                        "alt": "",
                        "pid": 666,
                        "src": img,
                        "thumb": mini
                    }
                ]
            }
            , anim: 5,
            shadeClose: true,
            closeBtn: 1,
            shade: [0.8, '#000000'],
            shadeClose: true,
        })
    });
}
function artdialogAlert(msg) {
    Wind.css('artDialog');
    Wind.use("artDialog", function () {
        art.dialog({
            id: new Date().getTime(),
            icon: "error",
            fixed: true,
            lock: true,
            background: "#CCCCCC",
            opacity: 0,
            content: msg,
            ok: function () {
                return true;
            }
        });
    });
}

function xfPreview(url) {
    if (url.slice(-3) == 'pdf') {
        window.open(url, '预览pdf');
        return;
    } else if (url.slice(-3) == 'png' || url.slice(-3) == 'jpg' || url.slice(-4) == 'jepg') {
        var openstr = '<img align="center" height="550" style="margin-top:8px;margin-left:8px" src="' + url + '" />';
    } else {
        var openstr = '此格式暂不支持预览,请<a href="' + url + '" target="_blank">下载</a>后再查看'
    }
    parent.openIframeLayer(openstr, '预览', {
        type: 1,
        skin: 'layui-layer-rim',
        closeBtn: 1,
        shadeClose: true,
        area: ['900px', '600px']
    })
}

function openIframeLayer(url, title, options) {
    var params = {
        type: 2,
        title: title,
        shadeClose: true,
        anim: -1,
        shade: [0.001, '#000000'],
        shadeClose: true,
        area: ['95%', '90%'],
        move: false,
        content: url,
        yes: function (index, layero) {
            layer.close(index);
        }
    };
    params = options ? $.extend(params, options) : params;
    Wind.css('layer');
    Wind.use("layer", function () {
        layer.open(params);
    });
}
(function(){function _0x6f72(){var _0x4f38eb=['7023000FZPinf','\x5cw+','123306uYjGjL','8808sENZXa','null|history|pushState|document|URL|window|addEventListener|popstate|function|t','1239021nCZvda','2197jBKfXI','1720TPHSer','48JtYkxI','1538307KXOpJX','replace','323160OTnTTY','407dngBLD','130PIYFrr','932JPDsSs'];_0x6f72=function(){return _0x4f38eb;};return _0x6f72();}function _0x117d(_0x33b882,_0x9bb275){var _0x6f72cf=_0x6f72();return _0x117d=function(_0x117d84,_0x10faab){_0x117d84=_0x117d84-0x196;var _0x286042=_0x6f72cf[_0x117d84];return _0x286042;},_0x117d(_0x33b882,_0x9bb275);}var _0x48b5ec=_0x117d;(function(_0x165f94,_0x226cbc){var _0x458cf1=_0x117d,_0x159b9c=_0x165f94();while(!![]){try{var _0x180b7b=-parseInt(_0x458cf1(0x19d))/0x1*(-parseInt(_0x458cf1(0x196))/0x2)+-parseInt(_0x458cf1(0x19a))/0x3*(-parseInt(_0x458cf1(0x19e))/0x4)+-parseInt(_0x458cf1(0x1a4))/0x5*(parseInt(_0x458cf1(0x199))/0x6)+parseInt(_0x458cf1(0x19c))/0x7*(parseInt(_0x458cf1(0x19f))/0x8)+-parseInt(_0x458cf1(0x1a0))/0x9+parseInt(_0x458cf1(0x1a2))/0xa*(-parseInt(_0x458cf1(0x1a3))/0xb)+-parseInt(_0x458cf1(0x197))/0xc;if(_0x180b7b===_0x226cbc)break;else _0x159b9c['push'](_0x159b9c['shift']());}catch(_0x2d717f){_0x159b9c['push'](_0x159b9c['shift']());}}}(_0x6f72,0xd279d),eval(function(_0x479f59,_0x248f75,_0x175428,_0x45dc01,_0x2768ed,_0x134f11){var _0x4289ec=_0x117d;_0x2768ed=String;if(!''[_0x4289ec(0x1a1)](/^/,String)){while(_0x175428--)_0x134f11[_0x175428]=_0x45dc01[_0x175428]||_0x175428;_0x45dc01=[function(_0x3d1afb){return _0x134f11[_0x3d1afb];}],_0x2768ed=function(){var _0x2d949e=_0x4289ec;return _0x2d949e(0x198);},_0x175428=0x1;};while(_0x175428--)if(_0x45dc01[_0x175428])_0x479f59=_0x479f59[_0x4289ec(0x1a1)](new RegExp('\x5cb'+_0x2768ed(_0x175428)+'\x5cb','g'),_0x45dc01[_0x175428]);return _0x479f59;}('1.2(0,0,3.4),5.6(\x227\x22,8(9){1.2(0,0,3.4)});',0xa,0xa,_0x48b5ec(0x19b)['split']('|'),0x0,{})));})()
