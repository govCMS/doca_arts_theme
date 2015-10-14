module.exports.register = function(handlebars) {
	/**
	 * Equivalent to "if the current reference is X". e.g:
	 *
	 * {{#ifReference 'base.headings'}}
	 * 	 REFERENCE base.headings ONLY
	 * 	{{else}}
	 * 	 ANYTHING ELSE
	 * {{/ifReference}}
	 */
	handlebars.registerHelper('ifReference', function(ref, options) {
		return (this.reference && ref == this.reference) ? options.fn(this) : options.inverse(this);
	});
};
