Window.implement({
	$new: function(selector, opts) {
		if (typeof opts == 'string'){
			var tag = selector.match(/^([\w-]*)(?=[#.[].*)/)[0];
			if(tag.toLowerCase() == 'input') opts = {value: opts};
			else opts = {html: opts};
		}
		return new Element(selector || 'div', opts);
	}
});
Element.implement({
	//·ÂjQueryµÄheight()/width();
	Height: function(val) {
		return this.Size("height", val);
	},
	Width: function(val) {
		return this.Size("width", val);
	},
	Size: function(XY, val) {
		if (!XY) return {
			height:this.Size("height"),
			width:this.Size("width"),
			x:this.Size('width'),
			y:this.Size('height')
		};
		if (typeOf(XY) == "object") return this.setStyles(XY);
		if (val) return this.setStyle(XY, val);
		var getXY = function(){
			return this.getStyle(XY).toInt() || 0;
		}.bind(this);
		return this.measure(getXY);
	},
	//mootools more
	measure: function(fn){
		var visibility = function(el){
			return !!(!el || el.offsetHeight || el.offsetWidth);
		};
		if (visibility(this)) return fn.apply(this);
		var parent = this.getParent(),
			toMeasure = [];
		while (!visibility(parent) && parent != document.body){
			toMeasure.push(parent.expose());
			parent = parent.getParent();
		}
		var restore = this.expose();
		var result = fn.apply(this);
		restore();
		toMeasure.each(function(restore){
			restore();
		});
		return result;
	},
	expose: function(){
		if (this.getStyle('display') != 'none') return function(){};
		var before = this.style.cssText;
		this.setStyles({
			display: 'block',
			position: 'absolute',
			visibility: 'hidden'
		});
		return function(){
			this.style.cssText = before;
		}.bind(this);
	},
	//unwrap the element
	unwrap: function() {
		var parent = this.getParent();
		parent.getChildren().inject(parent, 'before');
		parent.dispose();
		return this;
	},
	//this faster than innerHTML
	replaceHTML: function(html) {
		if(!+'\v1'){ // Pure innerHTML is slightly faster in IE
			this.innerHTML = html;
			return this;
		}
		var newEl = this.cloneNode(false);
		newEl.innerHTML = html;
		this.parentNode.replaceChild(newEl, this);
		/* Since we just removed the old element from the DOM, return a reference
		to the new element, which can be used to restore variable references. */
		return newEl;
	}
});
