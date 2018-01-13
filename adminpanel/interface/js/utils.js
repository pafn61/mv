$(document).ready(function()
{
	//Sends the ajax request to continue the session
	function keepSessionAlive()
	{
		$.ajax({
			type: "POST",
			data: "continue=1",
			url: mVobject.adminPanelPath + "ajax/session.php"
		});
	}
	
	//And then we run the script every few minutes
	setInterval(keepSessionAlive, 1000 * 60 * 10);

	$("form.model-elements-form td.field-content:has(:checkbox) div.help-text").css({display:"inline", padding: "0 3px"});	
	$("input.form-date-time-field").datetimepicker({timeFormat: 'hh:mm', dateFormat: mVobject.dateFormat});
	$("input.form-date-field").datepicker({dateFormat: mVobject.dateFormat});	
	$("td.not-editable-field").find(":checkbox, :text, :password, :file, textarea, select").attr("disabled", "disabled");
	$("td.not-editable-field").find("div.upload-buttons").empty();
	$("td.not-editable-field:has(div.upload-buttons)").find("div.controls").remove();	
	
	//Moves the items in many to many lists
	$("div.m2m-buttons span.m2m-left, div.m2m-buttons span.m2m-right").click(function()
	{
		var from = ".m2m-not-selected";
		var to = ".m2m-selected";
		
		if(this.className == "m2m-left")
		{
			from = ".m2m-selected";
			to = ".m2m-not-selected";			
		}
		
		$(this).parents("div.m2m-wrapper")
			   .find("select" + from + " option:selected")
			   .each(function()
				{				   
				   $(this).removeAttr("selected")
			    	       .parents("div.m2m-wrapper")
			    	       .find("select" + to)
			    	       .append($(this));
				});
		
		$(this).parents("div.m2m-wrapper").find("input[type='hidden']").val("");
		
		$(this).parents("div.m2m-wrapper").find("select.m2m-selected option, div.search-buffer option").each(function()
		{
			var value = $(this).parents("div.m2m-wrapper").find("input[type='hidden']").val();
			
			value = value ? (value + "," + this.value) : this.value;
			$(this).parents("div.m2m-wrapper").find("input[type='hidden']").val(value);
		});
	});
	
	//Changes the order of table columns by moving the options in select
	$("div.m2m-buttons span.m2m-up, div.m2m-buttons span.m2m-down").click(function()
	{
		var option = $(this).parent().prev().find("option:selected:first");
		
		if(this.className == "m2m-up")
			option.insertBefore(option.prev());
		else
			option.insertAfter(option.next());
			
		if($(this).parents("div.m2m-wrapper").hasClass("group-datatype") || 
		   $(this).parents("div.m2m-wrapper").hasClass("csv-fields"))
		{
			var values = [];
			
			$(this).parents("div.m2m-wrapper").find("select.m2m-selected option, div.search-buffer option").each(function()
			{
				values.push(this.value);
			});
		}
		
		$(this).parents("div.m2m-wrapper").find("input[type='hidden']").val(values.join(","));
	});
	
	//Deletes the file and shows back the file input
	$("div.file-input").each(function()
	{
		$(this).find("span[class='delete']").click(function()
		{
			if($(this).parents("td.field-content").hasClass("not-editable-field"))
				return;
			
			var _this_ = this;
			$(this).parent().fadeOut(300, function()
			{	
				$(_this_).parent().next().removeClass("no-display");
				$(_this_).parent().remove();
			}); 
		});
	});
	
	//Deletes the image and shows back the file input
	$("div.image-input").each(function()
	{		
		$(this).find("span").click(function()
		{
			if($(this).parents("td.field-content").hasClass("not-editable-field"))
				return;
			
			var _this_ = this;
			$(this).parents("div.image-input").fadeOut(300, function()
			{	
				$(_this_).parents("div.image-input").next().removeClass("no-display");
				$(_this_).parents("div.image-input").remove();
			}); 
		});
	});
	
	//Common function fir adding uploaded image into multi images area
	function addUploadedMultiImage(element_area_id, data)
	{
		var image = "<div class=\"images-wrapper\"><div class=\"controls\" id=\"" + data.image + "\">"; 
		image += "<span class=\"first\" title=\"" + mVobject.locale("move_first") + "\"></span> ";
		image += "<span class=\"left\" title=\"" + mVobject.locale("move_left") + "\"></span>";
		image += "<span class=\"right\" title=\"" + mVobject.locale("move_right") + "\"></span> ";
		image += "<span class=\"last\" title=\"" + mVobject.locale("move_last") + "\"></span>";
		image += "<span class=\"comment\" title=\"" + mVobject.locale("add_edit_comment") + "\"></span>";
		image += "<span class=\"delete\" title=\"" + mVobject.locale("delete_item") + "\"></span></div>";
		
		var big_image = data.small_image.replace("admin_multi/", "");
		
		image += "<a href=\"" + big_image + "\" target=\"_blank\"><img src=\"" + data.small_image + "\" /></a></div>";

		$(element_area_id).find("p.no-images").remove();
		$(element_area_id + " div.uploaded-images").append(image);		
		$(element_area_id + " div.uploaded-images").find("div.images-wrapper:last").hide().fadeIn(400);		
    	$(element_area_id + " input[type='hidden']").val(data.input_value);
	}
	
	//Uploads one image to the server and adds it into the list
	$("div.images-area input:file[class!='multiple-upload']").live("change", function()
	{	
		var file_input_id = $(this).attr("id");
		var element_area_id = "#" + $(this).parents("div.images-area").attr("id");		
		var real_action = $('form.model-elements-form').attr("action");
		
		$('form.model-elements-form').attr("action", mVobject.adminPanelPath + "ajax/upload-images.php");		
		$(element_area_id + " div.images-error").remove();

		$(this).parents(element_area_id).find("div.upload-one div.loading").addClass("small-loader");
		
		$('form.model-elements-form').ajaxSubmit(
		{
			dataType: 'json',
			data: {model: $('form.model-elements-form').attr('id')},
			success: function(data)
			{
				if(!data)
				{
					location.reload();
					return;
				}
				
				if(data && data.error)
        		{
        			$(element_area_id).prepend("<div class=\"images-error\"><p>" + data.error + "</p></div>");
        			$(element_area_id + " div.images-error").hide().fadeIn(500);
        		}
        		else if(data)
        			addUploadedMultiImage(element_area_id, data);
				
				$(element_area_id).find("div.upload-one div.loading").remove();
				$("#" + file_input_id).remove();
				$(element_area_id + " div.upload-one").append("<input type=\"file\" name=\"" + file_input_id + "\" id=\"" + file_input_id + "\">");
				$(element_area_id + " div.upload-one").append("<div class=\"loading\"></div>");
			}
		});
		
		$('form.model-elements-form').attr('action', real_action);
	});
	
	//Multiple image upload using uploadify
	$("div.images-area").each(function()
	{
		if($(this).parent().hasClass("not-editable-field") || $.fn.uploadify == undefined)
			return;
	
		var this_ = this;
		var model = $('form.model-elements-form').attr('id'); 
		var field = $(this).find("div.upload-many input").attr("id").replace(/-upload$/, "");
		var name = "multi-images-" + field;
		var check_code = $(this).find("div.upload-many").attr("id").replace(/.*-([^-]+)$/, "$1");
		var not_uploaded_files = [];
		var errors_messages = [];
		var upload_input = $(this).find("input.multiple-upload");
		
		setTimeout(function () {
			upload_input.uploadify({
	        	swf              : mVobject.adminPanelPath + 'interface/extra/uploadify.swf',
	        	uploader         : mVobject.adminPanelPath + 'ajax/uploadify.php',
	        	width            : 248,
	        	height           : 22,
	        	buttonText	     : "",
	        	buttonClass      : "upload-button",
	        	formData         : {model: model, code: check_code, field: field},
	        	fileObjName      : name,
	        	onDialogClose    : function(queue_data)
	        					   {
	        							if(queue_data.filesQueued)
	        								$(this_).find("div.stop-upload").addClass("small-loader");
	        							
	        							$(this_).find("div.images-error").remove();
	        							not_uploaded_files = [];
	        							errors_messages = [];
	        					   },
	        					   
	        	onUploadSuccess  : function(file, data, response)
	        					   {
	        							data = data ? $.parseJSON(data) : false;
	        							
	        							if(!data)
	        							{
	        								location.reload();
	        								return;
	        							}
	        							
						        		if(data && data.error)
						        		{
						        			if($.inArray(data.error, errors_messages) == -1)
						        				errors_messages.push(data.error);
	
						        			not_uploaded_files.push(file.name);					        			
						        		}
						        		else if(data && response)
	        							{
	        								var new_data = $(this_).find("input[name='" + field + "']").val();
	        								new_data = new_data ? (new_data + "-*//*-") : ""; 
	        								data.input_value = new_data + data.image;;        								
	        								addUploadedMultiImage("#" + this_.id, data);
	        							}        							
	       				           },
	       				           
	       		onQueueComplete  : function()
	       		                   {
	       							    $(this_).find("div.stop-upload").removeClass("small-loader");
	       							    
	       							    if(not_uploaded_files.length)
	       							    {
	       							    	var error_files = "<p>" + mVobject.locale("not_uploaded_files");
	       							    	error_files += ": " + not_uploaded_files.join(", ") + ".</p>";
	       							    	
	       							    	if(errors_messages.length)
	       							    		error_files += "<p>" + errors_messages.join("</p><p>") + "</p>";
	       							    	
	       							    	$(this_).prepend("<div class=\"images-error\">" + error_files + "</div>");
	       							    }
	       						   }
			});
		}, 0);
		
		$(this).find("div.stop-upload input").click(function()
		{
			upload_input.uploadify("stop");
			upload_input.uploadify('cancel', '*');			
		});
	});
		
	//Deletes one image from list of multi_images
	$("div.images-wrapper span.delete").live("click", function()
	{
		$(this).parents("div.images-wrapper").fadeOut(300, function()
		{
			var image = $(this).find("div.controls").attr("id");
			var values = $(this).parents("div.images-area").find("input[type='hidden']").val();
			var new_values = [];

			values = values.split("-*//*-");
			
			for(var i = 0; i < values.length; i ++)
				if(values[i].replace(/\(\*.*\*\)$/, '') != image)
					new_values.push(values[i])

			$(this).parents("div.images-area").find("input[type='hidden']").val(new_values.join("-*//*-"));
			
			if(!$(this).parents("div.images-area").find("input[type='hidden']").val())
				$(this).parents("div.images-area").prepend("<p class=\"no-images\">" + mVobject.locale("no_images") + "</p>");
			
			$(this).remove();
		});
	});
	
	//Opens dialog modal to add a comment for multi image
	$("div.images-wrapper span.comment").live("click", function()
	{
		dialogs.showConfirmMessage("{add_image_comment}", "", "");
		
		var id = $(this).parent().attr("id");
		var comment = $(this).parent().next().find("img").attr("title");
		comment = comment ? $.trim(comment) : "";
		
		var html = "<textarea id=\"image-comment-text\" name=\"" + id + "\">" + comment + "</textarea>";
		$("#message-confirm-delete div.message").append(html);		
		$("#message-confirm-delete #butt-ok").attr("name", "comment->" + id);
	});
	
	//Changes the multi_images input value according to the positions of images in list 
	function refreshImagesOrder(object)
	{
		var result = [];

		$(object).parents("div.uploaded-images").find("div.images-wrapper").each(function(index, element)
		{
			var comment = $(element).find("div.controls").next().find("img").attr("title");
			comment = comment ? "(*" + comment + "*)" : "";
			var image = $(element).find("div.controls").attr("id") + comment;
			
			result.push(image);
		});
		
		result = result.join("-*//*-");
		$(object).parents("div.images-area").find("input[type='hidden']").val(result);
	}
	
	//Moves image to the first position in list
	$("div.images-wrapper span.first").live("click", function()
	{
		var element = $(this).parents("div.images-wrapper");
		var index = element.index();
		
		if(parseInt(index) == 0) return;
		
		var first_element = $(this).parents("div.uploaded-images")
								   .find("div.images-wrapper:first");
		
		element.insertBefore(first_element);
		refreshImagesOrder(this);
	});
	
	//Moves image to the one position upper
	$("div.images-wrapper span.left").live("click", function()
	{
		var element = $(this).parents("div.images-wrapper");
		var index = element.index();
		var prev_element = $(this).parents("div.uploaded-images")
								  .find("div.images-wrapper:eq(" + (index - 1) +")");
		
		if(parseInt(index))
			element.insertBefore(prev_element);
		
		refreshImagesOrder(this);
	});
	
	//Moves image to the one position lower
	$("div.images-wrapper span.right").live("click", function()
	{
		var element = $(this).parents("div.images-wrapper");
		var size = $(this).parents("div.uploaded-images").find("div.images-wrapper").size() - 1;
		var index = element.index();
		var next_element = $(this).parents("div.uploaded-images")
		  						  .find("div.images-wrapper:eq(" + (index + 1) +")");
		
		if(index < size)
			element.insertAfter(next_element);
		
		refreshImagesOrder(this);
	});
	
	//Moves image to the last position in list
	$("div.images-wrapper span.last").live("click", function()
	{	
		var element = $(this).parents("div.images-wrapper");
		var size = $(this).parents("div.uploaded-images").find("div.images-wrapper").size() - 1;
		var index = element.index();
		
		if(index >= size) return;
		
		var last_element = $(this).parents("div.uploaded-images")
		  						  .find("div.images-wrapper:last");
		
		element.insertAfter(last_element);		
		refreshImagesOrder(this);
	});
	
	//If multi images field is not editable we hide control buttons
	$("div.images-area").each(function()
	{
		if($(this).find("input[disabled='disabled']").size())
			$(this).find("div.controls, input[type='file']").remove().end().find("div.upload-here").text("");
	});
	
	//Operates the filter's parameters if we have parent field
	var paramsKeeper = {normal: "", filter: ""};
	
	$("#admin-filters :checkbox").click(function()
	{
		var params = $("#admin-filters input[name='initial-form-params']").val();
		
		if(!paramsKeeper.normal)
			paramsKeeper.normal = params;
		
		if(!paramsKeeper.filter)
		{
			var parent_field = this.name.replace(/^parent-/, '');
			var re = new RegExp(parent_field + '=-?[0-9]+');
			paramsKeeper.filter = params.replace(re, '');
		}
		
		params = $(this).is(":checked") ? paramsKeeper.filter : paramsKeeper.normal;
		$("#admin-filters input[name='initial-form-params']").val(params);
	});
	
	//Filters form processing
	$("#filters-submit").click(function()
	{		
		var values = $("#admin-filters").find(":input, select, :checkbox:checked").serializeArray();
		var filled_values = [];
		
		$.each(values, function(key, val) //Collects only filled fields of form
		{
			if($.trim(val.value))
			{
				if(val.name != "initial-form-params")
					val.value = encodeURIComponent($.trim(val.value));

				filled_values.push(val.name + "=" + val.value);
			}
		});
		
		filled_values = filled_values.join("&").replace("initial-form-params=", "");
		filled_values = filled_values.replace("+", "%2B");
		var path = document.location.href.replace(/\?.*$/, "");
		document.location = path + "?" + filled_values;
		
		return false;
	});
	
	//Reset the filters form and reload the page
	$("#filters-reset").click(function()
	{
		$("#admin-filters input:text").val("");
		$("#admin-filters select option").removeAttr("selected");
		
		var params = $("input[name='initial-form-params']").val();
		var path = document.location.href.replace(/\?.*$/, "");
		document.location = path + "?" + params;
	});
	
	//Submits the search form when press 'enter' in the text field
	$("#admin-filters input:text").live("keyup", function(e)
	{
		if(e.keyCode == 13 && !$(this).hasClass("autocomplete-input"))
			$("#filters-submit").trigger("click");
	});
	
	//Disabled long list select of mode in filters column
	$("#admin-filters div.long-list-select select").each(function()
	{
		if($(this).val())
			$(this).parent().next().val("").attr("disabled", "disabled");
	});
	
	//Changes the mode of long list filter in admin panel
	$("#admin-filters div.long-list-select select").live("change", function()
	{
		var value = $.trim($(this).val());
		
		if(!value || value == "-" || value == "*")
			$(this).parent().next().val("").next().val(value);
		
		if(value)
			$(this).parent().next().attr("disabled", "disabled");
		else
			$(this).parent().next().removeAttr("disabled");
	});
	
	//Add new filter in list
	$("#add-filter").change(function()
	{
		var name = $(this).val();
		var select = this;
		
		if(name)
		{
			var model = $(this).parent().attr("id");
				
			$.ajax({
				type: "POST",
				dataType: "html",
				url: mVobject.adminPanelPath + "ajax/filters.php",
				data: "model=" + model.replace(/^model-/, "") + "&add-filter=" + name,
				success: function(data)
				{
					if(!data)
					{
						location.reload();
						return;
					}
					
					$(data).insertBefore("#admin-filters div.manage-filters");
					$(select).find("option[value='" + name + "']").appendTo("#remove-filter");
					$("#remove-filter option").removeAttr("selected");
					$("#remove-filter option[value='']").attr("selected", "selected");
					$("input.form-date-time-field").datetimepicker({timeFormat: 'hh:mm', dateFormat: mVobject.dateFormat});
					$("input.form-date-field").datepicker({dateFormat: mVobject.dateFormat});
					
					$("#admin-filters input.autocomplete-input").each(function(index, element)
		  		  	{
						runAutocomplete(element);
		  		  	});
				}
			});
		}		
	});
	
	//Remove one filter from list
	$("#remove-filter").change(function()
	{
		var name = $(this).val();
		
		if(name)
		{
			$("#filter-" + name).next().fadeOut(300, function(){ $(this).remove(); });
			$("#filter-" + name).fadeOut(300, function(){ $(this).remove(); });
			
			$(this).find("option[value='" + name + "']").appendTo("#add-filter");
			$("#add-filter option").removeAttr("selected");
			$("#add-filter option[value='']").attr("selected", "selected");
		}
	});
	
	//Change the visibility of model filters in session
	function changeFilterVisibility(value)
	{
		var params = "model=" + mVobject.currentModel + "&show-filters=" + (value ? "1" : "0");
				
		$.ajax({
			type: "POST",
			dataType: "text",
			url: mVobject.adminPanelPath + "ajax/filters.php",
			data: params
		});		
	}
	
	//Hides filters list in admin panel 
	$(document).on("click", "#hide-filters", function()
	{
		$("#model-filters").addClass("no-display");
		$("#show-filters").removeClass("no-display");
		$("#model-table-wrapper").addClass("hidden-filters");
		
		changeFilterVisibility(false);
	});
	
	//Shows the filters column
	$(document).on("click", "#show-filters", function()
	{
		$("#model-table-wrapper").removeClass("hidden-filters");
		$("#model-filters").removeClass("no-display");
		$("#show-filters").addClass("no-display");
		changeFilterVisibility(true);
	});
	
	//Changes the limit of records per page
	$("div.pager-limit select").live("change", function()
	{
		var params = $(this).parent().find("input").val();
		var href = mVobject.adminPanelPath;
		
		if(params == 'filemanager')
			href += "controls/filemanager.php?pager-limit=" + this.value;
		else if(params.match(/search\.php/))
			href += params + "&pager-limit=" + this.value;
		else
			href += "model/?" + params + "&pager-limit=" + this.value;
		
		location.href = href;
	});
	
	//Shows or hides multi actions menu
	$("div.multi-actions-menu input.button-list").click(function()
	{
		if($("table.model-table tr:gt(0) :checkbox:checked").size())
			$(this).parent().find("ul").css("width", $(this).outerWidth() - 2).toggle();
		else
			$(this).parent().find("ul").hide();
		
		//Moves bottom multi actions menu
		if($(this).parent().prop("id") == "bottom-actions-menu")
		{
			var menu = $("#bottom-actions-menu ul");
			menu.css("top", (menu.outerHeight() * -1) + "px");
		}
		
		return false; //Stops event to go upper
	});
			
	//Adds scroll bar if it's many multi actions in menu
	if($("div.multi-actions-menu").size() && $("div.multi-actions-menu:first ul").height() >= 280)
		$("div.multi-actions-menu ul").addClass("vertical-scroll");
	
	//Starts multi action dialog
	$("div.multi-actions-menu ul li").click(function()
	{
		var css_class = $(this).attr("class");
		
		if(css_class == "has-no-rights" || css_class == "has-no-rights-0" || css_class == "has-no-rights-1")
		{
			dialogs.showAlertMessage('{no_rights}');
			return;
		}
		
		var multi_action = css_class.replace("multi-", "");
		var multi_value = "";
		
		if(multi_action.match(/-(0|1)$/))
		{
			multi_value = multi_action.replace(/.*-(0|1)$/, '$1');
			multi_action = multi_action.replace(/-(0|1)$/, '');
		}
		else if(multi_action.match(/-(add|remove)$/))
		{
			multi_value = multi_action.replace(/.*-(add|remove)$/, '$1');
			multi_action = multi_action.replace(/-(add|remove)$/, '');
		}
		else if(multi_action != 'delete' && multi_action != 'restore')
			multi_value = 0;
		else
			multi_value = 1;
		
		dialogs.showMultiActionMessage(multi_action, multi_value);
	});
	
	//Quick edit of model table number and string fields
	if($("#model-table-form table tr").size() > 1 && $("#model-table-form table tr.no-hover").size() == 0 && 
	   $("#model-table-form table td[id^='quick-edit-']").size())
		$("input.mass-quick-edit").click(function()
		{
			$.ajax({
				type: "POST",
				data: "check=1",
				url: mVobject.adminPanelPath + "ajax/session.php",
				success: function(data)
				{	
					if(!data)
						location.reload();
				}
			});
			
			var pager_limit = parseInt($(this).attr("id").replace("quick-limit-", ""));
			
			if(pager_limit > 0)
			{
				dialogs.showAlertMessage('{quick_edit_limit}', {number: pager_limit});
				return;
			}
			
			$("div.form-no-errors, div.form-errors").remove();
			
			//Wraps table values into inputs in order to edit them
			$("#model-table-form td.edit-string, #model-table-form td.edit-number").each(function()
			{
				var value = $.trim($(this).html());
				
				if($(this).hasClass("edit-number"))
					value = value.replace(",", ".").replace(/\s/gi, "");
				else
				{
					value = (value == "-") ? "" : value;
					
					if(value.indexOf("<a ") == 0 && value.indexOf("</a>") != -1)
						value = value.replace(/<\/?[^>]+>/gi, "");
					
					value = value.replace(/"/gi, "&quot;");
				}
							
				$(this).html('<input type="text" name="' + this.id + '" value="' + value + '" />');			
			});
			
			$(this).remove();
			$("input.cancel-quick-edit, input.save-quick-edit").show();			
			
			//Extra buttons if we have long list of records on our page
			if($("table.model-table tr").size() > 11)
			{
				var extra_buttons = $("input.cancel-quick-edit, input.save-quick-edit").clone();
				extra_buttons.appendTo($("#bottom-actions-menu"));
			}
			
			$("input.cancel-quick-edit").click(function() { location.reload(); });
			
			$("input.save-quick-edit").click(function() //Save all values
			{
				var params = "model=" + mVobject.currentModel + "&" + $("#model-table-form").serialize();
				
				$.ajax({
					type: "POST",
					dataType: "json",
					url: mVobject.adminPanelPath + "ajax/quick-edit.php",
					data: params,
					success: function(data)
					{	
						if(!data || (data.updated && !data.general_errors && !data.wrong_fields))
							location.reload();
						
						if(data.general_errors) //Top error message
						{
							$("div.form-errors").remove();
							var message = '<div class="form-errors">' + data.general_errors + '</div>';
							$(message).insertAfter("#model-table h3.column-header").hide().fadeIn(400);
						}
						
						$("#model-table-form td").removeClass("quick-error-value");
						
						if(data.wrong_fields) //Marks inputs with wrong values
							$(data.wrong_fields).addClass("quick-error-value");
					}
				});
		   });
	});
		
	//Empty recycle bin button
	$("#empty-recycle-bin").on("click", function()
	{
		if($(this).hasClass("has-no-rights"))
		{
			dialogs.showAlertMessage('{no_rights}');
			return;
		}
		
		$.ajax({
			type: "POST",
			dataType: "text",
			url: mVobject.adminPanelPath + "ajax/multi-actions.php",
			data: "empty-recycle-bin=count",
			success: function(data)
			{	
				if(!$.trim(data))
				{
					if($("table.model-table :checkbox").size() > 1)
						location.reload();
					
					return;
				}
				
				var message = mVobject.locale("delete_many", {number_records: data});
				recycleBinRecordsCount = parseInt(data);
				$("#message-confirm-delete div.message").removeClass("update").addClass("delete").html(message);
				$("#message-confirm-delete").overlay(mVobject.paramsForDialogs).load();
				$("#message-confirm-delete #butt-ok").attr("name", "method->emptyRecycleBin");
			}
		});
	});
	
	//Highlights the row
	$("table.model-table tr:gt(0), #rights-table tr").live("mouseover", function()
	{
		$(this).removeClass("moved-line");
		
		if(!$(this).hasClass("no-hover"))
			$(this).addClass("active-line"); 	
	});
	
	//Moves out the hightlight form table row if it's not cahecked by checkbox
	$("table.model-table tr:gt(0)").live("mouseout", function()
	{
		if(!$(this).find("td:has(:checkbox:checked)").size())
			$(this).removeClass("active-line"); 
	}); 
	
	//Moves out the hightlight form rights table rows
	 $("#rights-table tr").live("mouseout", function()
	 {
		 $(this).removeClass("active-line"); 
	 });
	
	//Highlights the checked row
	$("table.model-table tr:gt(0) :checkbox").click(function()
	{
		if($(this).is(":checked"))
			$(this).parents("tr").addClass("active-line");
		else
			$(this).parents("tr").removeClass("active-line");
	});
	
	//Makes top checkbox able to mark all checkboxes in the list
	$("th.check-all :checkbox").click(function()
	{
		if(!$("table.model-table :checkbox").size())
			$(this).removeAttr("checked");  //If its no any checkbox in table we don't mark this checkbox
	
	    if($(this).is(":checked")) //Toggle the checking of all checkboxes
			$("table.model-table :checkbox:gt(0)").attr("checked", true).parents("tr").addClass("active-line");
		else
			$("table.model-table :checkbox:gt(0)").removeAttr("checked").parents("tr").removeClass("active-line");
	});
	
	//Keeps state if bool change ajax is in progress
	var boolChangeInProgress = false;
	
	//Make bool field icon clickable in model table to change it's value
	$("span.bool-field").on("click", function()
	{
		if(!boolChangeInProgress)
			boolChangeInProgress = true;
		else
			return;
			
		var this_ = this;
		
		$.ajax({
			type: "POST",
			dataType: "json",
			url: mVobject.adminPanelPath + "ajax/bool-change.php",
			data: "id=" + this.id + "&admin-panel-csrf-token=" + $("input[name='admin-panel-csrf-token']").val(),
			success: function(data)
			{
				if(data && data.title && data.css_class)
					$(this_).attr("title", data.title).removeClass("bool-true bool-false").addClass(data.css_class);
					
				boolChangeInProgress = false;
			}
		});		
	});
	
	//Showing the list of operations in model's table screen
	$("#operations-list-button").click(function()
	{
		$("#operations-menu").css("width", $(this).outerWidth() - 2).toggle();
		return false;
	});
	
	//Hides menu by click on any other element but button
  	$("#container").click(function()
	{
  		$("div.multi-actions-menu ul, #operations-menu").hide();
  		$("#models-list").hide("slide", {direction: "up"}, 300);
	});
  	
  	//Refers to model
  	$("#models-list a").click(function()
  	{
  		location.href = $(this).attr("href");
  		return false;
  	});
  	
  	//Stops event if click in models list
  	$("#models-list").click(function(){ return false; });

  	//Shows or hides the list of fields to show
	$("#fields-list #fields-list-button").click(function()
	{			
		$(this).parent().find("div.list").toggle();
		return false;
	});
	
	//Applies the action to show the selected fields in table
	$("#fields-list input.apply").click(function()
	{
		if(!$(this).parents("div.list").find("select.m2m-selected option").size())
			return;
		
		var params = [];
		
		$(this).parents("div.list").find("select.m2m-selected option").each(function()
		{
			params.push($(this).val());
		});
		
		$.ajax({ //Writes params into session on the server
			type: "POST",
			url: mVobject.adminPanelPath + "ajax/display-fields.php",
			data: mVobject.urlParams + "&model_display_fields=" + params.join(","),
			success: function(data)
			{
				location.href = mVobject.adminPanelPath + "model/?" + mVobject.urlParams;
			}
		});
	});
	
	//Close of selected fields window
	$("#fields-list input.cancel").click(function()
	{
		$("#fields-list-button").click();
	});
	  	
  	//Changes the actions of form if we dont want to go back to index page of module
  	$("#continue-button, #create-edit-button").click(function()
  	{
  		var action = (this.id == "continue-button") ? "&continue" : "&edit";
  		var form_action = $("form.model-elements-form").attr("action");
  			
  		$("form.model-elements-form").attr("action", (form_action + action)).submit();
  	});
	
  	//Extra save button on top
  	$("#top-save-button").click(function()
  	{
  		$("form.model-elements-form").submit()
  	});
  	
	//Shows / hides list of models
	$("#models-buttons span").click(function()
	{
		$("#models-list").toggle("slide", {direction: "up"}, 300);
		return false;
	});
	
	//Hides orders arrows in first and last row
	function hideOrderArrows()
	{
		$("table.model-table tr:eq(1) td:has(div[class^='move_'])").each(function()
		{
			var class_name = $(this).find("div").attr("class");
			$("table.model-table div." + class_name + ":first").find("span.up, span.top").addClass("hidden");
			$("table.model-table div." + class_name + ":last").find("span.down, span.bottom").addClass("hidden");
		});
	}
	
	hideOrderArrows();
  	  	
  	//Allies the moving action for rows on table of model (order type fields)
  	$("table.model-table td div[class^='move_'] span[class!='number']").click(function()
  	{
  		var field = $(this).parents("div").attr("class");
  		model_field = field.replace(/^move_/, "");
  		
  		if(model_field != mVobject.sortField)
  		{
  			dialogs.showAlertMessage('{sort_by_column}');
  			return;
  		}
  		else if(mVobject.allParentsFilter)
  		{
  			dialogs.showAlertMessage('{all_parents_filter}', {field: mVobject.allParentsFilter});
  			return;
  		}
  		else if(mVobject.relatedParentFilter)
  		{
  			dialogs.showAlertMessage('{parent_filter_needed}', {field: mVobject.relatedParentFilter});
  			return;
  		}
  		else if(typeof(mVobject.dependedOrderFields[model_field]) != "undefined")
  		{
  			dialogs.showAlertMessage('{parent_filter_needed}', {field: mVobject.dependedOrderFields[model_field]});
  			return;
  		}
  		
		var row = $(this).parents("tr");

		switch(this.className)
  		{
			case "top":	$(row).insertAfter("table.model-table tr:first");
  				break;	
  			case "up": $(row).insertBefore($(row).prev());
  				break;
  			case "down": $(row).insertAfter($(row).next());
  				break;
  			case "bottom": $(row).insertAfter("table.model-table tr:last");
  				break;
  		}
		
		var start_order = mVobject.startOrder;
		var data_for_ajax = [];
		
		$("table.model-table tr:gt(0)").each(function()
		{
			var number = $(this).find("div." + field + " span.number");
			var id = number.attr('id');
			number.text(start_order);
			
			data_for_ajax.push(id.replace(/.*_(\d+)$/, "$1") + "-" + start_order ++);
		});
		
		data_for_ajax = "orders_update_data=" + data_for_ajax.join("_") + "&" + mVobject.urlParams;
		data_for_ajax += "&model_field=" + model_field;
		data_for_ajax += "&admin-panel-csrf-token=" + $("input[name='admin-panel-csrf-token']").val();
		
		$.ajax({
			type: "POST",
			url: mVobject.adminPanelPath + "ajax/orders.php",
			data: data_for_ajax
		});
		
  		$("table.model-table td div span").removeClass("hidden").parents("table.model-table")
  										  .find("tr").removeClass("active-line");
  		
  		hideOrderArrows();
  		
  	    $("table.model-table tr").removeClass("moved-line");
  	    $("table.model-table tr:eq(" + row.index() + ")").addClass("moved-line");	  
  	});
  	
  	//Reload the list of versions after pager action
  	function updateVersionsList(params)
  	{
  		var version = get_params = "";
  		
  		if(location.href.match(/\?.*version=\d+/))
  		{
  			version = location.href.replace(/.*\?.*version=(\d+).*/, '$1');
  			version = "&version=" + parseInt(version); 
  		}
  		  		
  		if($("form.model-elements-form").attr("action").match(/current-tab=\d+/))
  			get_params = "?" + $("form.model-elements-form").attr("action").replace(/.*(current-tab=\d+).*/, "$1");
  		
		$.ajax({
			type: "POST",
			url: mVobject.adminPanelPath + "ajax/versions.php" + get_params,
			dataType: "html",
			data: mVobject.urlParams + params + version,
			success: function(data)
			{
				if(!data)
				{
					location.reload();
					return;
				}
				
				$("#versions-table, #versions-pager, #versions-limit").remove();
				$("#model-versions div.column-inner").append(data);
				$("#versions-table, #versions-pager, #versions-limit").hide().fadeIn(900);
			}
		});
  	}
  	
  	//Vesions pager actions
  	$("#versions-pager div.pager a").live("click", function()
  	{
  		var versions_page = $(this).attr("href");
  		versions_page = "&versions-page=" + parseInt(versions_page.replace(/.*versions-page=(\d+).*/, "$1"));
  		updateVersionsList(versions_page);
  		
  		return false;
  	});
  	
  	//Sets new limit for versions pager
  	$("#versions-pager div.limit select").live("change", function() 
  	{
  		var pager_limit = "&versions-pager-limit=" + parseInt($(this).val());
  		updateVersionsList(pager_limit);
  	});
  	  	
  	//Autocomplete field for long lists text inputs
  	if(!$("#model-form-tabs").size())  	
	  	$("input.autocomplete-input").each(function(index, element)
	  	{
	  		runAutocomplete(element);
	  	});
  	else
  	{
  		$("form.model-elements-form tr").each(function()
  		{
  			if(!$(this).hasClass("no-display") && $(this).find("input.autocomplete-input").size())
  				runAutocomplete($(this).find("input.autocomplete-input"));
  		});
  	}
  	
  	//Not selected m2m options ajax search
  	$("input.m2m-not-selected-search").keyup(function(event)
    {
  		if(event.keyCode == 16)
  			return;
  			
  		var request = encodeURIComponent($.trim($(this).val()));
  		var field = $(this).parents(".m2m-wrapper").find("input[type='hidden']").attr("name");
  		var ids = $(this).parents(".m2m-wrapper").find("input[type='hidden']").val();
  		var this_ = this;
  		var params = mVobject.urlParams + "&query=" + request + "&field=" + field + "&ids=" + ids;
		
  		if(!request)
  		{
  			$(this).parents("div.m2m-wrapper").find("select.m2m-not-selected").empty()
  			return;
  		}
		
  		if($(this).parents("div.m2m-wrapper").hasClass("group-datatype") && $(this).parents("div.m2m-wrapper").attr("id"))
  			params += "&self_id=" + $(this).parents("div.m2m-wrapper").attr("id").replace("group-self-id-", "");
  		
		$.ajax({
			type: "GET",
			url: mVobject.adminPanelPath + "ajax/m2m-search.php",
			dataType: "html",
			data: params,
			success: function(data)
			{
				$(this_).parents("div.m2m-wrapper").find("select.m2m-not-selected").empty().append(data);
			}
		});
    });
  	
  	//Selected m2m and group option search
	$("input.m2m-selected-search").keyup(function()
    {
  		var request = $.trim($(this).val());
  		var select = $(this).parents("div.m2m-wrapper").find("select.m2m-selected");
  		var buffer = $(this).parents("div.m2m-wrapper").find("div.search-buffer");
  		
  		if(!request)
  		{  			
  			$(buffer).find("option").appendTo(select);
  			return;
  		}
  		
  		var re = request.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
  		re = new RegExp(re, "i");
  		select.find("option").appendTo(buffer);
  		
  		$(buffer).find("option").each(function()
  		{
  			if($(this).text().match(re))
  				$(this).appendTo(select);
  		});
    });
	
	//Cancel button in create/update form
	$("#model-cancel").click(function()
  	{
  		location.href = $(this).attr("rel");
  	});
	
	//Tabs switching in create/update form
	$("#model-form-tabs ul li").click(function()
	{
		if($(this).hasClass("active"))
			return;
		
		$("#model-form-tabs ul li").removeClass("active");
		$(this).addClass("active");
		
		var group = $(this).attr("id").replace("tab-group-", "");
		var action = $("form.model-elements-form").attr("action").replace(/&current-tab=\d+/, "");
		action += "&current-tab=" + $(this).attr("id").replace("tab-group-", "");
		
		$("form.model-elements-form").attr("action", action);
		
		$("form.model-elements-form tr[class^='tab-group-']").removeClass("no-display").hide();
		$("form.model-elements-form tr[class='" + $(this).attr("id") + "']").show();
		
		$("#versions-table a").each(function()
		{
			var href = $(this).attr("href").replace(/&current-tab=\d+/, "") + "&current-tab=" + group;
			$(this).attr("href", href);
		});
		
		$("form.model-elements-form tr[class='" + $(this).attr("id") + "']").each(function()
		{
			if($(this).find("input.autocomplete-input"))
				runAutocomplete($(this).find("input.autocomplete-input"));
		});
	});
	
	//Main global search in admin panel
    var options = { 
    		serviceUrl: mVobject.adminPanelPath + "ajax/search.php", 
    		deferRequestBy: 200,
    		noCache: true,
    		onSelect: function(data, value, elem)
    				  {
    					  $("#header-search form").submit();
    				  }
    		};

    $("#header-search input.string").autocomplete(options);
    
    //Top alert message in admin panel
    $("#hide-system-warnings").on("click", function()
    {
		$.ajax({
			type: "POST",
			url: mVobject.adminPanelPath + "ajax/switch.php",
			dataType: "html",
			data: "switch-off=warnings",
			success: function(data)
			{
				if(!data)
					location.reload();
				else
					$("#admin-system-warnings").fadeOut(300, function() { $(this).remove(); } );
			}
		});
    });
    
    //Autocomplete of text field by translit of another field
	$("input[rel^='translit-from-']").each(function()
	{
		var _this = this;
		var field = $(this).attr("rel").replace(/^translit-from-/, "");
		
		$(this).next().click(function()
		{
			var value = $(_this).parents("form.model-elements-form").find("input[name='" + field + "']").val();
			
			$.ajax({
				type: "POST",
				url: mVobject.adminPanelPath + "ajax/autocomplete.php",
				dataType: "text",
				data: "action=translit&string=" + encodeURIComponent(value),
				success: function(data)
				{
					$(_this).val(data);		
				}
			});
		});
	});
	
	$("#user-settings-skin-select").on("change", function()
	{		
		applySelectedSkin($(this));
	});
});

//Variables for empting the recycle bin
var recycleBinRecordsCount = 0;
var recycleBinDeleteIterations = 0;

//Processing of empty recycle bin in admin panel
function emptyRecycleBin()
{
	$("#message-confirm-delete input.close").trigger("click");
	$("div.form-no-errors").remove();
	$("body").append("<div id=\"transparent-overlay\"></div>");
	$("#empty-recycle-bin").off("click").parent().addClass("small-loader").append('<span id="recycle-bin-percents">0%</span>');
	
	recycleBinDeleteIterations = Math.ceil(recycleBinRecordsCount / 50);
	removeDeletedRecords();
}

//One step of final delete process from recycle bin
function removeDeletedRecords()
{
	$.ajax({
		type: "POST",
		dataType: "text",
		url: mVobject.adminPanelPath + "ajax/multi-actions.php",
		data: "empty-recycle-bin=process&iterations-left=" + recycleBinDeleteIterations,
		success: function(data)
		{
			recycleBinDeleteIterations --;
			
			if(recycleBinDeleteIterations) //Next iteration starts
			{
				var percents = 100 - Math.round((50 * recycleBinDeleteIterations / recycleBinRecordsCount) * 100);
				$("#recycle-bin-percents").text(percents + "%");
				
				removeDeletedRecords();				
			}
			else //Garbage is empty so we reload the page
			{
				$("#recycle-bin-percents").text("100%");
				$("#empty-recycle-bin").parent().removeClass("small-loader");
				location.reload();
			}
		}
	});
}

//When we put cursor into filled aucomplete input
var keepAutocompleteData = {text: "", id: false, field: false};

//Runs autocomplete for ling lists
function runAutocomplete(element)
{
	var data = mVobject.urlParams.split("&");
	var input_params = {};
	
	$("input.autocomplete-input, input.autocomplete-multi").live("keyup", function()
	{
		if(!$.trim($(this).val())) //Deletes previous value
			$(this).next().val("").trigger("change");
	});
		
	for(var i = 0; i < data.length; i ++)
	{
		var param = data[i].split("=");
		input_params[param[0]] = param[1];
	}
	
	input_params.field = $(element).next().attr("name");
	
	if($(element).next().attr("id")) //If it's multi action modal window
		input_params.ids = $(element).next().attr("id");
	
    var options = { 
	serviceUrl: mVobject.adminPanelPath + "ajax/autocomplete.php", 
	deferRequestBy: 200,
	noCache: true,
	params: input_params,
	onSelect: function(data, value, elem)
			  {
				  $(elem).next().val(value).trigger("change");
				  
				  if($(elem).next().hasClass('autocomplete-multi-value'))
				  {
						var form_action = $("#model-table-form").attr("action");
						form_action = form_action.replace(/multi_value=[^&]*/, "multi_value=" + value);
						$("#model-table-form").attr("action", form_action);						
				  }
				  
				  $(elem).blur();
			  }
	};

	$(element).autocomplete(options);
	
	$("input.autocomplete-input, input.autocomplete-multi").live("focus", function()
	{
		var text = $.trim($(this).val());
		var id = $.trim($(this).next().val());
		
		if(text && id)
		{
			keepAutocompleteData.text = text;
			keepAutocompleteData.id = id;
			keepAutocompleteData.field = $(this).next().attr("name");
		}
	});
	
	$("input.autocomplete-input, input.autocomplete-multi").live("blur", function()
	{
		if(!$(this).next().val())
			$(this).val("");
			
		if(keepAutocompleteData.field == $(this).next().attr("name") && $(this).next().val() && $(this).val() &&
		   keepAutocompleteData.id == $(this).next().val() && keepAutocompleteData.text != $.trim($(this).val()))
		{
			$(this).val("").next().val("").trigger("change");
		}
	});
}

//Action of export model's data into csv file
function exportIntoCSV()
{
	if(!$.trim($("input[name='csv_fields']").val()))
	{
		dialogs.showAlertMessage("{select_fields}");
		return;
	}

	location.href = mVobject.adminPanelPath + "ajax/compose-csv.php?" + $("#csv-settings").serialize();
}

//Action of import daa from csv file into model's table
function importFromCSV()
{
	if($("#csv-upload-loader").hasClass("small-loader"))
		return;
	
	if(!$.trim($("input[name='csv_fields']").val()))
	{
		dialogs.showAlertMessage("{select_fields}");
		return;
	}
	else if(!$("#csv_file").val())
	{
		dialogs.showAlertMessage("{select_csv_file}");
		return;
	}
	
	$("#csv-upload-loader").addClass("small-loader");
	
	$('form#csv-settings').ajaxSubmit(
	{
		dataType: 'json',
		error: function(request, status, error)
		{
			$("div.form-no-errors, div.form-errors").remove();
			$("#csv-upload-loader").removeClass("small-loader");
			
			var message = '<div class="form-errors"><p>' + mVobject.locale("error_data_transfer");
			message += request.responseText ? "<br />" + request.responseText : ""; 
			message += '</p></div>';

			$(message).insertAfter("h3.column-header").hide().fadeIn(300);			
		},
		success: function(data)
		{
			if(!data)
			{
				location.reload();
				return;
			}
			
			$("div.form-no-errors, div.form-errors").remove();
			
			var css_class = data.error ? "form-errors" : "form-no-errors";
			var message = '<div class="' + css_class + '"><p>' + data.message + '</p></div>';
			
			$(message).insertAfter("h3.column-header").hide().fadeIn(300);
			$("#csv-upload-loader").removeClass("small-loader");
		}
	});
}

//Immediate showing of skin when we change select value by adding of css file
function applySelectedSkin(select)
{
	var path = mVobject.adminPanelPath + "interface/skins/" + $(select).val() + "/skin.css";
	var css_file = '<link id="skin-css" rel="stylesheet" type="text/css" href="' + path +'" />';
	
	$("#skin-css").remove();
	
	if($(select).val() != "none")
		$("head").append(css_file);
}

//Opens dialog window with available skins options to apply
function openSkinChooseDialog(skins)
{
	var select = "<div class=\"multi-value-select\"><select id=\"choose-skin-select\">";
	
	for(var i = 0; i < skins.length; i ++)
		select += "<option value=\"" + skins[i] + "\">" + skins[i] + "</option>";
	
	select += "</select></div>";
	
	dialogs.showConfirmMessage("{choose_skin}", "", "");
	$("#exposeMask, #message-confirm-delete div.close, #message-confirm-delete input.close").remove();

	$(select).appendTo($("#message-confirm-delete div.message"));
	
	$("#choose-skin-select").on("change", function()
	{		
		applySelectedSkin($(this));
	}).change();
	
	$("#butt-ok").off("click").on("click", function()
	{
		$.ajax({
			type: "POST",
			dataType: "text",
			url: mVobject.adminPanelPath + "ajax/display-fields.php",
			data: "set-user-skin=" + $("#choose-skin-select").val(),
			success: function(data)
			{
				if(data)
					location.reload();
			}
		});
	});
}