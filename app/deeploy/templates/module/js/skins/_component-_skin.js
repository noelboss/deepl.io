(function($) {
	"use strict";
	/**
	 * {{skin}} skin implementation for the {{component}} module.
	 *
	 * @author {{user}} <{{email}}>
	 * @namespace Tc.Module.{{component-js}}
	 * @class {{skin-js}}
	 * @extends Tc.Module
	 */
	Tc.Module.{{component-js}}.{{skin-js}} = function(parent) {

		this.on = function(callback) {
			var mod = this,
				$ctx = mod.$ctx;



			parent.on(callback);
		};

		this.after = function() {
			var mod = this,
				$ctx = mod.$ctx;



			parent.after();
		};

	};
}(Tc.$));
