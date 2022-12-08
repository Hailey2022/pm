/*
 * PHPWind util Library
 * @Copyright 	: Copyright 2011, phpwind.com
 * @Descript	: datePicker 日历组件
 * @Author		: jquerytools (http://jquerytools.org/demos/dateinput/)
 * @Modify		: chaoren1641@gmail.com
 * @Depend		: jquery.js(1.7 or later)
 * $Id: datePicker.js 22586 2012-12-25 10:54:55Z hao.lin $			:
 */
;(function($, window, document, undefined) {
	var pluginName = 'datePicker';
	var instances = [];

	
	var KEYS = [75, 76, 38, 39, 74, 72, 40, 37], LABELS = {};
	var defaults = {
			format 		: 'yyyy-mm-dd',
			selectors 	: true,
			time		: false,
			yearRange 	: [-50, 20],
			lang 		: 'zh-CN',
			offset : [0, 0],
			speed : 0,
			firstDay : 0, 
			min : undefined,
			max : undefined,
			trigger : false,

			css : {

				prefix : 'cal',
				input : 'date',

				
				root : 0,
				head : 0,
				title : 0,
				prev : 0,
				next : 0,
				month : 0,
				year : 0,
				days : 0,

				body : 0,
				weeks : 0,
				today : 0,
				current : 0,

				
				week : 0,
				off : 0,
				sunday : 0,
				focus : 0,
				disabled : 0,
				trigger : 0
		}
	};

	var localize = function(language, labels) {
		$.each(labels, function(key, val) {
			labels[key] = val.split(",");
		});
		LABELS[language] = labels;
	};

	//多语言配置
	localize("en", {
		months : 'January,February,March,April,May,June,July,August,September,October,November,December',
		shortMonths : 'Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec',
		days : 'Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
		shortDays : 'Sun,Mon,Tue,Wed,Thu,Fri,Sat'
	});
	localize("zh-CN", {
		months : '一月,二月,三月,四月,五月,六月,七月,八月,九月,十月,十一月,十二月',
		shortMonths : '一,二,三,四,五,六,七,八,九,十,十一,十二',
		days : '周日,周一,周二,周三,周四,周五,周六',
		shortDays : '日,一,二,三,四,五,六'
	});

	//{{{ private functions

	//返回某年某月的天数
	function dayAm(year, month) {
		return 32 - new Date(year, month, 32).getDate();
	}

	function zeropad(val, len) {
		val = '' + val;
		len = len || 2;
		while(val.length < len) {
			val = "0" + val;
		}
		return val;
	}

	
	var Re = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g, tmpTag = $("<a/>");

	//格式化时间
	function format(date, fmt, lang) {
		var d = date.getDate(), D = date.getDay(), m = date.getMonth(), y = date.getFullYear(),
			h = date.getHours(),M = date.getMinutes(),
			flags = {
				d : d,
				dd : zeropad(d),
				ddd : LABELS[lang].shortDays[D],
				dddd : LABELS[lang].days[D],
				m : m + 1,
				mm : zeropad(m + 1),
				mmm : LABELS[lang].shortMonths[m],
				mmmm : LABELS[lang].months[m],
				yy : String(y).slice(2),
				yyyy : y,
				HH: zeropad(h),
				MM : zeropad(M)
			};
		var ret = fmt.replace(Re, function($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
		
		return tmpTag.html(ret).html();

	}

	function integer(val) {
		return parseInt(val, 10);
	}

	function isSameDay(d1, d2) {
		return d1.getFullYear() === d2.getFullYear() && d1.getMonth() == d2.getMonth() && d1.getDate() == d2.getDate();
	}

	function parseDate(val) {
		if(!val) {
			return;
		}
		if(val.constructor == Date) {
			return val;
		}

		if( typeof val == 'string') {
			
			var els = val.split(" ");
			var date,time;
			var y,m,d,h,s;
			date = els[0].split('-');
			if(date.length !== 3) {
				return;
			}
			if(els.length === 2) {
				time = els[1].split(':');
			}else {
				time = '00:00'.split(':');
			}
			y = date[0],m = date[1]-1,d = date[2];
			h = time[0],s = time[1];
			return new Date(y,m,d,h,s,0);
			
			if(!/^-?\d+$/.test(val)) {
				return;
			}
			
			val = integer(val);
		}
		var date = new Date();
		date.setDate(date.getDate() + val);
		return date;
	}

	function Plugin( input, options ) {
		this.options = $.extend( {}, defaults, options) ;
		this.input = input;
		this.init();
    }

	Plugin.prototype.init = function() {
		var options = this.options,input = this.input;
		if(options.time) {
			options.format = 'yyyy-mm-dd HH:MM'
		}
		
		$.each(options.css, function(key, val) {
			if(!val && key != 'prefix') {
				options.css[key] = (options.css.prefix || '') + (val || key);
			}
		});
		
		var self = this, now = new Date(), css = options.css, labels = LABELS[options.lang], root = $("#" + css.root), title = root.find("#" + css.title), trigger, pm, nm, currYear, currMonth, currDay, value = input.attr("data-value") || options.value || input.val(), min = input.attr("min") || options.min, max = input.attr("max") || options.max, opened;
		
		if(min === 0) {
			min = "0";
		}
		
		value = parseDate(value) || now;
		min = parseDate(min || options.yearRange[0] * 365);
		max = parseDate(max || options.yearRange[1] * 365);

		
		if(!labels) {
			throw "不存在的语言: " + options.lang;
		}

		
		if(input.attr("type") === 'date') {
			var tmp = $("<input/>");

			$.each("class,disabled,id,maxlength,name,readonly,required,size,style,tabindex,title,value".split(","), function(i, attr) {
				tmp.attr(attr, input.attr(attr));
			});
			input.replaceWith(tmp);
			input = tmp;
		}
		input.addClass(css.input);

		var fire = input.add(self);

		
		if(!root.length) {

			
			root = $('<div><div><a/><div/><a/></div><div><div/><div/></div></div>').hide().css({
				position : 'absolute'
			}).attr("id", css.root);

			
			root.children().eq(0).attr("id", css.head).end().eq(1).attr("id", css.body).children().eq(0).attr("id", css.days).end().eq(1).attr("id", css.weeks).end().end().end().find("a").eq(0).attr("id", css.prev).end().eq(1).attr("id", css.next);

			
			title = root.find("#" + css.head).find("div").attr("id", css.title);

			
			if(options.selectors) {
				var monthSelector = $("<select/>").attr("id", css.month), yearSelector = $("<select/>").attr("id", css.year);
				title.html(monthSelector.add(yearSelector));
			}

			
			var days = root.find("#" + css.days);

			
			for(var d = 0; d < 7; d++) {
				days.append($("<span/>").text(labels.shortDays[(d + options.firstDay) % 7]));
			}

			var body = root.find("#" + css.body);
			$('<div class="caltime"><button type="button" class="btn btn_submit fr" name="submit">确认</button><input id="calHour" type="number" class="input" min="0" max="23" size="2" value="0"><span>点</span><input id="calMin" class="input" type="number" size="2" min="1" max="59" value="0"><span>分</span></div>').appendTo(body);

			$("body").append(root);

		}

		
		if(options.trigger) {
			trigger = $("<a/>").attr("href", "#").addClass(css.trigger).click(function(e) {
				self.show();
				return e.preventDefault();
			}).insertAfter(input);
		}

		
		var weeks = root.find("#" + css.weeks);
		yearSelector = root.find("#" + css.year);
		monthSelector = root.find("#" + css.month);

		//{{{ pick

		function select(date, options, e) {
			if(!date) return;
			
			value = date;
			currYear = date.getFullYear();
			currMonth = date.getMonth();
			currDay = date.getDate();

			if (e.type == "click" && $.browser && !$.browser.msie) {
				input.focus();
			}

			
			e = e || $.Event("api");
			e.type = "select";

			fire.trigger(e, [date]);
			if(e.isDefaultPrevented()) {
				return;
			}
			//如果选项有时间，则加上时间
			if(options.time) {
				var timeInput = root.find('input');
				var hour = parseInt(timeInput.eq(0).val(),10);
				var min = parseInt(timeInput.eq(1).val(),10);
				if(isNaN(hour)) {
					hour = 0;
				}
				if(isNaN(min)) {
					min = 0;
				}
				if(hour < 10) {
					hour = '0' + hour;
				}else if(hour < 0 || hour > 23) {
					hour = '00';
				}
				if(min < 10) {
					min = '0' + min;
				}else if(min < 0 || min > 59) {
					min = '00';
				}
				date.setHours(hour);
				date.setMinutes(min);
			}
			
			var date = format(date, options.format, options.lang);
			input.val(date);
			
			input.data("date", date);
			//设置val后，IE导致触发focus事件，导致窗口关闭后再次被打开
			setTimeout(function() {
				self.hide(e);
			},100);
		}

		//}}}

		//{{{ onShow

		function onShow(ev) {

			ev.type = "onShow";
			fire.trigger(ev);

			//快捷键处理
			$(document).bind("keydown.d", function(e) {

				if(e.ctrlKey) {
					return true;
				}
				var key = e.keyCode;

				
				if(e.target == input[0]) {//如果是在当前input按back键，清除值并隐藏日历
					if(key == 8 || key == 46) {
						input.val("");
						return self.hide(e);
					}
				}
				
				if(key == 27) {
					return self.hide(e);
				}
				//如果有time，则不要快捷键
				if(options.time) {
					return;
				}
				if($(KEYS).index(key) >= 0) {

					if(!opened) {
						self.show(e);
						return e.preventDefault();
					}

					var days = $("#" + css.weeks + " a"), el = $("." + css.focus), index = days.index(el);

					el.removeClass(css.focus);

					if(key == 74 || key == 40) {
						index += 7;
					} else if(key == 75 || key == 38) {
						index -= 7;
					} else if(key == 76 || key == 39) {
						index += 1;
					} else if(key == 72 || key == 37) {
						index -= 1;
					}

					if(index > 41) {
						self.addMonth();
						el = $("#" + css.weeks + " a:eq(" + (index - 42) + ")");
					} else if(index < 0) {
						self.addMonth(-1);
						el = $("#" + css.weeks + " a:eq(" + (index + 42) + ")");
					} else {
						el = days.eq(index);
					}

					el.addClass(css.focus);
					return e.preventDefault();

				}

				
				if(key == 34) {
					return self.addMonth();
				}
				if(key == 33) {
					return self.addMonth(-1);
				}

				
				if(key == 36) {
					return self.today();
				}

				
				if(key == 13) {
					if(!$(e.target).is("select")) {
						$("." + css.focus).dblclick();
					}
				}

				return $([16, 17, 18, 9]).index(key) >= 0;
			});

			
			/*$(document).bind("mousedown.d", function(e) {
				var el = e.target;

				if(!$(el).parents("#" + css.root).length && el != input[0] && (!trigger || el != trigger[0])) {
					self.hide(e);
				}

			});*/

			$(document.body).on("mousedown.d", function(e) {
				if(e.target !== input[0] && !$.contains(root[0],e.target)) {
					setTimeout(function(){
						self.hide();
					},100)
				}
			});
		}

		//}}}

		$.extend(self, {

			//{{{  show

			show : function(e) {
				if(input.prop("readonly") || input.prop("disabled") || opened) {
					return;
				}
				
				e = e || $.Event();
				e.type = "onBeforeShow";
				fire.trigger(e);
				if(e.isDefaultPrevented()) {
					return;
				}

				$.each(instances, function() {
					this.hide();
				});
				opened = true;

				
				monthSelector.unbind("change").change(function() {
					self.setValue(yearSelector.val(), $(this).val());
				});
				
				yearSelector.unbind("change").change(function() {
					self.setValue($(this).val(), monthSelector.val());
				});
				
				pm = root.find("#" + css.prev).unbind("click").click(function(e) {
					if(!pm.hasClass(css.disabled)) {
						self.addMonth(-1);
					}
					return false;
				});
				nm = root.find("#" + css.next).unbind("click").click(function(e) {
					if(!nm.hasClass(css.disabled)) {
						self.addMonth();
					}
					return false;
				});
				
				self.setValue(value);

				//是否显示时间选择
				if(options.time) {
					root.find('div.caltime').show();
				}else{
					root.find('div.caltime').hide();
				}

				
				var pos = input.offset();

				
				if(/iPad/i.test(navigator.userAgent)) {
					pos.top -= $(window).scrollTop();
				}
				var top = pos.top + input.outerHeight() + options.offset[0];
				if(top + root.height() > $(window).scrollTop() + $(window).height()) {
					top = pos.top - root.height();
				}
				root.css({
					top : top,
					left : pos.left + options.offset[1]
				});

				if(options.speed) {
					root.show(options.speed, function() {
						onShow(e);
					});
				} else {
					root.show();
					onShow(e);
				}

				return self;
			},
			//}}}

			//{{{  setValue

			setValue : function(year, month, day, hour, minute) {
				var date = integer(month) >= -1 ? new Date(integer(year), integer(month), integer(day || 1)) : year || value;

				if(date < min) {
					date = min;
				} else if(date > max) {
					date = max;
				}
				year = date.getFullYear();
				month = date.getMonth();
				day = date.getDate();
				hour = date.getHours();
				minute = date.getMinutes();
				
				if(month == -1) {
					month = 11;
					year--;
				} else if(month == 12) {
					month = 0;
					year++;
				}

				if(!opened) {
					select(date, options);
					return self;
				}
				currMonth = month;
				currYear = year;

				
				var tmp = new Date(year, month, 1 - options.firstDay), begin = tmp.getDay(), days = dayAm(year, month), prevDays = dayAm(year, month - 1), week;

				
				if(options.selectors) {

					
					monthSelector.empty();
					$.each(labels.months, function(i, m) {
						if(min < new Date(year, i + 1, -1) && max > new Date(year, i, 0)) {
							monthSelector.append($("<option/>").html(m).attr("value", i));
						}
					});
					
					yearSelector.empty();
					var yearNow = now.getFullYear();

					for(var i = yearNow + options.yearRange[0]; i < yearNow + options.yearRange[1]; i++) {
						if(min <= new Date(i + 1, -1, 1) && max > new Date(i, 0, 0)) {
							yearSelector.append($("<option/>").text(i));
						}
					}

					monthSelector.val(month);
					yearSelector.val(year);

					
				} else {
					title.html(labels.months[month] + " " + year);
				}

				
				weeks.empty();
				pm.add(nm).removeClass(css.disabled);

				
				for(var j = !begin ? -7 : 0, a, num; j < (!begin ? 35 : 42); j++) {
					a = $("<a/>");

					if(j % 7 === 0) {
						week = $("<div/>").addClass(css.week);
						weeks.append(week);
					}

					if(j < begin) {
						a.addClass(css.off);
						num = prevDays - begin + j + 1;
						date = new Date(year, month - 1, num);

					} else if(j >= begin + days) {
						a.addClass(css.off);
						num = j - days - begin + 1;
						date = new Date(year, month + 1, num);

					} else {
						num = j - begin + 1;
						date = new Date(year, month, num);

						
						if(isSameDay(value, date)) {
							a.attr("id", css.current).addClass(css.focus);

							
						} else if(isSameDay(now, date)) {
							a.attr("id", css.today);
						}
					}

					
					if(min && date < min) {
						a.add(pm).addClass(css.disabled);
					}

					if(max && date > max) {
						a.add(nm).addClass(css.disabled);
					}

					a.attr("href", "#" + num).text(num).data("date", date);

					week.append(a);

					if(options.selectors) {
						//console.log(year, month, day, hour, minute)
					}
				}

				//时间选择
				//!TODO:chaoren1641增加，有待重构
				if(options.time) {
					//如果有时间选项则点击确定或双击日期输入时间
					
					weeks.find("a").on('click',function(e) {
						var el = $(this);
						if(!el.hasClass(css.disabled)) {
							$("#" + css.current).removeAttr("id");
							el.attr("id", css.current);
						}
						return false;
					}).off('dblclick').dblclick(function(e) {
						var el = $(this);
						if(!el.hasClass(css.disabled)) {
							select(el.data("date"), options, e);
							//在IE下，重新赋值会引起focus，导致日历控件再次激活打开，所以setTimeout下
							setTimeout(function() {
								self.hide();
							},100);
						}
						return false;
					});

					var body = root.find("#" + css.body);
					body.find('button').off('click.d').on('click.d',function(e) {
						var el = root.find('#' + css.current);
						select(el.data("date"), options, e);
						//在IE下，重新赋值会引起focus，导致日历控件再次激活打开，所以setTimeout下
						setTimeout(function() {
							self.hide();
						},100);
						return false;
					});
					body.find('#calHour').val(hour);
					body.find('#calMin').val(minute);
				}else{
					
					weeks.find("a").click(function(e) {
						var el = $(this);
						if(!el.hasClass(css.disabled)) {
							$("#" + css.current).removeAttr("id");
							el.attr("id", css.current);
							select(el.data("date"), options, e);
						}
						return false;
					})
				}
				
				if(css.sunday) {
					weeks.find(css.week).each(function() {
						var beg = options.firstDay ? 7 - options.firstDay : 0;
						$(this).children().slice(beg, beg + 1).addClass(css.sunday);
					});
				}

				return self;
			},
			//}}}

			setMin : function(val, fit) {
				min = parseDate(val);
				if(fit && value < min) {
					self.setValue(min);
				}
				return self;
			},
			setMax : function(val, fit) {
				max = parseDate(val);
				if(fit && value > max) {
					self.setValue(max);
				}
				return self;
			},
			today : function() {
				return self.setValue(now);
			},
			addDay : function(amount) {
				return this.setValue(currYear, currMonth, currDay + (amount || 1));
			},
			addMonth : function(amount) {
				return this.setValue(currYear, currMonth + (amount || 1), currDay);
			},
			addYear : function(amount) {
				return this.setValue(currYear + (amount || 1), currMonth, currDay);
			},
			hide : function(e) {
				if(opened) {
					
					e = $.Event();
					e.type = "onHide";
					fire.trigger(e);

					$(document).unbind("click.d").unbind("keydown.d");

					
					if(e.isDefaultPrevented()) {
						return;
					}

					
					root.hide();
					opened = false;
				}

				return self;
			},
			getConf : function() {
				return options;
			},
			getInput : function() {
				return input;
			},
			getCalendar : function() {
				return root;
			},
			getValue : function(dateFormat) {
				return dateFormat ? format(value, dateFormat, options.lang) : value;
			},
			isOpen : function() {
				return opened;
			}
		});

		
		$.each(['onBeforeShow', 'onShow', 'select', 'onHide'], function(i, name) {

			
			if($.isFunction(options[name])) {
				$(self).bind(name, options[name]);
			}

			
			self[name] = function(fn) {
				if(fn) {
					$(self).bind(name, fn);
				}
				return self;
			};
		});
		
		input.bind("focus click", self.show).keydown(function(e) {
			var key = e.keyCode;

			
			if(!opened && $(KEYS).index(key) >= 0) {
				self.show(e);
				return e.preventDefault();
			}

			
			return e.shiftKey || e.ctrlKey || e.altKey || key == 9 ? true : e.preventDefault();

		});
		
		if(parseDate(input.val())) {
			//!TODO关闭初始化的值，这里会出现BUG
			//select(value, options);
		}
	};

	$.expr[':'].date = function(el) {
		var type = el.getAttribute("type");
		return type && type == 'date' || !!$(el).data("dateinput");
	};


	$.fn[pluginName] = function(options) {
		Wind.css('datePicker');
		return this.each(function() {
			if(!$.data(this, 'plugin_' + pluginName)) {
				var instance = new Plugin($(this), options);
				instances.push(instance);
				$.data(this, 'plugin_' + pluginName, instance);
			}
		});
	};

	/*$.fn.dateinput = function(conf) {


		
		if(this.data("dateinput")) {
			return this;
		}

		
		conf = $.extend(true, {}, tool.conf, conf);

		
		$.each(conf.css, function(key, val) {
			if(!val && key != 'prefix') {
				conf.css[key] = (conf.css.prefix || '') + (val || key);
			}
		});
		var els;

		this.each(function() {
			var el = new Dateinput($(this), conf);
			instances.push(el);
			var input = el.getInput().data("dateinput", el);
			els = els ? els.add(input) : input;
		});
		return els ? els : this;
	};*/


})(jQuery, window ,document);
