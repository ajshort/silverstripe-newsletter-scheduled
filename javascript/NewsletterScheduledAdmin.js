;(function($) {
	$("#Form_EditForm_IsScheduled").livequery(function() {
		$(this)
			.change(function() { $("#ScheduledTime").toggle(this.checked); })
			.trigger("change");
	});
})(jQuery);
