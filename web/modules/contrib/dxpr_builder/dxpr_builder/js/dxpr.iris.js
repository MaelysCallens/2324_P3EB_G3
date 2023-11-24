! function(t, e) {
    var o, i = '<a tabindex="0" class="wp-color-result" />',
        r = '<div class="wp-picker-holder" />',
        n = '<div class="wp-picker-container" />',
        l = '<input type="button" class="button button-small hidden" />';
    o = {
        options: {
            defaultColor: !1,
            change: !1,
            clear: !1,
            hide: !0,
            palettes: !0
        },
        _create: function() {
            if (t.support.iris) {
                var e = [];
                for (var o in drupalSettings.dxprBuilder.palette) e.push(drupalSettings.dxprBuilder.palette[o]);
                var a = this,
                    s = a.element;
                t.extend(a.options, s.data()), a.initialValue = s.val(), s.addClass("wp-color-picker").hide().wrap(n), a.wrap = s.parent(), a.toggler = t(i).insertBefore(s).css({
                    backgroundColor: a.initialValue
                }).attr("title", wpColorPickerL10n.pick), a.pickerContainer = t(r).insertAfter(s), a.button = t(l), a.options.defaultColor ? a.button.addClass("wp-picker-default").val(wpColorPickerL10n.defaultString) : a.button.addClass("wp-picker-clear").val(wpColorPickerL10n.clear), s.wrap('<span class="wp-picker-input-wrap hidden" />').after(a.button), s.iris({
                    target: a.pickerContainer,
                    hide: !0,
                    width: 255,
                    mode: "hsl",
                    palettes: e,
                    change: function(e, o) {
                        a.toggler.css({
                            backgroundColor: o.color.toCSS('rgba')
                        }), t.isFunction(a.options.change) && a.options.change.call(this, e, o)
                    }
                }), s.val(a.initialValue), a._addListeners(), a.options.hide || a.toggler.click()

                const colorResultToggler = this.toggler[0];
                const parent = colorResultToggler.parentNode;
                const wrapper = document.createElement('div');
                wrapper.className = 'color-result-wrapper';
                // set the wrapper as child (instead of the `colorResultToggler`)
                parent.replaceChild(wrapper, colorResultToggler);
                // set `colorResultToggler` as child of wrapper
                wrapper.appendChild(colorResultToggler);
            }
        },
        _addListeners: function() {
            var e = this;
            e.toggler.click(function(o) {
                
                const wpPickerInputWrap = e.wrap[0].querySelector('.wp-picker-input-wrap');
                wpPickerInputWrap.classList.toggle('hidden');

                o.stopPropagation(), e.element.toggle().iris("toggle"), e.button.toggleClass("hidden"), e.toggler.toggleClass("wp-picker-open"), e.toggler.hasClass("wp-picker-open") ? t("body").on("click", {
                    wrap: e.wrap,
                    toggler: e.toggler
                }, e._bodyListener) : t("body").off("click", e._bodyListener)
            }), e.element.change(function(o) {
                var i = t(this),
                    r = i.val();
                ("" === r || "#" === r) && (e.toggler.css("backgroundColor", ""), t.isFunction(e.options.clear) && e.options.clear.call(this, o))
            }), e.toggler.on("keyup", function(t) {
                (13 === t.keyCode || 32 === t.keyCode) && (t.preventDefault(), e.toggler.trigger("click").next().focus())
            }), e.button.click(function(o) {
                var i = t(this);
                i.hasClass("wp-picker-clear") ? (e.element.val(""), e.toggler.css("backgroundColor", ""), t.isFunction(e.options.clear) && e.options.clear.call(this, o)) : i.hasClass("wp-picker-default") && e.element.val(e.options.defaultColor).change()
            })
        },
        _bodyListener: function(t) {
            t.data.wrap.find(t.target).length || t.data.toggler.click()
        },
        color: function(t) {
            return t === e ? this.element.iris("option", "color") : void this.element.iris("option", "color", t)
        },
        defaultColor: function(t) {
            return t === e ? this.options.defaultColor : void(this.options.defaultColor = t)
        }
    }, t.widget("wp.wpColorPicker", o)
}(jQuery);