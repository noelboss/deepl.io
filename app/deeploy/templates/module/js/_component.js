(function($) {
	"use strict";
	/**
	 * {{component}} module implementation
	 *
	 * @author {{user}} <{{email}}>
	 * @namespace Tc.Module
	 * @class {{component-js}}
	 * @extends Tc.Module
	 */
	Tc.Module.{{component-js}} = Tc.Module.extend({

		init: function($ctx, sandbox, modId) {
			this._super($ctx, sandbox, modId);



		},

		on: function(callback) {
			var mod = this,
				$ctx = mod.$ctx;



			callback();
		},

		after: function() {
			var mod = this,
				$ctx = mod.$ctx;


		}

	});
}(Tc.$));
