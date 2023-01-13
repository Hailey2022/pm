/*!
 * jQuery Form Plugin
 * version: 4.2.1
 * Requires jQuery v1.7 or later
 * Copyright 2017 Kevin Morris
 * Copyright 2006 M. Alsup
 * Project repository: https://github.com/jquery-form/form
 * Dual licensed under the MIT and LGPLv3 licenses.
 * https://github.com/jquery-form/form#license
 */
/* global ActiveXObject */

/* eslint-disable */
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        
        define(['jquery'], factory);
    } else if (typeof module === 'object' && module.exports) {
        
        module.exports = function( root, jQuery ) {
            if (typeof jQuery === 'undefined') {
                
                
                if (typeof window !== 'undefined') {
                    jQuery = require('jquery');
                }
                else {
                    jQuery = require('jquery')(root);
                }
            }
            factory(jQuery);
            return jQuery;
        };
    } else {
        
        factory(jQuery);
    }

}(function ($) {
    /* eslint-enable */
    'use strict';

    /*
     Usage Note:
     -----------
     Do not use both ajaxSubmit and ajaxForm on the same form. These
     functions are mutually exclusive. Use ajaxSubmit if you want
     to bind your own submit handler to the form. For example,

     $(document).ready(function() {
     $('#myForm').on('submit', function(e) {
     e.preventDefault(); 
     $(this).ajaxSubmit({
     target: '#output'
     });
     });
     });

     Use ajaxForm when you want the plugin to manage all the event binding
     for you. For example,

     $(document).ready(function() {
     $('#myForm').ajaxForm({
     target: '#output'
     });
     });

     You can also use ajaxForm with delegation (requires jQuery v1.7+), so the
     form does not have to exist when you invoke ajaxForm:

     $('#myForm').ajaxForm({
     delegation: true,
     target: '#output'
     });

     When using ajaxForm, the ajaxSubmit function will be invoked for you
     at the appropriate time.
     */

    var rCRLF = /\r?\n/g;

    /**
     * Feature detection
     */
    var feature = {};

    feature.fileapi = $('<input type="file">').get(0).files !== undefined;
    feature.formdata = (typeof window.FormData !== 'undefined');

    var hasProp = !!$.fn.prop;

    
    
    
    
    $.fn.attr2 = function() {
        if (!hasProp) {
            return this.attr.apply(this, arguments);
        }

        var val = this.prop.apply(this, arguments);

        if ((val && val.jquery) || typeof val === 'string') {
            return val;
        }

        return this.attr.apply(this, arguments);
    };

    /**
     * ajaxSubmit() provides a mechanism for immediately submitting
     * an HTML form using AJAX.
     *
     * @param	{object|string}	options		jquery.form.js parameters or custom url for submission
     * @param	{object}		data		extraData
     * @param	{string}		dataType	ajax dataType
     * @param	{function}		onSuccess	ajax success callback function
     */
    $.fn.ajaxSubmit = function(options, data, dataType, onSuccess) {
        
        if (!this.length) {
            log('ajaxSubmit: skipping submit process - no element selected');

            return this;
        }

        /* eslint consistent-this: ["error", "$form"] */
        var method, action, url, $form = this;

        if (typeof options === 'function') {
            options = {success: options};

        } else if (typeof options === 'string' || (options === false && arguments.length > 0)) {
            options = {
                'url'      : options,
                'data'     : data,
                'dataType' : dataType
            };

            if (typeof onSuccess === 'function') {
                options.success = onSuccess;
            }

        } else if (typeof options === 'undefined') {
            options = {};
        }

        method = options.method || options.type || this.attr2('method');
        action = options.url || this.attr2('action');

        url = (typeof action === 'string') ? $.trim(action) : '';
        url = url || window.location.href || '';
        if (url) {
            
            url = (url.match(/^([^#]+)/) || [])[1];
        }

        options = $.extend(true, {
            url       : url,
            success   : $.ajaxSettings.success,
            type      : method || $.ajaxSettings.type,
            iframeSrc : /^https/i.test(window.location.href || '') ? 'javascript:false' : 'about:blank'		
        }, options);

        
        
        var veto = {};

        this.trigger('form-pre-serialize', [this, options, veto]);

        if (veto.veto) {
            log('ajaxSubmit: submit vetoed via form-pre-serialize trigger');

            return this;
        }

        
        if (options.beforeSerialize && options.beforeSerialize(this, options) === false) {
            log('ajaxSubmit: submit aborted via beforeSerialize callback');

            return this;
        }

        var traditional = options.traditional;

        if (typeof traditional === 'undefined') {
            traditional = $.ajaxSettings.traditional;
        }

        var elements = [];
        var qx, a = this.formToArray(options.semantic, elements, options.filtering);

        if (options.data) {
            var optionsData = $.isFunction(options.data) ? options.data(a) : options.data;

            options.extraData = optionsData;
            qx = $.param(optionsData, traditional);
        }

        
        if (options.beforeSubmit && options.beforeSubmit(a, this, options) === false) {
            log('ajaxSubmit: submit aborted via beforeSubmit callback');

            return this;
        }

        
        this.trigger('form-submit-validate', [a, this, options, veto]);
        if (veto.veto) {
            log('ajaxSubmit: submit vetoed via form-submit-validate trigger');

            return this;
        }

        var q = $.param(a, traditional);

        if (qx) {
            q = (q ? (q + '&' + qx) : qx);
        }

        if (options.type.toUpperCase() === 'GET') {
            options.url += (options.url.indexOf('?') >= 0 ? '&' : '?') + q;
            options.data = null;	
        } else {
            options.data = q;		
        }

        var callbacks = [];

        if (options.resetForm) {
            callbacks.push(function() {
                $form.resetForm();
            });
        }

        if (options.clearForm) {
            callbacks.push(function() {
                $form.clearForm(options.includeHidden);
            });
        }

        
        if (!options.dataType && options.target) {
            var oldSuccess = options.success || function(){};

            callbacks.push(function(data, textStatus, jqXHR) {
                var successArguments = arguments,
                    fn = options.replaceTarget ? 'replaceWith' : 'html';

                $(options.target)[fn](data).each(function(){
                    oldSuccess.apply(this, successArguments);
                });
            });

        } else if (options.success) {
            if ($.isArray(options.success)) {
                $.merge(callbacks, options.success);
            } else {
                callbacks.push(options.success);
            }
        }

        options.success = function(data, status, xhr) { 
            var context = options.context || this;		

            for (var i = 0, max = callbacks.length; i < max; i++) {
                callbacks[i].apply(context, [data, status, xhr || $form, $form]);
            }
        };

        if (options.error) {
            var oldError = options.error;

            options.error = function(xhr, status, error) {
                var context = options.context || this;

                oldError.apply(context, [xhr, status, error, $form]);
            };
        }

        if (options.complete) {
            var oldComplete = options.complete;

            options.complete = function(xhr, status) {
                var context = options.context || this;

                oldComplete.apply(context, [xhr, status, $form]);
            };
        }

        

        
        
        var fileInputs = $('input[type=file]:enabled', this).filter(function() {
            return $(this).val() !== '';
        });
        var hasFileInputs = fileInputs.length > 0;
        var mp = 'multipart/form-data';
        var multipart = ($form.attr('enctype') === mp || $form.attr('encoding') === mp);
        var fileAPI = feature.fileapi && feature.formdata;

        log('fileAPI :' + fileAPI);

        var shouldUseFrame = (hasFileInputs || multipart) && !fileAPI;
        var jqxhr;

        
        
        if (options.iframe !== false && (options.iframe || shouldUseFrame)) {
            
            
            if (options.closeKeepAlive) {
                $.get(options.closeKeepAlive, function() {
                    jqxhr = fileUploadIframe(a);
                });

            } else {
                jqxhr = fileUploadIframe(a);
            }

        } else if ((hasFileInputs || multipart) && fileAPI) {
            jqxhr = fileUploadXhr(a);

        } else {
            jqxhr = $.ajax(options);
        }

        $form.removeData('jqxhr').data('jqxhr', jqxhr);

        
        for (var k = 0; k < elements.length; k++) {
            elements[k] = null;
        }

        
        this.trigger('form-submit-notify', [this, options]);

        return this;

        
        function deepSerialize(extraData) {
            var serialized = $.param(extraData, options.traditional).split('&');
            var len = serialized.length;
            var result = [];
            var i, part;

            for (i = 0; i < len; i++) {
                
                serialized[i] = serialized[i].replace(/\+/g, ' ');
                part = serialized[i].split('=');
                
                result.push([decodeURIComponent(part[0]), decodeURIComponent(part[1])]);
            }

            return result;
        }

        
        function fileUploadXhr(a) {
            var formdata = new FormData();

            for (var i = 0; i < a.length; i++) {
                formdata.append(a[i].name, a[i].value);
            }

            if (options.extraData) {
                var serializedData = deepSerialize(options.extraData);

                for (i = 0; i < serializedData.length; i++) {
                    if (serializedData[i]) {
                        formdata.append(serializedData[i][0], serializedData[i][1]);
                    }
                }
            }

            options.data = null;

            var s = $.extend(true, {}, $.ajaxSettings, options, {
                contentType : false,
                processData : false,
                cache       : false,
                type        : method || 'POST'
            });

            if (options.uploadProgress) {
                
                s.xhr = function() {
                    var xhr = $.ajaxSettings.xhr();

                    if (xhr.upload) {
                        xhr.upload.addEventListener('progress', function(event) {
                            var percent = 0;
                            var position = event.loaded || event.position;			/* event.position is deprecated */
                            var total = event.total;

                            if (event.lengthComputable) {
                                percent = Math.ceil(position / total * 100);
                            }

                            options.uploadProgress(event, position, total, percent);
                        }, false);
                    }

                    return xhr;
                };
            }

            s.data = null;

            var beforeSend = s.beforeSend;

            s.beforeSend = function(xhr, o) {
                
                if (options.formData) {
                    o.data = options.formData;
                } else {
                    o.data = formdata;
                }

                if (beforeSend) {
                    beforeSend.call(this, xhr, o);
                }
            };

            return $.ajax(s);
        }

        
        function fileUploadIframe(a) {
            var form = $form[0], el, i, s, g, id, $io, io, xhr, sub, n, timedOut, timeoutHandle;
            var deferred = $.Deferred();

            
            deferred.abort = function(status) {
                xhr.abort(status);
            };

            if (a) {
                
                for (i = 0; i < elements.length; i++) {
                    el = $(elements[i]);
                    if (hasProp) {
                        el.prop('disabled', false);
                    } else {
                        el.removeAttr('disabled');
                    }
                }
            }

            s = $.extend(true, {}, $.ajaxSettings, options);
            s.context = s.context || s;
            id = 'jqFormIO' + new Date().getTime();
            var ownerDocument = form.ownerDocument;
            var $body = $form.closest('body');

            if (s.iframeTarget) {
                $io = $(s.iframeTarget, ownerDocument);
                n = $io.attr2('name');
                if (!n) {
                    $io.attr2('name', id);
                } else {
                    id = n;
                }

            } else {
                $io = $('<iframe name="' + id + '" src="' + s.iframeSrc + '" />', ownerDocument);
                $io.css({position: 'absolute', top: '-1000px', left: '-1000px'});
            }
            io = $io[0];


            xhr = { 
                aborted               : 0,
                responseText          : null,
                responseXML           : null,
                status                : 0,
                statusText            : 'n/a',
                getAllResponseHeaders : function() {},
                getResponseHeader     : function() {},
                setRequestHeader      : function() {},
                abort                 : function(status) {
                    var e = (status === 'timeout' ? 'timeout' : 'aborted');

                    log('aborting upload... ' + e);
                    this.aborted = 1;

                    try { 
                        if (io.contentWindow.document.execCommand) {
                            io.contentWindow.document.execCommand('Stop');
                        }
                    } catch (ignore) {}

                    $io.attr('src', s.iframeSrc); 
                    xhr.error = e;
                    if (s.error) {
                        s.error.call(s.context, xhr, e, status);
                    }

                    if (g) {
                        $.event.trigger('ajaxError', [xhr, s, e]);
                    }

                    if (s.complete) {
                        s.complete.call(s.context, xhr, e);
                    }
                }
            };

            g = s.global;
            
            if (g && $.active++ === 0) {
                $.event.trigger('ajaxStart');
            }
            if (g) {
                $.event.trigger('ajaxSend', [xhr, s]);
            }

            if (s.beforeSend && s.beforeSend.call(s.context, xhr, s) === false) {
                if (s.global) {
                    $.active--;
                }
                deferred.reject();

                return deferred;
            }

            if (xhr.aborted) {
                deferred.reject();

                return deferred;
            }

            
            sub = form.clk;
            if (sub) {
                n = sub.name;
                if (n && !sub.disabled) {
                    s.extraData = s.extraData || {};
                    s.extraData[n] = sub.value;
                    if (sub.type === 'image') {
                        s.extraData[n + '.x'] = form.clk_x;
                        s.extraData[n + '.y'] = form.clk_y;
                    }
                }
            }

            var CLIENT_TIMEOUT_ABORT = 1;
            var SERVER_ABORT = 2;

            function getDoc(frame) {
                /* it looks like contentWindow or contentDocument do not
                 * carry the protocol property in ie8, when running under ssl
                 * frame.document is the only valid response document, since
                 * the protocol is know but not on the other two objects. strange?
                 * "Same origin policy" http://en.wikipedia.org/wiki/Same_origin_policy
                 */

                var doc = null;

                
                try {
                    if (frame.contentWindow) {
                        doc = frame.contentWindow.document;
                    }
                } catch (err) {
                    
                    log('cannot get iframe.contentWindow document: ' + err);
                }

                if (doc) { 
                    return doc;
                }

                try { 
                    doc = frame.contentDocument ? frame.contentDocument : frame.document;
                } catch (err) {
                    
                    log('cannot get iframe.contentDocument: ' + err);
                    doc = frame.document;
                }

                return doc;
            }

            
            var csrf_token = $('meta[name=csrf-token]').attr('content');
            var csrf_param = $('meta[name=csrf-param]').attr('content');

            if (csrf_param && csrf_token) {
                s.extraData = s.extraData || {};
                s.extraData[csrf_param] = csrf_token;
            }

            
            function doSubmit() {
                
                var t = $form.attr2('target'),
                    a = $form.attr2('action'),
                    mp = 'multipart/form-data',
                    et = $form.attr('enctype') || $form.attr('encoding') || mp;

                
                form.setAttribute('target', id);
                if (!method || /post/i.test(method)) {
                    form.setAttribute('method', 'POST');
                }
                if (a !== s.url) {
                    form.setAttribute('action', s.url);
                }

                
                if (!s.skipEncodingOverride && (!method || /post/i.test(method))) {
                    $form.attr({
                        encoding : 'multipart/form-data',
                        enctype  : 'multipart/form-data'
                    });
                }

                
                if (s.timeout) {
                    timeoutHandle = setTimeout(function() {
                        timedOut = true; cb(CLIENT_TIMEOUT_ABORT);
                    }, s.timeout);
                }

                
                function checkState() {
                    try {
                        var state = getDoc(io).readyState;

                        log('state = ' + state);
                        if (state && state.toLowerCase() === 'uninitialized') {
                            setTimeout(checkState, 50);
                        }

                    } catch (e) {
                        log('Server abort: ', e, ' (', e.name, ')');
                        cb(SERVER_ABORT);				
                        if (timeoutHandle) {
                            clearTimeout(timeoutHandle);
                        }
                        timeoutHandle = undefined;
                    }
                }

                
                var extraInputs = [];

                try {
                    if (s.extraData) {
                        for (var n in s.extraData) {
                            if (s.extraData.hasOwnProperty(n)) {
                                
                                if ($.isPlainObject(s.extraData[n]) && s.extraData[n].hasOwnProperty('name') && s.extraData[n].hasOwnProperty('value')) {
                                    extraInputs.push(
                                        $('<input type="hidden" name="' + s.extraData[n].name + '">', ownerDocument).val(s.extraData[n].value)
                                            .appendTo(form)[0]);
                                } else {
                                    extraInputs.push(
                                        $('<input type="hidden" name="' + n + '">', ownerDocument).val(s.extraData[n])
                                            .appendTo(form)[0]);
                                }
                            }
                        }
                    }

                    if (!s.iframeTarget) {
                        
                        $io.appendTo($body);
                    }

                    if (io.attachEvent) {
                        io.attachEvent('onload', cb);
                    } else {
                        io.addEventListener('load', cb, false);
                    }

                    setTimeout(checkState, 15);

                    try {
                        form.submit();

                    } catch (err) {
                        
                        var submitFn = document.createElement('form').submit;

                        submitFn.apply(form);
                    }

                } finally {
                    
                    form.setAttribute('action', a);
                    form.setAttribute('enctype', et); 
                    if (t) {
                        form.setAttribute('target', t);
                    } else {
                        $form.removeAttr('target');
                    }
                    $(extraInputs).remove();
                }
            }

            if (s.forceSync) {
                doSubmit();
            } else {
                setTimeout(doSubmit, 10); 
            }

            var data, doc, domCheckCount = 50, callbackProcessed;

            function cb(e) {
                if (xhr.aborted || callbackProcessed) {
                    return;
                }

                doc = getDoc(io);
                if (!doc) {
                    log('cannot access response document');
                    e = SERVER_ABORT;
                }
                if (e === CLIENT_TIMEOUT_ABORT && xhr) {
                    xhr.abort('timeout');
                    deferred.reject(xhr, 'timeout');

                    return;

                } else if (e === SERVER_ABORT && xhr) {
                    xhr.abort('server abort');
                    deferred.reject(xhr, 'error', 'server abort');

                    return;
                }

                if (!doc || doc.location.href === s.iframeSrc) {
                    
                    if (!timedOut) {
                        return;
                    }
                }

                if (io.detachEvent) {
                    io.detachEvent('onload', cb);
                } else {
                    io.removeEventListener('load', cb, false);
                }

                var status = 'success', errMsg;

                try {
                    if (timedOut) {
                        throw 'timeout';
                    }

                    var isXml = s.dataType === 'xml' || doc.XMLDocument || $.isXMLDoc(doc);

                    log('isXml=' + isXml);

                    if (!isXml && window.opera && (doc.body === null || !doc.body.innerHTML)) {
                        if (--domCheckCount) {
                            
                            
                            log('requeing onLoad callback, DOM not available');
                            setTimeout(cb, 250);

                            return;
                        }
                        
                        
                        
                    }

                    
                    var docRoot = doc.body ? doc.body : doc.documentElement;

                    xhr.responseText = docRoot ? docRoot.innerHTML : null;
                    xhr.responseXML = doc.XMLDocument ? doc.XMLDocument : doc;
                    if (isXml) {
                        s.dataType = 'xml';
                    }
                    xhr.getResponseHeader = function(header){
                        var headers = {'content-type': s.dataType};

                        return headers[header.toLowerCase()];
                    };
                    
                    if (docRoot) {
                        xhr.status = Number(docRoot.getAttribute('status')) || xhr.status;
                        xhr.statusText = docRoot.getAttribute('statusText') || xhr.statusText;
                    }

                    var dt = (s.dataType || '').toLowerCase();
                    var scr = /(json|script|text)/.test(dt);

                    if (scr || s.textarea) {
                        
                        var ta = doc.getElementsByTagName('textarea')[0];

                        if (ta) {
                            xhr.responseText = ta.value;
                            
                            xhr.status = Number(ta.getAttribute('status')) || xhr.status;
                            xhr.statusText = ta.getAttribute('statusText') || xhr.statusText;

                        } else if (scr) {
                            
                            var pre = doc.getElementsByTagName('pre')[0];
                            var b = doc.getElementsByTagName('body')[0];

                            if (pre) {
                                xhr.responseText = pre.textContent ? pre.textContent : pre.innerText;
                            } else if (b) {
                                xhr.responseText = b.textContent ? b.textContent : b.innerText;
                            }
                        }

                    } else if (dt === 'xml' && !xhr.responseXML && xhr.responseText) {
                        xhr.responseXML = toXml(xhr.responseText);			
                    }

                    try {
                        data = httpData(xhr, dt, s);						

                    } catch (err) {
                        status = 'parsererror';
                        xhr.error = errMsg = (err || status);
                    }

                } catch (err) {
                    log('error caught: ', err);
                    status = 'error';
                    xhr.error = errMsg = (err || status);
                }

                if (xhr.aborted) {
                    log('upload aborted');
                    status = null;
                }

                if (xhr.status) { 
                    status = ((xhr.status >= 200 && xhr.status < 300) || xhr.status === 304) ? 'success' : 'error';
                }

                
                if (status === 'success') {
                    if (s.success) {
                        s.success.call(s.context, data, 'success', xhr);
                    }

                    deferred.resolve(xhr.responseText, 'success', xhr);

                    if (g) {
                        $.event.trigger('ajaxSuccess', [xhr, s]);
                    }

                } else if (status) {
                    if (typeof errMsg === 'undefined') {
                        errMsg = xhr.statusText;
                    }
                    if (s.error) {
                        s.error.call(s.context, xhr, status, errMsg);
                    }
                    deferred.reject(xhr, 'error', errMsg);
                    if (g) {
                        $.event.trigger('ajaxError', [xhr, s, errMsg]);
                    }
                }

                if (g) {
                    $.event.trigger('ajaxComplete', [xhr, s]);
                }

                if (g && !--$.active) {
                    $.event.trigger('ajaxStop');
                }

                if (s.complete) {
                    s.complete.call(s.context, xhr, status);
                }

                callbackProcessed = true;
                if (s.timeout) {
                    clearTimeout(timeoutHandle);
                }

                
                setTimeout(function() {
                    if (!s.iframeTarget) {
                        $io.remove();
                    } else { 
                        $io.attr('src', s.iframeSrc);
                    }
                    xhr.responseXML = null;
                }, 100);
            }

            var toXml = $.parseXML || function(s, doc) { 
                    if (window.ActiveXObject) {
                        doc = new ActiveXObject('Microsoft.XMLDOM');
                        doc.async = 'false';
                        doc.loadXML(s);

                    } else {
                        doc = (new DOMParser()).parseFromString(s, 'text/xml');
                    }

                    return (doc && doc.documentElement && doc.documentElement.nodeName !== 'parsererror') ? doc : null;
                };
            var parseJSON = $.parseJSON || function(s) {
                    /* jslint evil:true */
                    return window['eval']('(' + s + ')');			
                };

            var httpData = function(xhr, type, s) { 

                var ct = xhr.getResponseHeader('content-type') || '',
                    xml = ((type === 'xml' || !type) && ct.indexOf('xml') >= 0),
                    data = xml ? xhr.responseXML : xhr.responseText;

                if (xml && data.documentElement.nodeName === 'parsererror') {
                    if ($.error) {
                        $.error('parsererror');
                    }
                }
                if (s && s.dataFilter) {
                    data = s.dataFilter(data, type);
                }
                if (typeof data === 'string') {
                    if ((type === 'json' || !type) && ct.indexOf('json') >= 0) {
                        data = parseJSON(data);
                    } else if ((type === 'script' || !type) && ct.indexOf('javascript') >= 0) {
                        $.globalEval(data);
                    }
                }

                return data;
            };

            return deferred;
        }
    };

    /**
     * ajaxForm() provides a mechanism for fully automating form submission.
     *
     * The advantages of using this method instead of ajaxSubmit() are:
     *
     * 1: This method will include coordinates for <input type="image"> elements (if the element
     *	is used to submit the form).
     * 2. This method will include the submit element's name/value data (for the element that was
     *	used to submit the form).
     * 3. This method binds the submit() method to the form for you.
     *
     * The options argument for ajaxForm works exactly as it does for ajaxSubmit. ajaxForm merely
     * passes the options argument along after properly binding events for submit elements and
     * the form itself.
     */
    $.fn.ajaxForm = function(options, data, dataType, onSuccess) {
        if (typeof options === 'string' || (options === false && arguments.length > 0)) {
            options = {
                'url'      : options,
                'data'     : data,
                'dataType' : dataType
            };

            if (typeof onSuccess === 'function') {
                options.success = onSuccess;
            }
        }

        options = options || {};
        options.delegation = options.delegation && $.isFunction($.fn.on);

        
        if (!options.delegation && this.length === 0) {
            var o = {s: this.selector, c: this.context};

            if (!$.isReady && o.s) {
                log('DOM not ready, queuing ajaxForm');
                $(function() {
                    $(o.s, o.c).ajaxForm(options);
                });

                return this;
            }

            
            log('terminating; zero elements found by selector' + ($.isReady ? '' : ' (DOM not ready)'));

            return this;
        }

        if (options.delegation) {
            $(document)
                .off('submit.form-plugin', this.selector, doAjaxSubmit)
                .off('click.form-plugin', this.selector, captureSubmittingElement)
                .on('submit.form-plugin', this.selector, options, doAjaxSubmit)
                .on('click.form-plugin', this.selector, options, captureSubmittingElement);

            return this;
        }

        return this.ajaxFormUnbind()
            .on('submit.form-plugin', options, doAjaxSubmit)
            .on('click.form-plugin', options, captureSubmittingElement);
    };

    
    function doAjaxSubmit(e) {
        /* jshint validthis:true */
        var options = e.data;

        if (!e.isDefaultPrevented()) { 
            e.preventDefault();
            $(e.target).closest('form').ajaxSubmit(options); 
        }
    }

    function captureSubmittingElement(e) {
        /* jshint validthis:true */
        var target = e.target;
        var $el = $(target);

        if (!$el.is('[type=submit],[type=image]')) {
            
            var t = $el.closest('[type=submit]');

            if (t.length === 0) {
                return;
            }
            target = t[0];
        }

        var form = target.form;

        form.clk = target;

        if (target.type === 'image') {
            if (typeof e.offsetX !== 'undefined') {
                form.clk_x = e.offsetX;
                form.clk_y = e.offsetY;

            } else if (typeof $.fn.offset === 'function') {
                var offset = $el.offset();

                form.clk_x = e.pageX - offset.left;
                form.clk_y = e.pageY - offset.top;

            } else {
                form.clk_x = e.pageX - target.offsetLeft;
                form.clk_y = e.pageY - target.offsetTop;
            }
        }
        
        setTimeout(function() {
            form.clk = form.clk_x = form.clk_y = null;
        }, 100);
    }


    
    $.fn.ajaxFormUnbind = function() {
        return this.off('submit.form-plugin click.form-plugin');
    };

    /**
     * formToArray() gathers form element data into an array of objects that can
     * be passed to any of the following ajax functions: $.get, $.post, or load.
     * Each object in the array has both a 'name' and 'value' property. An example of
     * an array for a simple login form might be:
     *
     * [ { name: 'username', value: 'jresig' }, { name: 'password', value: 'secret' } ]
     *
     * It is this array that is passed to pre-submit callback functions provided to the
     * ajaxSubmit() and ajaxForm() methods.
     */
    $.fn.formToArray = function(semantic, elements, filtering) {
        var a = [];

        if (this.length === 0) {
            return a;
        }

        var form = this[0];
        var formId = this.attr('id');
        var els = (semantic || typeof form.elements === 'undefined') ? form.getElementsByTagName('*') : form.elements;
        var els2;

        if (els) {
            els = $.makeArray(els); 
        }

        
        
        if (formId && (semantic || /(Edge|Trident)\//.test(navigator.userAgent))) {
            els2 = $(':input[form="' + formId + '"]').get(); 
            if (els2.length) {
                els = (els || []).concat(els2);
            }
        }

        if (!els || !els.length) {
            return a;
        }

        if ($.isFunction(filtering)) {
            els = $.map(els, filtering);
        }

        var i, j, n, v, el, max, jmax;

        for (i = 0, max = els.length; i < max; i++) {
            el = els[i];
            n = el.name;
            if (!n || el.disabled) {
                continue;
            }

            if (semantic && form.clk && el.type === 'image') {
                
                if (form.clk === el) {
                    a.push({name: n, value: $(el).val(), type: el.type});
                    a.push({name: n + '.x', value: form.clk_x}, {name: n + '.y', value: form.clk_y});
                }
                continue;
            }

            v = $.fieldValue(el, true);
            if (v && v.constructor === Array) {
                if (elements) {
                    elements.push(el);
                }
                for (j = 0, jmax = v.length; j < jmax; j++) {
                    a.push({name: n, value: v[j]});
                }

            } else if (feature.fileapi && el.type === 'file') {
                if (elements) {
                    elements.push(el);
                }

                var files = el.files;

                if (files.length) {
                    for (j = 0; j < files.length; j++) {
                        a.push({name: n, value: files[j], type: el.type});
                    }
                } else {
                    
                    a.push({name: n, value: '', type: el.type});
                }

            } else if (v !== null && typeof v !== 'undefined') {
                if (elements) {
                    elements.push(el);
                }
                a.push({name: n, value: v, type: el.type, required: el.required});
            }
        }

        if (!semantic && form.clk) {
            
            var $input = $(form.clk), input = $input[0];

            n = input.name;

            if (n && !input.disabled && input.type === 'image') {
                a.push({name: n, value: $input.val()});
                a.push({name: n + '.x', value: form.clk_x}, {name: n + '.y', value: form.clk_y});
            }
        }

        return a;
    };

    /**
     * Serializes form data into a 'submittable' string. This method will return a string
     * in the format: name1=value1&amp;name2=value2
     */
    $.fn.formSerialize = function(semantic) {
        
        return $.param(this.formToArray(semantic));
    };

    /**
     * Serializes all field elements in the jQuery object into a query string.
     * This method will return a string in the format: name1=value1&amp;name2=value2
     */
    $.fn.fieldSerialize = function(successful) {
        var a = [];

        this.each(function() {
            var n = this.name;

            if (!n) {
                return;
            }

            var v = $.fieldValue(this, successful);

            if (v && v.constructor === Array) {
                for (var i = 0, max = v.length; i < max; i++) {
                    a.push({name: n, value: v[i]});
                }

            } else if (v !== null && typeof v !== 'undefined') {
                a.push({name: this.name, value: v});
            }
        });

        
        return $.param(a);
    };

    /**
     * Returns the value(s) of the element in the matched set. For example, consider the following form:
     *
     *	<form><fieldset>
     *		<input name="A" type="text">
     *		<input name="A" type="text">
     *		<input name="B" type="checkbox" value="B1">
     *		<input name="B" type="checkbox" value="B2">
     *		<input name="C" type="radio" value="C1">
     *		<input name="C" type="radio" value="C2">
     *	</fieldset></form>
     *
     *	var v = $('input[type=text]').fieldValue();
     *	
     *	v === ['','']
     *	
     *	v === ['foo','bar']
     *
     *	var v = $('input[type=checkbox]').fieldValue();
     *	
     *	v === undefined
     *	
     *	v === ['B1', 'B2']
     *
     *	var v = $('input[type=radio]').fieldValue();
     *	
     *	v === undefined
     *	
     *	v === ['C1']
     *
     * The successful argument controls whether or not the field element must be 'successful'
     * (per http://www.w3.org/TR/html4/interact/forms.html#successful-controls).
     * The default value of the successful argument is true. If this value is false the value(s)
     * for each element is returned.
     *
     * Note: This method *always* returns an array. If no valid value can be determined the
     *	array will be empty, otherwise it will contain one or more values.
     */
    $.fn.fieldValue = function(successful) {
        for (var val = [], i = 0, max = this.length; i < max; i++) {
            var el = this[i];
            var v = $.fieldValue(el, successful);

            if (v === null || typeof v === 'undefined' || (v.constructor === Array && !v.length)) {
                continue;
            }

            if (v.constructor === Array) {
                $.merge(val, v);
            } else {
                val.push(v);
            }
        }

        return val;
    };

    /**
     * Returns the value of the field element.
     */
    $.fieldValue = function(el, successful) {
        var n = el.name, t = el.type, tag = el.tagName.toLowerCase();

        if (typeof successful === 'undefined') {
            successful = true;
        }

        /* eslint-disable no-mixed-operators */
        if (successful && (!n || el.disabled || t === 'reset' || t === 'button' ||
            (t === 'checkbox' || t === 'radio') && !el.checked ||
            (t === 'submit' || t === 'image') && el.form && el.form.clk !== el ||
            tag === 'select' && el.selectedIndex === -1)) {
            /* eslint-enable no-mixed-operators */
            return null;
        }

        if (tag === 'select') {
            var index = el.selectedIndex;

            if (index < 0) {
                return null;
            }

            var a = [], ops = el.options;
            var one = (t === 'select-one');
            var max = (one ? index + 1 : ops.length);

            for (var i = (one ? index : 0); i < max; i++) {
                var op = ops[i];

                if (op.selected && !op.disabled) {
                    var v = op.value;

                    if (!v) { 
                        v = (op.attributes && op.attributes.value && !(op.attributes.value.specified)) ? op.text : op.value;
                    }

                    if (one) {
                        return v;
                    }

                    a.push(v);
                }
            }

            return a;
        }

        return $(el).val().replace(rCRLF, '\r\n');
    };

    /**
     * Clears the form data. Takes the following actions on the form's input fields:
     *  - input text fields will have their 'value' property set to the empty string
     *  - select elements will have their 'selectedIndex' property set to -1
     *  - checkbox and radio inputs will have their 'checked' property set to false
     *  - inputs of type submit, button, reset, and hidden will *not* be effected
     *  - button elements will *not* be effected
     */
    $.fn.clearForm = function(includeHidden) {
        return this.each(function() {
            $('input,select,textarea', this).clearFields(includeHidden);
        });
    };

    /**
     * Clears the selected form elements.
     */
    $.fn.clearFields = $.fn.clearInputs = function(includeHidden) {
        var re = /^(?:color|date|datetime|email|month|number|password|range|search|tel|text|time|url|week)$/i; 

        return this.each(function() {
            var t = this.type, tag = this.tagName.toLowerCase();

            if (re.test(t) || tag === 'textarea') {
                this.value = '';

            } else if (t === 'checkbox' || t === 'radio') {
                this.checked = false;

            } else if (tag === 'select') {
                this.selectedIndex = -1;

            } else if (t === 'file') {
                if (/MSIE/.test(navigator.userAgent)) {
                    $(this).replaceWith($(this).clone(true));
                } else {
                    $(this).val('');
                }

            } else if (includeHidden) {
                
                
                
                
                if ((includeHidden === true && /hidden/.test(t)) ||
                    (typeof includeHidden === 'string' && $(this).is(includeHidden))) {
                    this.value = '';
                }
            }
        });
    };


    /**
     * Resets the form data or individual elements. Takes the following actions
     * on the selected tags:
     * - all fields within form elements will be reset to their original value
     * - input / textarea / select fields will be reset to their original value
     * - option / optgroup fields (for multi-selects) will defaulted individually
     * - non-multiple options will find the right select to default
     * - label elements will be searched against its 'for' attribute
     * - all others will be searched for appropriate children to default
     */
    $.fn.resetForm = function() {
        return this.each(function() {
            var el = $(this);
            var tag = this.tagName.toLowerCase();

            switch (tag) {
                case 'input':
                    this.checked = this.defaultChecked;
                

                case 'textarea':
                    this.value = this.defaultValue;

                    return true;

                case 'option':
                case 'optgroup':
                    var select = el.parents('select');

                    if (select.length && select[0].multiple) {
                        if (tag === 'option') {
                            this.selected = this.defaultSelected;
                        } else {
                            el.find('option').resetForm();
                        }
                    } else {
                        select.resetForm();
                    }

                    return true;

                case 'select':
                    el.find('option').each(function(i) {				
                        this.selected = this.defaultSelected;
                        if (this.defaultSelected && !el[0].multiple) {
                            el[0].selectedIndex = i;

                            return false;
                        }
                    });

                    return true;

                case 'label':
                    var forEl = $(el.attr('for'));
                    var list = el.find('input,select,textarea');

                    if (forEl[0]) {
                        list.unshift(forEl[0]);
                    }

                    list.resetForm();

                    return true;

                case 'form':
                    
                    
                    if (typeof this.reset === 'function' || (typeof this.reset === 'object' && !this.reset.nodeType)) {
                        this.reset();
                    }

                    return true;

                default:
                    el.find('form,input,label,select,textarea').resetForm();

                    return true;
            }
        });
    };

    /**
     * Enables or disables any matching elements.
     */
    $.fn.enable = function(b) {
        if (typeof b === 'undefined') {
            b = true;
        }

        return this.each(function() {
            this.disabled = !b;
        });
    };

    /**
     * Checks/unchecks any matching checkboxes or radio buttons and
     * selects/deselects and matching option elements.
     */
    $.fn.selected = function(select) {
        if (typeof select === 'undefined') {
            select = true;
        }

        return this.each(function() {
            var t = this.type;

            if (t === 'checkbox' || t === 'radio') {
                this.checked = select;

            } else if (this.tagName.toLowerCase() === 'option') {
                var $sel = $(this).parent('select');

                if (select && $sel[0] && $sel[0].type === 'select-one') {
                    
                    $sel.find('option').selected(false);
                }

                this.selected = select;
            }
        });
    };

    
    $.fn.ajaxSubmit.debug = false;

    
    function log() {
        if (!$.fn.ajaxSubmit.debug) {
            return;
        }

        var msg = '[jquery.form] ' + Array.prototype.join.call(arguments, '');

        if (window.console && window.console.log) {
            window.console.log(msg);

        } else if (window.opera && window.opera.postError) {
            window.opera.postError(msg);
        }
    }
}));
