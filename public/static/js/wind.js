(function (win, undefined) {
    "use strict";
    var doc   = win.document,
        nav   = win.navigator,
        loc   = win.location,
        html  = doc.documentElement,
        klass = [],
        conf  = {
            screens: [240, 320, 480, 640, 768, 800, 1024, 1280, 1440, 1680, 1920],
            screensCss: {"gt": true, "gte": false, "lt": true, "lte": false, "eq": false},
            browsers: [
                {ie: {min: 6, max: 11}}
            ],
            browserCss: {"gt": true, "gte": false, "lt": true, "lte": false, "eq": true},
            html5: true,
            page: "-page",
            section: "-section",
            head: "head"
        };
    if (win.head_conf) {
        for (var item in win.head_conf) {
            if (win.head_conf[item] !== undefined) {
                conf[item] = win.head_conf[item];
            }
        }
    }
    function pushClass(name) {
        klass[klass.length] = name;
    }
    function removeClass(name) {
        var re         = new RegExp(" ?\\b" + name + "\\b");
        html.className = html.className.replace(re, "");
    }
    function each(arr, fn) {
        for (var i = 0, l = arr.length; i < l; i++) {
            fn.call(arr, arr[i], i);
        }
    }
    var api = win[conf.head] = function () {
        api.ready.apply(null, arguments);
    };
    api.feature = function (key, enabled, queue) {
        if (!key) {
            html.className += " " + klass.join(" ");
            klass = [];
            return api;
        }
        if (Object.prototype.toString.call(enabled) === "[object Function]") {
            enabled = enabled.call();
        }
        pushClass((enabled ? "" : "no-") + key);
        api[key] = !!enabled;
        if (!queue) {
            removeClass("no-" + key);
            removeClass(key);
            api.feature();
        }
        return api;
    };
    api.feature("js", true);
    var ua     = nav.userAgent.toLowerCase(),
        mobile = /mobile|android|kindle|silk|midp|phone|(windows .+arm|touch)/.test(ua);
    api.feature("mobile", mobile, true);
    api.feature("desktop", !mobile, true);
    ua = /(chrome|firefox)[ \/]([\w.]+)/.exec(ua) || 
        /(iphone|ipad|ipod)(?:.*version)?[ \/]([\w.]+)/.exec(ua) || 
        /(android)(?:.*version)?[ \/]([\w.]+)/.exec(ua) || 
        /(webkit|opera)(?:.*version)?[ \/]([\w.]+)/.exec(ua) || 
        /(msie) ([\w.]+)/.exec(ua) ||
        /(trident).+rv:(\w.)+/.exec(ua) || [];
    var browser = ua[1],
        version = parseFloat(ua[2]);
    switch (browser) {
        case "msie":
        case "trident":
            browser = "ie";
            version = doc.documentMode || version;
            break;
        case "firefox":
            browser = "ff";
            break;
        case "ipod":
        case "ipad":
        case "iphone":
            browser = "ios";
            break;
        case "webkit":
            browser = "safari";
            break;
    }
    api.browser          = {
        name: browser,
        version: version
    };
    api.browser[browser] = true;
    for (var i = 0, l = conf.browsers.length; i < l; i++) {
        for (var key in conf.browsers[i]) {
            if (browser === key) {
                pushClass(key);
                var min = conf.browsers[i][key].min;
                var max = conf.browsers[i][key].max;
                for (var v = min; v <= max; v++) {
                    if (version > v) {
                        if (conf.browserCss.gt) {
                            pushClass("gt-" + key + v);
                        }
                        if (conf.browserCss.gte) {
                            pushClass("gte-" + key + v);
                        }
                    } else if (version < v) {
                        if (conf.browserCss.lt) {
                            pushClass("lt-" + key + v);
                        }
                        if (conf.browserCss.lte) {
                            pushClass("lte-" + key + v);
                        }
                    } else if (version === v) {
                        if (conf.browserCss.lte) {
                            pushClass("lte-" + key + v);
                        }
                        if (conf.browserCss.eq) {
                            pushClass("eq-" + key + v);
                        }
                        if (conf.browserCss.gte) {
                            pushClass("gte-" + key + v);
                        }
                    }
                }
            } else {
                pushClass("no-" + key);
            }
        }
    }
    pushClass(browser);
    pushClass(browser + parseInt(version, 10));
    if (conf.html5 && browser === "ie" && version < 9) {
        each("abbr|article|aside|audio|canvas|details|figcaption|figure|footer|header|hgroup|main|mark|meter|nav|output|progress|section|summary|time|video".split("|"), function (el) {
            doc.createElement(el);
        });
    }
    each(loc.pathname.split("/"), function (el, i) {
        if (this.length > 2 && this[i + 1] !== undefined) {
            if (i) {
                pushClass(this.slice(i, i + 1).join("-").toLowerCase() + conf.section);
            }
        } else {
            var id = el || "index", index = id.indexOf(".");
            if (index > 0) {
                id = id.substring(0, index);
            }
            html.id = id.toLowerCase() + conf.page;
            if (!i) {
                pushClass("root" + conf.section);
            }
        }
    });
    api.screen = {
        height: win.screen.height,
        width: win.screen.width
    };
    function screenSize() {
        html.className = html.className.replace(/ (w-|eq-|gt-|gte-|lt-|lte-|portrait|no-portrait|landscape|no-landscape)\d+/g, "");
        var iw = win.innerWidth || html.clientWidth,
            ow = win.outerWidth || win.screen.width;
        api.screen.innerWidth = iw;
        api.screen.outerWidth = ow;
        pushClass("w-" + iw);
        each(conf.screens, function (width) {
            if (iw > width) {
                if (conf.screensCss.gt) {
                    pushClass("gt-" + width);
                }
                if (conf.screensCss.gte) {
                    pushClass("gte-" + width);
                }
            } else if (iw < width) {
                if (conf.screensCss.lt) {
                    pushClass("lt-" + width);
                }
                if (conf.screensCss.lte) {
                    pushClass("lte-" + width);
                }
            } else if (iw === width) {
                if (conf.screensCss.lte) {
                    pushClass("lte-" + width);
                }
                if (conf.screensCss.eq) {
                    pushClass("e-q" + width);
                }
                if (conf.screensCss.gte) {
                    pushClass("gte-" + width);
                }
            }
        });
        var ih = win.innerHeight || html.clientHeight,
            oh = win.outerHeight || win.screen.height;
        api.screen.innerHeight = ih;
        api.screen.outerHeight = oh;
        api.feature("portrait", (ih > iw));
        api.feature("landscape", (ih < iw));
    }
    screenSize();
    var resizeId = 0;
    function onResize() {
        win.clearTimeout(resizeId);
        resizeId = win.setTimeout(screenSize, 50);
    }
    if (win.addEventListener) {
        win.addEventListener("resize", onResize, false);
    } else {
        win.attachEvent("onresize", onResize);
    }
}(window));
(function (win, undefined) {
    "use strict";
    var doc      = win.document,
        el       = doc.createElement("i"),
        style    = el.style,
        prefs    = " -o- -moz- -ms- -webkit- -khtml- ".split(" "),
        domPrefs = "Webkit Moz O ms Khtml".split(" "),
        headVar  = win.head_conf && win.head_conf.head || "head",
        api      = win[headVar];
    function testProps(props) {
        for (var i in props) {
            if (style[props[i]] !== undefined) {
                return true;
            }
        }
        return false;
    }
    function testAll(prop) {
        var camel = prop.charAt(0).toUpperCase() + prop.substr(1),
            props = (prop + " " + domPrefs.join(camel + " ") + camel).split(" ");
        return !!testProps(props);
    }
    var tests = {
        gradient: function () {
            var s1 = "background-image:",
                s2 = "gradient(linear,left top,right bottom,from(#9f9),to(#fff));",
                s3 = "linear-gradient(left top,#eee,#fff);";
            style.cssText = (s1 + prefs.join(s2 + s1) + prefs.join(s3 + s1)).slice(0, -s1.length);
            return !!style.backgroundImage;
        },
        rgba: function () {
            style.cssText = "background-color:rgba(0,0,0,0.5)";
            return !!style.backgroundColor;
        },
        opacity: function () {
            return el.style.opacity === "";
        },
        textshadow: function () {
            return style.textShadow === "";
        },
        multiplebgs: function () {
            style.cssText = "background:url(https://),url(https://),red url(https://)";
            var result = (style.background || "").match(/url/g);
            return Object.prototype.toString.call(result) === "[object Array]" && result.length === 3;
        },
        boxshadow: function () {
            return testAll("boxShadow");
        },
        borderimage: function () {
            return testAll("borderImage");
        },
        borderradius: function () {
            return testAll("borderRadius");
        },
        cssreflections: function () {
            return testAll("boxReflect");
        },
        csstransforms: function () {
            return testAll("transform");
        },
        csstransitions: function () {
            return testAll("transition");
        },
        touch: function () {
            return "ontouchstart" in win;
        },
        retina: function () {
            return (win.devicePixelRatio > 1);
        },
        fontface: function () {
            var browser = api.browser.name, version = api.browser.version;
            switch (browser) {
                case "ie":
                    return version >= 9;
                case "chrome":
                    return version >= 13;
                case "ff":
                    return version >= 6;
                case "ios":
                    return version >= 5;
                case "android":
                    return false;
                case "webkit":
                    return version >= 5.1;
                case "opera":
                    return version >= 10;
                default:
                    return false;
            }
        }
    };
    for (var key in tests) {
        if (tests[key]) {
            api.feature(key, tests[key].call(), true);
        }
    }
    api.feature();
}(window));
(function (win, undefined) {
    "use strict";
    var doc        = win.document,
        domWaiters = [],
        handlers   = {}, 
        assets     = {}, 
        isAsync    = "async" in doc.createElement("script") || "MozAppearance" in doc.documentElement.style || win.opera,
        isDomReady,
        headVar    = win.head_conf && win.head_conf.head || "Wind",
        api        = win[headVar] = (win[headVar] || function () {
            api.ready.apply(null, arguments);
        }),
        PRELOADING = 1,
        PRELOADED  = 2,
        LOADING    = 3,
        LOADED     = 4;
    function noop() {
    }
    function each(arr, callback) {
        if (!arr) {
            return;
        }
        if (typeof arr === "object") {
            arr = [].slice.call(arr);
        }
        for (var i = 0, l = arr.length; i < l; i++) {
            callback.call(arr, arr[i], i);
        }
    }
    function is(type, obj) {
        var clas = Object.prototype.toString.call(obj).slice(8, -1);
        return obj !== undefined && obj !== null && clas === type;
    }
    function isFunction(item) {
        return is("Function", item);
    }
    function isArray(item) {
        return is("Array", item);
    }
    function toLabel(url) {
        var items = url.split("/"),
            name  = items[items.length - 1],
            i     = name.indexOf("?");
        return i !== -1 ? name.substring(0, i) : name;
    }
    function one(callback) {
        callback = callback || noop;
        if (callback._done) {
            return;
        }
        callback();
        callback._done = 1;
    }
    function conditional(test, success, failure, callback) {
        var obj = (typeof test === "object") ? test : {
            test: test,
            success: !!success ? isArray(success) ? success : [success] : false,
            failure: !!failure ? isArray(failure) ? failure : [failure] : false,
            callback: callback || noop
        };
        var passed = !!obj.test;
        if (passed && !!obj.success) {
            obj.success.push(obj.callback);
            api.load.apply(null, obj.success);
        }
        else if (!passed && !!obj.failure) {
            obj.failure.push(obj.callback);
            api.load.apply(null, obj.failure);
        }
        else {
            callback();
        }
        return api;
    }
    function getAsset(item) {
        var asset = {};
        if (typeof item === "object") {
            for (var label in item) {
                if (!!item[label]) {
                    asset = {
                        name: label,
                        url: item[label]
                    };
                }
            }
        }
        else {
            asset = {
                name: toLabel(item),
                url: item
            };
        }
        var existing = assets[asset.name];
        if (existing && existing.url === asset.url) {
            return existing;
        }
        assets[asset.name] = asset;
        return asset;
    }
    function allLoaded(items) {
        items = items || assets;
        for (var name in items) {
            if (items.hasOwnProperty(name) && items[name].state !== LOADED) {
                return false;
            }
        }
        return true;
    }
    function onPreload(asset) {
        asset.state = PRELOADED;
        each(asset.onpreload, function (afterPreload) {
            afterPreload.call();
        });
    }
    function preLoad(asset, callback) {
        if (asset.state === undefined) {
            asset.state     = PRELOADING;
            asset.onpreload = [];
            loadAsset({url: asset.url, type: "cache"}, function () {
                onPreload(asset);
            });
        }
    }
    function apiLoadHack() {
        var args     = arguments,
            callback = args[args.length - 1],
            rest     = [].slice.call(args, 1),
            next     = rest[0];
        if (!isFunction(callback)) {
            callback = null;
        }
        if (isArray(args[0])) {
            args[0].push(callback);
            api.load.apply(null, args[0]);
            return api;
        }
        if (!!next) {
            each(rest, function (item) {
                if (!isFunction(item) && !!item) {
                    preLoad(getAsset(item));
                }
            });
            load(getAsset(args[0]), isFunction(next) ? next : function () {
                api.load.apply(null, rest);
            });
        }
        else {
            load(getAsset(args[0]));
        }
        return api;
    }
    function apiLoadAsync() {
        var args     = arguments,
            callback = args[args.length - 1],
            items    = {};
        if (!isFunction(callback)) {
            callback = null;
        }
        if (isArray(args[0])) {
            args[0].push(callback);
            api.load.apply(null, args[0]);
            return api;
        }
        each(args, function (item, i) {
            if (item !== callback) {
                item             = getAsset(item);
                items[item.name] = item;
            }
        });
        each(args, function (item, i) {
            if (item !== callback) {
                item = getAsset(item);
                load(item, function () {
                    if (allLoaded(items)) {
                        one(callback);
                    }
                });
            }
        });
        return api;
    }
    function load(asset, callback) {
        callback = callback || noop;
        if (asset.state === LOADED) {
            callback();
            return;
        }
        if (asset.state === LOADING) {
            api.ready(asset.name, callback);
            return;
        }
        if (asset.state === PRELOADING) {
            asset.onpreload.push(function () {
                load(asset, callback);
            });
            return;
        }
        asset.state = LOADING;
        loadAsset(asset, function () {
            asset.state = LOADED;
            callback();
            each(handlers[asset.name], function (fn) {
                one(fn);
            });
            if (isDomReady && allLoaded()) {
                each(handlers.ALL, function (fn) {
                    one(fn);
                });
            }
        });
    }
    function getExtension(url) {
        url = url || "";
        var items = url.split("?")[0].split(".");
        return items[items.length - 1].toLowerCase();
    }
    function loadAsset(asset, callback) {
        callback = callback || noop;
        function error(event) {
            event = event || win.event;
            ele.onload = ele.onreadystatechange = ele.onerror = null;
            callback();
        }
        function process(event) {
            event = event || win.event;
            if (event.type === "load" || (/loaded|complete/.test(ele.readyState) && (!doc.documentMode || doc.documentMode < 9))) {
                win.clearTimeout(asset.errorTimeout);
                win.clearTimeout(asset.cssTimeout);
                ele.onload = ele.onreadystatechange = ele.onerror = null;
                callback();
            }
        }
        function isCssLoaded() {
            if (asset.state !== LOADED && asset.cssRetries <= 20) {
                for (var i = 0, l = doc.styleSheets.length; i < l; i++) {
                    if (doc.styleSheets[i].href === ele.href) {
                        process({"type": "load"});
                        return;
                    }
                }
                asset.cssRetries++;
                asset.cssTimeout = win.setTimeout(isCssLoaded, 250);
            }
        }
        var ele;
        var ext = getExtension(asset.url);
        if (ext === "css") {
            ele      = doc.createElement("link");
            ele.type = "text/" + (asset.type || "css");
            ele.rel  = "stylesheet";
            ele.href = asset.url;
            asset.cssRetries = 0;
            asset.cssTimeout = win.setTimeout(isCssLoaded, 500);
        }
        else {
            ele      = doc.createElement("script");
            ele.type = "text/" + (asset.type || "javascript");
            ele.src  = asset.url;
        }
        ele.onload = ele.onreadystatechange = process;
        ele.onerror = error;
        ele.async = false;
        ele.defer = false;
        asset.errorTimeout = win.setTimeout(function () {
            error({type: "timeout"});
        }, 7e3);
        var head = doc.head || doc.getElementsByTagName("head")[0];
        head.insertBefore(ele, head.lastChild);
    }
    function init() {
        var items = doc.getElementsByTagName("script");
        for (var i = 0, l = items.length; i < l; i++) {
            var dataMain = items[i].getAttribute("data-headjs-load");
            if (!!dataMain) {
                api.load(dataMain);
                return;
            }
        }
    }
    function ready(key, callback) {
        if (key === doc) {
            if (isDomReady) {
                one(callback);
            }
            else {
                domWaiters.push(callback);
            }
            return api;
        }
        if (isFunction(key)) {
            callback = key;
            key      = "ALL";
        }
        if (isArray(key)) {
            var items = {};
            each(key, function (item) {
                items[item] = assets[item];
                api.ready(item, function () {
                    if (allLoaded(items)) {
                        one(callback);
                    }
                });
            });
            return api;
        }
        if (typeof key !== "string" || !isFunction(callback)) {
            return api;
        }
        var asset = assets[key];
        if (asset && asset.state === LOADED || key === "ALL" && allLoaded() && isDomReady) {
            one(callback);
            return api;
        }
        var arr = handlers[key];
        if (!arr) {
            arr = handlers[key] = [callback];
        }
        else {
            arr.push(callback);
        }
        return api;
    }
    function domReady() {
        if (!doc.body) {
            win.clearTimeout(api.readyTimeout);
            api.readyTimeout = win.setTimeout(domReady, 50);
            return;
        }
        if (!isDomReady) {
            isDomReady = true;
            init();
            each(domWaiters, function (fn) {
                one(fn);
            });
        }
    }
    function domContentLoaded() {
        if (doc.addEventListener) {
            doc.removeEventListener("DOMContentLoaded", domContentLoaded, false);
            domReady();
        }
        else if (doc.readyState === "complete") {
            doc.detachEvent("onreadystatechange", domContentLoaded);
            domReady();
        }
    }
    if (doc.readyState === "complete") {
        domReady();
    }
    else if (doc.addEventListener) {
        doc.addEventListener("DOMContentLoaded", domContentLoaded, false);
        win.addEventListener("load", domReady, false);
    }
    else {
        doc.attachEvent("onreadystatechange", domContentLoaded);
        win.attachEvent("onload", domReady);
        var top = false;
        try {
            top = !win.frameElement && doc.documentElement;
        } catch (e) {
        }
        if (top && top.doScroll) {
            (function doScrollCheck() {
                if (!isDomReady) {
                    try {
                        top.doScroll("left");
                    } catch (error) {
                        win.clearTimeout(api.readyTimeout);
                        api.readyTimeout = win.setTimeout(doScrollCheck, 50);
                        return;
                    }
                    domReady();
                }
            }());
        }
    }
    api.load = api.js = isAsync ? apiLoadAsync : apiLoadHack;
    api.test  = conditional;
    api.ready = ready;
    api.ready(doc, function () {
        if (allLoaded()) {
            each(handlers.ALL, function (callback) {
                one(callback);
            });
        }
        if (api.feature) {
            api.feature("domloaded", true);
        }
    });
}(window));
if (!window.console) {
    window.console = {};
    var funs       = ["profiles", "memory", "_commandLineAPI", "debug", "error", "info", "log", "warn", "dir", "dirxml", "trace", "assert", "count", "markTimeline", "profile", "profileEnd", "time", "timeEnd", "timeStamp", "group", "groupCollapsed", "groupEnd"];
    for (var i = 0; i < funs.length; i++) {
        console[funs[i]] = function () {
        };
    }
}
Wind.ready(function () {
    if (!+'\v1' && !('maxHeight' in document.body.style)) {
        try {
            document.execCommand("BackgroundImageCache", false, true);
        } catch (e) {
        }
    }
});
(function (win) {
    var root      = win.GV.WEB_ROOT + win.GV.JS_ROOT || location.origin + '/public/js/',
        ver       = '',
        alias     = {
            datePicker: 'datePicker/datePicker',
            jquery: 'jquery',
            colorPicker: 'colorPicker/colorPicker',
            tabs: 'tabs/tabs',
            swfobject: 'swfobject',
            imgready: 'imgready',
            ajaxForm: 'ajaxForm',
            cookie: 'cookie',
            treeview: 'treeview',
            treeTable: 'treeTable/treeTable',
            draggable: 'draggable',
            validate: 'jquery.validate/jquery.validate',
            'validate-extends': 'jquery.validate/additional-methods',
            artDialog: 'artDialog/artDialog',
            iframeTools: 'artDialog/iframeTools',
            xd: 'xd',
            noty: 'noty/noty-2.4.1',
            noty3: 'noty3/noty.min',
            jcrop: 'jcrop/js/jcrop',
            ajaxfileupload: 'ajaxfileupload',
            layer: 'layer/layer',
            plupload: 'plupload/plupload.full.min',
            echarts: 'echarts/echarts.min',
            viewer: 'viewer/viewer',
            colorpicker:'colorpicker/js/colorpicker',
            mousewheel: 'jquery.mousewheel/jquery.mousewheel.min',
            bootstrapDatetimePicker: 'bootstrap-datetimepicker/js/bootstrap-datetimepicker',
            dragula: 'dragula/dragula.min',
            imagesloaded: 'masonry/imagesloaded.pkgd.min',
            masonry: 'masonry/masonry.pkgd.min',
            masonry3: 'masonry/masonry-3.3.2.pkgd',
            ueditor:'ueditor/ueditor.all.min'
        },
        alias_css = {
            colorPicker: 'colorPicker/style',
            artDialog: 'artDialog/skins/default',
            datePicker: 'datePicker/style',
            treeTable: 'treeTable/treeTable',
            jcrop: 'jcrop/css/jquery.Jcrop.min',
            layer: 'layer/skin/default/layer',
            viewer: 'viewer/viewer',
            noty3: 'noty3/noty',
            colorpicker: 'colorpicker/css/colorpicker',
            animate: 'animate/animate',
            bootstrapDatetimePicker: 'bootstrap-datetimepicker/css/bootstrap-datetimepicker',
            dragula: 'dragula/dragula.min',
            ueditor:'ueditor/themes/default/css/ueditor'
        };
    for (var i in alias) {
        if (alias.hasOwnProperty(i)) {
            alias[i] = root + alias[i] + '.js?v=' + ver;
        }
    }
    for (var i in alias_css) {
        if (alias_css.hasOwnProperty(i)) {
            alias_css[i] = root + alias_css[i] + '.css?v=' + ver;
        }
    }
    win.Wind = win.Wind || {};
    Wind.css = function (alias/*alias or path*/, callback) {
        var url     = alias_css[alias] ? alias_css[alias] : alias
        var link    = document.createElement('link');
        link.rel    = 'stylesheet';
        link.href   = url;
        link.onload = link.onreadystatechange = function () {
            var state = link.readyState;
            if (callback && !callback.done && (!state || /loaded|complete/.test(state))) {
                callback.done = true;
                callback();
            }
        }
        document.getElementsByTagName('head')[0].appendChild(link);
    };
    Wind.alias = function (newAlias) {
        for (var i in newAlias) {
            alias[i] = newAlias[i];
        }
    }
    Wind.aliasCss = function (newAlias) {
        for (var i in newAlias) {
            alias_css[i] = newAlias[i];
        }
    }
    Wind.use = function () {
        var args = arguments, len = args.length;
        for (var i = 0; i < len; i++) {
            if (typeof args[i] === 'string' && alias[args[i]]) {
                args[i] = alias[args[i]];
            }
        }
        Wind.js.apply(null, args);
    };
    var cache = {};
    Wind.tmpl = function (str, data) {
        var fn = !/\W/.test(str) ? cache[str] = cache[str] || tmpl(str) :
            new Function("obj", "var p=[],print=function(){p.push.apply(p,arguments);};" +
                "with(obj){p.push('" +
                str.replace(/[\r\t\n]/g, " ").split("<%").join("\t").replace(/((^|%>)[^\t]*)'/g, "$1\r").replace(/\t=(.*?)%>/g, "',$1,'").split("\t").join("');").split("%>").join("p.push('").split("\r").join("\\'") + "');}return p.join('');");
        return data ? fn(data) : fn;
    };
    Wind.Util = {}
})(window);